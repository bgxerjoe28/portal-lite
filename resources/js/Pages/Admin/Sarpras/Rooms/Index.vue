<template>
    <AppLayout title="Kelola Ruangan Sarpras">
        <div class="p-4">
            <!-- Header Section -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-building text-primary text-3xl"></i>
                        Daftar Ruangan Sekolah
                    </h2>
                    <p class="text-600 m-0 mt-1">Kelola master ruangan, kapasitas, fasilitas pendukung, dan status ketersediaan peminjaman.</p>
                </div>
                <div class="flex gap-2">
                    <Button 
                        label="Import Excel" 
                        icon="pi pi-file-excel" 
                        class="p-button-outlined p-button-success shadow-1" 
                        @click="openImportModal"
                    />
                    <Button 
                        label="Tambah Ruangan" 
                        icon="pi pi-plus" 
                        class="p-button-primary shadow-2" 
                        @click="openCreateModal"
                    />
                </div>
            </div>

            <!-- Filters & Search -->
            <div class="surface-card p-3 border-round-xl shadow-1 mb-4 flex flex-column md:flex-row justify-content-between gap-3">
                <div class="flex flex-wrap gap-2 align-items-center">
                    <IconField iconPosition="left" class="w-full md:w-20rem">
                        <InputIcon class="pi pi-search" />
                        <InputText 
                            v-model="searchQuery" 
                            placeholder="Cari kode / nama ruangan / lokasi..." 
                            class="w-full"
                            @keydown.enter="applyFilters"
                        />
                    </IconField>
                    <Select 
                        v-model="selectedStatus" 
                        :options="statusOptions" 
                        optionLabel="label" 
                        optionValue="value" 
                        placeholder="Status Ruangan" 
                        class="w-full md:w-14rem"
                        showClear
                        @change="applyFilters"
                    />
                    <Button icon="pi pi-filter" label="Filter" class="p-button-outlined" @click="applyFilters" />
                    <Button v-if="hasFilter" icon="pi pi-filter-slash" class="p-button-text p-button-secondary" @click="resetFilters" tooltip="Reset Filter" />
                </div>
            </div>

            <!-- Room Cards / Grid View -->
            <div v-if="rooms.data && rooms.data.length > 0" class="grid">
                <div v-for="room in rooms.data" :key="room.id" class="col-12 md:col-6 lg:col-4">
                    <div class="surface-card border-round-xl shadow-2 overflow-hidden flex flex-column h-full transition-all transition-duration-200 hover:shadow-4">
                        <!-- Room Image / Placeholder -->
                        <div class="relative h-12rem bg-slate-100 flex align-items-center justify-content-center overflow-hidden">
                            <img v-if="room.image" :src="room.image" :alt="room.name" class="w-full h-full object-cover" />
                            <div v-else class="text-center p-4">
                                <i class="pi pi-image text-400 text-5xl mb-2"></i>
                                <div class="text-500 text-xs">Belum ada foto ruangan</div>
                            </div>
                            <span class="absolute top-0 right-0 m-3">
                                <Tag :value="getStatusLabel(room.status)" :severity="getStatusSeverity(room.status)" />
                            </span>
                            <span class="absolute bottom-0 left-0 m-3 px-2 py-1 bg-black-alpha-70 text-white border-round text-xs font-mono">
                                {{ room.code }}
                            </span>
                            <span v-if="room.images && room.images.length > 1" class="absolute bottom-0 right-0 m-3 px-2 py-1 bg-black-alpha-70 text-white border-round text-xs flex align-items-center gap-1">
                                <i class="pi pi-images text-xs"></i> {{ room.images.length }} Foto
                            </span>
                        </div>

                        <!-- Room Details -->
                        <div class="p-4 flex-1 flex flex-column justify-content-between">
                            <div>
                                <h3 class="text-xl font-bold text-900 m-0 mb-1">{{ room.name }}</h3>
                                <div class="flex align-items-center text-600 text-sm mb-3">
                                    <i class="pi pi-map-marker mr-1 text-primary"></i>
                                    <span>{{ room.location || 'Lokasi tidak diset' }}</span>
                                    <span class="mx-2">•</span>
                                    <i class="pi pi-users mr-1 text-blue-500"></i>
                                    <span>Kapasitas: {{ room.capacity }} Orang</span>
                                </div>

                                <p v-if="room.description" class="text-700 text-sm line-height-3 mb-3" style="min-height: 2.5rem;">
                                    {{ room.description }}
                                </p>

                                <!-- Facility Tags -->
                                <div v-if="room.facilities && room.facilities.length > 0" class="flex flex-wrap gap-1 mb-3">
                                    <span v-for="fac in room.facilities" :key="fac" class="surface-100 text-700 px-2 py-1 border-round text-xs">
                                        <i class="pi pi-check text-xs text-green-600 mr-1"></i>{{ fac }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="pt-3 border-top-1 border-200 flex justify-content-between align-items-center">
                                <span class="text-xs text-500">
                                    Dapat Dipinjam: <strong :class="room.is_reservable ? 'text-green-600' : 'text-red-600'">{{ room.is_reservable ? 'Ya' : 'Tidak' }}</strong>
                                </span>
                                <div class="flex gap-2">
                                    <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info" tooltip="Edit Ruangan" @click="openEditModal(room)" />
                                    <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" tooltip="Hapus Ruangan" @click="confirmDelete(room)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="surface-card p-6 border-round-xl text-center shadow-1">
                <i class="pi pi-building text-6xl text-400 mb-3"></i>
                <h4 class="text-xl font-bold text-700 m-0 mb-2">Belum ada data ruangan</h4>
                <p class="text-500 m-0 mb-4">Tambahkan ruangan baru untuk mulai mendigitalkan peminjaman fasilitas sekolah.</p>
                <Button label="Tambah Ruangan Pertama" icon="pi pi-plus" @click="openCreateModal" />
            </div>

            <!-- Pagination -->
            <div v-if="rooms.total > rooms.per_page" class="mt-4 flex justify-content-center">
                <Paginator 
                    :rows="rooms.per_page" 
                    :totalRecords="rooms.total" 
                    :first="(rooms.current_page - 1) * rooms.per_page"
                    @page="onPageChange"
                />
            </div>
        </div>

        <!-- Standard Admin Modal Form Create / Edit -->
        <Dialog 
            v-model:visible="showModal" 
            modal 
            :style="{ width: '90vw', maxWidth: '650px' }"
            :dismissableMask="false"
        >
            <template #header>
                <div class="flex align-items-center gap-2">
                    <div class="w-2.5rem h-2.5rem border-round-lg bg-primary-50 text-primary flex align-items-center justify-content-center">
                        <i class="pi pi-building text-lg"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-lg text-900 m-0">{{ isEditMode ? 'Edit Data Ruangan' : 'Tambah Ruangan Baru' }}</h4>
                        <p class="text-xs text-500 m-0">Lengkapi data master ruangan fasilitas sekolah.</p>
                    </div>
                </div>
            </template>

            <form id="roomForm" @submit.prevent="submitForm" class="pt-2">
                <div class="flex flex-column gap-3">
                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Kode Ruangan <span class="text-red-500">*</span></label>
                            <InputText v-model="form.code" placeholder="Misal: LAB-KOMP-1, AULA-UTAMA" class="w-full" required />
                        </div>
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Nama Ruangan <span class="text-red-500">*</span></label>
                            <InputText v-model="form.name" placeholder="Misal: Lab Komputer Multimedia 1" class="w-full" required />
                        </div>
                    </div>

                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Kapasitas (Orang) <span class="text-red-500">*</span></label>
                            <InputNumber v-model="form.capacity" placeholder="Kapasitas max" class="w-full" :min="1" />
                        </div>
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Lokasi / Gedung</label>
                            <InputText v-model="form.location" placeholder="Misal: Gedung C Lantai 2" class="w-full" />
                        </div>
                    </div>

                    <div>
                        <label class="font-semibold text-sm text-900 block mb-1">Fasilitas Ruangan (Ketik & Tekan Enter / Koma)</label>
                        <div class="p-2 border-1 border-300 border-round-lg surface-50 flex flex-wrap gap-2 align-items-center" style="min-height: 42px;">
                            <span v-for="(fac, idx) in form.facilities" :key="idx" class="bg-primary text-white text-xs px-2 py-1 border-round-lg flex align-items-center gap-1 font-semibold">
                                {{ fac }}
                                <i class="pi pi-times text-xs cursor-pointer hover:text-red-200" @click="removeFacility(idx)"></i>
                            </span>
                            <input 
                                v-model="facilityInput" 
                                placeholder="Tambah fasilitas (misal: AC, Proyektor, WiFi) lalu tekan Enter..." 
                                class="flex-1 bg-transparent border-none outline-none text-sm p-1"
                                @keydown.enter.prevent="addFacility"
                                @keydown="handleFacilityKey"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="font-semibold text-sm text-900 block mb-1">Deskripsi & Catatan</label>
                        <Textarea v-model="form.description" rows="3" placeholder="Keterangan fungsi ruangan atau tata tertib penggunaan..." class="w-full" />
                    </div>

                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Status Ketersediaan</label>
                            <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                        <div class="col-12 md:col-6 flex align-items-center pt-4">
                            <Checkbox v-model="form.is_reservable" :binary="true" inputId="is_reservable" />
                            <label for="is_reservable" class="ml-2 font-semibold text-sm text-700 cursor-pointer">Izinkan Peminjaman Mandiri oleh Siswa</label>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-content-between align-items-center mb-1">
                            <label class="font-semibold text-sm text-900">Foto Ruangan (Bisa beberapa foto, maks 3MB/foto)</label>
                            <span class="text-xs text-500 font-semibold" v-if="existingImages.length + newImageFiles.length > 0">
                                {{ existingImages.length + newImageFiles.length }} Foto Terpilih
                            </span>
                        </div>

                        <!-- Gallery Preview List -->
                        <div v-if="existingImages.length > 0 || newImageFiles.length > 0" class="flex flex-wrap gap-2 mb-3 p-2 surface-50 border-1 border-200 border-round-lg">
                            <!-- Existing DB Photos -->
                            <div v-for="(imgUrl, idx) in existingImages" :key="'exist-' + idx" class="relative w-5rem h-4rem border-round overflow-hidden border-1 border-300 shadow-1">
                                <img :src="imgUrl" class="w-full h-full object-cover" alt="Room Photo" />
                                <span v-if="idx === 0" class="absolute bottom-0 left-0 right-0 text-center bg-black-alpha-70 text-white font-bold" style="font-size: 10px; padding: 1px 0;">
                                    Utama
                                </span>
                                <button 
                                    type="button" 
                                    class="absolute top-0 right-0 m-1 bg-red-600 hover:bg-red-700 text-white border-none border-circle flex align-items-center justify-content-center cursor-pointer p-0" 
                                    style="width: 1.25rem; height: 1.25rem;"
                                    @click="removeExistingImage(idx)"
                                    title="Hapus foto ini"
                                >
                                    <i class="pi pi-times" style="font-size: 10px;"></i>
                                </button>
                            </div>

                            <!-- New Uploaded Photos -->
                            <div v-for="(item, idx) in newImageFiles" :key="'new-' + idx" class="relative w-5rem h-4rem border-round overflow-hidden border-1 border-primary-300 shadow-1">
                                <img :src="item.previewUrl" class="w-full h-full object-cover" alt="New Room Photo" />
                                <span v-if="existingImages.length === 0 && idx === 0" class="absolute bottom-0 left-0 right-0 text-center bg-primary text-white font-bold" style="font-size: 10px; padding: 1px 0;">
                                    Utama
                                </span>
                                <button 
                                    type="button" 
                                    class="absolute top-0 right-0 m-1 bg-red-600 hover:bg-red-700 text-white border-none border-circle flex align-items-center justify-content-center cursor-pointer p-0" 
                                    style="width: 1.25rem; height: 1.25rem;"
                                    @click="removeNewImage(idx)"
                                    title="Hapus foto ini"
                                >
                                    <i class="pi pi-times" style="font-size: 10px;"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Upload Button -->
                        <label class="cursor-pointer flex align-items-center justify-content-center gap-2 border-2 border-dashed border-300 hover:border-primary border-round-lg p-3 surface-50 text-700 hover:text-primary transition-colors text-sm font-semibold">
                            <i class="pi pi-images text-lg"></i>
                            <span>Pilih / Tambah Foto Ruangan</span>
                            <input type="file" multiple @change="onFilesSelected" accept="image/*" class="hidden" />
                        </label>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="showModal = false" />
                    <Button :label="isEditMode ? 'Simpan Perubahan' : 'Simpan Ruangan'" icon="pi pi-check" :loading="isSubmitting" @click="submitForm" />
                </div>
            </template>
        </Dialog>

        <!-- Modal Import Ruangan Excel -->
        <Dialog 
            v-model:visible="showImportModal" 
            modal 
            :style="{ width: '90vw', maxWidth: '540px' }"
            :dismissableMask="true"
        >
            <template #header>
                <div class="flex align-items-center gap-2">
                    <div class="w-2.5rem h-2.5rem border-round-lg bg-green-50 text-green-600 flex align-items-center justify-content-center">
                        <i class="pi pi-file-excel text-lg"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-lg text-900 m-0">Import Data Ruangan</h4>
                        <p class="text-xs text-500 m-0">Unggah file Excel untuk menambah atau memperbarui data ruangan.</p>
                    </div>
                </div>
            </template>

            <div class="flex flex-column gap-3 pt-2">
                <div class="surface-ground p-3 border-round-lg text-sm text-700">
                    <div class="font-semibold text-900 mb-2 flex align-items-center gap-2">
                        <i class="pi pi-info-circle text-primary"></i> Panduan Pengisian Excel:
                    </div>
                    <ul class="pl-3 m-0 line-height-3 text-600 text-xs">
                        <li>Gunakan format template yang sudah disediakan.</li>
                        <li><b>Kode Ruangan</b> harus unik (jika kode sudah ada, data ruangan tersebut akan otomatis diperbarui).</li>
                        <li><b>Fasilitas</b> dapat diisi lebih dari satu dipisahkan tanda koma (contoh: <code>AC, Proyektor, Sound System, WiFi</code>).</li>
                        <li><b>Status</b>: <code>available</code> (Tersedia), <code>maintenance</code> (Pemeliharaan), atau <code>inactive</code> (Non-Aktif).</li>
                        <li><b>Dapat Dipinjam</b>: isi <code>1</code> (Ya) atau <code>0</code> (Tidak).</li>
                    </ul>
                </div>

                <div class="flex justify-content-between align-items-center p-3 border-1 border-200 border-round-lg surface-50">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-download text-primary text-xl"></i>
                        <div>
                            <div class="text-sm font-semibold text-900">Download Template</div>
                            <div class="text-xs text-500">File template Excel (.xlsx) dengan contoh isian</div>
                        </div>
                    </div>
                    <a :href="route('admin.sarpras.rooms.template')" class="no-underline" download>
                        <Button label="Download" icon="pi pi-download" size="small" class="p-button-outlined p-button-secondary" />
                    </a>
                </div>

                <Divider class="my-1" />

                <form @submit.prevent="submitImport" class="flex flex-column gap-3">
                    <div>
                        <label class="font-semibold text-sm text-900 block mb-2">Pilih File Excel (.xlsx / .xls / .csv) <span class="text-red-500">*</span></label>
                        <div 
                            class="border-2 border-dashed border-300 hover:border-primary border-round-lg p-4 text-center cursor-pointer surface-50 transition-colors"
                            @click="triggerFileInput"
                        >
                            <input 
                                ref="fileInputRef"
                                type="file" 
                                accept=".xlsx,.xls,.csv" 
                                class="hidden" 
                                @change="handleImportFileChange" 
                            />
                            <i class="pi pi-cloud-upload text-3xl text-500 mb-2 block"></i>
                            <span v-if="importFile" class="text-sm font-semibold text-primary block">
                                {{ importFile.name }} ({{ (importFile.size / 1024).toFixed(1) }} KB)
                            </span>
                            <span v-else class="text-sm text-600 block">
                                Klik untuk memilih file Excel
                            </span>
                            <span class="text-xs text-400 block mt-1">Maksimal ukuran file: 5 MB</span>
                        </div>
                        <small v-if="importError" class="text-red-500 text-xs mt-1 block">{{ importError }}</small>
                    </div>

                    <div class="flex justify-content-end gap-2 pt-2 border-top-1 border-200">
                        <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="showImportModal = false" :disabled="isImporting" />
                        <Button 
                            label="Mulai Import" 
                            icon="pi pi-upload" 
                            type="submit" 
                            severity="success" 
                            :loading="isImporting" 
                            :disabled="!importFile" 
                        />
                    </div>
                </form>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Checkbox from 'primevue/checkbox';
import Paginator from 'primevue/paginator';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Divider from 'primevue/divider';

const props = defineProps({
    rooms: Object,
    filters: Object,
});

const confirm = useConfirm();
const toast = useToast();

const searchQuery = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || null);
const hasFilter = computed(() => !!searchQuery.value || !!selectedStatus.value);

