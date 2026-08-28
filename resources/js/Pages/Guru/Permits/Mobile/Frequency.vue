<template>
    <MobileLayout title="Rekap Frekuensi Izin">
        <div class="p-2 md:p-4">
            <div class="flex flex-column md:flex-row justify-content-between align-items-center gap-3 mb-4">
                <div class="flex align-items-center gap-3 w-full md:w-auto">
                    <div class="bg-blue-50 p-2 border-round">
                        <i class="pi pi-chart-bar text-blue-600 text-xl md:text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl md:text-2xl font-bold m-0 text-900">Rekap Kedisiplinan</h2>
                        <p class="text-500 text-xs md:text-sm m-0">Analisis frekuensi ketidakhadiran</p>
                    </div>
                </div>

                <div class="flex gap-1 w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
                    <Link :href="route('permits.index')">
                        <Button label="Harian" icon="pi pi-calendar" severity="secondary" text size="small" />
                    </Link>
                    <Button label="Sampah" icon="pi pi-trash" severity="danger" text size="small" @click="router.get(route('permits.trash'))" />
                </div>
            </div>

            <div class="surface-card p-3 md:p-4 shadow-2 border-round-xl mb-4 border-left-4 border-blue-500 bg-blue-50">
                <div class="grid p-fluid align-items-end">
                    <div class="col-12 md:col-4 lg:col-3">
                        <label class="block mb-2 font-semibold text-700">Tahun Ajaran</label>
                        <MultiSelect 
                            v-model="selectedAcademicYears" 
                            :options="academicYears" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Pilih Tahun Ajaran" 
                            display="chip"
                            class="w-full"
                        />
                    </div>
                    <div class="col-12 md:col-4 lg:col-3">
                        <label class="block mb-2 font-semibold text-700">Rentang Waktu</label>
                        <DatePicker 
                            v-model="dateRange" 
                            selectionMode="range" 
                            placeholder="Mulai - Selesai" 
                            showIcon 
                            class="w-full"
                        />
                    </div>
                    <div class="col-12 md:col-4 lg:col-6 flex gap-2">
                        <Button label="Filter" icon="pi pi-filter" @click="applyFilter" severity="info" class="flex-1" />
                        <Button icon="pi pi-refresh" @click="resetFilter" severity="secondary" outlined />
                        <SplitButton 
                            label="Export" 
                            icon="pi pi-download" 
                            :model="exportItems" 
                            severity="success" 
                            outlined 
                            class="flex-1"
                        />
                    </div>
                </div>
            </div>

            <div class="hidden md:block">
                <DataTable v-bind="$pagination({ label: 'statistics' })" :value="statistics" stripedRows size="small" class="shadow-1 border-round-lg overflow-hidden">
                    <Column header="No." style="width: 50px" class="text-center">
                        <template #body="{ index }">{{ index + 1 }}</template>
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
                    <Column header="S" class="text-center"><template #body="{ data }"><Tag :value="data.total_sakit" severity="info" rounded /></template></Column>
                    <Column header="I" class="text-center"><template #body="{ data }"><Tag :value="data.total_izin" severity="warn" rounded /></template></Column>
                    <Column header="D" class="text-center"><template #body="{ data }"><Tag :value="data.total_dispen" severity="help" rounded /></template></Column>
                    <Column header="T" class="text-center">
                        <template #body="{ data }">
                            <Tag :value="data.total_terlambat" :severity="data.total_terlambat > 3 ? 'danger' : 'primary'" rounded />
                        </template>
                    </Column>
                    <Column header="A" class="text-center"><template #body="{ data }"><Tag :value="data.total_alfa" severity="danger" rounded /></template></Column>
                    <Column header="Total" class="text-center font-bold">
                        <template #body="{ data }">{{ data.total_akumulasi }}</template>
                    </Column>
                </DataTable>
            </div>

            <div class="md:hidden flex flex-column gap-3">
                <div v-for="(data, index) in statistics" :key="data.student.id" class="surface-card p-3 shadow-1 border-round-xl border border-200">
                    <div class="flex justify-content-between align-items-center mb-3">
                        <div class="flex align-items-center gap-2">
                            <span class="bg-gray-200 text-gray-700 font-bold border-round-circle w-2rem h-2rem flex align-items-center justify-content-center text-xs">
                                {{ index + 1 }}
                            </span>
                            <div class="flex flex-column">
                                <Link :href="route('permits.index', { student_id: data.student.id })" class="font-bold text-blue-600 hover:underline cursor-pointer text-sm">
                                    {{ data.student.full_name }}
                                </Link>
                                <small class="text-500">Kelas: {{ data.student?.current_classroom?.name || '-' }}</small>
                            </div>
                        </div>
                        <div class="bg-blue-50 text-blue-700 px-2 py-1 border-round font-bold text-sm">
                            Tot: {{ data.total_akumulasi }}
                        </div>
                    </div>

                    <div class="grid grid-nogutter text-center border-top-1 border-100 pt-3">
                        <div class="col-4 flex flex-column gap-1 border-right-1 border-100">
                            <span class="text-xs text-500 font-semibold uppercase">Sakit</span>
                            <span class="text-info font-bold">{{ data.total_sakit }}</span>
                        </div>
                        <div class="col-4 flex flex-column gap-1 border-right-1 border-100">
                            <span class="text-xs text-500 font-semibold uppercase">Izin</span>
                            <span class="text-warning-600 font-bold" style="color: #d97706">{{ data.total_izin }}</span>
                        </div>
                        <div class="col-4 flex flex-column gap-1">
                            <span class="text-xs text-500 font-semibold uppercase">Dispen</span>
                            <span class="text-purple-600 font-bold">{{ data.total_dispen }}</span>
                        </div>
                        <div class="col-12 mt-2 mb-2 border-top-1 border-100"></div>
                        <div class="col-6 flex flex-column gap-1 border-right-1 border-100">
                            <span class="text-xs text-500 font-semibold uppercase">Terlambat</span>
                            <span :class="data.total_terlambat > 3 ? 'text-red-600' : 'text-primary'" class="font-bold">{{ data.total_terlambat }}</span>
                        </div>
                        <div class="col-6 flex flex-column gap-1">
                            <span class="text-xs text-500 font-semibold uppercase">Alfa</span>
                            <span class="text-red-600 font-bold">{{ data.total_alfa }}</span>
                        </div>
                    </div>
                </div>
                <div v-if="statistics.length === 0" class="text-center p-5 text-500">
                    Tidak ada data rekap untuk periode ini.
                </div>
            </div>
        </div>
    </MobileLayout>
