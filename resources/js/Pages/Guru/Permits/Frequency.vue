<template>
    <AppLayout title="Rekap Frekuensi Izin">
        <div class="card border-0 shadow-sm">
            
            <div class="flex flex-column md:flex-row justify-content-between align-items-center gap-3 mb-5">
                <div class="flex align-items-center gap-3">
                    <div class="bg-info-50 p-2 border-round">
                        <i class="pi pi-chart-bar text-info text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold m-0 text-900">Rekapitulasi Kedisiplinan</h2>
                        <p class="text-500 text-sm">Analisis frekuensi ketidakhadiran siswa</p>
                    </div>
                </div>

                <div class="flex gap-2 align-items-center">
                    <Link :href="route('permits.index')">
                        <Button label="Harian" icon="pi pi-calendar" severity="secondary" text class="p-button-sm" />
                    </Link>
                    <Button label="Rekap Frekuensi" icon="pi pi-chart-bar" severity="info" class="p-button-sm" />
                    <Divider layout="vertical" />
                    <Button 
                        label="Sampah" 
                        icon="pi pi-trash" 
                        severity="danger" 
                        outlined 
                        size="small" 
                        @click="router.get(route('permits.trash'))" 
                    />
                </div>
            </div>
            <!-- FILTER CARD -->
<div class="surface-card border-round-2xl mb-5 border surface-border overflow-hidden">

    <!-- Header Filter -->
    <div class="flex align-items-center justify-content-between px-4 py-3 surface-50 border-bottom-1 surface-border">
        <div class="flex align-items-center gap-2">
            <i class="pi pi-filter text-primary"></i>
            <span class="font-semibold text-900">Filter Data</span>
        </div>
        <small class="text-500">Gunakan filter untuk mempersempit hasil</small>
    </div>

    <!-- Body -->
    <div class="p-4">
        <div class="grid p-fluid">

            <!-- Cari Siswa -->
            <div class="col-12 md:col-4">
                <label class="block mb-2 font-semibold text-700 text-sm">
                    Nama Siswa
                </label>
                <span class="p-input w-full">
                    <InputText 
                        v-model="tableFilters['student.full_name'].value" 
                        placeholder="Cari nama siswa..." 
                        class="w-full"
                        
                    />
                </span>
            </div>

            <!-- Rentang Tanggal -->
            <div class="col-12 md:col-4">
                <label class="block mb-2 font-semibold text-700 text-sm">
                    Rentang Tanggal
                </label>
                <DatePicker 
                    v-model="dateRange" 
                    selectionMode="range"
                    placeholder="Pilih tanggal..."
                    showIcon
                    class="w-full"
                />
            </div>

            <!-- Kelas -->
            <div class="col-12 md:col-4">
                <label class="block mb-2 font-semibold text-700 text-sm">
                    Kelas
                </label>
                <Select 
                    v-model="tableFilters['student.current_classroom.name'].value"
                    :options="classroomOptions"
                    placeholder="Semua Kelas"
                    class="w-full"
                    :showClear="true"
                />
            </div>

        </div>

        <!-- Action Buttons -->
        <div class="flex flex-column md:flex-row justify-content-between align-items-center gap-3 mt-4 pt-4 border-top-1 surface-border">

            <div class="flex gap-2">
                <Button 
                    label="Terapkan" 
                    icon="pi pi-check"
                    severity="primary"
                    @click="applyFilter"
                />
                <Button 
                    label="Reset"
                    icon="pi pi-refresh"
                    outlined
                    severity="secondary"
                    @click="resetFilter"
                />
            </div>

            <SplitButton 
                label="Export"
                icon="pi pi-download"
                :model="exportItems"
                severity="success"
                outlined
            />
        </div>

    </div>
