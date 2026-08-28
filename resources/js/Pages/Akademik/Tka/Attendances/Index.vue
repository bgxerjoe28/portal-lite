<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode } from '@primevue/core/api';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    session: Object,
    attendances: Array,
});

const toast = useToast();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// Create form with attendances array
const form = useForm({
    attendances: props.attendances.map(a => ({
        student_id: a.student_id,
        status: a.status || 'hadir', // default to hadir if empty
        notes: a.notes || '',
    }))
});

const save = () => {
    form.post(route('akademik.tka-sessions.attendances.store', props.session.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Sukses', detail: 'Data presensi berhasil disimpan', life: 3000 });
        },
    });
};

const setAllStatus = (status) => {
    form.attendances.forEach(a => {
        a.status = status;
    });
};
</script>

<template>
    <AppLayout :title="`Presensi - ${session.title}`">
        <div class="card">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('akademik.tka-subjects.sessions.index', session.tka_subject_id)">
                        <Button icon="pi pi-arrow-left" class="p-button-rounded p-button-text" />
                    </Link>
                    <div>
                        <h2 class="text-xl font-bold">Presensi: {{ session.title }}</h2>
                        <p class="text-gray-500 text-sm">{{ session.date }} | {{ session.tka_subject?.name }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button label="Hadir Semua" class="p-button-outlined p-button-success" @click="setAllStatus('hadir')" />
                    <Button label="Simpan Presensi" icon="pi pi-save" @click="save" :loading="form.processing" />
                </div>
            </div>

            <DataTable :value="form.attendances" responsiveLayout="scroll" v-model:filters="filters" dataKey="student_id" :globalFilterFields="['student.full_name', 'student.name', 'student.nisn', 'student.classroom.name']">
                <template #header>
                    <div class="flex justify-end">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters['global'].value" placeholder="Cari siswa..." />
                        </IconField>
                    </div>
                </template>
                
                <Column header="Siswa">
                    <template #body="slotProps">
                        <!-- Find original student data since form only has IDs -->
                        <div class="font-semibold">{{ attendances.find(a => a.student_id === slotProps.data.student_id)?.student?.full_name || attendances.find(a => a.student_id === slotProps.data.student_id)?.student?.name }}</div>
                        <div class="text-sm text-gray-500">
                            {{ attendances.find(a => a.student_id === slotProps.data.student_id)?.student?.nisn }} - 
                            {{ attendances.find(a => a.student_id === slotProps.data.student_id)?.student?.classroom?.name }}
                        </div>
                    </template>
                </Column>
                
                <Column header="Status Presensi">
                    <template #body="slotProps">
                        <div class="flex gap-4">
                            <div class="flex items-center">
                                <RadioButton v-model="slotProps.data.status" inputId="hadir" value="hadir" />
                                <label for="hadir" class="ml-2 text-green-600 font-bold">Hadir</label>
                            </div>
                            <div class="flex items-center">
                                <RadioButton v-model="slotProps.data.status" inputId="izin" value="izin" />
                                <label for="izin" class="ml-2 text-blue-600 font-bold">Izin</label>
                            </div>
                            <div class="flex items-center">
                                <RadioButton v-model="slotProps.data.status" inputId="sakit" value="sakit" />
                                <label for="sakit" class="ml-2 text-yellow-600 font-bold">Sakit</label>
                            </div>
                            <div class="flex items-center">
                                <RadioButton v-model="slotProps.data.status" inputId="alpa" value="alpa" />
                                <label for="alpa" class="ml-2 text-red-600 font-bold">Alpa</label>
                            </div>
                        </div>
                    </template>
                </Column>
                
                <Column header="Keterangan" style="min-width: 200px">
                    <template #body="slotProps">
                        <InputText v-model="slotProps.data.notes" class="w-full" placeholder="Catatan tambahan (opsional)" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
