<template>
    <AppLayout title="Kelola Peminjaman Sarpras">
        <div class="p-4">
            <!-- Header -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-calendar-plus text-primary text-3xl"></i>
                        Peminjaman Ruang & Aset (Modul Sarpras)
                    </h2>
                    <p class="text-600 m-0 mt-1">Verifikasi persetujuan final (Tahap 2), pantau peminjaman aktif, dan unduh rekap berkas izin.</p>
                </div>
                <div class="flex gap-2">
                    <Button 
                        label="Kalender Jadwal" 
                        icon="pi pi-calendar" 
                        class="p-button-outlined p-button-secondary" 
                        @click="router.get(route('admin.sarpras.reservations.calendar'))"
                    />
                    <Button 
                        label="Export Rekap PDF" 
                        icon="pi pi-file-pdf" 
                        class="p-button-danger p-button-outlined" 
                        @click="exportPdf"
                    />
                </div>
            </div>

            <!-- Stats / KPI Cards -->
            <div class="grid mb-4">
                <div class="col-12 sm:col-6 lg:col-3">
                    <div class="surface-card p-3 border-round-xl shadow-1 border-left-3 border-orange-500 flex justify-content-between align-items-center">
                        <div>
                            <span class="block text-500 font-semibold text-xs uppercase mb-1">Menunggu Sarpras</span>
                            <span class="text-2xl font-bold text-900">{{ stats.pending_approval }} Pengajuan</span>
                        </div>
                        <div class="w-2.5rem h-2.5rem border-round bg-orange-100 flex align-items-center justify-content-center">
                            <i class="pi pi-clock text-orange-600 text-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="col-12 sm:col-6 lg:col-3">
                    <div class="surface-card p-3 border-round-xl shadow-1 border-left-3 border-purple-500 flex justify-content-between align-items-center">
                        <div>
                            <span class="block text-500 font-semibold text-xs uppercase mb-1">Sedang Digunakan</span>
                            <span class="text-2xl font-bold text-900">{{ stats.active_in_use }} Aktif</span>
                        </div>
                        <div class="w-2.5rem h-2.5rem border-round bg-purple-100 flex align-items-center justify-content-center">
                            <i class="pi pi-sync text-purple-600 text-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="col-12 sm:col-6 lg:col-3">
                    <div class="surface-card p-3 border-round-xl shadow-1 border-left-3 border-blue-500 flex justify-content-between align-items-center">
                        <div>
                            <span class="block text-500 font-semibold text-xs uppercase mb-1">Disetujui (Akan Datang)</span>
                            <span class="text-2xl font-bold text-900">{{ stats.approved_upcoming }} Booking</span>
                        </div>
                        <div class="w-2.5rem h-2.5rem border-round bg-blue-100 flex align-items-center justify-content-center">
                            <i class="pi pi-check-circle text-blue-600 text-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="col-12 sm:col-6 lg:col-3">
                    <div class="surface-card p-3 border-round-xl shadow-1 border-left-3 border-green-500 flex justify-content-between align-items-center">
                        <div>
                            <span class="block text-500 font-semibold text-xs uppercase mb-1">Peminjaman Selesai</span>
                            <span class="text-2xl font-bold text-900">{{ stats.total_completed }} Riwayat</span>
                        </div>
                        <div class="w-2.5rem h-2.5rem border-round bg-green-100 flex align-items-center justify-content-center">
                            <i class="pi pi-history text-green-600 text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="surface-card p-3 border-round-xl shadow-1 mb-4 flex flex-column md:flex-row justify-content-between gap-3">
                <div class="flex flex-wrap gap-2 align-items-center">
                    <IconField iconPosition="left" class="w-full md:w-18rem">
                        <InputIcon class="pi pi-search" />
                        <InputText 
                            v-model="searchQuery" 
                            placeholder="Cari kode / pemohon / judul..." 
                            class="w-full"
                            @keydown.enter="applyFilters"
                        />
                    </IconField>
                    <Select 
                        v-model="selectedStatus" 
                        :options="statusOptions" 
                        optionLabel="label" 
                        optionValue="value" 
                        placeholder="Status Pengajuan" 
                        class="w-full md:w-13rem"
                        showClear
                        @change="applyFilters"
                    />
                    <Select 
                        v-model="selectedRoom" 
                        :options="rooms" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Filter Ruangan" 
                        class="w-full md:w-13rem"
                        showClear
                        @change="applyFilters"
                    />
                    <Button icon="pi pi-filter" label="Filter" class="p-button-outlined" @click="applyFilters" />
                    <Button v-if="hasFilter" icon="pi pi-filter-slash" class="p-button-text p-button-secondary" @click="resetFilters" tooltip="Reset Filter" />
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="surface-card border-round-xl shadow-2 overflow-hidden">
                <DataTable :value="reservations.data" responsiveLayout="scroll" :rowHover="true" class="p-datatable-sm">
                    <template #empty>
                        <div class="p-5 text-center text-500">
                            <i class="pi pi-calendar-times text-5xl mb-3 text-400"></i>
                            <p class="m-0">Tidak ada data peminjaman yang ditemukan.</p>
                        </div>
                    </template>

                    <Column header="Kode & Waktu Buat" style="min-width: 140px">
                        <template #body="{ data }">
                            <div class="font-mono font-bold text-primary">{{ data.reservation_code }}</div>
                            <div class="text-xs text-500">{{ formatDate(data.created_at) }}</div>
                        </template>
                    </Column>

                    <Column header="Pemohon / Eskul" style="min-width: 180px">
                        <template #body="{ data }">
                            <div class="font-bold text-900">{{ data.user?.name || '-' }}</div>
                            <div class="text-xs text-600">
                                <span v-if="data.extracurricular" class="text-indigo-600 font-semibold">{{ data.extracurricular.name }}</span>
                                <span v-else class="text-500">Pribadi / Panitia</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Kegiatan & Keperluan" style="min-width: 220px">
                        <template #body="{ data }">
                            <div class="font-bold text-900">{{ data.title }}</div>
                            <div class="text-xs text-600 line-clamp-1">{{ data.purpose }}</div>
                            <a v-if="data.proposal_file_path" :href="data.proposal_file_path" target="_blank" class="text-xs text-blue-600 hover:underline flex align-items-center gap-1 mt-1">
                                <i class="pi pi-file-pdf text-red-500"></i> Berkas Proposal
                            </a>
                        </template>
                    </Column>

                    <Column header="Fasilitas Diminta" style="min-width: 180px">
                        <template #body="{ data }">
                            <div v-if="data.room" class="text-xs font-semibold text-primary mb-1">
                                <i class="pi pi-building mr-1"></i> {{ data.room.name }}
                            </div>
                            <div v-if="data.assets && data.assets.length > 0" class="text-xs text-600">
                                <i class="pi pi-box mr-1 text-orange-500"></i> {{ data.assets.length }} Item Aset
                            </div>
                        </template>
                    </Column>

                    <Column header="Jadwal Penggunaan" style="min-width: 180px">
                        <template #body="{ data }">
                            <div class="text-xs font-bold text-900">{{ formatDateTime(data.start_time) }}</div>
                            <div class="text-xs text-500">s/d {{ formatDateTime(data.end_time) }}</div>
                        </template>
                    </Column>

                    <Column header="Status" style="min-width: 140px">
                        <template #body="{ data }">
                            <Tag :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" />
                        </template>
                    </Column>

                    <Column header="Aksi" style="min-width: 140px" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-1 justify-content-end">
                                <Button 
                                    icon="pi pi-eye" 
                                    class="p-button-rounded p-button-text p-button-info" 
                                    tooltip="Lihat Detail" 
                                    @click="router.get(route('admin.sarpras.reservations.show', data.id))" 
                                />
                                <template v-if="data.status === 'pending_sarpras'">
                                    <Button 
                                        icon="pi pi-check" 
                                        class="p-button-rounded p-button-text p-button-success" 
                                        tooltip="Setujui (Approval Tahap 2)" 
                                        @click="openApproveModal(data)" 
                                    />
                                    <Button 
                                        icon="pi pi-times" 
                                        class="p-button-rounded p-button-text p-button-danger" 
                                        tooltip="Tolak Pengajuan" 
                                        @click="openRejectModal(data)" 
                                    />
                                </template>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Pagination -->
            <div v-if="reservations.total > reservations.per_page" class="mt-4 flex justify-content-center">
                <Paginator 
                    :rows="reservations.per_page" 
                    :totalRecords="reservations.total" 
                    :first="(reservations.current_page - 1) * reservations.per_page"
                    @page="onPageChange"
                />
            </div>
        </div>

        <!-- Approval Dialog -->
        <Dialog v-model:visible="showApproveDialog" header="Konfirmasi Persetujuan Final (Sarpras)" :modal="true" class="p-fluid w-full md:w-5">
            <div v-if="selectedReservation" class="flex flex-column gap-3 pt-2">
                <div class="surface-100 p-3 border-round-lg border-1 border-300">
                    <div class="font-bold text-sm text-900">{{ selectedReservation.title }}</div>
                    <div class="text-xs text-500 font-mono">{{ selectedReservation.reservation_code }} • {{ selectedReservation.user?.name }}</div>
                    <div class="text-xs text-700 mt-2">
                        <strong>Jadwal: </strong> {{ formatDateTime(selectedReservation.start_time) }} s/d {{ formatDateTime(selectedReservation.end_time) }}
                    </div>
                </div>

                <div>
                    <label class="font-bold text-sm block mb-1">Catatan Persetujuan (Opsional)</label>
                    <Textarea v-model="approvalNotes" rows="3" placeholder="Misal: Harap koordinasikan kunci ruangan dengan satpam..." />
                </div>

                <div class="flex justify-content-end gap-2 mt-3">
                    <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="showApproveDialog = false" />
                    <Button label="Setujui & Kunci Jadwal" icon="pi pi-check" class="p-button-success" :loading="isProcessing" @click="submitApprove" />
                </div>
            </div>
        </Dialog>

        <!-- Rejection Dialog -->
        <Dialog v-model:visible="showRejectDialog" header="Tolak Pengajuan Peminjaman" :modal="true" class="p-fluid w-full md:w-5">
            <div v-if="selectedReservation" class="flex flex-column gap-3 pt-2">
                <div class="surface-100 p-3 border-round-lg border-1 border-300">
                    <div class="font-bold text-sm text-900">{{ selectedReservation.title }}</div>
                    <div class="text-xs text-500 font-mono">{{ selectedReservation.reservation_code }}</div>
                </div>

                <div>
                    <label class="font-bold text-sm block mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                    <Textarea v-model="rejectionNotes" rows="3" placeholder="Jelaskan alasan penolakan agar pemohon dapat merevisi/memahami..." required />
                </div>

                <div class="flex justify-content-end gap-2 mt-3">
                    <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="showRejectDialog = false" />
                    <Button label="Tolak Permohonan" icon="pi pi-ban" class="p-button-danger" :loading="isProcessing" @click="submitReject" />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
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

