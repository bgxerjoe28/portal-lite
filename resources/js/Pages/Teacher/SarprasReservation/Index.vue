<template>
    <AppLayout title="Peminjaman Fasilitas & Aset Guru">
        <div class="p-4 max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-box text-primary text-3xl"></i>
                        Peminjaman Fasilitas & Aset
                    </h2>
                    <p class="text-600 m-0 mt-1">Ajukan peminjaman ruangan atau peralatan/aset sekolah untuk kegiatan pembelajaran, praktikum, atau acara resmi.</p>
                </div>
                <div>
                    <Button 
                        label="Buat Pengajuan Baru" 
                        icon="pi pi-plus" 
                        class="p-button-primary font-bold shadow-2 w-full md:w-auto" 
                        @click="router.get(route('guru.sarpras.reservations.create'))"
                    />
                </div>
            </div>

            <!-- Filter Status Bar -->
            <div class="surface-card p-3 border-round-xl shadow-1 mb-4 flex flex-wrap gap-2 align-items-center">
                <Button 
                    label="Semua" 
                    :class="!selectedStatus ? 'p-button-primary' : 'p-button-outlined p-button-secondary'" 
                    class="p-button-sm"
                    @click="filterStatus(null)" 
                />
                <Button 
                    label="Menunggu Sarpras" 
                    :class="selectedStatus === 'pending_sarpras' ? 'p-button-warning' : 'p-button-outlined p-button-secondary'" 
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

            <!-- Table -->
            <div class="surface-card border-round-xl shadow-2 overflow-hidden">
                <DataTable :value="reservations.data" responsiveLayout="scroll" :rowHover="true" class="p-datatable-sm">
                    <template #empty>
                        <div class="p-6 text-center text-500">
                            <i class="pi pi-box text-5xl mb-3 text-300"></i>
                            <h4 class="text-lg font-bold text-700 m-0 mb-1">Belum Ada Pengajuan Peminjaman</h4>
                            <p class="text-xs text-500 m-0 mb-4">Butuh proyektor, lab, laptop, atau peralatan pembelajaran lainnya? Ajukan sekarang!</p>
                            <Button label="Buat Peminjaman" icon="pi pi-plus" size="small" @click="router.get(route('guru.sarpras.reservations.create'))" />
                        </div>
                    </template>

                    <Column header="No. Registrasi" style="min-width: 140px">
                        <template #body="{ data }">
                            <div class="font-mono font-bold text-primary">{{ data.reservation_code }}</div>
                            <div class="text-xs text-500">{{ formatDate(data.created_at) }}</div>
                        </template>
                    </Column>

                    <Column header="Kegiatan & Keperluan" style="min-width: 220px">
                        <template #body="{ data }">
                            <div class="font-bold text-900">{{ data.title }}</div>
                            <div class="text-xs text-600 line-clamp-1">{{ data.purpose }}</div>
                            <Tag v-if="data.extracurricular" :value="data.extracurricular.name" severity="secondary" class="text-xxs mt-1" />
                        </template>
                    </Column>

                    <Column header="Ruangan / Aset" style="min-width: 200px">
                        <template #body="{ data }">
                            <div v-if="data.room" class="text-xs font-semibold text-primary">
                                <i class="pi pi-building mr-1"></i> {{ data.room.name }} ({{ data.room.code }})
                            </div>
                            <div v-if="data.assets && data.assets.length > 0" class="text-xs text-orange-600 font-semibold mt-1">
                                <i class="pi pi-box mr-1"></i> {{ data.assets.length }} Item: {{ data.assets.map(a => a.name).join(', ') }}
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

                    <Column header="Aksi" style="min-width: 100px" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <Button 
                                icon="pi pi-eye" 
                                class="p-button-rounded p-button-text p-button-info" 
                                tooltip="Lihat Rincian" 
                                @click="router.get(route('guru.sarpras.reservations.show', data.id))" 
                            />
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
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
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
        case 'approved': return 'Disetujui (Siap Pakai)';
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

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
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
    router.get(route('guru.sarpras.reservations.index'), {
        status: status,
    }, {
        preserveState: true,
        replace: true,
    });
};

const onPageChange = (event) => {
    router.get(route('guru.sarpras.reservations.index'), {
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
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
