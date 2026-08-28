<template>
    <AppLayout title="Berita Acara Kerusakan & Kehilangan Aset">
        <div class="p-4">
            <!-- Header -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-exclamation-triangle text-red-500 text-3xl"></i>
                        Berita Acara Kerusakan & Ganti Rugi
                    </h2>
                    <p class="text-600 m-0 mt-1">Daftar insiden fisik barang rusak atau hilang saat peminjaman beserta catatan tindak lanjut dan kompensasi.</p>
                </div>
            </div>

            <!-- Table -->
            <div class="surface-card border-round-xl shadow-2 overflow-hidden">
                <DataTable :value="reports.data" responsiveLayout="scroll" :rowHover="true" class="p-datatable-sm">
                    <template #empty>
                        <div class="p-5 text-center text-500">
                            <i class="pi pi-shield text-5xl mb-3 text-green-500"></i>
                            <p class="m-0">Tidak ada catatan kerusakan atau kehilangan aset aktif.</p>
                        </div>
                    </template>

                    <Column header="No. Dokumen" style="min-width: 140px">
                        <template #body="{ data }">
                            <div class="font-mono font-bold text-red-600">{{ data.report_code }}</div>
                            <div class="text-xs text-500">{{ formatDate(data.created_at) }}</div>
                        </template>
                    </Column>

                    <Column header="Jenis & Barang" style="min-width: 200px">
                        <template #body="{ data }">
                            <Tag :value="data.damage_type === 'lost' ? 'KEHILANGAN' : 'KERUSAKAN'" :severity="data.damage_type === 'lost' ? 'danger' : 'warn'" class="mb-1" />
                            <div v-if="data.asset" class="text-sm font-bold text-900">{{ data.asset.name }}</div>
                            <div v-if="data.room" class="text-xs text-primary font-semibold">Ruang: {{ data.room.name }}</div>
                        </template>
                    </Column>

                    <Column header="Peminjam / Penanggung Jawab" style="min-width: 180px">
                        <template #body="{ data }">
                            <div class="font-bold text-900">{{ data.reservation?.user?.name || '-' }}</div>
                            <div class="text-xs text-indigo-600">{{ data.reservation?.extracurricular?.name || 'Pribadi' }}</div>
                        </template>
                    </Column>

                    <Column header="Kronologi & Biaya" style="min-width: 240px">
                        <template #body="{ data }">
                            <p class="text-xs text-700 m-0 line-clamp-2">{{ data.description }}</p>
                            <div class="text-xs font-bold text-orange-600 mt-1">
                                Estimasi Kompensasi: Rp {{ formatRupiah(data.compensation_fee) }}
                            </div>
                        </template>
                    </Column>

                    <Column header="Status" style="min-width: 130px">
                        <template #body="{ data }">
                            <Tag :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" />
                            <div class="text-xs mt-1" :class="data.is_compensated ? 'text-green-600 font-bold' : 'text-red-500'">
                                {{ data.is_compensated ? 'Lunas / Diganti' : 'Belum Lunas' }}
                            </div>
                        </template>
                    </Column>

                    <Column header="Aksi" style="min-width: 120px" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-1 justify-content-end">
                                <Button 
                                    icon="pi pi-file-pdf" 
                                    class="p-button-rounded p-button-text p-button-danger" 
                                    tooltip="Unduh PDF Berita Acara" 
                                    @click="downloadPdf(data.id)" 
                                />
                                <Button 
                                    icon="pi pi-pencil" 
                                    class="p-button-rounded p-button-text p-button-info" 
                                    tooltip="Update Status / Tindak Lanjut" 
                                    @click="openEditModal(data)" 
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Pagination -->
            <div v-if="reports.total > reports.per_page" class="mt-4 flex justify-content-center">
                <Paginator 
                    :rows="reports.per_page" 
                    :totalRecords="reports.total" 
                    :first="(reports.current_page - 1) * reports.per_page"
                    @page="onPageChange"
                />
            </div>
        </div>

        <!-- Edit Status Dialog -->
        <Dialog v-model:visible="showModal" header="Tindak Lanjut Berita Acara Kerusakan" :modal="true" class="p-fluid w-full md:w-5">
            <div v-if="selectedReport" class="flex flex-column gap-3 pt-2">
                <div class="surface-100 p-3 border-round-lg">
                    <span class="font-mono text-xs font-bold text-red-600">{{ selectedReport.report_code }}</span>
                    <div class="font-bold text-sm text-900 mt-1">{{ selectedReport.asset?.name || selectedReport.room?.name }}</div>
                </div>

                <div>
                    <label class="font-bold text-sm block mb-1">Status Penanganan</label>
                    <Select v-model="editForm.status" :options="statusOptions" optionLabel="label" optionValue="value" />
                </div>

                <div>
                    <label class="font-bold text-sm block mb-1">Rencana Tindak Lanjut / Catatan Perbaikan</label>
                    <Textarea v-model="editForm.action_plan" rows="3" placeholder="Misal: Sudah diganti unit baru / servis di toko resmi..." />
                </div>

                <div>
                    <label class="font-bold text-sm block mb-1">Biaya Kompensasi / Ganti Rugi (Rp)</label>
                    <InputNumber v-model="editForm.compensation_fee" />
                </div>

                <div class="flex align-items-center">
                    <Checkbox v-model="editForm.is_compensated" :binary="true" inputId="is_compensated" />
                    <label for="is_compensated" class="ml-2 font-bold text-sm cursor-pointer">Kompensasi / Ganti Rugi Sudah Diterima (Lunas)</label>
                </div>

                <div class="flex justify-content-end gap-2 mt-3">
                    <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="showModal = false" />
                    <Button label="Simpan Perubahan" icon="pi pi-check" :loading="isSubmitting" @click="submitUpdate" />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from 'primevue/usetoast';