const props = defineProps({
    reservations: Object,
    rooms: Array,
    stats: Object,
    filters: Object,
});

const toast = useToast();

const searchQuery = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || null);
const selectedRoom = ref(props.filters?.room_id || null);

const hasFilter = computed(() => !!searchQuery.value || !!selectedStatus.value || !!selectedRoom.value);

const statusOptions = [
    { label: 'Menunggu Pembina Eskul', value: 'pending_coach' },
    { label: 'Menunggu Sarpras', value: 'pending_sarpras' },
    { label: 'Disetujui', value: 'approved' },
    { label: 'Sedang Digunakan', value: 'in_use' },
    { label: 'Selesai', value: 'completed' },
    { label: 'Ditolak', value: 'rejected' },
    { label: 'Dibatalkan', value: 'cancelled' },
    { label: 'Ada Insiden Kerusakan', value: 'incident' },
];

const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const selectedReservation = ref(null);
const approvalNotes = ref('');
const rejectionNotes = ref('');
const isProcessing = ref(false);

const getStatusLabel = (status) => {
    switch(status) {
        case 'pending_coach': return 'Menunggu Pembina';
        case 'pending_sarpras': return 'Menunggu Sarpras';
        case 'approved': return 'Disetujui';
        case 'in_use': return 'Sedang Digunakan';
        case 'completed': return 'Selesai';
        case 'rejected': return 'Ditolak';
        case 'cancelled': return 'Dibatalkan';
        case 'incident': return 'Insiden Kerusakan';
        default: return status;
    }
};

