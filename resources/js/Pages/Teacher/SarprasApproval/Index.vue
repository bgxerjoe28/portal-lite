<template>
    <AppLayout title="Persetujuan Izin Peminjaman Sarpras Eskul">
        <div class="p-4">
            <!-- Header -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-check-square text-primary text-3xl"></i>
                        Persetujuan Izin Sarpras (Pembina Eskul)
                    </h2>
                    <p class="text-600 m-0 mt-1">Verifikasi Tahap 1 untuk permohonan peminjaman ruangan dan peralatan oleh ekstrakurikuler binaan Anda.</p>
                </div>
            </div>

            <!-- Table -->
            <div class="surface-card border-round-xl shadow-2 overflow-hidden">
                <DataTable :value="reservations.data" responsiveLayout="scroll" :rowHover="true" class="p-datatable-sm">
                    <template #empty>
                        <div class="p-5 text-center text-500">
                            <i class="pi pi-check-circle text-5xl mb-3 text-400"></i>
                            <p class="m-0">Tidak ada antrean permohonan peminjaman yang membutuhkan persetujuan Anda saat ini.</p>
                        </div>
                    </template>

                    <Column header="No. Registrasi" style="min-width: 140px">
                        <template #body="{ data }">
                            <div class="font-mono font-bold text-primary">{{ data.reservation_code }}</div>
                            <div class="text-xs text-500">{{ formatDate(data.created_at) }}</div>
                        </template>
                    </Column>

                    <Column header="Eskul & Pemohon" style="min-width: 180px">
                        <template #body="{ data }">
                            <div class="font-bold text-indigo-600">{{ data.extracurricular?.name || 'Ekstrakurikuler' }}</div>
                            <div class="text-xs text-700">Oleh: {{ data.user?.name }}</div>
                        </template>
                    </Column>

                    <Column header="Kegiatan & Proposal" style="min-width: 220px">
                        <template #body="{ data }">
                            <div class="font-bold text-900">{{ data.title }}</div>
                            <div class="text-xs text-600 line-clamp-1">{{ data.purpose }}</div>
                            <a v-if="data.proposal_file_path" :href="data.proposal_file_path" target="_blank" class="text-xs text-blue-600 hover:underline flex align-items-center gap-1 mt-1">
                                <i class="pi pi-file-pdf text-red-500"></i> Tinjau Surat / Proposal
                            </a>
                        </template>
                    </Column>

                    <Column header="Ruang / Aset Diminta" style="min-width: 180px">
                        <template #body="{ data }">
                            <div v-if="data.room" class="text-xs font-semibold text-primary">
                                <i class="pi pi-building mr-1"></i> {{ data.room.name }}
                            </div>
                            <div v-if="data.assets && data.assets.length > 0" class="text-xs text-600">
                                <i class="pi pi-box mr-1 text-orange-500"></i> {{ data.assets.length }} Item Alat
                            </div>
                        </template>
                    </Column>

                    <Column header="Jadwal Pemakaian" style="min-width: 180px">
                        <template #body="{ data }">
                            <div class="text-xs font-bold text-900">{{ formatDateTime(data.start_time) }}</div>
                            <div class="text-xs text-500">s/d {{ formatDateTime(data.end_time) }}</div>
                        </template>
                    </Column>

                    <Column header="Status Tahap 1" style="min-width: 130px">
                        <template #body="{ data }">
                            <Tag :value="getStage1Label(data.stage1_status)" :severity="getStage1Severity(data.stage1_status)" />
                        </template>
                    </Column>

                    <Column header="Aksi" style="min-width: 140px" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-1 justify-content-end">
                                <Button 
                                    icon="pi pi-eye" 
                                    class="p-button-rounded p-button-text p-button-info" 
                                    tooltip="Lihat Detail" 
                                    @click="router.get(route('guru.sarpras.approvals.show', data.id))" 
                                />
                                <template v-if="data.stage1_status === 'pending'">
                                    <Button 
                                        icon="pi pi-check" 
                                        class="p-button-rounded p-button-text p-button-success" 
                                        tooltip="Setujui Izin Kegiatan" 
                                        @click="openApproveModal(data)" 
                                    />
                                    <Button 
                                        icon="pi pi-times" 
                                        class="p-button-rounded p-button-text p-button-danger" 
                                        tooltip="Tolak Izin" 
                                        @click="openRejectModal(data)" 
                                    />
                                </template>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Approval Dialog -->
        <Dialog v-model:visible="showApproveDialog" header="Setujui Izin Kegiatan (Tahap 1)" :modal="true" class="p-fluid w-full md:w-5">
            <div v-if="selectedRes" class="flex flex-column gap-3 pt-2">
                <p class="text-sm text-700 m-0">Dengan menyetujui izin ini, permohonan peminjaman sarpras eskul <strong>{{ selectedRes.extracurricular?.name }}</strong> akan diteruskan ke Petugas Sarpras untuk approval final.</p>
                <div>
                    <label class="font-bold text-sm block mb-1">Catatan Pembina (Opsional)</label>
                    <Textarea v-model="approvalNotes" rows="3" placeholder="Pesan untuk siswa atau petugas sarpras..." />
                </div>
                <div class="flex justify-content-end gap-2 mt-3">
                    <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="showApproveDialog = false" />
                    <Button label="Setujui & Teruskan" icon="pi pi-check" class="p-button-success" :loading="isProcessing" @click="submitApprove" />
                </div>
            </div>
        </Dialog>

        <!-- Rejection Dialog -->
        <Dialog v-model:visible="showRejectDialog" header="Tolak Izin Kegiatan" :modal="true" class="p-fluid w-full md:w-5">
            <div v-if="selectedRes" class="flex flex-column gap-3 pt-2">
                <div>
                    <label class="font-bold text-sm block mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                    <Textarea v-model="rejectionNotes" rows="3" placeholder="Jelaskan alasan penolakan izin..." required />
                </div>
                <div class="flex justify-content-end gap-2 mt-3">
                    <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="showRejectDialog = false" />
                    <Button label="Tolak Izin" icon="pi pi-ban" class="p-button-danger" :loading="isProcessing" @click="submitReject" />
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
    reservations: Object,
    filters: Object,
});

