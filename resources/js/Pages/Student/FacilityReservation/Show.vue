<template>
    <SiswaLayout :title="`Status Peminjaman ${reservation.reservation_code}`">
        <div class="p-3 md:p-4 max-w-4xl mx-auto">
            <!-- Header Bar -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div class="flex align-items-center gap-3">
                    <Button icon="pi pi-arrow-left" class="p-button-outlined p-button-secondary" @click="router.get(route('student.sarpras.reservations.index'))" />
                    <div>
                        <div class="flex align-items-center gap-2">
                            <h2 class="text-xl md:text-2xl font-extrabold text-900 m-0">{{ reservation.title }}</h2>
                            <Tag :value="getStatusLabel(reservation.status)" :severity="getStatusSeverity(reservation.status)" />
                        </div>
                        <span class="font-mono text-xs text-primary">{{ reservation.reservation_code }}</span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button 
                        v-if="['approved', 'in_use', 'completed'].includes(reservation.status)"
                        label="Unduh E-Permit (PDF)" 
                        icon="pi pi-file-pdf" 
                        class="p-button-danger font-bold" 
                        @click="downloadPermit"
                    />
                    <Button 
                        v-if="['pending_coach', 'pending_sarpras', 'draft'].includes(reservation.status)"
                        label="Batalkan Pengajuan" 
                        icon="pi pi-trash" 
                        class="p-button-outlined p-button-danger text-xs" 
                        @click="confirmCancel"
                    />
                </div>
            </div>

            <!-- E-Permit Digital QR Box (Prominent if Approved / In Use) -->
            <div v-if="['approved', 'in_use'].includes(reservation.status)" class="surface-card p-4 border-round-2xl shadow-3 mb-4 text-center border-top-3 border-green-500 bg-green-50">
                <span class="bg-green-600 text-white text-xs font-bold px-3 py-1 border-round-full uppercase">Tiket Pengambilan / Serah Terima Aktif</span>
                <h3 class="text-xl font-bold text-green-950 m-0 mt-2">Tunjukkan Kode QR ini ke Petugas Sarpras</h3>
                <p class="text-xs text-green-800 m-0 mb-3">Petugas akan scan barcode ini untuk serah terima barang dan verifikasi pengembalian.</p>

                <div class="surface-0 p-3 border-round-2xl shadow-2 inline-block border-1 border-green-200">
                    <img 
                        :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(reservation.qr_token)}`" 
                        alt="QR Permit" 
                        width="170" 
                        height="170"
                        class="mx-auto"
                    />
                    <div class="font-mono text-sm font-extrabold text-900 mt-2">{{ reservation.qr_token }}</div>
                </div>
            </div>

            <!-- Progression Steps Card -->
            <div class="surface-card p-4 border-round-2xl shadow-2 mb-4">
                <h3 class="text-base font-bold text-900 m-0 mb-4 border-bottom-1 border-100 pb-2">Status Persetujuan Berjenjang</h3>

                <div class="grid">
                    <!-- Step 1 -->
                    <div class="col-12 md:col-6 mb-2">
                        <div class="p-3 border-1 border-round-xl" :class="reservation.stage1_status === 'approved' ? 'bg-green-50 border-green-300' : (reservation.stage1_status === 'rejected' ? 'bg-red-50 border-red-300' : 'bg-yellow-50 border-yellow-300')">
                            <div class="flex justify-content-between align-items-center mb-1">
                                <span class="font-bold text-xs">1. Persetujuan Pembina Eskul</span>
                                <Tag :value="reservation.stage1_status" :severity="reservation.stage1_status === 'approved' ? 'success' : (reservation.stage1_status === 'rejected' ? 'danger' : 'warn')" />
                            </div>
                            <div v-if="reservation.stage1_by" class="text-xs text-700">
                                Disetujui oleh: <strong>{{ reservation.stage1_approver?.name }}</strong>
                            </div>
                            <div v-if="reservation.stage1_notes" class="text-xs mt-1 text-800">
                                Catatan: <em>"{{ reservation.stage1_notes }}"</em>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="col-12 md:col-6 mb-2">
                        <div class="p-3 border-1 border-round-xl" :class="reservation.stage2_status === 'approved' ? 'bg-green-50 border-green-300' : (reservation.stage2_status === 'rejected' ? 'bg-red-50 border-red-300' : 'bg-yellow-50 border-yellow-300')">
                            <div class="flex justify-content-between align-items-center mb-1">
                                <span class="font-bold text-xs">2. Persetujuan Petugas Sarpras</span>
                                <Tag :value="reservation.stage2_status" :severity="reservation.stage2_status === 'approved' ? 'success' : (reservation.stage2_status === 'rejected' ? 'danger' : 'warn')" />
                            </div>
                            <div v-if="reservation.stage2_by" class="text-xs text-700">
                                Disetujui oleh: <strong>{{ reservation.stage2_approver?.name }}</strong>
                            </div>
                            <div v-if="reservation.stage2_notes" class="text-xs mt-1 text-800">
                                Catatan: <em>"{{ reservation.stage2_notes }}"</em>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Card -->
            <div class="surface-card p-4 border-round-2xl shadow-2 mb-4">
                <h3 class="text-base font-bold text-900 m-0 mb-3 border-bottom-1 border-100 pb-2">Rincian Fasilitas & Jadwal</h3>

                <div class="grid">
                    <div class="col-12 md:col-6">
                        <span class="text-xxs text-500 font-bold uppercase block">Waktu Penggunaan</span>
                        <div class="text-sm font-bold text-900">{{ formatDateTime(reservation.start_time) }}</div>
                        <div class="text-xs text-500">s/d {{ formatDateTime(reservation.end_time) }}</div>
                    </div>

                    <div class="col-12 md:col-6">
                        <span class="text-xxs text-500 font-bold uppercase block">Organisasi / Eskul</span>
                        <div class="text-sm font-bold text-indigo-600">{{ reservation.extracurricular?.name || 'Pribadi / Panitia Sekolah' }}</div>
                    </div>

                    <div class="col-12 mt-2">
                        <span class="text-xxs text-500 font-bold uppercase block">Tujuan & Keperluan</span>
                        <p class="text-sm text-800 m-0 mt-1 line-height-3">{{ reservation.purpose }}</p>
                    </div>
                </div>

                <!-- Room info -->
                <div v-if="reservation.room" class="mt-4 p-3 bg-blue-50 border-round-lg border-1 border-blue-200">
                    <span class="text-xxs text-blue-700 font-bold uppercase block">Ruangan Terpinjam</span>
                    <div class="font-bold text-base text-blue-950">{{ reservation.room.name }}</div>
                    <div class="text-xs text-blue-800">Lokasi: {{ reservation.room.location || '-' }}</div>
                </div>

                <!-- Assets list -->
                <div v-if="reservation.assets && reservation.assets.length > 0" class="mt-4">
                    <span class="text-xxs text-500 font-bold uppercase block mb-2">Daftar Alat / Aset ({{ reservation.assets.length }} Item)</span>
                    <ul class="list-none p-0 m-0 border-1 border-200 border-round-xl overflow-hidden">
                        <li v-for="asset in reservation.assets" :key="asset.id" class="p-3 border-bottom-1 border-100 flex justify-content-between align-items-center">
                            <div>
                                <span class="font-bold text-sm text-900">{{ asset.name }}</span>
                                <span class="text-xs font-mono text-500 ml-2">[{{ asset.asset_code }}]</span>
                            </div>
                            <Tag :value="asset.pivot?.checkout_condition || 'Baik'" severity="secondary" />
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </SiswaLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
    reservation: Object,
});

const confirm = useConfirm();
const toast = useToast();

const getStatusLabel = (status) => {
    switch(status) {
        case 'pending_coach': return 'Menunggu Pembina';
        case 'pending_sarpras': return 'Menunggu Sarpras';
        case 'approved': return 'Disetujui (Siap Ambil)';
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
        case 'pending_coach': case 'pending_sarpras': return 'warn';
        case 'approved': return 'info';
        case 'in_use': return 'help';
        case 'completed': return 'success';
        case 'rejected': case 'cancelled': case 'incident': return 'danger';
        default: return 'secondary';
    }
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const downloadPermit = () => {
    window.open(route('student.sarpras.reservations.permit-pdf', props.reservation.id), '_blank');
};

const confirmCancel = () => {
    confirm.require({
        message: 'Apakah Anda yakin ingin membatalkan pengajuan peminjaman fasilitas ini?',
        header: 'Konfirmasi Pembatalan',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('student.sarpras.reservations.cancel', props.reservation.id), {}, {
                onSuccess: () => {
                    toast.add({ severity: 'info', summary: 'Dibatalkan', detail: 'Pengajuan telah dibatalkan', life: 3000 });
                }
            });
        }
    });
};
</script>

<style scoped>
.text-xxs {
    font-size: 0.7rem;
}
</style>
