<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

const props = defineProps({
    subject: Object,
    sessions: Object,
});

const toast = useToast();
const isDialogVisible = ref(false);
const editingId = ref(null);

const form = useForm({
    title: '',
    date: '',
    start_time: '',
    end_time: '',
    description: '',
});

const openDialog = (session = null) => {
    if (session) {
        editingId.value = session.id;
        form.title = session.title;
        form.date = session.date;
        form.start_time = session.start_time ? session.start_time.substring(0, 5) : '';
        form.end_time = session.end_time ? session.end_time.substring(0, 5) : '';
        form.description = session.description || '';
    } else {
        editingId.value = null;
        form.reset();
    }
    isDialogVisible.value = true;
};

const save = () => {
    if (editingId.value) {
        form.put(route('akademik.tka-sessions.update', editingId.value), {
            onSuccess: () => {
                isDialogVisible.value = false;
                toast.add({ severity: 'success', summary: 'Sukses', detail: 'Sesi diperbarui', life: 3000 });
            },
        });
    } else {
        form.post(route('akademik.tka-subjects.sessions.store', props.subject.id), {
            onSuccess: () => {
                isDialogVisible.value = false;
                toast.add({ severity: 'success', summary: 'Sukses', detail: 'Sesi ditambahkan', life: 3000 });
            },
        });
    }
};

const confirmDelete = (id) => {
    if (confirm('Yakin ingin menghapus sesi ini?')) {
        router.delete(route('akademik.tka-sessions.destroy', id), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Sukses', detail: 'Sesi dihapus', life: 3000 });
            }
        });
    }
};
</script>

<template>
    <AppLayout title="Sesi TKA">
        
        <!-- ===== CARD HEADER ===== -->
        <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
            <div class="flex justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Sesi TKA: {{ subject.name }}</h2>
                    <span class="text-600 text-sm">Kelola jadwal sesi dan absensi mapel TKA.</span>
                </div>

                <div class="flex gap-2 align-items-center">
                    <Button label="Kembali" icon="pi pi-arrow-left" severity="secondary" outlined @click="router.get(route('akademik.tka-subjects.index'))" />
                    <Button label="Tambah Sesi" icon="pi pi-plus" @click="openDialog()" />
                </div>
            </div>
        </div>

        <!-- ===== CARD TABLE ===== -->
        <div class="surface-card p-4 border-round-lg shadow-1">
            <DataTable v-bind="$pagination({ label: 'sessions' })" :value="sessions.data" stripedRows showGridlines tableStyle="min-width: 50rem" :totalRecords="sessions.total" :lazy="false">
                <template #empty>Belum ada data sesi TKA.</template>
                <Column field="date" header="Tanggal">
                    <template #body="slotProps">
                        {{ new Date(slotProps.data.date).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </template>
                </Column>
                <Column field="title" header="Judul Sesi"></Column>
                <Column field="start_time" header="Waktu">
                    <template #body="slotProps">
                        {{ slotProps.data.start_time?.substring(0,5) }} - {{ slotProps.data.end_time?.substring(0,5) || 'Selesai' }}
                    </template>
                </Column>
                <Column field="attendances_count" header="Jml Hadir/Presensi"></Column>
                <Column header="Aksi" :exportable="false" style="width: 15%">
                    <template #body="slotProps">
                        <div class="flex gap-2">
                            <Button icon="pi pi-check-square" severity="info" text rounded @click="router.get(route('akademik.tka-sessions.attendances.index', slotProps.data.id))" v-tooltip.top="'Isi Presensi'" />
                            <Button icon="pi pi-pencil" severity="success" text rounded @click="openDialog(slotProps.data)" />
                            <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmDelete(slotProps.data.id)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="isDialogVisible" :header="editingId ? 'Edit Sesi' : 'Tambah Sesi'" :modal="true" :style="{ width: '450px' }">
            <form @submit.prevent="save">
                <div class="field mb-3">
                    <label class="font-bold">Judul Sesi</label>
                    <InputText v-model="form.title" required autofocus class="w-full" placeholder="Contoh: Pertemuan 1 - Pengenalan" />
                </div>
                <div class="field mb-3">
                    <label class="font-bold">Tanggal</label>
                    <InputText type="date" v-model="form.date" required class="w-full" />
                </div>
                <div class="flex gap-2 mb-3">
                    <div class="field flex-1">
                        <label class="font-bold">Jam Mulai</label>
                        <InputText type="time" v-model="form.start_time" required class="w-full" />
                    </div>
                    <div class="field flex-1">
                        <label class="font-bold">Jam Selesai</label>
                        <InputText type="time" v-model="form.end_time" class="w-full" />
                    </div>
                </div>
                <div class="field mb-4">
                    <label class="font-bold">Keterangan / Materi</label>
                    <Textarea v-model="form.description" rows="3" class="w-full" />
                </div>
                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" severity="secondary" text @click="isDialogVisible = false" />
                    <Button :label="editingId ? 'Simpan' : 'Tambah'" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
