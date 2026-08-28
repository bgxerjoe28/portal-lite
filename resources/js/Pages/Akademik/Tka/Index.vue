<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import ToggleSwitch from 'primevue/toggleswitch';

const props = defineProps({
    subjects: Object,
    filters: Object,
    is_registration_active: Boolean,
});

const toast = useToast();
const isDialogVisible = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    code: '',
    is_active: true,
});

const openDialog = (subject = null) => {
    if (subject) {
        editingId.value = subject.id;
        form.name = subject.name;
        form.code = subject.code;
        form.is_active = subject.is_active;
    } else {
        editingId.value = null;
        form.reset();
    }
    isDialogVisible.value = true;
};

const save = () => {
    if (editingId.value) {
        form.put(route('akademik.tka-subjects.update', editingId.value), {
            onSuccess: () => {
                isDialogVisible.value = false;
                toast.add({ severity: 'success', summary: 'Sukses', detail: 'Mapel TKA diperbarui', life: 3000 });
            },
        });
    } else {
        form.post(route('akademik.tka-subjects.store'), {
            onSuccess: () => {
                isDialogVisible.value = false;
                toast.add({ severity: 'success', summary: 'Sukses', detail: 'Mapel TKA ditambahkan', life: 3000 });
            },
        });
    }
};

const confirmDelete = (id) => {
    if (confirm('Yakin ingin menghapus mapel ini?')) {
        router.delete(route('akademik.tka-subjects.destroy', id), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Sukses', detail: 'Mapel TKA dihapus', life: 3000 });
            }
        });
    }
};

const registrationActive = ref(props.is_registration_active);

const toggleRegistration = () => {
    router.post(route('akademik.tka-subjects.toggle-registration'), {
        is_active: registrationActive.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Sukses', detail: 'Status pendaftaran TKA diperbarui', life: 3000 });
        }
    });
};
</script>

<template>
    <AppLayout title="Manajemen TKA">
        
        <!-- ===== CARD HEADER ===== -->
        <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
            <div class="flex justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Master Mapel TKA (Kelas XII)</h2>
                    <span class="text-600 text-sm">Kelola mata pelajaran pilihan TKA.</span>
                </div>

                <div class="flex gap-4 align-items-center">
                    <div class="flex align-items-center gap-2 bg-blue-50 p-2 border-round">
                        <ToggleSwitch v-model="registrationActive" @change="toggleRegistration" />
                        <span class="font-bold text-blue-900">Buka Pendaftaran Siswa</span>
                    </div>
                    <Button label="Rekap per Kelas" icon="pi pi-list" severity="info" outlined @click="router.get(route('akademik.tka-recap.index'))" />
                    <Button label="Tambah Mapel" icon="pi pi-plus" @click="openDialog()" />
                </div>
            </div>
        </div>

        <!-- ===== CARD TABLE ===== -->
        <div class="surface-card p-4 border-round-lg shadow-1">
            <DataTable v-bind="$pagination({ label: 'subjects' })" :value="subjects.data" stripedRows showGridlines tableStyle="min-width: 50rem" :totalRecords="subjects.total" :lazy="false">
                <template #empty>Belum ada data mapel TKA.</template>
                <Column field="code" header="Kode" style="width: 15%">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.code" severity="info" />
                    </template>
                </Column>
                <Column field="name" header="Nama Mata Pelajaran" sortable></Column>
                <Column field="students_count" header="Jml Siswa" style="width: 15%"></Column>
                <Column field="is_active" header="Status" style="width: 15%">
                    <template #body="slotProps">
                        <Tag :severity="slotProps.data.is_active ? 'success' : 'danger'" :value="slotProps.data.is_active ? 'Aktif' : 'Nonaktif'"></Tag>
                    </template>
                </Column>
                <Column header="Aksi" :exportable="false" style="width: 20%">
                    <template #body="slotProps">
                        <div class="flex gap-2">
                            <Button icon="pi pi-users" severity="info" text rounded @click="router.get(route('akademik.tka-subjects.students.index', slotProps.data.id))" v-tooltip.top="'Kelola Siswa'" />
                            <Button icon="pi pi-calendar" severity="warning" text rounded @click="router.get(route('akademik.tka-subjects.sessions.index', slotProps.data.id))" v-tooltip.top="'Kelola Sesi & Presensi'" />
                            <Button icon="pi pi-pencil" severity="success" text rounded @click="openDialog(slotProps.data)" />
                            <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmDelete(slotProps.data.id)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="isDialogVisible" :header="editingId ? 'Edit Mapel TKA' : 'Tambah Mapel TKA'" :modal="true" :style="{ width: '450px' }">
            <form @submit.prevent="save">
                <div class="field mb-3">
                    <label class="font-bold">Kode Mapel</label>
                    <InputText v-model="form.code" required autofocus class="w-full" />
                </div>
                <div class="field mb-4">
                    <label class="font-bold">Nama Mata Pelajaran</label>
                    <InputText v-model="form.name" required class="w-full" />
                </div>
                <div class="field-checkbox mb-3">
                    <Checkbox id="is_active" v-model="form.is_active" :binary="true" />
                    <label for="is_active" class="ml-2">Aktif</label>
                </div>
                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" severity="secondary" text @click="isDialogVisible = false" />
                    <Button :label="editingId ? 'Simpan' : 'Tambah'" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
