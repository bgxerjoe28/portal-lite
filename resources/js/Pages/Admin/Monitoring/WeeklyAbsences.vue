<template>
    <AppLayout title="Rekap Absensi Mingguan">
        <div class="card p-4">
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center mb-4 gap-3">
                <h1 class="text-2xl font-bold m-0">Rekap Absensi Mingguan</h1>
                <div class="flex gap-2">
                    <Button label="Harian" icon="pi pi-calendar-times" class="p-button-outlined p-button-secondary" @click="goToDaily" />
                    <Button label="Mingguan" icon="pi pi-calendar" class="p-button-primary" />
                </div>
            </div>
            
            <p class="text-gray-600 mb-6">Pilih kelas dan rentang waktu minggu untuk melihat rekap presensi seluruh siswa di kelas tersebut selama satu minggu.</p>

            <div class="flex flex-wrap gap-4 mb-6">
                <Select v-model="form.academic_year_id" :options="academicYears" optionLabel="name" optionValue="id" placeholder="Tahun Ajaran" class="w-full md:w-12rem" />
                <Select v-model="form.classroom_id" :options="classrooms" optionLabel="name" optionValue="id" placeholder="Pilih Kelas" class="w-full md:w-15rem" />
                <DatePicker v-model="form.date" dateFormat="yy-mm-dd" placeholder="Pilih Tanggal (Otomatis 1 Minggu)" class="w-full md:w-20rem" />
                <Button label="Tampilkan Data" icon="pi pi-search" @click="fetchData" />
            </div>

            <div v-if="filters.classroom_id">
                <DataTable v-bind="$pagination({ label: 'students' })" :value="students" stripedRows responsiveLayout="scroll" class="p-datatable-sm">
                    
                    <Column field="nis" header="NIS" frozen style="min-width: 100px; background-color: white;"></Column>
                    <Column field="name" header="Nama Siswa" frozen style="min-width: 200px; background-color: white; font-weight: bold;"></Column>
                    
                    <Column v-for="(day, index) in days" :key="index" :header="day.day_name + ' (' + day.date + ')'">
                        <template #body="slotProps">
                            <Tag :severity="getSystemSeverity(slotProps.data.attendances[day.date])" :value="slotProps.data.attendances[day.date]" />
                        </template>
                    </Column>
                </DataTable>
            </div>
            <div v-else class="text-center p-5 text-gray-500">
                Silakan pilih kelas dan klik "Tampilkan Data" untuk memonitor rekap mingguan.
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    academicYears: Array,
    classrooms: Array,
    students: Array,
    days: Array,
    filters: Object,
    activeYear: Object
});

const form = ref({
    academic_year_id: props.filters.academic_year_id || props.activeYear?.id,
    classroom_id: props.filters.classroom_id,
    date: props.filters.date ? dayjs(props.filters.date).toDate() : new Date()
});

const fetchData = () => {
    router.get(route(route().current()), {
        academic_year_id: form.value.academic_year_id,
        classroom_id: form.value.classroom_id,
        date: dayjs(form.value.date).format('YYYY-MM-DD')
    }, { preserveState: true });
};

const goToDaily = () => {
    const current = route().current();
    if (current.endsWith('.weekly')) {
        router.get(route(current.replace('.weekly', '')));
    }
};

const getSystemSeverity = (status) => {
    switch(status) {
        case 'H': return 'success';
        case 'A': return 'danger';
        case 'S': return 'warning';
        case 'I': return 'info';
        case 'T': return 'warning';
        case 'D': return 'success';
        default: return 'secondary';
    }
};
</script>

<style scoped>
:deep(.p-datatable-frozen-tbody) {
    font-weight: bold;
}
</style>
