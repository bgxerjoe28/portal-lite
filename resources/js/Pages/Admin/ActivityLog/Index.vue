<template>
    <AppLayout>
        <Head title="Log Aktivitas & Audit Trail - Portal SMA" />

        <div class="card p-4 shadow-2 border-round">
            <!-- HEADER -->
            <div class="flex flex-column md:flex-row md:align-items-center justify-content-between gap-3 mb-4 border-bottom-1 border-200 pb-3">
                <div class="flex align-items-center gap-3">
                    <div class="border-circle bg-primary-100 text-primary p-3 flex align-items-center justify-content-center text-2xl">
                        <i class="pi pi-history"></i>
                    </div>
                    <div>
                        <h1 class="m-0 text-900 text-2xl font-bold">Log Aktivitas & Audit Trail</h1>
                        <span class="text-600 text-sm">
                            Pantau riwayat login, keamanan, dan seluruh modifikasi data pada sistem secara auditable.
                        </span>
                    </div>
                </div>
                <div class="flex align-items-center gap-2">
                    <Tag severity="info" value="Append-Only Log" icon="pi pi-lock" />
                    <span class="text-sm font-bold text-600">Total: {{ logs.total }} Catatan</span>
                </div>
            </div>

            <!-- FILTER BAR -->
            <div class="surface-ground p-3 border-round border-1 border-300 mb-4">
                <div class="grid formgrid p-fluid align-items-end">
                    <div class="col-12 md:col-3">
                        <label class="block text-sm font-bold text-700 mb-1">Cari Keterangan / Pengguna</label>
                        <IconField iconPosition="left" class="w-full">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filterForm.search" placeholder="Nama, Email, IP, Deskripsi..." @keyup.enter="applyFilter" />
                        </IconField>
                    </div>
                    <div class="col-12 sm:col-6 md:col-2">
                        <label class="block text-sm font-bold text-700 mb-1">Role Pengguna</label>
                        <Select
                            v-model="filterForm.role"
                            :options="roleOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Semua Role"
                            class="w-full"
                        />
                    </div>
                    <div class="col-12 sm:col-6 md:col-2">
                        <label class="block text-sm font-bold text-700 mb-1">Jenis Aksi</label>
                        <Select
                            v-model="filterForm.action"
                            :options="actionOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Semua Aksi"
                            class="w-full"
                        />
                    </div>
                    <div class="col-12 sm:col-6 md:col-2">
                        <label class="block text-sm font-bold text-700 mb-1">Dari Tanggal</label>
                        <InputText type="date" v-model="filterForm.date_from" class="w-full" />
                    </div>
                    <div class="col-12 sm:col-6 md:col-2">
                        <label class="block text-sm font-bold text-700 mb-1">Sampai Tanggal</label>
                        <InputText type="date" v-model="filterForm.date_to" class="w-full" />
                    </div>
                    <div class="col-12 md:col-1 flex gap-2">
                        <Button icon="pi pi-filter" label="Cari" class="p-button-primary w-full" @click="applyFilter" />
                        <Button icon="pi pi-refresh" class="p-button-outlined p-button-secondary" @click="resetFilter" v-tooltip="'Reset Filter'" />
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <DataTable
                :value="logs.data"
                responsiveLayout="scroll"
                stripedRows
                class="p-datatable-sm"
                emptyMessage="Belum ada catatan log aktivitas yang sesuai filter."
            >
                <Column header="Waktu" style="width: 130px">
                    <template #body="slotProps">
                        <div class="text-sm font-medium text-900">
                            {{ formatDateTime(slotProps.data.created_at) }}
                        </div>
                    </template>
                </Column>

                <Column header="Pengguna" style="width: 200px">
                    <template #body="slotProps">
                        <div class="flex flex-column">
                            <span class="font-bold text-900">{{ slotProps.data.user_name || 'System' }}</span>
                            <span v-if="slotProps.data.user_email" class="text-xs text-500">{{ slotProps.data.user_email }}</span>
                            <div class="mt-1">
                                <Tag
                                    :value="slotProps.data.role || 'system'"
                                    :severity="getRoleSeverity(slotProps.data.role)"
                                    class="text-xs uppercase"
                                />
                            </div>
                        </div>
                    </template>
                </Column>

                <Column header="Aksi" style="width: 140px">
                    <template #body="slotProps">
                        <Tag
                            :value="slotProps.data.action"
                            :severity="getActionSeverity(slotProps.data.action)"
                            class="font-bold"
                        />
                    </template>
                </Column>

                <Column header="Deskripsi & Sasaran">
                    <template #body="slotProps">
                        <div class="text-900 line-height-3">
                            {{ slotProps.data.description }}
                        </div>
                        <div v-if="slotProps.data.subject_type" class="text-xs text-600 mt-1 font-mono">
                            Target: <span class="text-primary font-bold">{{ cleanModelName(slotProps.data.subject_type) }}</span>
                            <span v-if="slotProps.data.subject_id"> #{{ slotProps.data.subject_id }}</span>
                        </div>
                    </template>
                </Column>

                <Column header="IP & Perangkat" style="width: 150px">
                    <template #body="slotProps">
                        <div class="text-sm font-mono text-700">
                            {{ slotProps.data.ip_address || '-' }}
                        </div>
                        <div class="text-xs text-500 white-space-nowrap overflow-hidden text-overflow-ellipsis" style="max-width: 140px;" :title="slotProps.data.user_agent">
                            {{ slotProps.data.user_agent || '-' }}
                        </div>
                    </template>
                </Column>

                <Column header="Audit Diff" style="width: 120px; text-align: center;">
                    <template #body="slotProps">
                        <Button
                            v-if="slotProps.data.old_values || slotProps.data.new_values"
                            icon="pi pi-eye"
                            label="Perubahan"
                            class="p-button-outlined p-button-sm p-button-info font-bold"
                            @click="showDetailModal(slotProps.data)"
                        />
                        <span v-else class="text-xs text-400">-</span>
                    </template>
                </Column>
            </DataTable>

            <!-- PAGINATION -->
            <div v-if="logs.links && logs.links.length > 3" class="flex align-items-center justify-content-between border-top-1 border-200 pt-3 mt-3">
                <div class="text-sm text-600">
                    Menampilkan {{ logs.from || 0 }} sampai {{ logs.to || 0 }} dari {{ logs.total }} catatan
                </div>
                <div class="flex gap-1">
                    <Button
                        v-for="(link, idx) in logs.links"
                        :key="idx"
                        :label="link.label"
                        :disabled="!link.url || link.active"
                        :class="link.active ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                        class="p-button-sm"
                        @click="goToPage(link.url)"
                    />
                </div>
            </div>
        </div>

        <!-- MODAL AUDIT DIFF DETAIL -->
        <Dialog
            v-model:visible="detailModalVisible"
            header="Detail Perubahan Data (Audit Trail Diff)"
            modal
            class="p-fluid w-full md:w-8"
            :style="{ maxWidth: '850px' }"
        >
            <div v-if="selectedLog" class="p-2">
                <div class="surface-ground p-3 border-round mb-4">
                    <div class="grid">
                        <div class="col-12 sm:col-6">
                            <span class="text-xs text-500 block">Pelaku</span>
                            <span class="font-bold text-900">{{ selectedLog.user_name || 'System' }}</span>
                            <span v-if="selectedLog.user_email" class="text-xs text-600 block">{{ selectedLog.user_email }}</span>
                        </div>
                        <div class="col-12 sm:col-6">
                            <span class="text-xs text-500 block">Waktu Kejadian</span>
                            <span class="font-bold text-900">{{ formatDateTime(selectedLog.created_at) }}</span>
                        </div>
                        <div class="col-12 mt-2">
                            <span class="text-xs text-500 block">Aksi / Deskripsi</span>
                            <span class="font-bold text-900 block">{{ selectedLog.description }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid">
                    <!-- OLD VALUES -->
                    <div class="col-12 md:col-6">
                        <div class="surface-card border-1 border-300 border-round p-3 h-full">
                            <div class="flex align-items-center gap-2 mb-2 pb-2 border-bottom-1 border-200">
                                <Tag severity="danger" value="Sebelum (Old Values)" icon="pi pi-minus-circle" />
                            </div>
                            <pre v-if="selectedLog.old_values" class="text-xs font-mono m-0 overflow-auto" style="max-height: 350px;">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
                            <div v-else class="text-xs text-500 font-italic py-3 text-center">Tidak ada data awal (pembuatan baru atau aksi autentikasi)</div>
                        </div>
                    </div>

                    <!-- NEW VALUES -->
                    <div class="col-12 md:col-6">
                        <div class="surface-card border-1 border-300 border-round p-3 h-full">
                            <div class="flex align-items-center gap-2 mb-2 pb-2 border-bottom-1 border-200">
                                <Tag severity="success" value="Sesudah (New Values)" icon="pi pi-plus-circle" />
                            </div>
                            <pre v-if="selectedLog.new_values" class="text-xs font-mono m-0 overflow-auto" style="max-height: 350px;">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
                            <div v-else class="text-xs text-500 font-italic py-3 text-center">Tidak ada data baru (penghapusan atau tidak ada mutasi atribut)</div>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Tutup" icon="pi pi-times" class="p-button-secondary" @click="detailModalVisible = false" />
            </template>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    logs: Object,
    filters: Object,
    availableRoles: Array,
    availableActions: Array,
});

