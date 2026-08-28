<template>
    <AppLayout title="Daftar Ruang Ujian">
        <div class="card">
            <div class="flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Daftar Ruang Ujian</h2>
                    <span class="text-500 block mt-1">Kelola data ruangan untuk ujian CBT dan penempatan tempat duduk siswa</span>
                </div>
                
                <div class="flex gap-2">
                    <Button label="Tambah Ruangan" icon="pi pi-plus" severity="primary" @click="openCreateModal" />
                </div>
            </div>

            <div class="surface-card p-4 shadow-2 border-round">
                <DataTable :value="rooms.data" stripedRows tableStyle="min-width: 50rem">
                    <template #empty> Belum ada ruang ujian dibuat. </template>

                    <Column field="name" header="Nama Ruangan" sortable>
                        <template #body="{ data }">
                            <span class="font-bold text-lg text-900">{{ data.name }}</span>
                        </template>
                    </Column>

                    <Column field="capacity" header="Kapasitas Kursi" sortable style="width: 20%">
                        <template #body="{ data }">
                            <span class="font-semibold text-700">{{ data.capacity }} Kursi</span>
                        </template>
                    </Column>

                    <Column field="students_count" header="Siswa Terdaftar" style="width: 20%">
                        <template #body="{ data }">
                            <span class="bg-blue-50 text-blue-700 font-semibold px-2 py-1 border-round text-xs">
                                {{ data.students_count }} / 36 Siswa
                            </span>
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 25%">
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Link :href="route('cbt.rooms.seating', data.id)">
                                    <Button icon="pi pi-th-large" label="Atur Kursi" severity="info" text raised size="small" />
                                </Link>
                                <Button icon="pi pi-pencil" severity="warning" text rounded v-tooltip.top="'Edit'" @click="openEditModal(data)" />
                                <Button icon="pi pi-trash" severity="danger" text rounded v-tooltip.top="'Hapus'" @click="confirmDelete(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
                <Pagination :links="rooms.links" class="mt-4" />
            </div>
        </div>

        <!-- Create / Edit Dialog -->
        <Dialog v-model:visible="displayModal" :header="isEditing ? 'Edit Ruang Ujian' : 'Tambah Ruang Ujian'" :modal="true" :style="{ width: '450px' }">
            <form @submit.prevent="submitForm" class="p-fluid">
                <div class="field mb-3">
                    <label for="name" class="font-medium">Nama Ruangan <span class="text-red-500">*</span></label>
                    <InputText id="name" v-model="form.name" class="w-full" :class="{'p-invalid': form.errors.name}" placeholder="Contoh: Lab Komputer 1, Ruang Kelas 10-A" />
                    <small class="p-error" v-if="form.errors.name">{{ form.errors.name }}</small>
                </div>

                <div class="field mb-4">
                    <label for="capacity" class="font-medium">Kapasitas Kursi <span class="text-red-500">*</span></label>
                    <InputNumber id="capacity" v-model="form.capacity" class="w-full" :min="1" :max="100" :useGrouping="false" :class="{'p-invalid': form.errors.capacity}" placeholder="Default: 36" />
                    <small class="p-error" v-if="form.errors.capacity">{{ form.errors.capacity }}</small>
                </div>

                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" severity="secondary" text @click="displayModal = false" />
                    <Button :label="isEditing ? 'Simpan' : 'Buat'" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';

defineProps({
    rooms: Object,
});

const confirm = useConfirm();
const toast = useToast();
const displayModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);

const form = useForm({
    name: '',
    capacity: 36,
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.capacity = 36;
    displayModal.value = true;
};

const openEditModal = (data) => {
    isEditing.value = true;
    editId.value = data.id;
    form.name = data.name;
    form.capacity = data.capacity;
    displayModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('cbt.rooms.update', editId.value), {
            onSuccess: () => {
                displayModal.value = false;
                form.reset();
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data ruangan berhasil diperbarui', life: 3000 });
            }
        });
    } else {
        form.post(route('cbt.rooms.store'), {
            onSuccess: () => {
                displayModal.value = false;
                form.reset();
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data ruangan berhasil dibuat', life: 3000 });
            }
        });
    }
};

const confirmDelete = (data) => {
    confirm.require({
        message: `Hapus ruang ujian <b>${data.name}</b>? Semua data tempat duduk siswa di ruangan ini akan ikut terhapus.`,
        header: 'Konfirmasi Hapus Ruangan',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('cbt.rooms.destroy', data.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data ruangan berhasil dihapus', life: 3000 });
                }
            });
        }
    });
};
</script>
