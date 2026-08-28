<template>
    <SiswaLayout title="Peminjaman Ruang & Aset Saya">
        <div class="p-3 md:p-4 max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div>
                    <h2 class="text-2xl md:text-3xl font-extrabold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-calendar-plus text-primary text-2xl md:text-3xl"></i>
                        Peminjaman Fasilitas & Alat
                    </h2>
                    <p class="text-600 m-0 mt-1 text-sm">Ajukan izin peminjaman ruang kelas/lab dan peralatan untuk kegiatan eskul.</p>
                </div>
                <div>
                    <Button 
                        label="Buat Pengajuan Baru" 
                        icon="pi pi-plus" 
                        class="p-button-primary font-bold shadow-2 w-full md:w-auto" 
                        @click="router.get(route('student.sarpras.reservations.create'))"
                    />
                </div>
            </div>

            <!-- Filter Status -->
            <div class="surface-card p-3 border-round-xl shadow-1 mb-4 flex flex-wrap gap-2 align-items-center">
                <Button 
                    label="Semua" 
                    :class="!selectedStatus ? 'p-button-primary' : 'p-button-outlined p-button-secondary'" 
                    class="p-button-sm"
                    @click="filterStatus(null)" 
                />
                <Button 
                    label="Menunggu" 
                    :class="selectedStatus === 'pending_sarpras' || selectedStatus === 'pending_coach' ? 'p-button-warning' : 'p-button-outlined p-button-secondary'" 
                    class="p-button-sm"
                    @click="filterStatus('pending_sarpras')" 
                />
                <Button 
                    label="Disetujui" 
                    :class="selectedStatus === 'approved' ? 'p-button-info' : 'p-button-outlined p-button-secondary'" 
                    class="p-button-sm"
                    @click="filterStatus('approved')" 
                />
                <Button 
                    label="Sedang Digunakan" 
                    :class="selectedStatus === 'in_use' ? 'p-button-help' : 'p-button-outlined p-button-secondary'" 
                    class="p-button-sm"
                    @click="filterStatus('in_use')" 
                />
                <Button 
                    label="Selesai" 
                    :class="selectedStatus === 'completed' ? 'p-button-success' : 'p-button-outlined p-button-secondary'" 
                    class="p-button-sm"
                    @click="filterStatus('completed')" 
                />
            </div>

            <!-- List Cards -->
            <div v-if="reservations.data && reservations.data.length > 0" class="flex flex-column gap-3">
                <div 
                    v-for="res in reservations.data" 
                    :key="res.id"
                    class="surface-card p-4 border-round-xl shadow-2 border-1 border-200 transition-all transition-duration-150 hover:shadow-4 flex flex-column justify-content-between cursor-pointer"
                    @click="router.get(route('student.sarpras.reservations.show', res.id))"
                >
                    <div>
                        <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-2 border-bottom-1 border-100 pb-2 mb-3">
                            <div class="flex align-items-center gap-2">
                                <span class="font-mono font-bold text-xs text-primary">{{ res.reservation_code }}</span>
                                <span v-if="res.extracurricular" class="text-xs bg-indigo-50 text-indigo-700 px-2 py-1 border-round font-semibold">
                                    {{ res.extracurricular.name }}
                                </span>
                            </div>
                            <Tag :value="getStatusLabel(res.status)" :severity="getStatusSeverity(res.status)" />
                        </div>

                        <h3 class="text-lg font-bold text-900 m-0 mb-1">{{ res.title }}</h3>
                        <p class="text-xs text-600 m-0 mb-3 line-clamp-2">{{ res.purpose }}</p>

                        <!-- Facilities Requested -->
                        <div class="flex flex-wrap gap-3 text-xs mb-3">
                            <div v-if="res.room" class="flex align-items-center gap-1 text-700 font-semibold">
                                <i class="pi pi-building text-primary"></i>
                                <span>Ruang: {{ res.room.name }}</span>
                            </div>
                            <div v-if="res.assets && res.assets.length > 0" class="flex align-items-center gap-1 text-700 font-semibold">
                                <i class="pi pi-box text-orange-500"></i>
                                <span>{{ res.assets.length }} Item Alat Dipinjam</span>
                            </div>
                        </div>

                        <div class="flex align-items-center gap-2 text-xxs text-500">
                            <i class="pi pi-clock"></i>
                            <span>{{ formatDateTime(res.start_time) }} - {{ formatDateTime(res.end_time) }}</span>
                        </div>
                    </div>

                    <!-- Action Link & Progress Indicator -->
                    <div class="mt-4 pt-3 border-top-1 border-100 flex justify-content-between align-items-center">
                        <div class="flex align-items-center gap-1 text-xxs text-600">
                            <span :class="res.stage1_status === 'approved' ? 'text-green-600 font-bold' : (res.stage1_status === 'rejected' ? 'text-red-500' : 'text-orange-500')">
                                Pembina: {{ res.stage1_status }}
                            </span>
                            <span>•</span>
                            <span :class="res.stage2_status === 'approved' ? 'text-green-600 font-bold' : (res.stage2_status === 'rejected' ? 'text-red-500' : 'text-orange-500')">
                                Sarpras: {{ res.stage2_status }}
                            </span>
                        </div>
                        <Button label="Lihat Rincian" icon="pi pi-arrow-right" iconPos="right" class="p-button-text p-button-sm text-xs font-bold" />
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="surface-card p-6 border-round-xl text-center shadow-1">
                <i class="pi pi-calendar-plus text-5xl text-400 mb-3"></i>
                <h4 class="text-lg font-bold text-700 m-0 mb-2">Belum ada riwayat peminjaman</h4>
                <p class="text-500 text-xs m-0 mb-4">Butuh ruang rapat, sound system, lab, atau laptop untuk kegiatan eskul? Ajukan sekarang!</p>
                <Button label="Ajukan Peminjaman" icon="pi pi-plus" @click="router.get(route('student.sarpras.reservations.create'))" />
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
    </SiswaLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Paginator from 'primevue/paginator';

const props = defineProps({
    reservations: Object,
    filters: Object,
});

const selectedStatus = ref(props.filters?.status || null);

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

const filterStatus = (status) => {
    selectedStatus.value = status;
    router.get(route('student.sarpras.reservations.index'), {
        status: status,
    }, {
        preserveState: true,
        replace: true,
    });
};

const onPageChange = (event) => {
    router.get(route('student.sarpras.reservations.index'), {
        page: event.page + 1,
        status: selectedStatus.value,
    }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<style scoped>
.text-xxs {
    font-size: 0.7rem;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