const getStatusSeverity = (status) => {
    switch(status) {
        case 'pending_coach': return 'warn';
        case 'pending_sarpras': return 'warn';
        case 'approved': return 'info';
        case 'in_use': return 'help';
        case 'completed': return 'success';
        case 'rejected': case 'cancelled': return 'danger';
        case 'incident': return 'danger';
        default: return 'secondary';
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const applyFilters = () => {
    router.get(route('admin.sarpras.reservations.index'), {
        search: searchQuery.value || undefined,
        status: selectedStatus.value || undefined,
        room_id: selectedRoom.value || undefined,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = null;
    selectedRoom.value = null;
    applyFilters();
};

const onPageChange = (event) => {
    const page = event.page + 1;
    router.get(route('admin.sarpras.reservations.index'), {
        ...props.filters,
        page,
    }, { preserveState: true, replace: true });
};

const openApproveModal = (res) => {
    selectedReservation.value = res;
    approvalNotes.value = '';
    showApproveDialog.value = true;
};

const openRejectModal = (res) => {
    selectedReservation.value = res;
    rejectionNotes.value = '';
    showRejectDialog.value = true;
};

const submitApprove = () => {
    if (!selectedReservation.value) return;
    isProcessing.value = true;
    router.post(route('admin.sarpras.reservations.approve', selectedReservation.value.id), {
        notes: approvalNotes.value,
    }, {
        onSuccess: () => {
            showApproveDialog.value = false;
            toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Peminjaman telah disetujui & jadwal terkunci', life: 3000 });
        },
        onError: (err) => {
            toast.add({ severity: 'error', summary: 'Gagal', detail: err.error || 'Terjadi kesalahan', life: 4000 });
        },
        onFinish: () => { isProcessing.value = false; }
    });
};

const submitReject = () => {
    if (!selectedReservation.value || !rejectionNotes.value) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Alasan penolakan wajib diisi', life: 3000 });
        return;
    }
    isProcessing.value = true;
    router.post(route('admin.sarpras.reservations.reject', selectedReservation.value.id), {
        notes: rejectionNotes.value,
    }, {
        onSuccess: () => {
            showRejectDialog.value = false;
            toast.add({ severity: 'info', summary: 'Ditolak', detail: 'Pengajuan telah ditolak', life: 3000 });
        },
        onError: (err) => {
            toast.add({ severity: 'error', summary: 'Gagal', detail: err.error || 'Terjadi kesalahan', life: 4000 });
        },
        onFinish: () => { isProcessing.value = false; }
    });
};

const exportPdf = () => {
    window.open(route('admin.sarpras.reservations.export-pdf', {
        status: selectedStatus.value || undefined,
    }), '_blank');
};
</script>