import Button from 'primevue/button';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';

const props = defineProps({
    reports: Object,
    filters: Object,
});

const toast = useToast();

const showModal = ref(false);
const selectedReport = ref(null);
const isSubmitting = ref(false);

const editForm = ref({
    status: 'open',
    action_plan: '',
    compensation_fee: 0,
    is_compensated: false,
});

const statusOptions = [
    { label: 'Belum Selesai (Open)', value: 'open' },
    { label: 'Dalam Peninjauan (In Review)', value: 'in_review' },
    { label: 'Selesai / Dituntaskan (Resolved)', value: 'resolved' },
];

const getStatusLabel = (status) => {
    switch(status) {
        case 'open': return 'Open';
        case 'in_review': return 'In Review';
        case 'resolved': return 'Resolved';
        default: return status;
    }
};

const getStatusSeverity = (status) => {
    switch(status) {
        case 'open': return 'danger';
        case 'in_review': return 'warn';
        case 'resolved': return 'success';
        default: return 'secondary';
    }
};

const formatDate = (d) => {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatRupiah = (val) => {
    if (!val) return '0';
    return Number(val).toLocaleString('id-ID');
};

const openEditModal = (report) => {
    selectedReport.value = report;
    editForm.value = {
        status: report.status,
        action_plan: report.action_plan || '',
        compensation_fee: Number(report.compensation_fee) || 0,
        is_compensated: !!report.is_compensated,
    };
    showModal.value = true;
};

const submitUpdate = () => {
    if (!selectedReport.value) return;
    isSubmitting.value = true;

    router.put(route('admin.sarpras.damages.update', selectedReport.value.id), editForm.value, {
        onSuccess: () => {
            showModal.value = false;
            toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Berita acara berhasil diperbarui', life: 3000 });
        },
        onFinish: () => { isSubmitting.value = false; }
    });
};

const downloadPdf = (id) => {
    window.open(route('admin.sarpras.damages.pdf', id), '_blank');
};

const onPageChange = (event) => {
    const page = event.page + 1;
    router.get(route('admin.sarpras.damages.index'), {
        ...props.filters,
        page,
    }, { preserveState: true, replace: true });
};
</script>
