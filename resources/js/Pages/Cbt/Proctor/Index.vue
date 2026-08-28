<template>
    <AppLayout title="Proktoring Ujian">
        <CbtTabMenu />

        <div class="card">
            <div class="flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Daftar Pengawasan Ujian</h2>
                    <span class="text-500 block mt-1">Daftar jadwal pengawasan ujian CBT di masing-masing ruangan Anda</span>
                </div>
            </div>

            <div class="surface-card p-4 shadow-2 border-round">
                <DataTable :value="sessions.data" stripedRows tableStyle="min-width: 50rem">
                    <template #empty> Anda tidak memiliki jadwal pengawasan ujian saat ini. </template>

                    <Column header="Sesi & Jadwal" sortable style="width: 20%">
                        <template #body="{ data }">
                            <span class="font-bold text-lg text-900 block">{{ data.session?.name || '-' }}</span>
                            <small class="text-500">Tanggal: <b>{{ formatScheduleDate(data.date) }}</b></small>
                            <br>
                            <small class="text-500" v-if="data.session?.start_time">Pukul: <b>{{ data.session.start_time.substring(0,5) }} - {{ data.session.end_time.substring(0,5) }}</b></small>
                        </template>
                    </Column>

                    <Column field="room.name" header="Ruangan" sortable style="width: 15%">
                        <template #body="{ data }">
                            <span class="font-semibold text-700 block">{{ data.room?.name || '-' }}</span>
                            <small class="text-500">Kapasitas: {{ data.room?.capacity || 36 }} Kursi</small>
                        </template>
                    </Column>

                    <Column field="teacher.full_name" header="Pengawas" style="width: 20%">
                        <template #body="{ data }">
                            <span class="text-600 font-semibold">{{ data.teacher?.full_name || '-' }}</span>
                        </template>
                    </Column>

                    <Column field="status" header="Status Sesi" style="width: 15%">
                        <template #body="{ data }">
                            <Tag 
                                :value="formatStatusLabel(data.status)" 
                                :severity="formatStatusSeverity(data.status)" 
                            />
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 20%">
                        <template #body="{ data }">
                            <Link :href="route('cbt.proctor.show', data.id)">
                                <Button 
                                    label="Buka Pengawas" 
                                    icon="pi pi-desktop" 
                                    severity="primary" 
                                    size="small"
                                    class="p-button-raised"
                                />
                            </Link>
                        </template>
                    </Column>
                </DataTable>
                <Pagination :links="sessions.links" class="mt-4" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import CbtTabMenu from '../CbtTabMenu.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

defineProps({
    sessions: Object,
});

const formatStatusLabel = (status) => {
    const labels = {
        'not_started': 'Belum Mulai',
        'started': 'Sedang Berlangsung',
        'ended': 'Selesai',
    };
    return labels[status] || status;
};

const formatStatusSeverity = (status) => {
    const severities = {
        'not_started': 'secondary',
        'started': 'warn',
        'ended': 'success',
    };
    return severities[status] || 'info';
};

const formatScheduleDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>
