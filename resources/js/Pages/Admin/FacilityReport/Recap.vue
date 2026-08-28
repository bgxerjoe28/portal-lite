<template>
    <AppLayout title="Rekap Sarana Prasarana">
        <div class="p-4">
            <!-- Header Section -->
            <div class="mb-4">
                <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3">
                    <div>
                        <h2 class="text-3xl font-extrabold text-900 m-0 flex align-items-center gap-2">
                            <i class="pi pi-list text-orange-500 text-3xl"></i>
                            Rekap Sarana & Prasarana
                        </h2>
                        <p class="text-600 m-0 mt-1">Daftar seluruh sarana dan prasarana yang tercatat pada laporan kerusakan terverifikasi.</p>
                    </div>
                    <div class="flex gap-2">
                        <Button label="Cetak PDF" icon="pi pi-file-pdf" severity="danger" as="a" :href="pdfExportUrl" target="_blank" />
                        <Button label="Kembali ke Kelola Laporan" icon="pi pi-arrow-left" severity="secondary" outlined @click="goBack" />
                    </div>
                </div>
            </div>

            <!-- Tab Menu -->
            <div class="mb-4">
                <TabMenu :model="tabItems" :activeIndex="1" />
            </div>

            <!-- Stats Bar -->
            <div class="grid mb-4">
                <div class="col-12 md:col-4">
                    <div class="surface-card p-4 border-round-xl shadow-2 border-left-3 border-yellow-500 flex align-items-center justify-content-between">
                        <div>
                            <span class="block text-500 font-medium mb-1">Dilaporkan</span>
                            <span class="text-900 font-bold text-2xl">
                                {{ items.data.filter(r => r.status === 'dilaporkan').length }} Item
                            </span>
                        </div>
                        <div class="flex align-items-center justify-content-center bg-yellow-100 border-round-md" style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-exclamation-circle text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 md:col-4">
                    <div class="surface-card p-4 border-round-xl shadow-2 border-left-3 border-blue-500 flex align-items-center justify-content-between">
                        <div>
                            <span class="block text-500 font-medium mb-1">Dalam Perbaikan</span>
                            <span class="text-900 font-bold text-2xl">
                                {{ items.data.filter(r => r.status === 'diperbaiki').length }} Item
                            </span>
                        </div>
                        <div class="flex align-items-center justify-content-center bg-blue-100 border-round-md" style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-cog text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 md:col-4">
                    <div class="surface-card p-4 border-round-xl shadow-2 border-left-3 border-green-500 flex align-items-center justify-content-between">
                        <div>
                            <span class="block text-500 font-medium mb-1">Selesai</span>
                            <span class="text-900 font-bold text-2xl">
                                {{ items.data.filter(r => r.status === 'selesai').length }} Item
                            </span>
                        </div>
                        <div class="flex align-items-center justify-content-center bg-green-100 border-round-md" style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-check text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="surface-card shadow-2 border-round-xl p-4 mb-4">
                <div class="flex flex-wrap gap-3 align-items-center justify-content-between">
                    <div class="flex flex-wrap gap-3 p-fluid">
                        <div style="min-width: 180px">
                            <Select 
                                v-model="filter.status" 
                                :options="statusOptions" 
                                optionLabel="label" 
                                optionValue="value" 
                                placeholder="Filter Status" 
                                showClear 
                            />
                        </div>
                        <div style="min-width: 180px">
                            <Select 
                                v-model="filter.type" 
                                :options="typeOptions" 
                                optionLabel="label" 
                                optionValue="value" 
                                placeholder="Filter Kategori" 
                                showClear 
                            />
                        </div>
                        <div style="min-width: 180px">
                            <Select 
                                v-model="filter.severity" 
                                :options="severityOptions" 
                                optionLabel="label" 
                                optionValue="value" 
                                placeholder="Filter Keparahan" 
                                showClear 
                            />
                        </div>
                        <div style="min-width: 180px">
                            <Select 
                                v-model="filter.room_name" 
                                :options="uniqueRooms" 
                                placeholder="Filter Ruang / Gedung" 
                                filter
                                showClear 
                            />
                        </div>
                    </div>
                    <div class="flex gap-2 w-full md:w-auto mt-3 md:mt-0">
                        <Button label="Reset" icon="pi pi-refresh" severity="secondary" outlined @click="resetFilter" class="w-full md:w-auto" />
                        <Button label="Terapkan" icon="pi pi-filter" severity="primary" @click="applyFilter" class="w-full md:w-auto" />
                    </div>
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="surface-card shadow-2 border-round-xl p-0 overflow-hidden">
                <DataTable 
                    :value="items.data" 
                    responsiveLayout="scroll"
                    stripedRows
                    class="p-datatable-sm p-datatable-gridlines border-none"
                    :rowHover="true"
                    emptyMessage="Tidak ada data rekap sarana/prasarana ditemukan."
                >
                    <Column header="ID Laporan" style="width: 5%">
                        <template #body="{ data }">
                            <Link :href="route('admin.facility.reports.show', data.facility_report_id)" class="text-blue-600 font-bold no-underline hover:underline">
                                #{{ data.facility_report_id }}
                            </Link>
                        </template>
                    </Column>
                    
                    <Column header="Tanggal Laporan" style="width: 15%">
                        <template #body="{ data }">
                            <div class="flex flex-column">
                                <span class="font-bold text-900">{{ formatDateOnly(data.facility_report.created_at) }}</span>
                                <span class="text-sm text-500">{{ formatTimeOnly(data.facility_report.created_at) }} WIB</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Kategori" style="width: 10%">
                        <template #body="{ data }">
                            <Tag :value="formatType(data.type)" :severity="data.type === 'sarana' ? 'info' : 'help'" />
                        </template>
                    </Column>

                    <Column header="Nama Barang" style="width: 15%">
                        <template #body="{ data }">
                            <span class="font-bold text-teal-700">{{ data.item_name }}</span>
                        </template>
                    </Column>

                    <Column header="Ruang / Gedung" style="width: 15%">
                        <template #body="{ data }">
                            <span class="text-sm text-600 font-semibold"><i class="pi pi-map-marker text-xs mr-1 text-orange-500"></i>{{ data.room_name || data.facility_report.location }}</span>
                        </template>
                    </Column>

                    <Column header="Keparahan" style="width: 10%">
                        <template #body="{ data }">
                            <span :class="severityClass(data.severity)" class="font-semibold px-2 py-1 border-round text-xs">
                                {{ data.severity.toUpperCase() }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Saran / Rekomendasi" style="width: 15%">
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.recommendation || '-' }}</span>
                        </template>
                    </Column>

                    <Column header="Status" style="width: 15%">
                        <template #body="{ data }">
                            <Tag :value="formatItemStatus(data.status)" :severity="itemStatusSeverity(data.status)" class="text-sm" />
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 5%" bodyStyle="text-align:center">
                        <template #body="{ data }">
                            <div class="flex justify-content-center gap-2">
                                <Link :href="route('admin.facility.reports.show', data.facility_report_id)">
                                    <Button icon="pi pi-eye" class="p-button-text p-button-info p-button-sm p-0 w-2rem h-2rem" v-tooltip.left="'Lihat Laporan Detail'" />
                                </Link>
                                <Button icon="pi pi-pencil" class="p-button-text p-button-warning p-button-sm p-0 w-2rem h-2rem" v-tooltip.left="'Edit Item'" @click="openEditDialog(data)" />
                                <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm p-0 w-2rem h-2rem" v-tooltip.left="'Hapus Item'" @click="confirmDelete(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
                
                <div class="p-3 border-top-1 border-300">
                    <Paginator 
                        v-if="items.total > items.per_page"
                        :rows="items.per_page" 
                        :totalRecords="items.total" 
                        :first="(items.current_page - 1) * items.per_page"
                        @page="onPage"
                        template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                    />
                </div>
            </div>
        </div>

        <!-- Edit Dialog -->
        <Dialog v-model:visible="displayEditDialog" modal header="Edit Sarana / Prasarana" :style="{ width: '90vw', maxWidth: '600px' }">
            <div class="flex flex-column gap-3 mt-3" v-if="editForm">
                <div class="field">
                    <label class="font-bold">Kategori</label>
                    <Select v-model="editForm.type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div class="field">
                    <label class="font-bold">Nama Barang</label>
                    <InputText v-model="editForm.item_name" class="w-full" />
                </div>
                <div class="field">
                    <label class="font-bold">Ruang / Gedung</label>
                    <InputText v-model="editForm.room_name" class="w-full" />
                </div>
                <div class="field">
                    <label class="font-bold">Keparahan</label>
                    <Select v-model="editForm.severity" :options="severityOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div class="field">
                    <label class="font-bold">Status</label>
                    <Select v-model="editForm.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div class="field">
                    <label class="font-bold">Saran Perbaikan</label>
                    <InputText v-model="editForm.recommendation" class="w-full" />
                </div>
                <div class="field">
                    <label class="font-bold">Keterangan Tambahan</label>
                    <Textarea v-model="editForm.notes" rows="3" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="displayEditDialog = false" :disabled="submitting" />
                <Button label="Simpan Perubahan" icon="pi pi-check" @click="submitEdit" :loading="submitting" />
            </template>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { reactive, ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import TabMenu from 'primevue/tabmenu';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    items: Object,
    uniqueRooms: Array,
    filters: Object
});

