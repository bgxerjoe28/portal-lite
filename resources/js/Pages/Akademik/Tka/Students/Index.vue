<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode } from '@primevue/core/api';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    subject: Object,
    students: Array,
});

const toast = useToast();
const isDialogVisible = ref(false);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const form = useForm({
    student_id: null,
});

// A dummy list of students for selection should ideally come from backend. 
// For now, we will assume there is an endpoint or we just use a generic select.
// But since we didn't pass all class XII students from backend, we will just use a text input for student_id for simplicity in this initial version, or assume a select.
// To make it functional without a full student list API, I'll put a basic input for ID, but in real app it would be an autocomplete.

const save = () => {
    form.post(route('akademik.tka-subjects.students.store', props.subject.id), {
        onSuccess: () => {
            isDialogVisible.value = false;
            toast.add({ severity: 'success', summary: 'Sukses', detail: 'Siswa berhasil didaftarkan', life: 3000 });
            form.reset();
        },
    });
};

const confirmDelete = (studentId) => {
    if (confirm('Yakin ingin menghapus siswa ini dari mapel?')) {
        router.delete(route('akademik.tka-subjects.students.destroy', [props.subject.id, studentId]), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Sukses', detail: 'Siswa dihapus', life: 3000 });
            }
        });
    }
};
</script>

<template>
    <AppLayout title="Siswa TKA">
        
        <!-- ===== CARD HEADER ===== -->
        <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
            <div class="flex justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Siswa TKA: {{ subject.name }}</h2>
                    <span class="text-600 text-sm">Kelola daftar siswa yang mengambil mata pelajaran ini.</span>
                </div>

                <div class="flex gap-2 align-items-center">
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="filters['global'].value" placeholder="Cari siswa..." />
                    </IconField>
                    <Button label="Rekap per Kelas" icon="pi pi-list" severity="info" outlined @click="router.get(route('akademik.tka-recap.index'))" />
                    <Button label="Kembali" icon="pi pi-arrow-left" severity="secondary" outlined @click="router.get(route('akademik.tka-subjects.index'))" />
                    <Button label="Daftarkan Siswa" icon="pi pi-plus" @click="isDialogVisible = true" />
                </div>
            </div>
        </div>

        <!-- ===== CARD TABLE ===== -->
        <div class="surface-card p-4 border-round-lg shadow-1">
            <DataTable v-bind="$pagination({ label: 'students' })" :value="students" stripedRows showGridlines tableStyle="min-width: 50rem" v-model:filters="filters" dataKey="id" :globalFilterFields="['student.full_name', 'student.name', 'student.nisn', 'student.classroom.name']">
                <template #empty>Belum ada siswa terdaftar.</template>
                <Column field="student.nisn" header="NISN" style="width: 15%"></Column>
                <Column field="student.full_name" header="Nama Siswa" sortable>
                    <template #body="slotProps">
                        {{ slotProps.data.student?.full_name || slotProps.data.student?.name }}
                    </template>
                </Column>
                <Column field="student.classroom.name" header="Kelas" style="width: 15%" sortable></Column>
                <Column field="status" header="Status" style="width: 15%">
                    <template #body="slotProps">
                        <Tag severity="success" value="Aktif"></Tag>
                    </template>
                </Column>
                <Column header="Aksi" :exportable="false" style="width: 15%">
                    <template #body="slotProps">
                        <div class="flex gap-2">
                            <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmDelete(slotProps.data.id)" v-tooltip.top="'Keluarkan Siswa'" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="isDialogVisible" header="Daftarkan Siswa (Kelas XII)" :modal="true" :style="{ width: '450px' }">
            <form @submit.prevent="save">
                <div class="field mb-4">
                    <label class="font-bold">ID Siswa (Contoh)</label>
                    <InputText v-model="form.student_id" required autofocus class="w-full" placeholder="Masukkan ID Siswa" />
                    <small class="text-gray-500 mt-1 block">Gunakan ID Siswa yang valid dari database (hanya kelas XII).</small>
                </div>
                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" severity="secondary" text @click="isDialogVisible = false" />
                    <Button label="Simpan" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