const statusOptions = [
    { label: 'Tersedia (Available)', value: 'available' },
    { label: 'Dalam Pemeliharaan (Maintenance)', value: 'maintenance' },
    { label: 'Non-Aktif', value: 'inactive' },
];

const showModal = ref(false);
const isEditMode = ref(false);
const editId = ref(null);
const isSubmitting = ref(false);
const existingImages = ref([]);
const newImageFiles = ref([]);

const facilityInput = ref('');
const addFacility = () => {
    const val = facilityInput.value.trim().replace(/^,|,$/g, '');
    if (val && !form.value.facilities.includes(val)) {
        form.value.facilities.push(val);
    }
    facilityInput.value = '';
};

const handleFacilityKey = (e) => {
    if (e.key === ',') {
        e.preventDefault();
        addFacility();
    }
};

const removeFacility = (index) => {
    form.value.facilities.splice(index, 1);
};

const form = ref({
    code: '',
    name: '',
    capacity: 30,
    location: '',
    facilities: ['AC', 'Proyektor', 'WiFi'],
    description: '',
    status: 'available',
    is_reservable: true,
});

const getStatusLabel = (status) => {
    switch(status) {
        case 'available': return 'Tersedia';
        case 'maintenance': return 'Pemeliharaan';
        case 'inactive': return 'Non-Aktif';
        default: return status;
    }
};

