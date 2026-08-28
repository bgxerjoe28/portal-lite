<template>
    <AppLayout title="Kelola Laporan Sarana & Prasarana">
        <div class="p-4">
            <!-- Header Section -->
            <div class="mb-4">
                <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3">
                    <div>
                        <h2 class="text-3xl font-extrabold text-900 m-0 flex align-items-center gap-2">
                            <i class="pi pi-wrench text-orange-500 text-3xl"></i>
                            Kelola Laporan Sarana & Prasarana
                        </h2>
                        <p class="text-600 m-0 mt-1">Verifikasi, evaluasi, dan perbarui status penanganan laporan sarana prasarana sekolah.</p>
                    </div>
                </div>
            </div>

            <!-- Tab Menu -->
            <div class="mb-4">
                <TabMenu :model="tabItems" :activeIndex="0" />
            </div>

            <!-- Stats Bar -->
            <div class="grid mb-4">
                <div class="col-12 md:col-3">
                    <div class="surface-card p-4 border-round-xl shadow-2 border-left-3 border-yellow-500 flex align-items-center justify-content-between">
                        <div>
                            <span class="block text-500 font-medium mb-1">Laporan Pending</span>
                            <span class="text-900 font-bold text-2xl">
                                {{ reports.data.filter(r => r.status === 'pending').length }} Laporan
                            </span>
                        </div>
                        <div class="flex align-items-center justify-content-center bg-yellow-100 border-round-md" style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 md:col-3">
                    <div class="surface-card p-4 border-round-xl shadow-2 border-left-3 border-blue-500 flex align-items-center justify-content-between">
                        <div>
                            <span class="block text-500 font-medium mb-1">Diverifikasi</span>
                            <span class="text-900 font-bold text-2xl">
                                {{ reports.data.filter(r => r.status === 'verified').length }} Laporan
                            </span>
                        </div>
                        <div class="flex align-items-center justify-content-center bg-blue-100 border-round-md" style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-check-circle text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 md:col-3">
                    <div class="surface-card p-4 border-round-xl shadow-2 border-left-3 border-indigo-500 flex align-items-center justify-content-between">
                        <div>
                            <span class="block text-500 font-medium mb-1">Sedang Diperbaiki</span>
                            <span class="text-900 font-bold text-2xl">
                                {{ reports.data.filter(r => r.status === 'in_progress').length }} Laporan
                            </span>
                        </div>
                        <div class="flex align-items-center justify-content-center bg-indigo-100 border-round-md" style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-cog text-indigo-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 md:col-3">
                    <div class="surface-card p-4 border-round-xl shadow-2 border-left-3 border-green-500 flex align-items-center justify-content-between">
                        <div>
                            <span class="block text-500 font-medium mb-1">Selesai Ditangani</span>
                            <span class="text-900 font-bold text-2xl">
                                {{ reports.data.filter(r => r.status === 'resolved').length }} Laporan
                            </span>
                        </div>
                        <div class="flex align-items-center justify-content-center bg-green-100 border-round-md" style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-thumbs-up text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="surface-card shadow-2 border-round-xl p-4 mb-4">
                <div class="flex flex-wrap gap-3 align-items-center justify-content-between">
                    <div class="flex flex-wrap gap-3 p-fluid">
                        <div style="min-width: 180px">
                            <Select 
                                v-model="filter.status" 
                                :options="statusOptions" 
                                optionLabel="label" 
                                optionValue="value" 
                                placeholder="Filter Status" 
                                showClear 
                            />
                        </div>
                        <div style="min-width: 180px">
                            <Select 
                                v-model="filter.type" 
                                :options="typeOptions" 
                                optionLabel="label" 
                                optionValue="value" 
                                placeholder="Filter Kategori" 
                                showClear 
                            />
                        </div>
                        <div style="min-width: 180px">
                            <Select 
                                v-model="filter.severity" 
                                :options="severityOptions" 
                                optionLabel="label" 
                                optionValue="value" 
                                placeholder="Filter Keparahan" 
                                showClear 
                            />
                        </div>
                    </div>
                    <div class="text-500 text-sm">
                        Total: {{ reports.total || 0 }} laporan masuk
                    </div>
                </div>

                <Divider class="my-4" />

                <!-- Table Content -->
                <DataTable :value="reports.data" stripedRows responsiveLayout="scroll" :rows="10" class="p-datatable-sm">
                    <Column header="No" headerStyle="width: 3rem">
                        <template #body="slotProps">
                            {{ slotProps.index + 1 }}
                        </template>
                    </Column>
                    <Column field="title" header="Laporan" sortable>
                        <template #body="{ data }">
                            <div class="flex flex-column">
                                <span class="font-semibold text-900 text-base">{{ data.title }}</span>
                                <span class="text-xs text-500">ID #{{ data.id }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="reporter.name" header="Pelapor" sortable>
                        <template #body="{ data }">
                            <div class="flex flex-column">
                                <span class="font-semibold text-800">{{ data.reporter?.name }}</span>
                                <span class="text-xs text-500">NIP: {{ data.reporter?.teacher?.nip || '-' }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="type" header="Kategori" sortable>
                        <template #body="{ data }">
                            <Tag :value="formatType(data.type)" :severity="data.type === 'sarana' ? 'info' : 'help'" />
                        </template>
                    </Column>
                    <Column field="item_name" header="Item" sortable></Column>
                    <Column field="location" header="Lokasi" sortable>
                        <template #body="{ data }">
                            <span class="text-700 font-medium"><i class="pi pi-map-marker text-orange-500 mr-1"></i>{{ data.location }}</span>
                        </template>
                    </Column>
                    <Column field="severity" header="Tingkat" sortable>
                        <template #body="{ data }">
                            <span :class="severityClass(data.severity)" class="font-bold px-2 py-1 border-round text-xs">
                                {{ data.severity.toUpperCase() }}
                            </span>
                        </template>
                    </Column>
                    <Column field="status" header="Status" sortable>
                        <template #body="{ data }">
                            <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" class="font-bold" />
                        </template>
                    </Column>
                    <Column field="created_at" header="Tanggal Lapor">
                        <template #body="{ data }">
                            {{ formatDate(data.created_at) }}
                        </template>
                    </Column>
                    <Column header="Tindakan" headerStyle="width: 8rem; text-align: center" bodyStyle="text-align: center">
                        <template #body="{ data }">
                            <Button 
                                as="a"
                                :href="route('admin.facility.reports.show', data.id)"
                                target="_blank"
                                icon="pi pi-wrench" 
                                text 
                                rounded 
                                severity="warning" 
                                v-tooltip.top="'Kelola Penanganan'"
                            />
                        </template>
                    </Column>
                </DataTable>

                <!-- Custom Pagination -->
                <div v-if="reports.links && reports.links.length > 3" class="flex justify-content-center gap-1 mt-4">
                    <Button 
                        v-for="(link, idx) in reports.links" 
                        :key="idx" 
                        :disabled="!link.url || link.active"
                        :severity="link.active ? 'primary' : 'secondary'"
                        text
                        outlined
                        size="small"
                        @click="router.get(link.url)" 
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Tag from 'primevue/tag';
import TabMenu from 'primevue/tabmenu';

const props = defineProps({
    reports: Object,
    filters: Object
});

const filter = reactive({
    status: props.filters.status || null,
    type: props.filters.type || null,
    severity: props.filters.severity || null
});

const tabItems = [
    { label: 'Daftar Laporan', icon: 'pi pi-file', command: () => router.get(route('admin.facility.reports.index')) },
    { label: 'Rekap Sarana Prasarana', icon: 'pi pi-list', command: () => router.get(route('admin.facility.reports.recap')) }
];

const statusOptions = [
    { label: 'Pending (Menunggu)', value: 'pending' },
    { label: 'Terverifikasi', value: 'verified' },
    { label: 'Sedang Diperbaiki', value: 'in_progress' },
    { label: 'Selesai Perbaikan', value: 'resolved' },
    { label: 'Ditolak', value: 'rejected' }
];

const typeOptions = [
    { label: 'Sarana (Lampu, LCD, Meja, dll)', value: 'sarana' },
    { label: 'Prasarana (Plafon, Tembok, dll)', value: 'prasarana' }
];

const severityOptions = [
    { label: 'Ringan', value: 'ringan' },
    { label: 'Sedang', value: 'sedang' },
    { label: 'Berat', value: 'berat' }
];

watch(
    () => [filter.status, filter.type, filter.severity],
    ([newStatus, newType, newSeverity]) => {
        router.get(route('admin.facility.reports.index'), 
            { 
                status: newStatus, 
                type: newType,
                severity: newSeverity
            }, 
            { 
                preserveState: true, 
                replace: true, 
                only: ['reports']
            }
        );
    }
);

const formatType = (type) => {
    return type === 'sarana' ? 'Sarana' : 'Prasarana';
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