const filter = reactive({
    status: props.filters.status || null,
    type: props.filters.type || null,
    severity: props.filters.severity || null,
    room_name: props.filters.room_name || null,
});

const pdfExportUrl = computed(() => {
    const url = new URL(route('admin.facility.reports.export-pdf'), window.location.origin);
    if (filter.status) url.searchParams.append('status', filter.status);
    if (filter.type) url.searchParams.append('type', filter.type);
    if (filter.severity) url.searchParams.append('severity', filter.severity);
    if (filter.room_name) url.searchParams.append('room_name', filter.room_name);
    return url.toString();
});

const tabItems = [
    { label: 'Daftar Laporan', icon: 'pi pi-file', command: () => router.get(route('admin.facility.reports.index')) },
    { label: 'Rekap Sarana Prasarana', icon: 'pi pi-list', command: () => router.get(route('admin.facility.reports.recap')) }
];

const statusOptions = [
    { label: 'Dilaporkan', value: 'dilaporkan' },
    { label: 'Dalam Perbaikan', value: 'diperbaiki' },
    { label: 'Selesai', value: 'selesai' }
];

const typeOptions = [
    { label: 'Sarana', value: 'sarana' },
    { label: 'Prasarana', value: 'prasarana' }
];

const severityOptions = [
    { label: 'Ringan', value: 'ringan' },
    { label: 'Sedang', value: 'sedang' },
    { label: 'Berat', value: 'berat' }
];

