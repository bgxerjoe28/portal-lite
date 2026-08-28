<template>
    <AppLayout title="Manajemen Sesi Ujian">
        <div class="card">
            <div class="flex justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Sesi Ujian</h2>
                    <span class="text-500 block mt-1">Kelola sesi ujian (contoh: Sesi 1, Sesi 2)</span>
                </div>
                
                <div class="flex gap-2 align-items-center">
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="search" placeholder="Cari Sesi..." />
                    </IconField>
                    <Button label="Tambah Sesi" icon="pi pi-plus" severity="primary" @click="openCreateModal" />
                </div>
            </div>

            <div class="surface-card p-4 shadow-2 border-round">
                <DataTable :value="sessions.data" :rows="10" stripedRows tableStyle="min-width: 50rem">
                    <template #empty> Belum ada data sesi ujian. </template>

                    <Column field="name" header="Nama Sesi" sortable>
                        <template #body="{ data }">
                            <span class="font-bold text-lg text-900">{{ data.name }}</span>
                        </template>
                    </Column>

                    <Column header="Waktu Sesi">
                        <template #body="{ data }">
                            <span v-if="data.start_time && data.end_time" class="text-600">
                                <i class="pi pi-clock mr-1 text-sm"></i> {{ data.start_time }} - {{ data.end_time }}
                            </span>
                            <span v-else class="text-500 italic">Belum ditentukan</span>
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 15%">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button icon="pi pi-pencil" severity="warning" text rounded v-tooltip.top="'Edit'" @click="openEditModal(data)" />
                                <Button icon="pi pi-trash" severity="danger" text rounded v-tooltip.top="'Hapus'" @click="confirmDelete(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
                <Pagination :links="sessions.links" class="mt-4" />
            </div>
        </div>

        <Dialog v-model:visible="displayModal" :header="isEditing ? 'Edit Sesi' : 'Tambah Sesi'" :modal="true" :style="{ width: '400px' }">
            <form @submit.prevent="submitForm" class="p-fluid">
                <div class="field mb-3">
                    <label for="name" class="font-medium">Nama Sesi <span class="text-red-500">*</span></label>
                    <InputText id="name" v-model="form.name" class="w-full" :class="{'p-invalid': form.errors.name}" placeholder="Contoh: Sesi 1" />
                    <small class="p-error" v-if="form.errors.name">{{ form.errors.name }}</small>
                </div>
                
                <div class="formgrid grid">
                    <div class="field col-6 mb-3">
                        <label for="start_time" class="font-medium">Waktu Mulai</label>
                        <input type="time" id="start_time" v-model="form.start_time" class="p-inputtext w-full" :class="{'p-invalid': form.errors.start_time}" />
                        <small class="p-error" v-if="form.errors.start_time">{{ form.errors.start_time }}</small>
                    </div>
                    <div class="field col-6 mb-3">
                        <label for="end_time" class="font-medium">Waktu Selesai</label>
                        <input type="time" id="end_time" v-model="form.end_time" class="p-inputtext w-full" :class="{'p-invalid': form.errors.end_time}" />
                        <small class="p-error" v-if="form.errors.end_time">{{ form.errors.end_time }}</small>
                    </div>
                </div>

                <div class="flex justify-content-end gap-2 mt-4">
                    <Button label="Batal" severity="secondary" text @click="displayModal = false" />
                    <Button :label="isEditing ? 'Simpan' : 'Tambah'" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';

import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    sessions: Object,
    filters: Object,
});

const confirm = useConfirm();
const displayModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const search = ref(props.filters?.search || '');

const form = useForm({
    name: '',
    start_time: '',
    end_time: '',
});

let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('cbt.sessions.index'), { search: val }, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    displayModal.value = true;
};

const openEditModal = (data) => {
    isEditing.value = true;
    editId.value = data.id;
    form.name = data.name;
    form.start_time = data.start_time ? data.start_time.substring(0, 5) : '';
    form.end_time = data.end_time ? data.end_time.substring(0, 5) : '';
    displayModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('cbt.sessions.update', editId.value), {
            onSuccess: () => {
                displayModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('cbt.sessions.store'), {
            onSuccess: () => {
                displayModal.value = false;
                form.reset();
            }
        });
    }
};

const confirmDelete = (data) => {
    confirm.require({
        message: `Hapus sesi <b>${data.name}</b>?`,
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('cbt.sessions.destroy', data.id));
        }
    });
};
</script>
