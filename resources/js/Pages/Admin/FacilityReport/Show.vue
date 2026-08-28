<template>
    <AppLayout title="Kelola Penanganan Kerusakan">
        <div class="p-4">
            <div class="mb-4">
                <Button 
                    label="Kembali ke Daftar Kelola" 
                    icon="pi pi-arrow-left" 
                    text 
                    severity="secondary" 
                    class="p-0 mb-3" 
                    @click="goBack" 
                />
                <div class="flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center gap-3">
                    <div>
                        <h2 class="text-3xl font-extrabold text-900 m-0">{{ report.title }}</h2>
                        <p class="text-600 m-0 mt-1">Laporan #{{ report.id }} dikirim pada {{ formatDate(report.created_at) }}</p>
                    </div>
                    <Tag :value="formatStatus(report.status)" :severity="statusSeverity(report.status)" class="text-lg font-bold px-3 py-2 border-round-lg shadow-1" />
                </div>
            </div>

            <div class="grid">
                <!-- Left Column: Details & Photos -->
                <div class="col-12 lg:col-8">
                    <!-- General Info -->
                    <div class="surface-card shadow-2 border-round-xl p-4 mb-4">
                        <h3 class="text-lg font-bold text-900 m-0 mb-3 border-bottom-1 border-200 pb-2">Informasi Laporan</h3>
                        
                        <div class="grid">
                            <div class="col-12 md:col-6 flex flex-column gap-1 mb-3">
                                <span class="text-sm text-500 font-medium">Kategori Kerusakan</span>
                                <div>
                                    <Tag :value="formatType(report.type)" :severity="report.type === 'sarana' ? 'info' : 'help'" />
                                </div>
                            </div>

                            <div class="col-12 md:col-6 flex flex-column gap-1 mb-3">
                                <span class="text-sm text-500 font-medium">Item Kerusakan</span>
                                <span class="font-bold text-teal-700 text-lg">{{ report.item_name }}</span>
                            </div>

                            <div class="col-12 md:col-6 flex flex-column gap-1 mb-3">
                                <span class="text-sm text-500 font-medium">Lokasi Fisik Ruangan</span>
                                <span class="font-semibold text-900"><i class="pi pi-map-marker text-orange-500 mr-1"></i>{{ report.location }}</span>
                            </div>

                            <div class="col-12 md:col-6 flex flex-column gap-1 mb-3">
                                <span class="text-sm text-500 font-medium">Tingkat Kerusakan</span>
                                <div>
                                    <span :class="severityClass(report.severity)" class="font-bold px-2 py-1 border-round text-xs">
                                        {{ report.severity.toUpperCase() }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-12 md:col-6 flex flex-column gap-1 mb-3" v-if="report.classroom">
                                <span class="text-sm text-500 font-medium">Kelas Terkait</span>
                                <span class="font-semibold text-blue-700">{{ report.classroom.name }}</span>
                            </div>
                        </div>

                        <div class="flex flex-column gap-1 mt-3">
                            <span class="text-sm text-500 font-medium">Deskripsi Lengkap</span>
                            <p class="m-0 p-3 bg-50 border-round-lg border border-200 text-900 line-height-3 white-space-pre-line">
                                {{ report.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="surface-card shadow-2 border-round-xl p-4 mb-4" v-if="report.status !== 'pending' && report.status !== 'rejected'">
                        <div class="flex justify-content-between align-items-center mb-3 border-bottom-1 border-200 pb-2">
                            <h3 class="text-lg font-bold text-900 m-0">Daftar Sarana & Prasarana</h3>
                            <Button 
                                label="Tambah Sarana/Prasarana" 
                                icon="pi pi-plus" 
                                size="small" 
                                @click="openItemsDialog" 
                                v-if="report.status !== 'resolved'"
                            />
                        </div>
                        
                        <div v-if="report.items && report.items.length > 0">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse border border-300">
                                    <thead class="bg-100 text-700">
                                        <tr>
                                            <th class="p-2 border border-300">Jenis</th>
                                            <th class="p-2 border border-300">Nama Barang</th>
                                            <th class="p-2 border border-300">Ruangan</th>
                                            <th class="p-2 border border-300">Kerusakan</th>
                                            <th class="p-2 border border-300">Saran</th>
                                            <th class="p-2 border border-300">Status</th>
                                            <th class="p-2 border border-300">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-sm">
                                        <tr v-for="item in report.items" :key="item.id" class="hover:bg-50">
                                            <td class="p-2 border border-300">
                                                <Tag :value="formatType(item.type)" :severity="item.type === 'sarana' ? 'info' : 'help'" />
                                            </td>
                                            <td class="p-2 border border-300 font-semibold">{{ item.item_name }}</td>
                                            <td class="p-2 border border-300">{{ item.room_name || '-' }}</td>
                                            <td class="p-2 border border-300">
                                                <span :class="severityClass(item.severity)" class="px-2 py-1 border-round text-xs">
                                                    {{ item.severity.toUpperCase() }}
                                                </span>
                                            </td>
                                            <td class="p-2 border border-300">{{ item.recommendation || '-' }}</td>
                                            <td class="p-2 border border-300">
                                                <Tag :value="formatItemStatus(item.status)" :severity="itemStatusSeverity(item.status)" />
                                            </td>
                                            <td class="p-2 border border-300">
                                                <div class="flex gap-2">
                                                    <Button 
                                                        v-if="item.status === 'dilaporkan'" 
                                                        icon="pi pi-cog" 
                                                        size="small" 
                                                        severity="warning" 
                                                        text 
                                                        v-tooltip.top="'Tandai Diperbaiki'"
                                                        @click="updateItemStatus(item.id, 'diperbaiki')" 
                                                    />
                                                    <Button icon="pi pi-check" severity="success" size="small" text @click="updateItemStatus(item.id, 'selesai')" v-tooltip.top="'Selesai Diperbaiki'" v-if="item.status !== 'selesai' && report.status !== 'resolved'" />
                                                    <Button icon="pi pi-trash" severity="danger" size="small" text @click="confirmDelete(item)" v-tooltip.top="'Hapus Item'" />
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-500 text-sm">
                            Belum ada daftar sarana/prasarana yang ditambahkan.
                        </div>
                    </div>

                    <!-- Photos -->
                    <div class="surface-card shadow-2 border-round-xl p-4 mb-4" v-if="report.photos && report.photos.length > 0">
                        <h3 class="text-lg font-bold text-900 m-0 mb-3 border-bottom-1 border-200 pb-2">Foto Bukti Kerusakan</h3>
                        
                        <div class="flex flex-wrap gap-3">
                            <div 
                                v-for="(photo, idx) in report.photos" 
                                :key="idx" 
                                class="border-round border border-300 overflow-hidden shadow-1 transition-all hover:scale-105"
                                style="width: 150px; height: 150px;"
                            >
                                <Image :src="photo" alt="Foto Kerusakan" preview imageClass="w-full h-full object-cover" class="w-full h-full block" style="width: 100%; height: 100%" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Updater & Reporter Info, Actions -->
                <div class="col-12 lg:col-4">
                    <!-- Reporter details -->
                    <div class="surface-card shadow-2 border-round-xl p-4 mb-4">
                        <h3 class="text-lg font-bold text-900 m-0 mb-3 border-bottom-1 border-200 pb-2">Identitas Pelapor</h3>
                        <div class="flex align-items-center gap-3 mb-3">
                            <Avatar icon="pi pi-user" size="large" shape="circle" class="bg-blue-100 text-blue-600" />
                            <div class="flex flex-column">
                                <span class="font-bold text-900">{{ report.reporter?.name }}</span>
                                <span class="text-xs text-500">{{ report.reporter?.email }}</span>
                            </div>
                        </div>
                        <div class="flex flex-column gap-1 text-sm border-top-1 border-100 pt-3">
                            <div class="flex justify-content-between">
                                <span class="text-500">Jabatan:</span>
                                <span class="font-semibold text-800">{{ report.reporter?.teacher ? 'Guru' : 'Staf' }}</span>
                            </div>
                            <div class="flex justify-content-between" v-if="report.reporter?.teacher?.nip">
                                <span class="text-500">NIP:</span>
                                <span class="font-semibold text-800">{{ report.reporter?.teacher?.nip }}</span>
                            </div>
                            <div class="flex justify-content-between" v-if="report.reporter?.teacher?.phone">
                                <span class="text-500">No. HP:</span>
                                <span class="font-semibold text-800">{{ report.reporter?.teacher?.phone }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- GPS Coordinates -->
                    <div class="surface-card shadow-2 border-round-xl p-4 mb-4" v-if="report.latitude && report.longitude">
                        <h3 class="text-lg font-bold text-900 m-0 mb-3 flex align-items-center gap-2">
                            <i class="pi pi-compass text-red-500"></i>
                            Koordinat GPS
                        </h3>
                        <div class="p-3 bg-50 border-round-lg border border-200 mb-3 text-sm">
                            <div class="flex justify-content-between mb-1">
                                <span class="text-500">Lat:</span>
                                <span class="font-semibold">{{ report.latitude }}</span>
                            </div>
                            <div class="flex justify-content-between">
                                <span class="text-500">Long:</span>
                                <span class="font-semibold">{{ report.longitude }}</span>
                            </div>
                        </div>
                        <Button 
                            label="Buka Google Maps" 
                            icon="pi pi-external-link" 
                            severity="warning" 
                            outlined 
                            fluid 
                            class="border-round-lg font-bold"
                            @click="openGoogleMaps" 
                        />
                    </div>

                    <!-- Update Status Action Card -->
                    <div class="surface-card shadow-2 border-round-xl p-4 mb-4 border-left-3 border-orange-500">
                        <h3 class="text-lg font-bold text-900 m-0 mb-3">Update Penanganan</h3>
                        <form @submit.prevent="submitUpdate" class="flex flex-column gap-3">
                            <div class="flex flex-column gap-2">
                                <label for="new_status" class="font-bold text-sm text-800">Ubah Status</label>
                                <Select 
                                    id="new_status" 
                                    v-model="updateForm.new_status" 
                                    :options="statusOptions" 
                                    optionLabel="label" 
                                    optionValue="value" 
                                    placeholder="Pilih Status Baru" 
                                />
                            </div>

                            <div class="flex flex-column gap-2">
                                <label for="notes" class="font-bold text-sm text-800">Catatan Perbaikan / Tindakan</label>
                                <Textarea 
                                    id="notes" 
                                    v-model="updateForm.notes" 
                                    rows="3" 
                                    placeholder="Tulis tindakan atau perkembangan perbaikan..." 
                                    required
                                />
                            </div>

                            <Button 
                                label="Simpan Perubahan" 
                                icon="pi pi-save" 
                                severity="warning" 
                                type="submit" 
                                :loading="loading" 
                                fluid 
                                class="font-bold py-2 mt-2" 
                            />
                        </form>
                    </div>

                    <!-- Status History Timeline -->
                    <div class="surface-card shadow-2 border-round-xl p-4 mb-4">
                        <h3 class="text-lg font-bold text-900 m-0 mb-3 border-bottom-1 border-200 pb-2">Riwayat Tindakan</h3>
                        
                        <div v-if="report.updates && report.updates.length > 0" class="flex flex-column gap-4 mt-3">
                            <div 
                                v-for="(upd, idx) in report.updates" 
                                :key="upd.id" 
                                class="flex gap-3 relative"
                            >
                                <div 
                                    v-if="idx < report.updates.length - 1" 
                                    class="absolute bg-200" 
                                    style="left: 14px; top: 30px; bottom: -30px; width: 2px"
                                ></div>

                                <div class="flex align-items-center justify-content-center border-round-circle shadow-1 z-1" :class="updateStatusBg(upd.new_status)" style="width: 30px; height: 30px">
                                    <i class="text-white text-xs" :class="updateStatusIcon(upd.new_status)"></i>
                                </div>

                                <div class="flex-1 bg-50 p-3 border-round-lg border border-200 text-sm">
                                    <div class="flex justify-content-between align-items-center mb-1">
                                        <span class="font-bold text-900">{{ formatStatus(upd.new_status) }}</span>
                                        <span class="text-xs text-500">{{ formatDate(upd.created_at) }}</span>
                                    </div>
                                    <span class="text-xs text-600 block mb-2">Petugas: {{ upd.updater?.name }}</span>
                                    <p class="m-0 text-700 italic" v-if="upd.notes">
                                        "{{ upd.notes }}"
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-4 text-500 text-sm">
                            Belum ada riwayat tindakan.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Dialog -->
        <Dialog v-model:visible="displayItemsDialog" modal header="Tambah Daftar Sarana & Prasarana" :style="{ width: '90vw', maxWidth: '1000px' }">
            <p class="text-500 mb-4 mt-0">Tambahkan daftar sarana/prasarana yang terlibat dalam laporan ini. Anda dapat menambah lebih dari 1 baris.</p>
            
            <form @submit.prevent="submitItems">
                <div class="overflow-x-auto mb-4">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-100 text-700 text-sm">
                            <tr>
                                <th class="p-2">Jenis <span class="text-red-500">*</span></th>
                                <th class="p-2">Nama Barang <span class="text-red-500">*</span></th>
                                <th class="p-2">Ruang / Gedung</th>
                                <th class="p-2">Kerusakan <span class="text-red-500">*</span></th>
                                <th class="p-2">Saran</th>
                                <th class="p-2">Keterangan</th>
                                <th class="p-2 text-center">Hapus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in itemsForm.items" :key="index" class="border-bottom-1 border-300 align-top">
                                <td class="p-2">
                                    <Select v-model="item.type" :options="[{label:'Sarana', value:'sarana'}, {label:'Prasarana', value:'prasarana'}]" optionLabel="label" optionValue="value" class="w-full min-w-min" />
                                </td>
                                <td class="p-2">
                                    <InputText v-model="item.item_name" class="w-full" required placeholder="Cth: Lampu LED" />
                                </td>
                                <td class="p-2">
                                    <InputText v-model="item.room_name" class="w-full" placeholder="Cth: Ruang Kelas 10A" />
                                </td>
                                <td class="p-2">
                                    <Select v-model="item.severity" :options="[{label:'Ringan', value:'ringan'}, {label:'Sedang', value:'sedang'}, {label:'Berat', value:'berat'}]" optionLabel="label" optionValue="value" class="w-full min-w-min" />
                                </td>
                                <td class="p-2">
                                    <Select v-model="item.recommendation" :options="[{label:'Perbaikan', value:'Perbaikan'}, {label:'Penggantian', value:'Penggantian'}, {label:'Penghapusan', value:'Penghapusan'}]" optionLabel="label" optionValue="value" editable placeholder="Pilih / Ketik" class="w-full min-w-min" />
                                </td>
                                <td class="p-2">
                                    <InputText v-model="item.notes" class="w-full" placeholder="Keterangan opsional..." />
                                </td>
                                <td class="p-2 text-center">
                                    <Button icon="pi pi-trash" severity="danger" text rounded @click="removeItemRow(index)" :disabled="itemsForm.items.length === 1" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-content-between align-items-center">
                    <Button label="Tambah Baris" icon="pi pi-plus" severity="secondary" outlined @click="addItemRow" />
                    <div class="flex gap-2">
                        <Button label="Batal" text severity="secondary" @click="displayItemsDialog = false" />
                        <Button label="Simpan Daftar" icon="pi pi-save" type="submit" :loading="submittingItems" />
                    </div>
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Image from 'primevue/image';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import Avatar from 'primevue/avatar';
import TabMenu from 'primevue/tabmenu';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    report: Object
});

const toast = useToast();
const confirm = useConfirm();
const loading = ref(false);
const submittingItems = ref(false);
const displayItemsDialog = ref(false);
const updateForm = reactive({
    new_status: props.report.status,
    notes: ''
});

const itemsForm = reactive({
    items: []
});

const initItemRow = () => ({
    type: 'sarana',
    item_name: '',
    room_name: props.report.location || '',
    severity: 'ringan',
    recommendation: '',
    notes: ''
});

const statusOptions = [
    { label: 'Verifikasi Laporan', value: 'verified' },
    { label: 'Pindahkan ke Perbaikan', value: 'in_progress' },
    { label: 'Selesai / Diperbaiki', value: 'resolved' },
    { label: 'Tolak Laporan', value: 'rejected' }
];

const goBack = () => {
    router.get(route('admin.facility.reports.index'));
};

const openGoogleMaps = () => {
    const url = `https://www.google.com/maps/search/?api=1&query=${props.report.latitude},${props.report.longitude}`;
    window.open(url, '_blank');
};

const openItemsDialog = () => {
    itemsForm.items = [initItemRow()];
    displayItemsDialog.value = true;
};

const addItemRow = () => {
    itemsForm.items.push(initItemRow());
};

const removeItemRow = (index) => {
    if (itemsForm.items.length > 1) {
        itemsForm.items.splice(index, 1);
    }
};

const submitItems = () => {
    submittingItems.value = true;
    router.post(route('admin.facility.reports.items.store', props.report.id), itemsForm, {
        preserveScroll: true,
        onSuccess: () => {
            displayItemsDialog.value = false;
            submittingItems.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Gagal', detail: 'Terjadi kesalahan saat menyimpan data.', life: 3000 });
            submittingItems.value = false;
        }
    });
};

const updateItemStatus = (itemId, newStatus) => {
    confirm.require({
        message: `Apakah Anda yakin ingin mengubah status item ini menjadi ${formatItemStatus(newStatus)}?`,
        header: 'Konfirmasi Perubahan Status',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Ya, Ubah',
        rejectLabel: 'Batal',
        acceptClass: 'p-button-primary',
        rejectClass: 'p-button-secondary p-button-text',
        accept: () => {
            router.patch(route('admin.facility.reports.items.update', itemId), { status: newStatus }, {
                preserveScroll: true
            });
        }
    });
};

const confirmDelete = (item) => {
    confirm.require({
        message: `Apakah Anda yakin ingin menghapus sarana/prasarana "${item.item_name}" dari laporan ini?`,
        header: 'Konfirmasi Penghapusan',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Ya, Hapus',
        rejectLabel: 'Batal',
        acceptClass: 'p-button-danger',
        rejectClass: 'p-button-secondary p-button-text',
        accept: () => {
            router.delete(route('admin.facility.reports.items.destroy', item.id), {
                preserveScroll: true
            });
        }
    });
};

const submitUpdate = () => {
    loading.value = true;
    const isVerifying = updateForm.new_status === 'verified' && props.report.status !== 'verified';
    router.post(route('admin.facility.reports.add-update', props.report.id), updateForm, {
        onSuccess: () => {
            updateForm.notes = '';
            loading.value = false;
            
            if (isVerifying) {
                openItemsDialog();
            }
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memperbarui status laporan.', life: 3000 });
            loading.value = false;
        }
    });
};

const formatType = (type) => {
    return type === 'sarana' ? 'Sarana' : 'Prasarana';
};

const formatItemStatus = (status) => {
    switch (status) {
        case 'dilaporkan': return 'Dilaporkan';
        case 'diperbaiki': return 'Dalam Perbaikan';
        case 'selesai': return 'Selesai';
        default: return status;
    }
};

const itemStatusSeverity = (status) => {
    switch (status) {
        case 'dilaporkan': return 'warn';
        case 'diperbaiki': return 'info';
        case 'selesai': return 'success';
        default: return 'secondary';
    }
};

const formatStatus = (status) => {
    switch (status) {
        case 'pending': return 'Pending';
        case 'verified': return 'Diverifikasi';
        case 'in_progress': return 'Dalam Perbaikan';
        case 'resolved': return 'Selesai';
        case 'rejected': return 'Ditolak';
        default: return status;
    }
};

const statusSeverity = (status) => {
    switch (status) {
        case 'pending': return 'warn';
        case 'verified': return 'info';
        case 'in_progress': return 'primary';
        case 'resolved': return 'success';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
};

const severityClass = (severity) => {
    switch (severity) {
        case 'ringan': return 'bg-green-100 text-green-700';
        case 'sedang': return 'bg-yellow-100 text-yellow-700';
        case 'berat': return 'bg-red-100 text-red-700';
        default: return 'bg-gray-100 text-gray-700';
    }
};

const updateStatusBg = (status) => {
    switch (status) {
        case 'pending': return 'bg-yellow-500';
        case 'verified': return 'bg-blue-500';
        case 'in_progress': return 'bg-indigo-500';
        case 'resolved': return 'bg-green-500';
        case 'rejected': return 'bg-red-500';
        default: return 'bg-gray-500';
    }
};

const updateStatusIcon = (status) => {
    switch (status) {
        case 'pending': return 'pi pi-clock';
        case 'verified': return 'pi pi-check';
        case 'in_progress': return 'pi pi-cog';
        case 'resolved': return 'pi pi-check-circle';
        case 'rejected': return 'pi pi-times-circle';
        default: return 'pi pi-info-circle';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date) + ' WIB';
};
</script>