const filterForm = ref({
    search: props.filters.search || '',
    role: props.filters.role || null,
    action: props.filters.action || null,
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const roleOptions = computed(() => {
    const list = [
        { label: 'Semua Role', value: null },
        { label: 'System / Guest', value: 'system' }
    ];
    (props.availableRoles || []).forEach(r => {
        if (r !== 'system') {
            list.push({ label: r.toUpperCase(), value: r });
        }
    });
    return list;
});

const actionOptions = computed(() => {
    const list = [{ label: 'Semua Aksi', value: null }];
    (props.availableActions || []).forEach(act => {
        list.push({ label: act, value: act });
    });
    return list;
});

const applyFilter = () => {
    router.get(route('admin.activity-logs.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    filterForm.value = {
        search: '',
        role: null,
        action: null,
        date_from: '',
        date_to: '',
    };
    applyFilter();
};

const goToPage = (url) => {
    if (url) {
        router.visit(url, { preserveState: true, preserveScroll: true });
    }
};

const detailModalVisible = ref(false);
const selectedLog = ref(null);

const showDetailModal = (log) => {
    selectedLog.value = log;
    detailModalVisible.value = true;
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getRoleSeverity = (role) => {
    if (!role) return 'secondary';
    const r = role.toLowerCase();
    if (r === 'admin') return 'danger';
    if (r === 'kepala sekolah') return 'warn';
    if (r === 'guru') return 'info';
    if (r === 'siswa') return 'success';
    return 'secondary';
};

const getActionSeverity = (action) => {
    if (!action) return 'primary';
    const a = action.toUpperCase();
    if (a.includes('FAIL') || a === 'DELETED') return 'danger';
    if (a === 'LOGIN' || a === 'CREATED') return 'success';
    if (a === 'UPDATED') return 'info';
    if (a === 'LOGOUT') return 'secondary';
    return 'warn';
};

const cleanModelName = (str) => {
    if (!str) return '';
    const parts = str.split('\\');
    return parts[parts.length - 1];
};
</script>
