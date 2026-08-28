<template>
    <AppLayout title="Data Izin Non Aktif">
        <div class="card border-0 shadow-none">
            <div class="flex flex-column md:flex-row md:justify-content-between md:align-items-center mb-4 gap-3">
                <div>
                    <h2 class="text-3xl font-bold m-0 text-teal-600 flex align-items-center">
                        <i class="pi pi-trash mr-3 text-3xl"></i>Sampah Data Izin Siswa
                    </h2>
                    <p class="text-500 m-0 mt-2">Daftar data izin yang telah dihapus sementara. Anda bisa memulihkan atau menghapus permanen.</p>
                </div>
                <div class="flex gap-2">
                    <Button 
                        label="Kosongkan Sampah" 
                        icon="pi pi-exclamation-circle" 
                        severity="danger" 
                        @click="handleEmptyTrash" 
                        :disabled="trash.length === 0"
                    />
                    <Button label="Kembali" icon="pi pi-arrow-left" severity="secondary" @click="router.get(route('permits.index'))" />
                </div>
            </div>

            <DataTable 
                :value="trash" 
                stripedRows 
                class="p-datatable-sm shadow-4 border-round-xl overflow-hidden"
                dataKey="id"
                removableSort
            >
                <template #empty>
                    <div class="p-5 text-center">
                        <i class="pi pi-folder-open text-4xl text-200 mb-3"></i>
                        <p class="text-500 font-medium">Tidak ada data di keranjang sampah.</p>
                    </div>
                </template>

                <Column field="date" header="Tanggal Izin" sortable>
                    <template #body="{ data }">
                        <span class="font-bold">{{ formatDate(data.date) }}</span>
                    </template>
                </Column>

                <Column header="Siswa" sortable field="student.full_name">
                    <template #body="{ data }">
                        <div class="flex align-items-center gap-2">
                            <div class="flex flex-column">
                                <span class="font-bold text-900">{{ data.student?.full_name }}</span>
                                <small class="text-500">NIS: {{ data.student?.nis }}</small>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column header="Kelas" field="student.current_classroom.name">
                    <template #body="{ data }">
                        <Tag severity="info" :value="data.student?.current_classroom?.name" rounded />
                    </template>
                </Column>

                <Column header="Jenis" style="width: 100px">
                    <template #body="{ data }">
                        <Tag :value="data.permit_type" :severity="getSeverity(data.permit_type)" />
                    </template>
                </Column>

                <Column field="reason" header="Keterangan" />

                <Column field="display_name" header="Dicatat Oleh" />

                <Column field="deleted_at" header="Waktu Hapus" sortable>
                    <template #body="{ data }">
                        <span class="text-red-500 font-medium">{{ formatDateTime(data.deleted_at) }}</span>
                    </template>
                </Column>

                <Column header="Aksi" style="width: 180px" class="text-center">
                    <template #body="{ data }">
                        <div class="flex gap-2 justify-content-center">
                            <Button 
                                icon="pi pi-refresh" 
                                severity="success" 
                                label="Restore" 
                                size="small" 
                                @click="handleRestore(data.id)" 
                                v-tooltip.top="'Kembalikan Data'"
                            />
                            <Button 
                                icon="pi pi-exclamation-triangle" 
                                severity="danger" 
                                label="Hapus" 
                                size="small" 
                                @click="handleForceDelete(data.id)" 
                                v-tooltip.top="'Hapus Permanen'"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import { useConfirm } from 'primevue/useconfirm';

const confirm = useConfirm();

dayjs.locale('id');

const props = defineProps({
    trash: Array
});

// Helpers
const formatDate = (val) => dayjs(val).format('DD MMM YYYY');
const formatDateTime = (val) => dayjs(val).format('DD/MM/YY HH:mm');
const getSeverity = (t) => ({ 'S': 'info', 'I': 'warn', 'D': 'help', 'A': 'danger', 'T': 'primary' }[t] || 'secondary');


const handleRestore = (id) => {
  confirm.require({
    message: 'Pulihkan data ini?',
    header: 'Konfirmasi Restore',
    icon: 'pi pi-replay',
    acceptLabel: 'Ya, Kembalikan',
    rejectLabel: 'Batal',
    acceptClass: 'p-button-info',
    accept: () => {
      router.post(route('permits.restore', id));
    }
  })
}

const handleForceDelete = (id) => {
  confirm.require({
    message: 'HAPUS PERMANEN? Data tidak akan bisa dikembalikan lagi.',
    header: 'Konfirmasi Hapus',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Ya, Hapus',
    rejectLabel: 'Batal',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(route('permits.force-delete', id))
    }
  })
}

const handleEmptyTrash = () => {
  confirm.require({
    message: 'PERINGATAN: Semua data di sampah akan dihapus PERMANEN dan tidak bisa dikembalikan. Anda yakin?',
    header: 'Konfirmasi Hapus',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Ya, Hapus',
    rejectLabel: 'Batal',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.post(route('permits.empty-trash'));
    }
  })
}
</script>

<style scoped>
/* Styling agar tabel terlihat lebih bersih di desktop */
:deep(.p-datatable-thead > tr > th) {
    background-color: #f8fafc;
    color: #475569;
}
</style>