const applyFilter = () => {
    router.get(route('admin.facility.reports.recap'), filter, {
        preserveState: true,
        preserveScroll: true
    });
};

const resetFilter = () => {
    filter.status = null;
    filter.type = null;
    filter.severity = null;
    filter.room_name = null;
    applyFilter();
};

const onPage = (event) => {
    const params = { ...filter, page: event.page + 1 };
    router.get(route('admin.facility.reports.recap'), params, {
        preserveState: true,
        preserveScroll: true
    });
};

const goBack = () => {
    router.get(route('admin.facility.reports.index'));
};

const formatType = (type) => {
    return type === 'sarana' ? 'Sarana' : 'Prasarana';
};

const formatItemStatus = (status) => {
    switch (status) {
        case 'dilaporkan': return 'Dilaporkan';
        case 'diperbaiki': return 'Dalam Perbaikan';
        case 'selesai': return 'Selesai';
        default: return status;
    }
};

const itemStatusSeverity = (status) => {
    switch (status) {
        case 'dilaporkan': return 'warn';
        case 'diperbaiki': return 'info';
        case 'selesai': return 'success';
        default: return 'secondary';
    }
};

const severityClass = (severity) => {
    switch (severity) {
        case 'ringan': return 'bg-green-100 text-green-700';
        case 'sedang': return 'bg-yellow-100 text-yellow-700';
        case 'berat': return 'bg-red-100 text-red-700';
        default: return 'bg-gray-100 text-gray-700';
    }
};

const formatDateOnly = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    }).format(date);
};

const formatTimeOnly = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};

const toast = useToast();
const confirm = useConfirm();
const displayEditDialog = ref(false);
const submitting = ref(false);
const editForm = ref(null);

const openEditDialog = (item) => {
    editForm.value = {
        id: item.id,
        type: item.type,
        item_name: item.item_name,
        room_name: item.room_name,
        severity: item.severity,
        recommendation: item.recommendation,
        status: item.status,
        notes: item.notes
    };
    displayEditDialog.value = true;
};

const submitEdit = () => {
    submitting.value = true;
    router.put(route('admin.facility.reports.items.update-full', editForm.value.id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            displayEditDialog.value = false;
        },
        onFinish: () => {
            submitting.value = false;
        }
    });
};

const confirmDelete = (item) => {
    confirm.require({
        message: `Apakah Anda yakin ingin menghapus sarana/prasarana "${item.item_name}"?`,
        header: 'Konfirmasi Penghapusan',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Ya, Hapus',
        rejectLabel: 'Batal',
        acceptClass: 'p-button-danger',
        rejectClass: 'p-button-secondary p-button-text',
        accept: () => {
            router.delete(route('admin.facility.reports.items.destroy', item.id), {
                preserveScroll: true
            });
        }
    });
};
</script>