const toast = useToast();

const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const selectedRes = ref(null);
const approvalNotes = ref('');
const rejectionNotes = ref('');
const isProcessing = ref(false);

const getStage1Label = (st) => {
    switch(st) {
        case 'pending': return 'Menunggu Approval';
        case 'approved': return 'Disetujui Pembina';
        case 'rejected': return 'Ditolak Pembina';
        default: return st;
    }
};

const getStage1Severity = (st) => {
    switch(st) {
        case 'pending': return 'warn';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
};

const formatDate = (d) => {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const openApproveModal = (res) => {
    selectedRes.value = res;
    approvalNotes.value = '';
    showApproveDialog.value = true;
};

const openRejectModal = (res) => {
    selectedRes.value = res;
    rejectionNotes.value = '';
    showRejectDialog.value = true;
};

const submitApprove = () => {
    if (!selectedRes.value) return;
    isProcessing.value = true;
    router.post(route('guru.sarpras.approvals.approve', selectedRes.value.id), {
        notes: approvalNotes.value,
    }, {
        onSuccess: () => {
            showApproveDialog.value = false;
            toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Izin kegiatan disetujui & diteruskan ke Sarpras', life: 3000 });
        },
        onFinish: () => { isProcessing.value = false; }
    });
};

const submitReject = () => {
    if (!selectedRes.value || !rejectionNotes.value) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Alasan penolakan wajib diisi', life: 3000 });
        return;
    }
    isProcessing.value = true;
    router.post(route('guru.sarpras.approvals.reject', selectedRes.value.id), {
        notes: rejectionNotes.value,
    }, {
        onSuccess: () => {
            showRejectDialog.value = false;
            toast.add({ severity: 'info', summary: 'Ditolak', detail: 'Pengajuan izin telah ditolak', life: 3000 });
        },
        onFinish: () => { isProcessing.value = false; }
    });
};
</script>
