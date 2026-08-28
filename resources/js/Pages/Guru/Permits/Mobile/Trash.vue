<template>
    <MobileLayout title="Data Non Aktif Izin">
        <div class="p-3 mb-8">
            <div class="flex align-items-center gap-2 mb-4">
                <Button icon="pi pi-arrow-left" text rounded @click="router.get(route('permits.index'))" />
                <h2 class="text-xl font-bold m-0 text-900">Sampah Data Izin</h2>
                <div class="p-3" v-if="trash.length > 0">
                    <Button 
                        label="Kosongkan Semua Sampah" 
                        icon="pi pi-trash" 
                        severity="danger" 
                        outlined 
                        class="w-full"
                        @click="handleEmptyTrash" 
                    />
                </div>
            </div>

            <div v-if="trash.length === 0" class="text-center py-8 surface-card border-round-xl shadow-1">
                <i class="pi pi-trash text-4xl text-200 mb-3"></i>
                <p class="text-500">Keranjang sampah kosong.</p>
            </div>

            <div class="flex flex-column gap-3">
                <div v-for="item in trash" :key="item.id" class="p-3 surface-card border-round-xl shadow-2 border-left-3 border-500">
                    <div class="flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="text-sm font-bold text-900">{{ item.student?.full_name }}</div>
                            <small class="text-500">{{ item.student?.current_classroom?.name }} | {{ item.date }}</small>
                        </div>
                        <Tag :value="item.permit_type" severity="secondary" />
                    </div>

                    <p class="text-xs text-600 mb-3 italic">"{{ item.reason }}"</p>
                    
                    <div class="flex justify-content-between align-items-center pt-2 border-top-1 border-100">
                        <small class="text-400">Dihapus: {{ formatDate(item.deleted_at) }}</small>
                        <div class="flex gap-2">
                            <Button icon="pi pi-refresh" severity="success" label="Restore" size="small" @click="handleRestore(item.id)" />
                            <Button icon="pi pi-exclamation-triangle" severity="danger" text size="small" @click="handleForceDelete(item.id)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import dayjs from 'dayjs';
import { useConfirm } from 'primevue/useconfirm';

const confirm = useConfirm();

const props = defineProps({ trash: Array });

const formatDate = (val) => dayjs(val).format('DD MMM YYYY, HH:mm');


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