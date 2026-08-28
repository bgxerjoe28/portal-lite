<template>
    <AppLayout title="Jadwal Pengawas Ujian">
        <CbtTabMenu />

        <div class="card">
            <div class="flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Jadwal Pengawas</h2>
                    <span class="text-500 block mt-1">{{ isAdmin ? 'Kelola penugasan pengawas untuk setiap ruangan dan sesi' : 'Daftar penugasan tugas pengawasan ujian Anda' }}</span>
                </div>
                
                <div class="flex gap-2 align-items-center">
                    <input 
                        type="date" 
                        class="p-inputtext p-component w-12rem" 
                        v-model="filterDate"
                        @change="changeDate"
                    />
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="search" placeholder="Cari Pengawas..." />
                    </IconField>
                    <Button v-if="isAdmin" label="Tambah Jadwal" icon="pi pi-plus" severity="primary" @click="openCreateModal" />
                </div>
            </div>

            <div class="surface-card p-4 shadow-2 border-round">
                <DataTable :value="schedules.data" :rows="10" stripedRows tableStyle="min-width: 50rem">
                    <template #empty> 
                        <div class="text-center p-4 text-500">
                            Belum ada jadwal pengawas pada tanggal <b>{{ formatDateIndo(filterDate) }}</b>.
                        </div>
                    </template>

                    <Column field="date" header="Tanggal" sortable>
                        <template #body="{ data }">
                            <span class="font-semibold">{{ formatDateIndo(data.date) }}</span>
                        </template>
                    </Column>

                    <Column header="Sesi">
                        <template #body="{ data }">
                            <div class="flex flex-column gap-1">
                                <div class="flex align-items-center gap-2 flex-wrap">
                                    <Tag :value="data.session?.name || '-'" :severity="data.session?.name?.includes('Mandiri') ? 'warn' : 'info'" />
                                    <Tag v-if="data.session?.name?.includes('Mandiri')" value="Ujian Mandiri" severity="secondary" class="text-xs" />
                                </div>
                                <small class="block text-500" v-if="data.session?.start_time">
                                    <i class="pi pi-clock text-xs mr-1"></i>{{ data.session.start_time.substring(0,5) }} - {{ data.session.end_time.substring(0,5) }}
                                </small>
                            </div>
                        </template>
                    </Column>

                    <Column header="Ruangan">
                        <template #body="{ data }">
                            <span class="font-bold text-800">{{ data.room?.name || '-' }}</span>
                        </template>
                    </Column>

                    <Column field="teacher.full_name" header="Pengawas (Guru)" sortable>
                        <template #body="{ data }">
                            <div class="flex align-items-center gap-2">
                                <i class="pi pi-user text-primary"></i>
                                <span class="font-medium text-900">{{ data.teacher?.full_name || '-' }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Status" style="width: 15%">
                        <template #body="{ data }">
                            <Tag v-if="data.status === 'not_started'" value="Belum Mulai" severity="secondary" />
                            <Tag v-else-if="data.status === 'started'" value="Berlangsung" severity="success" />
                            <Tag v-else-if="data.status === 'ended'" value="Selesai" severity="danger" />
                        </template>
                    </Column>

                    <Column v-if="isAdmin" header="Aksi" style="width: 10%">
                        <template #body="{ data }">
                            <div class="flex gap-1" v-if="data.status === 'not_started'">
                                <Button icon="pi pi-pencil" severity="warning" text rounded v-tooltip.top="'Edit'" @click="openEditModal(data)" />
                                <Button icon="pi pi-trash" severity="danger" text rounded v-tooltip.top="'Hapus'" @click="confirmDelete(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
                <Pagination :links="schedules.links" class="mt-4" />
            </div>
        </div>

        <Dialog v-model:visible="displayModal" :header="isEditing ? 'Edit Jadwal' : 'Tambah Jadwal Pengawas'" :modal="true" :style="{ width: '500px' }">
            <form @submit.prevent="submitForm" class="p-fluid">
                <div class="field mb-3">
                    <label for="date" class="font-medium">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" id="date" v-model="form.date" class="p-inputtext w-full" :class="{'p-invalid': form.errors.date}" />
                    <small class="p-error" v-if="form.errors.date">{{ form.errors.date }}</small>
                </div>

                <div class="formgrid grid">
                    <div class="field col-6 mb-3">
                        <label for="cbt_session_id" class="font-medium">Sesi Ujian <span class="text-red-500">*</span></label>
                        <Select 
                            v-model="form.cbt_session_id" 
                            :options="sessions" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Pilih Sesi" 
                            class="w-full"
                            :class="{'p-invalid': form.errors.cbt_session_id}"
                        />
                        <small class="p-error" v-if="form.errors.cbt_session_id">{{ form.errors.cbt_session_id }}</small>
                    </div>
                    <div class="field col-6 mb-3">
                        <label for="cbt_room_id" class="font-medium">Ruangan <span class="text-red-500">*</span></label>
                        <Select 
                            v-model="form.cbt_room_id" 
                            :options="rooms" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Pilih Ruang" 
                            class="w-full"
                            filter
                            :class="{'p-invalid': form.errors.cbt_room_id}"
                        />
                        <small class="p-error" v-if="form.errors.cbt_room_id">{{ form.errors.cbt_room_id }}</small>
                    </div>
                </div>

                <div class="field mb-3">
                    <label for="teacher_id" class="font-medium">Pengawas (Guru) <span class="text-red-500">*</span></label>
                    <Select 
                        v-model="form.teacher_id" 
                        :options="teachers" 
                        optionLabel="full_name" 
                        optionValue="id" 
                        placeholder="Pilih Guru Pengawas" 
                        class="w-full"
                        filter
                        :class="{'p-invalid': form.errors.teacher_id}"
                    />
                    <small class="p-error" v-if="form.errors.teacher_id">{{ form.errors.teacher_id }}</small>
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
import { ref, watch, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';

import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import CbtTabMenu from '../CbtTabMenu.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Tag from 'primevue/tag';

const page = usePage();
const isAdmin = computed(() => (page.props.auth?.user?.roles ?? []).some(r => r.name === 'admin'));

const props = defineProps({
    schedules: Object,
    filters: Object,
    rooms: Array,
    sessions: Array,
    teachers: Array,
});

const confirm = useConfirm();
const displayModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const search = ref(props.filters?.search || '');
const filterDate = ref(props.filters?.date || new Date().toISOString().split('T')[0]);

const form = useForm({
    date: filterDate.value,
    cbt_room_id: null,
    cbt_session_id: null,
    teacher_id: null,
});

const changeDate = () => {
    router.get(route('cbt.proctor-schedules.index'), { search: search.value, date: filterDate.value }, { preserveState: true, replace: true, preserveScroll: true });
};

let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('cbt.proctor-schedules.index'), { search: val, date: filterDate.value }, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.date = filterDate.value;
    displayModal.value = true;
};

const openEditModal = (data) => {
    isEditing.value = true;
    editId.value = data.id;
    form.date = data.date;
    form.cbt_room_id = data.cbt_room_id;
    form.cbt_session_id = data.cbt_session_id;
    form.teacher_id = data.teacher_id;
    displayModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('cbt.proctor-schedules.update', editId.value), {
            onSuccess: () => {
                displayModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('cbt.proctor-schedules.store'), {
            onSuccess: () => {
                displayModal.value = false;
                form.reset();
            }
        });
    }
};

const confirmDelete = (data) => {
    confirm.require({
        message: `Hapus jadwal pengawas untuk ruangan <b>${data.room?.name}</b> sesi <b>${data.session?.name}</b>?`,
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('cbt.proctor-schedules.destroy', data.id));
        }
    });
};

const formatDateIndo = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>
