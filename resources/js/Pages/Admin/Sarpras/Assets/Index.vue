<template>
    <AppLayout title="Kelola Aset & Peralatan Sarpras">
        <div class="p-4">
            <!-- Header -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-box text-primary text-3xl"></i>
                        Katalog Aset & Tag QR Code
                    </h2>
                    <p class="text-600 m-0 mt-1">Pencatatan inventaris alat sekolah, serial number, kondisi fisik, dan barcode/QR tagging.</p>
                </div>
                <div class="flex gap-2">
                    <Button 
                        label="Import Excel" 
                        icon="pi pi-file-excel" 
                        class="p-button-outlined p-button-success shadow-1" 
                        @click="openImportModal"
                    />
                    <Button 
                        label="Cetak Label QR Aset" 
                        icon="pi pi-print" 
                        class="p-button-outlined p-button-secondary" 
                        @click="router.get(route('admin.sarpras.assets.print-qr'))"
                    />
                    <Button 
                        label="Tambah Aset Baru" 
                        icon="pi pi-plus" 
                        class="p-button-primary shadow-2" 
                        @click="openCreateModal"
                    />
                </div>
            </div>

            <!-- Filters -->
            <div class="surface-card p-3 border-round-xl shadow-1 mb-4 flex flex-column md:flex-row justify-content-between gap-3">
                <div class="flex flex-wrap gap-2 align-items-center">
                    <IconField iconPosition="left" class="w-full md:w-18rem">
                        <InputIcon class="pi pi-search" />
                        <InputText 
                            v-model="searchQuery" 
                            placeholder="Cari kode aset / nama / SN..." 
                            class="w-full"
                            @keydown.enter="applyFilters"
                        />
                    </IconField>
                    <Select 
                        v-model="selectedCategory" 
                        :options="categoryOptions" 
                        placeholder="Kategori" 
                        class="w-full md:w-12rem"
                        showClear
                        @change="applyFilters"
                    />
                    <Select 
                        v-model="selectedStatus" 
                        :options="statusOptions" 
                        optionLabel="label" 
                        optionValue="value" 
                        placeholder="Status" 
                        class="w-full md:w-12rem"
                        showClear
                        @change="applyFilters"
                    />
                    <Select 
                        v-model="selectedCondition" 
                        :options="conditionOptions" 
                        optionLabel="label" 
                        optionValue="value" 
                        placeholder="Kondisi" 
                        class="w-full md:w-12rem"
                        showClear
                        @change="applyFilters"
                    />
                    <Button icon="pi pi-filter" label="Filter" class="p-button-outlined" @click="applyFilters" />
                    <Button v-if="hasFilter" icon="pi pi-filter-slash" class="p-button-text p-button-secondary" @click="resetFilters" tooltip="Reset Filter" />
                </div>
            </div>

            <!-- Asset Table View -->
            <div class="surface-card border-round-xl shadow-2 overflow-hidden">
                <DataTable :value="assets.data" responsiveLayout="scroll" :rowHover="true" class="p-datatable-sm">
                    <template #empty>
                        <div class="p-5 text-center text-500">
                            <i class="pi pi-box text-5xl mb-3 text-400"></i>
                            <p class="m-0">Belum ada data aset yang sesuai kriteria pencarian.</p>
                        </div>
                    </template>

                    <Column header="Aset / Barang" style="min-width: 250px">
                        <template #body="{ data }">
                            <div class="flex align-items-center gap-3">
                                <div class="w-3rem h-3rem border-round bg-slate-100 flex align-items-center justify-content-center overflow-hidden flex-shrink-0 border-1 border-200">
                                    <img v-if="data.image" :src="data.image" :alt="data.name" class="w-full h-full object-cover" />
                                    <i v-else class="pi pi-desktop text-primary text-xl"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-900">{{ data.name }}</div>
                                    <div class="text-xs text-500 font-mono">{{ data.asset_code }} <span v-if="data.brand_model" class="text-600 font-sans">• {{ data.brand_model }}</span></div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Kategori" field="category" style="min-width: 120px">
                        <template #body="{ data }">
                            <span class="surface-100 text-700 px-2 py-1 border-round text-xs font-semibold">
                                {{ data.category }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Serial Number / Lokasi" style="min-width: 160px">
                        <template #body="{ data }">
                            <div class="text-xs font-mono text-700">SN: {{ data.serial_number || '-' }}</div>
                            <div class="text-xs text-500"><i class="pi pi-map-marker text-xs mr-1 text-primary"></i>{{ data.location || 'Ruang Sarpras' }}</div>
                        </template>
                    </Column>

                    <Column header="Kondisi Fisik" style="min-width: 130px">
                        <template #body="{ data }">
                            <Tag :value="getConditionLabel(data.condition)" :severity="getConditionSeverity(data.condition)" />
                        </template>
                    </Column>

                    <Column header="Status Pemakaian" style="min-width: 140px">
                        <template #body="{ data }">
                            <Tag :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" />
                        </template>
                    </Column>

                    <Column header="QR Token" style="min-width: 120px">
                        <template #body="{ data }">
                            <Button 
                                :label="data.qr_code_token" 
                                icon="pi pi-qrcode" 
                                class="p-button-outlined p-button-sm font-mono text-xs" 
                                @click="previewQr(data)"
                            />
                        </template>
                    </Column>

                    <Column header="Aksi" style="min-width: 100px" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-1 justify-content-end">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info" tooltip="Edit Aset" @click="openEditModal(data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" tooltip="Hapus Aset" @click="confirmDelete(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Pagination -->
            <div v-if="assets.total > assets.per_page" class="mt-4 flex justify-content-center">
                <Paginator 
                    :rows="assets.per_page" 
                    :totalRecords="assets.total" 
                    :first="(assets.current_page - 1) * assets.per_page"
                    @page="onPageChange"
                />
            </div>
        </div>

        <!-- Modal Form Create / Edit Asset -->
        <Dialog 
            v-model:visible="showModal" 
            :modal="true" 
            class="p-fluid"
            :style="{ width: '90vw', maxWidth: '650px' }"
        >
            <template #header>
                <div class="flex align-items-center gap-2">
                    <div class="bg-primary-50 text-primary-600 border-circle inline-flex justify-content-center align-items-center" style="width: 2.5rem; height: 2.5rem">
                        <i class="pi pi-box text-xl"></i>
                    </div>
                    <span class="font-bold text-xl">{{ isEditMode ? 'Edit Data Aset' : 'Tambah Aset Baru' }}</span>
                </div>
            </template>

            <form id="assetForm" @submit.prevent="submitForm">
                <div class="flex flex-column gap-3 pt-2">
                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Kode Aset <span class="text-red-500">*</span></label>
                            <InputText v-model="form.asset_code" placeholder="Misal: AST-LAP-001" class="w-full" required />
                        </div>
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Nama Aset / Alat <span class="text-red-500">*</span></label>
                            <InputText v-model="form.name" placeholder="Misal: Laptop ASUS ROG 15" class="w-full" required />
                        </div>
                    </div>

                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Kategori <span class="text-red-500">*</span></label>
                            <Select 
                                v-model="form.category" 
                                :options="categoryOptions" 
                                editable 
                                placeholder="Pilih atau ketik kategori..." 
                                class="w-full" 
                            />
                        </div>
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Merk / Tipe / Model</label>
                            <InputText v-model="form.brand_model" placeholder="Misal: ASUS ROG Strix G15" class="w-full" />
                        </div>
                    </div>

                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Serial Number (S/N)</label>
                            <InputText v-model="form.serial_number" placeholder="Nomor seri pabrikan" class="w-full" />
                        </div>
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Lokasi Penyimpanan</label>
                            <InputText v-model="form.location" placeholder="Misal: Lemari A - Ruang Sarpras" class="w-full" />
                        </div>
                    </div>

                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Kondisi Fisik</label>
                            <Select v-model="form.condition" :options="conditionOptions" optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                        <div class="col-12 md:col-6">
                            <label class="font-semibold text-sm text-900 block mb-1">Status Ketersediaan</label>
                            <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                    </div>

                    <div>
                        <label class="font-semibold text-sm text-900 block mb-1">Catatan Tambahan</label>
                        <Textarea v-model="form.notes" rows="3" placeholder="Kelengkapan aksesoris (misal: charger, kabel HDMI, tas)..." class="w-full" />
                    </div>

                    <div>
                        <div class="flex justify-content-between align-items-center mb-1">
                            <label class="font-semibold text-sm text-900">Foto Barang (Bisa beberapa foto, maks 3MB/foto)</label>
                            <span class="text-xs text-500 font-semibold" v-if="existingImages.length + newImageFiles.length > 0">
                                {{ existingImages.length + newImageFiles.length }} Foto Terpilih
                            </span>
                        </div>

                        <!-- Gallery Preview List -->
                        <div v-if="existingImages.length > 0 || newImageFiles.length > 0" class="flex flex-wrap gap-2 mb-3 p-2 surface-50 border-1 border-200 border-round-lg">
                            <!-- Existing DB Photos -->
                            <div v-for="(imgUrl, idx) in existingImages" :key="'exist-' + idx" class="relative w-5rem h-4rem border-round overflow-hidden border-1 border-300 shadow-1">
                                <img :src="imgUrl" class="w-full h-full object-cover" alt="Asset Photo" />
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
                                <img :src="item.previewUrl" class="w-full h-full object-cover" alt="New Asset Photo" />
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
                            <span>Pilih / Tambah Foto Barang</span>
                            <input type="file" multiple @change="onFilesSelected" accept="image/*" class="hidden" />
                        </label>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="showModal = false" />
                    <Button :label="isEditMode ? 'Simpan Perubahan' : 'Simpan Aset'" icon="pi pi-check" :loading="isSubmitting" @click="submitForm" />
                </div>
            </template>
        </Dialog>

        <!-- Preview QR Code Modal -->
        <Dialog v-model:visible="showQrModal" header="Tag QR Code Aset" :modal="true" class="w-full md:w-3 text-center">
            <div v-if="selectedAssetForQr" class="p-3">
                <div class="surface-100 p-4 border-round-xl border-1 border-300 mb-3">
                    <div class="font-bold text-sm text-700 mb-1">{{ selectedAssetForQr.name }}</div>
                    <div class="font-mono text-xs text-primary mb-3">{{ selectedAssetForQr.asset_code }}</div>
                    <div class="relative inline-flex align-items-center justify-content-center">
                        <img 
                            :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&ecc=H&data=${encodeURIComponent(selectedAssetForQr.qr_code_token)}`" 
                            alt="QR Code" 
                            class="border-round shadow-1 mx-auto"
                            width="180" 
                            height="180" 
                        />
                        <div 
                            v-if="schoolLogo" 
                            class="absolute bg-white border-circle flex align-items-center justify-content-center shadow-2 p-1"
                            style="width: 44px; height: 44px; top: 50%; left: 50%; transform: translate(-50%, -50%);"
                        >
                            <img :src="schoolLogo" alt="Logo" class="w-full h-full object-contain border-circle" />
                        </div>
                    </div>
                    <div class="font-mono text-sm font-bold text-900 mt-3">{{ selectedAssetForQr.qr_code_token }}</div>
                </div>
                <p class="text-xs text-500 m-0">Gunakan scanner QR Sarpras untuk membaca barcode/tag fisik saat serah terima barang.</p>
            </div>
        </Dialog>

        <!-- Modal Import Aset Excel -->
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
                        <h4 class="font-bold text-lg text-900 m-0">Import Data Aset & Peralatan</h4>
                        <p class="text-xs text-500 m-0">Unggah file Excel untuk menambah atau memperbarui data aset inventaris.</p>
                    </div>
                </div>
            </template>

            <div class="flex flex-column gap-3 pt-2">
                <div class="surface-ground p-3 border-round-lg text-sm text-700">
                    <div class="font-semibold text-900 mb-2 flex align-items-center gap-2">
                        <i class="pi pi-info-circle text-primary"></i> Panduan Pengisian Excel:
                    </div>
                    <ul class="pl-3 m-0 line-height-3 text-600 text-xs">
                        <li>Gunakan template Excel yang sudah disediakan.</li>
                        <li><b>Kode Aset</b> harus unik (jika kode sudah ada, data aset tersebut akan otomatis diperbarui).</li>
                        <li><b>Kategori</b>: Contohnya <code>Elektronik</code>, <code>Multimedia</code>, <code>Olahraga</code>, <code>Laboratorium</code>, <code>Musik</code>, <code>Umum</code>.</li>
                        <li><b>Kondisi</b>: <code>good</code> (Baik), <code>minor_damage</code> (Rusak Ringan), atau <code>heavy_damage</code> (Rusak Berat).</li>
                        <li><b>Status</b>: <code>available</code> (Tersedia), <code>borrowed</code> (Dipinjam), <code>maintenance</code> (Perbaikan), <code>lost</code> (Hilang), <code>disposed</code> (Afkir).</li>
                        <li>Token QR Code akan otomatis dibuatkan oleh sistem untuk setiap aset baru.</li>
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
                    <a :href="route('admin.sarpras.assets.template')" class="no-underline" download>
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
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Divider from 'primevue/divider';

const props = defineProps({
    assets: Object,
    categories: Array,
    filters: Object,
});

const page = usePage();
const schoolLogo = computed(() => {
    const logo = page.props.settings?.site_logo || page.props.app?.settings?.site_logo;
    if (!logo) return null;
    if (logo.startsWith('http') || logo.startsWith('/')) return logo;
    return `/storage/${logo}`;
});

const confirm = useConfirm();
const toast = useToast();

const searchQuery = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || null);
const selectedStatus = ref(props.filters?.status || null);
const selectedCondition = ref(props.filters?.condition || null);

const hasFilter = computed(() => !!searchQuery.value || !!selectedCategory.value || !!selectedStatus.value || !!selectedCondition.value);

const categoryOptions = computed(() => {
    const defaultCats = ['Elektronik', 'Multimedia', 'Olahraga', 'Laboratorium', 'Musik', 'Umum'];
    const propCats = Array.isArray(props.categories) ? props.categories : (props.categories?.data || []);
    return Array.from(new Set([...defaultCats, ...propCats])).filter(Boolean);
});

const statusOptions = [
    { label: 'Tersedia (Available)', value: 'available' },
    { label: 'Sedang Dipinjam (Borrowed)', value: 'borrowed' },
    { label: 'Perbaikan (Maintenance)', value: 'maintenance' },
    { label: 'Hilang (Lost)', value: 'lost' },
    { label: 'Dihapus/Afkir (Disposed)', value: 'disposed' },
];

const conditionOptions = [
    { label: 'Baik (Good)', value: 'good' },
    { label: 'Rusak Ringan', value: 'minor_damage' },
    { label: 'Rusak Berat', value: 'heavy_damage' },
];

const showModal = ref(false);
const isEditMode = ref(false);
const editId = ref(null);
const isSubmitting = ref(false);
const existingImages = ref([]);
const newImageFiles = ref([]);

const showQrModal = ref(false);
const selectedAssetForQr = ref(null);

const form = ref({
    asset_code: '',
    name: '',
    category: 'Elektronik',
    brand_model: '',
    serial_number: '',
    condition: 'good',
    status: 'available',
    location: 'Ruang Sarpras',
    notes: '',
    image: null,
});

const getStatusLabel = (status) => {
    switch(status) {
        case 'available': return 'Tersedia';
        case 'borrowed': return 'Dipinjam';
        case 'maintenance': return 'Perbaikan';
        case 'lost': return 'Hilang';
        case 'disposed': return 'Afkir';
        default: return status;
    }
};

const getStatusSeverity = (status) => {
    switch(status) {
        case 'available': return 'success';
        case 'borrowed': return 'info';
        case 'maintenance': return 'warn';
        case 'lost': case 'disposed': return 'danger';
        default: return 'secondary';
    }
};

const getConditionLabel = (cond) => {
    switch(cond) {
        case 'good': return 'Baik';
        case 'minor_damage': return 'Rusak Ringan';
        case 'heavy_damage': return 'Rusak Berat';
        default: return cond;
    }
};

const getConditionSeverity = (cond) => {
    switch(cond) {
        case 'good': return 'success';
        case 'minor_damage': return 'warn';
        case 'heavy_damage': return 'danger';
        default: return 'secondary';
    }
};

const previewQr = (asset) => {
    selectedAssetForQr.value = asset;
    showQrModal.value = true;
};

const applyFilters = () => {
    router.get(route('admin.sarpras.assets.index'), {
        search: searchQuery.value || undefined,
        category: selectedCategory.value || undefined,
        status: selectedStatus.value || undefined,
        condition: selectedCondition.value || undefined,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = null;
    selectedStatus.value = null;
    selectedCondition.value = null;
    applyFilters();
};

const onPageChange = (event) => {
    const page = event.page + 1;
    router.get(route('admin.sarpras.assets.index'), {
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
        asset_code: '',
        name: '',
        category: 'Elektronik',
        brand_model: '',
        serial_number: '',
        condition: 'good',
        status: 'available',
        location: 'Ruang Sarpras',
        notes: '',
    };
    showModal.value = true;
};

const openEditModal = (asset) => {
    isEditMode.value = true;
    editId.value = asset.id;
    existingImages.value = (asset.images && asset.images.length > 0) ? [...asset.images] : (asset.image ? [asset.image] : []);
    newImageFiles.value = [];
    form.value = {
        asset_code: asset.asset_code,
        name: asset.name,
        category: asset.category,
        brand_model: asset.brand_model || '',
        serial_number: asset.serial_number || '',
        condition: asset.condition,
        status: asset.status,
        location: asset.location || '',
        notes: asset.notes || '',
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
    formData.append('asset_code', form.value.asset_code);
    formData.append('name', form.value.name);
    formData.append('category', form.value.category);
    formData.append('brand_model', form.value.brand_model || '');
    formData.append('serial_number', form.value.serial_number || '');
    formData.append('condition', form.value.condition);
    formData.append('status', form.value.status);
    formData.append('location', form.value.location || '');
    formData.append('notes', form.value.notes || '');

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
        router.post(route('admin.sarpras.assets.update', editId.value), formData, {
            onSuccess: () => {
                showModal.value = false;
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Aset berhasil diperbarui', life: 3000 });
            },
            onFinish: () => { isSubmitting.value = false; },
        });
    } else {
        router.post(route('admin.sarpras.assets.store'), formData, {
            onSuccess: () => {
                showModal.value = false;
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Aset baru berhasil ditambahkan', life: 3000 });
            },
            onFinish: () => { isSubmitting.value = false; },
        });
    }
};

const confirmDelete = (asset) => {
    confirm.require({
        message: `Apakah Anda yakin ingin menghapus aset <strong>${asset.name} (${asset.asset_code})</strong>?`,
        header: 'Konfirmasi Hapus Aset',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.sarpras.assets.destroy', asset.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Terhapus', detail: 'Aset berhasil dihapus', life: 3000 });
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

    router.post(route('admin.sarpras.assets.import'), formData, {
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
