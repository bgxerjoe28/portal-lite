<template>
    <AppLayout :title="`Detail Peminjaman ${reservation.reservation_code}`">
        <div class="p-4 max-w-7xl mx-auto">
            <!-- Header Bar -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div class="flex align-items-center gap-3">
                    <Button icon="pi pi-arrow-left" class="p-button-outlined p-button-secondary" @click="router.get(route('admin.sarpras.reservations.index'))" />
                    <div>
                        <div class="flex align-items-center gap-2">
                            <h2 class="text-2xl font-extrabold text-900 m-0">{{ reservation.title }}</h2>
                            <Tag :value="getStatusLabel(reservation.status)" :severity="getStatusSeverity(reservation.status)" />
                        </div>
                        <span class="font-mono text-xs text-primary">{{ reservation.reservation_code }} • Dibuat {{ formatDate(reservation.created_at) }}</span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <template v-if="reservation.status === 'pending_sarpras'">
                        <Button label="Setujui (Approval Sarpras)" icon="pi pi-check" class="p-button-success" @click="showApproveDialog = true" />
                        <Button label="Tolak" icon="pi pi-times" class="p-button-danger p-button-outlined" @click="showRejectDialog = true" />
                    </template>
                </div>
            </div>

            <div class="grid">
                <!-- Left Column: Details & Items -->
                <div class="col-12 lg:col-8">
                    <!-- Basic Information Card -->
                    <div class="surface-card p-4 border-round-xl shadow-1 mb-4">
                        <h3 class="text-lg font-bold text-900 m-0 mb-3 border-bottom-1 border-200 pb-2">Informasi Permohonan</h3>
                        
                        <div class="grid">
                            <div class="col-12 md:col-6">
                                <span class="text-xs text-500 uppercase block font-semibold">Nama Pemohon</span>
                                <span class="text-sm font-bold text-900">{{ reservation.user?.name || '-' }}</span>
                                <div class="text-xs text-600">{{ reservation.user?.email }}</div>
                            </div>
                            <div class="col-12 md:col-6">
                                <span class="text-xs text-500 uppercase block font-semibold">Organisasi / Eskul</span>
                                <span class="text-sm font-bold text-indigo-600">{{ reservation.extracurricular ? reservation.extracurricular.name : 'Pribadi / Panitia Sekolah' }}</span>
                            </div>

                            <div class="col-12 md:col-6 mt-2">
                                <span class="text-xs text-500 uppercase block font-semibold">Jadwal Mulai</span>
                                <span class="text-sm font-bold text-900">{{ formatDateTime(reservation.start_time) }}</span>
                            </div>
                            <div class="col-12 md:col-6 mt-2">
                                <span class="text-xs text-500 uppercase block font-semibold">Jadwal Selesai</span>
                                <span class="text-sm font-bold text-900">{{ formatDateTime(reservation.end_time) }}</span>
                            </div>

                            <div class="col-12 mt-2">
                                <span class="text-xs text-500 uppercase block font-semibold">Tujuan / Keperluan</span>
                                <p class="text-sm text-800 m-0 mt-1 line-height-3">{{ reservation.purpose }}</p>
                            </div>

                            <div v-if="reservation.proposal_file_path" class="col-12 mt-2">
                                <span class="text-xs text-500 uppercase block font-semibold mb-1">Berkas Surat / Proposal</span>
                                <a :href="reservation.proposal_file_path" target="_blank" class="inline-flex align-items-center gap-2 p-2 border-1 border-blue-200 bg-blue-50 border-round text-blue-700 text-sm hover:surface-100">
                                    <i class="pi pi-file-pdf text-red-500 text-lg"></i>
                                    <span>Unduh / Tinjau Berkas Proposal (PDF)</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Room Detail Card (If Room is requested) -->
                    <div v-if="reservation.room" class="surface-card p-4 border-round-xl shadow-1 mb-4">
                        <h3 class="text-lg font-bold text-900 m-0 mb-3 border-bottom-1 border-200 pb-2 flex align-items-center gap-2">
                            <i class="pi pi-building text-primary"></i> Ruangan yang Dipinjam
                        </h3>
                        <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3">
                            <div>
                                <h4 class="text-base font-bold text-900 m-0">{{ reservation.room.name }}</h4>
                                <div class="text-xs text-600 font-mono mt-1">{{ reservation.room.code }} • Lokasi: {{ reservation.room.location || '-' }}</div>
                                <div class="text-xs text-500 mt-1">Kapasitas Maksimal: {{ reservation.room.capacity }} Orang</div>
                            </div>
                            <Tag :value="reservation.room.status" severity="success" />
                        </div>
                    </div>

                    <!-- Assets List Table (If Assets are requested) -->
                    <div v-if="reservation.assets && reservation.assets.length > 0" class="surface-card p-4 border-round-xl shadow-1 mb-4">
                        <h3 class="text-lg font-bold text-900 m-0 mb-3 border-bottom-1 border-200 pb-2 flex align-items-center gap-2">
                            <i class="pi pi-box text-primary"></i> Daftar Aset / Peralatan Terpinjam ({{ reservation.assets.length }} Item)
                        </h3>
                        
                        <DataTable :value="reservation.assets" responsiveLayout="scroll" class="p-datatable-sm">
                            <Column header="Kode & Nama Aset">
                                <template #body="{ data }">
                                    <div class="font-bold text-sm text-900">{{ data.name }}</div>
                                    <div class="font-mono text-xs text-primary">{{ data.asset_code }}</div>
                                </template>
                            </Column>
                            <Column header="Kategori" field="category" />
                            <Column header="Kondisi Saat Ambil">
                                <template #body="{ data }">
                                    <span class="text-xs font-semibold">{{ data.pivot?.checkout_condition || 'Baik' }}</span>
                                </template>
                            </Column>
                            <Column header="Kondisi Saat Kembali">
                                <template #body="{ data }">
                                    <span v-if="data.pivot?.return_condition" class="text-xs font-semibold" :class="data.pivot?.return_condition === 'good' ? 'text-green-600' : 'text-red-600'">
                                        {{ data.pivot?.return_condition }}
                                    </span>
                                    <span v-else class="text-xs text-400">Belum dikembalikan</span>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>

                <!-- Right Column: Approval Timeline & QR Handover -->
                <div class="col-12 lg:col-4">
                    <!-- QR Code Permit Card -->
                    <div class="surface-card p-4 border-round-xl shadow-1 mb-4 text-center">
                        <h3 class="text-base font-bold text-900 m-0 mb-2">QR E-Permit Digital</h3>
                        <p class="text-xs text-500 m-0 mb-3">Tunjukkan QR ini ke petugas Sarpras saat serah terima barang di hari-H.</p>
                        
                        <div class="surface-100 p-3 border-round-xl border-1 border-300 inline-block">
                            <img 
                                :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(reservation.qr_token)}`" 
                                alt="QR Permit" 
                                width="140" 
                                height="140"
                                class="mx-auto"
                            />
                            <div class="font-mono text-xs font-bold text-900 mt-2">{{ reservation.qr_token }}</div>
                        </div>
                    </div>

                    <!-- Workflow Timeline Card -->
                    <div class="surface-card p-4 border-round-xl shadow-1 mb-4">
                        <h3 class="text-base font-bold text-900 m-0 mb-3 border-bottom-1 border-200 pb-2">Alur Verifikasi Bertingkat</h3>
                        
                        <!-- Step 1: Tahap 1 (Pembina) -->
                        <div class="mb-3 pb-3 border-bottom-1 border-100">
                            <div class="flex justify-content-between align-items-center mb-1">
                                <span class="text-xs font-bold text-700">Tahap 1: Pembina Eskul</span>
                                <Tag :value="reservation.stage1_status" :severity="reservation.stage1_status === 'approved' ? 'success' : (reservation.stage1_status === 'rejected' ? 'danger' : 'warn')" />
                            </div>
                            <div v-if="reservation.stage1_by" class="text-xs text-600">
                                Disetujui oleh: <strong>{{ reservation.stage1_approver?.name || 'Pembina' }}</strong>
                                <div class="text-500">{{ formatDateTime(reservation.stage1_at) }}</div>
                            </div>
                            <div v-if="reservation.stage1_notes" class="text-xs text-700 mt-1 bg-yellow-50 p-2 border-round">
                                <em>"{{ reservation.stage1_notes }}"</em>
                            </div>
                        </div>

                        <!-- Step 2: Tahap 2 (Sarpras) -->
                        <div class="mb-3 pb-3 border-bottom-1 border-100">
                            <div class="flex justify-content-between align-items-center mb-1">
                                <span class="text-xs font-bold text-700">Tahap 2: Petugas Sarpras</span>
                                <Tag :value="reservation.stage2_status" :severity="reservation.stage2_status === 'approved' ? 'success' : (reservation.stage2_status === 'rejected' ? 'danger' : 'warn')" />
                            </div>
                            <div v-if="reservation.stage2_by" class="text-xs text-600">
                                Disetujui oleh: <strong>{{ reservation.stage2_approver?.name || 'Petugas Sarpras' }}</strong>
                                <div class="text-500">{{ formatDateTime(reservation.stage2_at) }}</div>
                            </div>
                            <div v-if="reservation.stage2_notes" class="text-xs text-700 mt-1 bg-blue-50 p-2 border-round">
                                <em>"{{ reservation.stage2_notes }}"</em>
                            </div>
                        </div>

                        <!-- Step 3: Serah Terima -->
                        <div class="mb-3 pb-3 border-bottom-1 border-100">
                            <div class="flex justify-content-between align-items-center mb-1">
                                <span class="text-xs font-bold text-700">Serah Terima (Pengambilan)</span>
                                <span class="text-xs font-semibold" :class="reservation.handover_at ? 'text-green-600' : 'text-500'">
                                    {{ reservation.handover_at ? 'Selesai' : 'Menunggu Hari-H' }}
                                </span>
                            </div>
                            <div v-if="reservation.handover_by" class="text-xs text-600">
                                Petugas: <strong>{{ reservation.handover_user?.name }}</strong>
                                <div class="text-500">{{ formatDateTime(reservation.handover_at) }}</div>
                            </div>
                        </div>

                        <!-- Step 4: Pengembalian -->
                        <div>
                            <div class="flex justify-content-between align-items-center mb-1">
                                <span class="text-xs font-bold text-700">Pengembalian & Cek Fisik</span>
                                <span class="text-xs font-semibold" :class="reservation.return_at ? 'text-green-600' : 'text-500'">
                                    {{ reservation.return_at ? 'Selesai' : 'Belum Kembali' }}
                                </span>
                            </div>
                            <div v-if="reservation.return_by" class="text-xs text-600">
                                Petugas: <strong>{{ reservation.return_user?.name }}</strong>
                                <div class="text-500">{{ formatDateTime(reservation.return_at) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Dialog -->
        <Dialog v-model:visible="showApproveDialog" header="Setujui Peminjaman (Approval Sarpras)" :modal="true" class="p-fluid w-full md:w-5">
            <div class="flex flex-column gap-3 pt-2">
                <p class="text-sm text-700 m-0">Apakah Anda yakin ingin menyetujui peminjaman ini? Jadwal ruangan dan peralatan akan otomatis terkunci.</p>
                <div>
                    <label class="font-bold text-sm block mb-1">Catatan Persetujuan (Opsional)</label>
                    <Textarea v-model="approvalNotes" rows="3" placeholder="Instruksi untuk peminjam atau petugas jaga..." />
                </div>
                <div class="flex justify-content-end gap-2 mt-3">
                    <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="showApproveDialog = false" />
                    <Button label="Setujui Sekarang" icon="pi pi-check" class="p-button-success" :loading="isProcessing" @click="submitApprove" />
                </div>
            </div>
        </Dialog>

        <!-- Rejection Dialog -->
        <Dialog v-model:visible="showRejectDialog" header="Tolak Pengajuan" :modal="true" class="p-fluid w-full md:w-5">
            <div class="flex flex-column gap-3 pt-2">
                <div>
                    <label class="font-bold text-sm block mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                    <Textarea v-model="rejectionNotes" rows="3" placeholder="Alasan penolakan..." required />
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
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from 'primevue/usetoast';

import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    reservation: Object,
});

const toast = useToast();

const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
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

const submitApprove = () => {
    isProcessing.value = true;
    router.post(route('admin.sarpras.reservations.approve', props.reservation.id), {
        notes: approvalNotes.value,
    }, {
        onSuccess: () => {
            showApproveDialog.value = false;
            toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Peminjaman telah disetujui', life: 3000 });
        },
        onError: (err) => {
            toast.add({ severity: 'error', summary: 'Gagal', detail: err.error || 'Terjadi kesalahan', life: 4000 });
        },
        onFinish: () => { isProcessing.value = false; }
    });
};

const submitReject = () => {
    if (!rejectionNotes.value) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Alasan penolakan wajib diisi', life: 3000 });
        return;
    }
    isProcessing.value = true;
    router.post(route('admin.sarpras.reservations.reject', props.reservation.id), {
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
</script>