</div>

            
            <DataTable v-bind="$pagination({ label: 'statistics' })" 
                :value="statistics" 
                v-model:filters="tableFilters" 
                filterDisplay="row"
                stripedRows 
                size="small"
                class="shadow-1 border-round-lg overflow-hidden"
                responsiveLayout="scroll"
            >
            <Column header="No.">
                    <template #body="{ index }">
                        <div class="flex flex-column">
                            <span class="font-bold text-900">{{ index + 1 }}</span>
                        </div>
                    </template>
                </Column>    
            <Column header="Nama Siswa">
                    <template #body="{ data }">
                        <div class="flex flex-column">
                            <Link :href="route('permits.index', { student_id: data.student.id })" class="font-bold text-blue-600 hover:underline cursor-pointer">
                                {{ data.student.full_name }}
                            </Link>
                            <small class="text-500">NIS {{ data.student.nis }}</small>
                        </div>
                    </template>
                </Column>

                <Column header="Kelas" style="width: 100px">
                    <template #body="{ data }">
                        <Tag :value="data.student?.current_classroom?.name || '-'" severity="secondary" rounded />
                    </template>
                </Column>

                <Column header="S" headerClass="justify-content-center" class="text-center" style="width: 70px">
                    <template #body="{ data }">
                        <div class="flex justify-content-center">
                            <Tag :value="data.total_sakit" severity="info" rounded v-tooltip.top="'Sakit'" />
                        </div>
                    </template>
                </Column>

                <Column header="I" headerClass="justify-content-center" class="text-center" style="width: 70px">
                    <template #body="{ data }">
                        <div class="flex justify-content-center">
                            <Tag :value="data.total_izin" severity="warn" rounded v-tooltip.top="'Izin'" />
                        </div>
                    </template>
                </Column>

                <Column header="D" headerClass="justify-content-center" class="text-center" style="width: 70px">
                    <template #body="{ data }">
                        <div class="flex justify-content-center">
                            <Tag :value="data.total_dispen" severity="help" rounded v-tooltip.top="'Dispensasi'" />
                        </div>
                    </template>
                </Column>

                <Column header="T" headerClass="justify-content-center" class="text-center" style="width: 70px">
                    <template #body="{ data }">
                        <div class="flex justify-content-center">
                            <Tag 
                                :value="data.total_terlambat" 
                                :severity="data.total_terlambat > 3 ? 'danger' : 'primary'" 
                                rounded 
                                v-tooltip.top="'Terlambat'" 
                            />
                        </div>
                    </template>
                </Column>

                <Column header="A" headerClass="justify-content-center" class="text-center" style="width: 70px">
                    <template #body="{ data }">
                        <div class="flex justify-content-center">
                            <Tag :value="data.total_alfa" severity="danger" rounded v-tooltip.top="'Tanpa Keterangan'" />
                        </div>
                    </template>
                </Column>

                <Column header="Total" headerClass="justify-content-center" class="text-center" style="width: 80px">
                    <template #body="{ data }">
                        <span class="font-bold text-900">{{ data.total_akumulasi }}</span>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref,computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Divider from 'primevue/divider';
import Tooltip from 'primevue/tooltip';
import SplitButton from 'primevue/splitbutton';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';

const tableFilters = ref({
    'student.full_name': { value: null },
    'student.current_classroom.name': { value: null },
});

const props = defineProps({
    statistics: Array,
    filters: Object
});
const classroomOptions = computed(() => {
    const classes = props.statistics.map(s => s.student?.current_classroom?.name).filter(Boolean);
    return [...new Set(classes)].sort();
});
// State untuk Filter Tanggal
const dateRange = ref(props.filters.start_date ? [new Date(props.filters.start_date), new Date(props.filters.end_date)] : null);

// Fungsi format YYYY-MM-DD
const formatDateISO = (date) => date ? new Date(date).toLocaleDateString('en-CA') : null;

const applyFilter = () => {
    router.get(route('permits.frequency'), {
        start_date: formatDateISO(dateRange.value?.[0]),
        end_date: formatDateISO(dateRange.value?.[1]),
    }, {
        preserveState: true,
        replace: true
    });
};

const resetFilter = () => {
    dateRange.value = null;
    router.get(route('permits.frequency'));
};
const exportItems = [
    {
        label: 'Excel (.xlsx)',
        icon: 'pi pi-file-excel',
        command: () => {
            const params = new URLSearchParams({
                start_date: formatDateISO(dateRange.value?.[0]) || '',
                end_date: formatDateISO(dateRange.value?.[1]) || ''
            }).toString();
            window.location.href = route('permits.export-excel') + '?' + params;
        }
    },
    {
        label: 'PDF (.pdf)',
        icon: 'pi pi-file-pdf',
        command: () => {
            const params = new URLSearchParams({
                start_date: formatDateISO(dateRange.value?.[0]) || '',
                end_date: formatDateISO(dateRange.value?.[1]) || ''
            }).toString();
            window.location.href = route('permits.export-pdf') + '?' + params;
        }
    }
];
</script>

<style scoped>
/* Tambahkan sedikit padding pada tag agar tidak terlalu mepet dengan angka */
:deep(.p-tag-value) {
    padding: 0 4px;
    min-width: 1.5rem;
    text-align: center;
}
</style>