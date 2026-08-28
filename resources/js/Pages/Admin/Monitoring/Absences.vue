<template>
    <AppLayout title="Monitoring Absensi Siswa">
        <div class="card p-4">
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center mb-4 gap-3">
                <h1 class="text-2xl font-bold m-0">Monitoring Absensi Siswa</h1>
                <div class="flex gap-2">
                    <Button label="Harian" icon="pi pi-calendar-times" class="p-button-primary" />
                    <Button label="Mingguan" icon="pi pi-calendar" class="p-button-outlined p-button-secondary" @click="goToWeekly" />
                </div>
            </div>
            
            <p class="text-gray-600 mb-6">Pilih kelas dan tanggal untuk melihat detail siswa yang Tanpa Keterangan (Sistem) atau Tidak Hadir Mata Pelajaran tertentu.</p>

            <div class="flex flex-wrap gap-4 mb-6">
                <Select v-model="form.academic_year_id" :options="academicYears" optionLabel="name" optionValue="id" placeholder="Tahun Ajaran" class="w-full md:w-12rem" />
                <Select v-model="form.classroom_id" :options="classrooms" optionLabel="name" optionValue="id" placeholder="Pilih Kelas" class="w-full md:w-15rem" />
                <DatePicker v-model="form.date" dateFormat="yy-mm-dd" placeholder="Pilih Tanggal" class="w-full md:w-15rem" />
                <Button label="Tampilkan Data" icon="pi pi-search" @click="fetchData" />
            </div>

            <div v-if="filters.classroom_id">
                <DataTable v-bind="$pagination({ label: 'students' })" :value="students" stripedRows responsiveLayout="scroll" class="p-datatable-sm">
                    <Column field="nis" header="NIS"></Column>
                    <Column field="name" header="Nama Siswa">
                        <template #body="slotProps">
                            <span :class="{'font-bold text-red-600': slotProps.data.is_alert}">{{ slotProps.data.name }}</span>
                        </template>
                    </Column>
                    <Column header="Presensi Sekolah (Sistem)">
                        <template #body="slotProps">
                            <Tag :severity="getSystemSeverity(slotProps.data.system_status)" :value="slotProps.data.system_status" />
                            <span v-if="slotProps.data.system_note" class="text-xs text-500 block mt-1">{{ slotProps.data.system_note }}</span>
                        </template>
                    </Column>
                    <Column header="Tidak Hadir (TH)">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.missing_subjects && slotProps.data.missing_subjects.length > 0">
                                <Tag severity="danger" v-for="(subj, idx) in slotProps.data.missing_subjects" :key="idx" :value="subj" class="mr-1 mb-1" />
                            </div>
                            <span v-else class="text-500">-</span>
                        </template>
                    </Column>
                    <Column header="Status Mapel Lain">
                        <template #body="slotProps">
                            <ul v-if="slotProps.data.other_notes && slotProps.data.other_notes.length > 0" class="pl-3 m-0 text-sm">
                                <li v-for="(note, idx) in slotProps.data.other_notes" :key="idx">{{ note }}</li>
                            </ul>
                            <span v-else class="text-500">-</span>
                        </template>
                    </Column>
                </DataTable>
            </div>
            <div v-else class="text-center p-5 text-gray-500">
                Silakan pilih kelas dan klik "Tampilkan Data" untuk memonitor absensi.
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

const props = defineProps({
    academicYears: Array,
    classrooms: Array,
    students: Array,
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

const goToWeekly = () => {
    const current = route().current();
    if (!current.endsWith('.weekly')) {
        router.get(route(current + '.weekly'));
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
