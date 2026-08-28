<template>
    <AppLayout title="Kelola Jadwal Terkunci (Blackout Dates)">
        <div class="p-4">
            <!-- Header -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-lock text-primary text-3xl"></i>
                        Jadwal Terkunci / Agenda Khusus Sekolah
                    </h2>
                    <p class="text-600 m-0 mt-1">Kunci jadwal ruangan untuk agenda prioritas (ANBK, Ujian, Rapat Guru) sehingga tidak dapat dibooking oleh siswa/eskul.</p>
                </div>
                <Button label="Kunci Jadwal Baru" icon="pi pi-plus" class="p-button-primary shadow-2" @click="showModal = true" />
            </div>

            <!-- Table -->
            <div class="surface-card border-round-xl shadow-2 overflow-hidden">
                <DataTable :value="blackouts.data" responsiveLayout="scroll" :rowHover="true" class="p-datatable-sm">
                    <template #empty>
                        <div class="p-5 text-center text-500">
                            <i class="pi pi-calendar-plus text-5xl mb-3 text-400"></i>
                            <p class="m-0">Belum ada agenda sekolah / blackout dates yang aktif.</p>
                        </div>
                    </template>

                    <Column header="Nama Agenda / Keperluan" style="min-width: 220px">
                        <template #body="{ data }">
                            <div class="font-bold text-900">{{ data.name }}</div>
                            <div class="text-xs text-500">{{ data.reason || '-' }}</div>
                        </template>
                    </Column>

                    <Column header="Ruangan Terkunci" style="min-width: 180px">
                        <template #body="{ data }">
                            <Tag v-if="!data.room_id" value="Semua Ruangan Sekolah" severity="danger" />
                            <Tag v-else :value="data.room?.name" severity="info" />
                        </template>
                    </Column>

                    <Column header="Rentang Waktu Terkunci" style="min-width: 220px">
                        <template #body="{ data }">
                            <div class="text-xs font-bold text-900">{{ formatDateTime(data.start_time) }}</div>
                            <div class="text-xs text-500">s/d {{ formatDateTime(data.end_time) }}</div>
                        </template>
                    </Column>

                    <Column header="Dibuat Oleh" style="min-width: 140px">
                        <template #body="{ data }">
                            <div class="text-xs text-700 font-semibold">{{ data.creator?.name || '-' }}</div>
                        </template>
                    </Column>

                    <Column header="Aksi" style="min-width: 80px" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" tooltip="Hapus Kunci Jadwal" @click="confirmDelete(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Create Modal -->
        <Dialog v-model:visible="showModal" header="Kunci Jadwal Ruangan Sekolah" :modal="true" class="p-fluid w-full md:w-5">
            <form @submit.prevent="submitForm">
                <div class="flex flex-column gap-3 pt-2">
                    <div>
                        <label class="font-bold text-sm block mb-1">Nama Agenda / Kegiatan Sekolah <span class="text-red-500">*</span></label>
                        <InputText v-model="form.name" placeholder="Misal: Ujian Sekolah CBT / ANBK" required />
                    </div>

                    <div>
                        <label class="font-bold text-sm block mb-1">Ruangan yang Dikunci</label>
                        <Select 
                            v-model="form.room_id" 
                            :options="roomOptions" 
                            optionLabel="label" 
                            optionValue="value" 
                            placeholder="Pilih Ruangan (atau Semua Ruangan)" 
                        />
                    </div>

                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <label class="font-bold text-sm block mb-1">Waktu Mulai <span class="text-red-500">*</span></label>
                            <InputText v-model="form.start_time" type="datetime-local" required />
                        </div>
                        <div class="col-12 md:col-6">
                            <label class="font-bold text-sm block mb-1">Waktu Selesai <span class="text-red-500">*</span></label>
                            <InputText v-model="form.end_time" type="datetime-local" required />
                        </div>
                    </div>

                    <div>
                        <label class="font-bold text-sm block mb-1">Keterangan / Alasan</label>
                        <Textarea v-model="form.reason" rows="2" placeholder="Catatan internal..." />
                    </div>
                </div>

                <div class="flex justify-content-end gap-2 mt-4 pt-3 border-top-1 border-200">
                    <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="showModal = false" />
                    <Button label="Kunci Jadwal" icon="pi pi-lock" type="submit" :loading="isSubmitting" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    blackouts: Object,
    rooms: Array,
});

const confirm = useConfirm();
const toast = useToast();

const showModal = ref(false);
const isSubmitting = ref(false);

const form = ref({
    name: '',
    room_id: null,
    start_time: '',
    end_time: '',
    reason: '',
});

const roomOptions = computed(() => {
    const list = [{ label: '🔒 Kunci Semua Ruangan', value: null }];
    if (props.rooms) {
        props.rooms.forEach(r => {
            list.push({ label: r.name, value: r.id });
        });
    }
    return list;
});

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const submitForm = () => {
    isSubmitting.value = true;
    router.post(route('admin.sarpras.blackouts.store'), form.value, {
        onSuccess: () => {
            showModal.value = false;
            form.value = { name: '', room_id: null, start_time: '', end_time: '', reason: '' };
            toast.add({ severity: 'success', summary: 'Terkunci', detail: 'Jadwal blackout berhasil disimpan', life: 3000 });
        },
        onError: (err) => {
            toast.add({ severity: 'error', summary: 'Gagal', detail: Object.values(err)[0] || 'Terjadi kesalahan', life: 4000 });
        },
        onFinish: () => { isSubmitting.value = false; }
    });
};

const confirmDelete = (item) => {
    confirm.require({
        message: `Buka kembali penguncian jadwal untuk <strong>${item.name}</strong>?`,
        header: 'Hapus Kunci Jadwal',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.sarpras.blackouts.destroy', item.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Dihapus', detail: 'Kunci jadwal telah dibuka', life: 3000 });
                }
            });
        }
    });
};
</script>
