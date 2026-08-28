<template>
    <AppLayout :title="`Detail Izin Kegiatan ${reservation.reservation_code}`">
        <div class="p-4 max-w-5xl mx-auto">
            <!-- Header Bar -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div class="flex align-items-center gap-3">
                    <Button icon="pi pi-arrow-left" class="p-button-outlined p-button-secondary" @click="router.get(route('guru.sarpras.approvals.index'))" />
                    <div>
                        <div class="flex align-items-center gap-2">
                            <h2 class="text-2xl font-extrabold text-900 m-0">{{ reservation.title }}</h2>
                            <Tag :value="reservation.stage1_status" :severity="reservation.stage1_status === 'approved' ? 'success' : (reservation.stage1_status === 'rejected' ? 'danger' : 'warn')" />
                        </div>
                        <span class="font-mono text-xs text-primary">{{ reservation.reservation_code }}</span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <template v-if="reservation.stage1_status === 'pending'">
                        <Button label="Setujui Izin" icon="pi pi-check" class="p-button-success" @click="showApproveDialog = true" />
                        <Button label="Tolak Izin" icon="pi pi-times" class="p-button-danger p-button-outlined" @click="showRejectDialog = true" />
                    </template>
                </div>
            </div>

            <!-- Content Card -->
            <div class="surface-card p-4 border-round-xl shadow-2 mb-4">
                <h3 class="text-lg font-bold text-900 m-0 mb-3 border-bottom-1 border-200 pb-2">Detail Pengajuan Kegiatan</h3>

                <div class="grid">
                    <div class="col-12 md:col-6">
                        <span class="text-xs text-500 font-bold uppercase block">Ekstrakurikuler</span>
                        <div class="text-base font-bold text-indigo-600">{{ reservation.extracurricular?.name || '-' }}</div>
                        <div class="text-xs text-600">Diajukan oleh: {{ reservation.user?.name }}</div>
                    </div>

                    <div class="col-12 md:col-6">
                        <span class="text-xs text-500 font-bold uppercase block">Jadwal Pelaksanaan</span>
                        <div class="text-sm font-bold text-900">{{ formatDateTime(reservation.start_time) }}</div>
                        <div class="text-xs text-500">s/d {{ formatDateTime(reservation.end_time) }}</div>
                    </div>

                    <div class="col-12 mt-2">
                        <span class="text-xs text-500 font-bold uppercase block">Tujuan & Keperluan</span>
                        <p class="text-sm text-800 m-0 mt-1 line-height-3">{{ reservation.purpose }}</p>
                    </div>

                    <div v-if="reservation.proposal_file_path" class="col-12 mt-2">
                        <span class="text-xs text-500 font-bold uppercase block mb-1">Proposal Kegiatan</span>
                        <a :href="reservation.proposal_file_path" target="_blank" class="inline-flex align-items-center gap-2 p-2 border-1 border-blue-200 bg-blue-50 border-round text-blue-700 text-sm">
                            <i class="pi pi-file-pdf text-red-500 text-lg"></i>
                            <span>Buka / Unduh Proposal Kegiatan (PDF)</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Facilities Card -->
            <div class="surface-card p-4 border-round-xl shadow-2 mb-4">
                <h3 class="text-lg font-bold text-900 m-0 mb-3 border-bottom-1 border-200 pb-2">Sarana & Prasarana yang Diminta</h3>
                
                <div v-if="reservation.room" class="mb-3 p-3 bg-blue-50 border-round border-1 border-blue-200">
                    <span class="text-xs text-blue-600 font-bold uppercase block">Ruangan</span>
                    <div class="font-bold text-base text-blue-900">{{ reservation.room.name }}</div>
                    <div class="text-xs text-blue-700">Lokasi: {{ reservation.room.location || '-' }} • Kapasitas: {{ reservation.room.capacity }} Orang</div>
                </div>

                <div v-if="reservation.assets && reservation.assets.length > 0">
                    <span class="text-xs text-500 font-bold uppercase block mb-2">Peralatan / Aset ({{ reservation.assets.length }} Item)</span>
                    <ul class="list-none p-0 m-0">
                        <li v-for="asset in reservation.assets" :key="asset.id" class="p-2 border-bottom-1 border-100 flex justify-content-between align-items-center text-sm">
                            <div>
                                <span class="font-bold text-900">{{ asset.name }}</span>
                                <span class="font-mono text-xs text-500 ml-2">[{{ asset.asset_code }}]</span>
                            </div>
                            <Tag :value="asset.category" severity="secondary" />
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Approval Dialog -->
        <Dialog v-model:visible="showApproveDialog" header="Setujui Izin Kegiatan (Tahap 1)" :modal="true" class="p-fluid w-full md:w-5">
            <div class="flex flex-column gap-3 pt-2">
                <p class="text-sm text-700 m-0">Setujui pengajuan izin kegiatan ini untuk diteruskan ke Petugas Sarpras?</p>
                <div>
                    <label class="font-bold text-sm block mb-1">Catatan Pembina (Opsional)</label>
                    <Textarea v-model="approvalNotes" rows="3" placeholder="Pesan / catatan..." />
                </div>
                <div class="flex justify-content-end gap-2 mt-3">
                    <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="showApproveDialog = false" />
                    <Button label="Setujui Izin" icon="pi pi-check" class="p-button-success" :loading="isProcessing" @click="submitApprove" />
                </div>
            </div>
        </Dialog>

        <!-- Rejection Dialog -->
        <Dialog v-model:visible="showRejectDialog" header="Tolak Izin Kegiatan" :modal="true" class="p-fluid w-full md:w-5">
            <div class="flex flex-column gap-3 pt-2">
                <div>
                    <label class="font-bold text-sm block mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                    <Textarea v-model="rejectionNotes" rows="3" placeholder="Alasan penolakan..." required />
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

const props = defineProps({
    reservation: Object,
});

const toast = useToast();

const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const approvalNotes = ref('');
const rejectionNotes = ref('');
const isProcessing = ref(false);

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const submitApprove = () => {
    isProcessing.value = true;
    router.post(route('guru.sarpras.approvals.approve', props.reservation.id), {
        notes: approvalNotes.value,
    }, {
        onSuccess: () => {
            showApproveDialog.value = false;
            toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Izin kegiatan disetujui', life: 3000 });
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
    router.post(route('guru.sarpras.approvals.reject', props.reservation.id), {
        notes: rejectionNotes.value,
    }, {
        onSuccess: () => {
            showRejectDialog.value = false;
            toast.add({ severity: 'info', summary: 'Ditolak', detail: 'Pengajuan izin ditolak', life: 3000 });
        },
        onFinish: () => { isProcessing.value = false; }
    });
};
</script>
