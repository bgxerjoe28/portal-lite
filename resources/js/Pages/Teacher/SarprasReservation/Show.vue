<template>
    <AppLayout :title="`Detail Peminjaman ${reservation.reservation_code}`">
        <div class="p-4 max-w-4xl mx-auto">
            <!-- Header Bar -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div class="flex align-items-center gap-3">
                    <Button icon="pi pi-arrow-left" class="p-button-outlined p-button-secondary" @click="router.get(route('guru.sarpras.reservations.index'))" />
                    <div>
                        <div class="flex align-items-center gap-2">
                            <h2 class="text-2xl font-extrabold text-900 m-0">{{ reservation.title }}</h2>
                            <Tag :value="getStatusLabel(reservation.status)" :severity="getStatusSeverity(reservation.status)" />
                        </div>
                        <span class="font-mono text-xs text-primary">{{ reservation.reservation_code }}</span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button 
                        v-if="['pending_sarpras', 'approved'].includes(reservation.status)" 
                        label="Batalkan Peminjaman" 
                        icon="pi pi-times" 
                        severity="danger" 
                        outlined 
                        @click="confirmCancel" 
                    />
                </div>
            </div>

            <!-- Content Card -->
            <div class="surface-card p-4 border-round-xl shadow-2 mb-4">
                <h3 class="text-lg font-bold text-900 m-0 mb-3 border-bottom-1 border-200 pb-2">Informasi Peminjaman</h3>

                <div class="grid">
                    <div class="col-12 md:col-6">
                        <span class="text-xs text-500 font-bold uppercase block">Waktu Penggunaan</span>
                        <div class="text-sm font-bold text-900">{{ formatDateTime(reservation.start_time) }}</div>
                        <div class="text-xs text-500">s/d {{ formatDateTime(reservation.end_time) }}</div>
                    </div>

                    <div class="col-12 md:col-6">
                        <span class="text-xs text-500 font-bold uppercase block">Mewakili Kegiatan</span>
                        <div class="text-sm font-bold text-indigo-600">{{ reservation.extracurricular?.name || 'Kegiatan Pembelajaran / KBM Guru' }}</div>
                    </div>

                    <div class="col-12 mt-2">
                        <span class="text-xs text-500 font-bold uppercase block">Tujuan & Keperluan</span>
                        <p class="text-sm text-800 m-0 mt-1 line-height-3">{{ reservation.purpose }}</p>
                    </div>

                    <div v-if="reservation.proposal_file_path" class="col-12 mt-2">
                        <span class="text-xs text-500 font-bold uppercase block mb-1">Berkas Lampiran</span>
                        <a :href="reservation.proposal_file_path" target="_blank" class="inline-flex align-items-center gap-2 p-2 border-1 border-blue-200 bg-blue-50 border-round text-blue-700 text-sm">
                            <i class="pi pi-file text-red-500 text-lg"></i>
                            <span>Buka / Unduh Lampiran Berkas</span>
                        </a>
                    </div>
                </div>

                <!-- Room info -->
                <div v-if="reservation.room" class="mt-4 p-3 bg-blue-50 border-round-lg border-1 border-blue-200">
                    <span class="text-xxs text-blue-700 font-bold uppercase block">Ruangan Terpinjam</span>
                    <div class="font-bold text-base text-blue-950">{{ reservation.room.name }}</div>
                    <div class="text-xs text-blue-800">Lokasi: {{ reservation.room.location || '-' }} (Kapasitas: {{ reservation.room.capacity }} Orang)</div>
                </div>

                <!-- Assets list -->
                <div v-if="reservation.assets && reservation.assets.length > 0" class="mt-4">
                    <span class="text-xxs text-500 font-bold uppercase block mb-2">Daftar Alat / Aset yang Dipinjam ({{ reservation.assets.length }} Item)</span>
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
    </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
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
        case 'pending_coach': return 'warn';
        case 'pending_sarpras': return 'warning';
        case 'approved': return 'info';
        case 'in_use': return 'help';
        case 'completed': return 'success';
        case 'rejected':
        case 'cancelled':
        case 'incident': return 'danger';
        default: return 'secondary';
    }
};

const formatDateTime = (datetimeStr) => {
    if (!datetimeStr) return '-';
    const d = new Date(datetimeStr);
    return d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const confirmCancel = () => {
    confirm.require({
        message: 'Apakah Anda yakin ingin membatalkan permohonan peminjaman sarpras ini?',
        header: 'Konfirmasi Pembatalan',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('guru.sarpras.reservations.cancel', props.reservation.id), {}, {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Peminjaman berhasil dibatalkan', life: 3000 });
                }
            });
        }
    });
};
</script>