</template>

<style scoped>
/* Menghilangkan scrollbar pada navigasi mobile */
.overflow-x-auto {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.overflow-x-auto::-webkit-scrollbar {
    display: none;
}
</style>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import MultiSelect from 'primevue/multiselect';
import Divider from 'primevue/divider';
import Tooltip from 'primevue/tooltip';
import SplitButton from 'primevue/splitbutton';

const props = defineProps({
    statistics: Array,
    academicYears: Array,
    filters: Object
});

// Parse existing academic_year_ids filter, ensuring it's an array of numbers
const initAcademicYears = () => {
    if (!props.filters.academic_year_ids) return [];
    if (Array.isArray(props.filters.academic_year_ids)) return props.filters.academic_year_ids.map(id => Number(id));
    if (typeof props.filters.academic_year_ids === 'string') {
        return props.filters.academic_year_ids.split(',').map(id => Number(id));
    }
    return [];
};

const selectedAcademicYears = ref(initAcademicYears());
const dateRange = ref(props.filters.start_date ? [new Date(props.filters.start_date), new Date(props.filters.end_date)] : null);

// Fungsi format YYYY-MM-DD
const formatDateISO = (date) => date ? new Date(date).toLocaleDateString('en-CA') : null;

const applyFilter = () => {
    router.get(route('permits.frequency'), {
        start_date: formatDateISO(dateRange.value?.[0]),
        end_date: formatDateISO(dateRange.value?.[1]),
        academic_year_ids: selectedAcademicYears.value.join(',')
    }, {
        preserveState: true,
        replace: true
    });
};

const resetFilter = () => {
    dateRange.value = null;
    selectedAcademicYears.value = [];
    router.get(route('permits.frequency'));
};
const exportItems = [
    {
        label: 'Excel (.xlsx)',
        icon: 'pi pi-file-excel',
        command: () => {
            const params = new URLSearchParams({
                start_date: formatDateISO(dateRange.value?.[0]) || '',
                end_date: formatDateISO(dateRange.value?.[1]) || '',
                academic_year_ids: selectedAcademicYears.value.join(',') || ''
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
                end_date: formatDateISO(dateRange.value?.[1]) || '',
                academic_year_ids: selectedAcademicYears.value.join(',') || ''
            }).toString();
            window.location.href = route('permits.export-pdf') + '?' + params;
        }
    }
];
</script>