const getStatusSeverity = (status) => {
    switch(status) {
        case 'available': return 'success';
        case 'maintenance': return 'warn';
        case 'inactive': return 'danger';
        default: return 'info';
    }
};

const applyFilters = () => {
    router.get(route('admin.sarpras.rooms.index'), {
        search: searchQuery.value || undefined,
        status: selectedStatus.value || undefined,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = null;
    applyFilters();
};

const onPageChange = (event) => {
    const page = event.page + 1;
    router.get(route('admin.sarpras.rooms.index'), {
        ...props.filters,
        page,
    }, { preserveState: true, replace: true });
};

const openCreateModal = () => {
    isEditMode.value = false;
    editId.value = null;
    existingImages.value = [];
    newImageFiles.value = [];
    form.value = {
        code: '',
        name: '',
        capacity: 30,
        location: '',
        facilities: ['AC', 'Proyektor', 'WiFi'],
        description: '',
        status: 'available',
        is_reservable: true,
    };
    showModal.value = true;
};

const openEditModal = (room) => {
    isEditMode.value = true;
    editId.value = room.id;
    existingImages.value = (room.images && room.images.length > 0) ? [...room.images] : (room.image ? [room.image] : []);
    newImageFiles.value = [];
    form.value = {
        code: room.code,
        name: room.name,
        capacity: room.capacity,
        location: room.location || '',
        facilities: room.facilities || [],
        description: room.description || '',
        status: room.status,
        is_reservable: !!room.is_reservable,
    };
    showModal.value = true;
};

const onFilesSelected = (event) => {
    const files = Array.from(event.target.files || []);
    files.forEach(file => {
        newImageFiles.value.push({
            file,
            previewUrl: URL.createObjectURL(file),
        });
    });
    event.target.value = '';
};

const removeExistingImage = (index) => {
    existingImages.value.splice(index, 1);
};

const removeNewImage = (index) => {
    newImageFiles.value.splice(index, 1);
};

const submitForm = () => {
    isSubmitting.value = true;
    const formData = new FormData();
    formData.append('code', form.value.code);
    formData.append('name', form.value.name);
    formData.append('capacity', form.value.capacity);
    formData.append('location', form.value.location || '');
    formData.append('description', form.value.description || '');
    formData.append('status', form.value.status);
    formData.append('is_reservable', form.value.is_reservable ? '1' : '0');

    if (form.value.facilities && form.value.facilities.length > 0) {
        form.value.facilities.forEach((f, idx) => {
            formData.append(`facilities[${idx}]`, f);
        });
    }

    if (isEditMode.value) {
        existingImages.value.forEach((url, idx) => {
            formData.append(`existing_images[${idx}]`, url);
        });
    }

    newImageFiles.value.forEach((item, idx) => {
        formData.append(`images[${idx}]`, item.file);
    });

    if (isEditMode.value) {
        formData.append('_method', 'PUT');
        router.post(route('admin.sarpras.rooms.update', editId.value), formData, {
            onSuccess: () => {
                showModal.value = false;
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Ruangan berhasil diperbarui', life: 3000 });
            },
            onFinish: () => { isSubmitting.value = false; },
        });
    } else {
        router.post(route('admin.sarpras.rooms.store'), formData, {
            onSuccess: () => {
                showModal.value = false;
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Ruangan baru berhasil ditambahkan', life: 3000 });
            },
            onFinish: () => { isSubmitting.value = false; },
        });
    }
};

const confirmDelete = (room) => {
    confirm.require({
        message: `Apakah Anda yakin ingin menghapus ruangan <strong>${room.name}</strong>? Data peminjaman terkait mungkin akan terpengaruh.`,
        header: 'Konfirmasi Hapus Ruangan',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.sarpras.rooms.destroy', room.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Terhapus', detail: 'Ruangan berhasil dihapus', life: 3000 });
                }
            });
        }
    });
};

// Import Excel State & Methods
const showImportModal = ref(false);
const importFile = ref(null);
const importError = ref('');
const isImporting = ref(false);
const fileInputRef = ref(null);

const openImportModal = () => {
    importFile.value = null;
    importError.value = '';
    showImportModal.value = true;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const triggerFileInput = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const handleImportFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        importFile.value = file;
        importError.value = '';
    }
};

const submitImport = () => {
    if (!importFile.value) {
        importError.value = 'Silakan pilih file Excel terlebih dahulu.';
        return;
    }

    isImporting.value = true;
    const formData = new FormData();
    formData.append('file', importFile.value);

    router.post(route('admin.sarpras.rooms.import'), formData, {
        onSuccess: () => {
            showImportModal.value = false;
            importFile.value = null;
        },
        onError: (errors) => {
            importError.value = errors.file || 'Gagal mengimpor file.';
        },
        onFinish: () => {
            isImporting.value = false;
        },
    });
};
</script>
