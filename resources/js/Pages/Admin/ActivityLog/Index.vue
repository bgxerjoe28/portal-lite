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
                            Pantau riwayat aksi administratif (sesi ujian, jadwal, bank soal, penugasan, akun user) dan autentikasi secara transparan.
                        </span>
                    </div>
                </div>
                <div class="flex align-items-center gap-2">
                    <Tag severity="info" value="Append-Only Audit Log" icon="pi pi-shield" />
                    <span class="text-sm font-bold text-600">Total: {{ logs.total }} Catatan</span>
                </div>
            </div>

            <!-- QUICK CATEGORY TABS & STUDENT LOGIN TOGGLE -->
            <div class="flex flex-column lg:flex-row align-items-stretch lg:align-items-center justify-content-between gap-3 mb-3">
                <!-- Category Pills -->
                <div class="flex flex-wrap gap-2">
                    <Button 
                        :label="`Semua Log (${counts.all || 0})`" 
                        icon="pi pi-list" 
                        size="small"
                        :outlined="currentCategory !== null" 
                        :severity="currentCategory === null ? 'primary' : 'secondary'"
                        @click="selectCategory(null)" 
                    />
                    <Button 
                        :label="`Kesiswaan & Ekstra (${counts.kesiswaan || 0})`" 
                        icon="pi pi-star" 
                        size="small"
                        :outlined="currentCategory !== 'kesiswaan'" 
                        :severity="currentCategory === 'kesiswaan' ? 'success' : 'secondary'"
                        @click="selectCategory('kesiswaan')" 
                    />
                    <Button 
                        :label="`CBT & Sesi Ujian (${counts.cbt || 0})`" 
                        icon="pi pi-desktop" 
                        size="small"
                        :outlined="currentCategory !== 'cbt'" 
                        :severity="currentCategory === 'cbt' ? 'help' : 'secondary'"
                        @click="selectCategory('cbt')" 
                    />
                    <Button 
                        :label="`Penugasan & Nilai (${counts.assignment || 0})`" 
                        icon="pi pi-file-edit" 
                        size="small"
                        :outlined="currentCategory !== 'assignment'" 
                        :severity="currentCategory === 'assignment' ? 'warn' : 'secondary'"
                        @click="selectCategory('assignment')" 
                    />
                    <Button 
                        :label="`Akademik & User (${counts.academic || 0})`" 
                        icon="pi pi-users" 
                        size="small"
                        :outlined="currentCategory !== 'academic'" 
                        :severity="currentCategory === 'academic' ? 'info' : 'secondary'"
                        @click="selectCategory('academic')" 
                    />
                    <Button 
                        :label="`Autentikasi & Login (${counts.auth || 0})`" 
                        icon="pi pi-sign-in" 
                        size="small"
                        :outlined="currentCategory !== 'auth'" 
                        :severity="currentCategory === 'auth' ? 'contrast' : 'secondary'"
                        @click="selectCategory('auth')" 
                    />
                </div>

                <!-- Toggle Sembunyikan Login Siswa -->
                <div class="flex align-items-center gap-2 bg-blue-50 border-1 border-blue-200 border-round-xl px-3 py-2">
                    <ToggleSwitch 
                        v-model="hideStudentLogins" 
                        @change="toggleHideStudentLogins"
                    />
                    <div class="flex flex-column cursor-pointer" @click="toggleHideStudentLoginsDirectly">
                        <span class="text-xs font-bold text-blue-900 line-height-1">Sembunyikan Login Siswa</span>
                        <small class="text-blue-600 text-xs mt-1">Fokuskan pada aksi operasional guru & admin</small>
                    </div>
                </div>
            </div>

            <!-- ADVANCED FILTER BAR -->
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
                        <label class="block text-sm font-bold text-700 mb-1">Jenis Aksi Spesifik</label>
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
                <Column header="Waktu" style="width: 140px">
                    <template #body="slotProps">
                        <div class="text-sm font-semibold text-900">
                            {{ formatDateTime(slotProps.data.created_at) }}
                        </div>
                    </template>
                </Column>

                <Column header="Pengguna / Pelaku" style="width: 210px">
                    <template #body="slotProps">
                        <div class="flex flex-column">
                            <span class="font-bold text-900">{{ slotProps.data.user_name || 'System / Guest' }}</span>
                            <span v-if="slotProps.data.user_email" class="text-xs text-500">{{ slotProps.data.user_email }}</span>
                            <div class="mt-1 flex align-items-center gap-1">
                                <Tag
                                    :value="slotProps.data.role || 'system'"
                                    :severity="getRoleSeverity(slotProps.data.role)"
                                    class="text-xs uppercase"
                                />
                            </div>
                        </div>
                    </template>
                </Column>

                <Column header="Jenis Aksi" style="width: 180px">
                    <template #body="slotProps">
                        <Tag
                            :value="formatActionLabel(slotProps.data.action)"
                            :severity="getActionSeverity(slotProps.data.action)"
                            class="font-bold text-xs"
                        />
                    </template>
                </Column>

                <Column header="Deskripsi & Sasaran Aktivitas">
                    <template #body="slotProps">
                        <div class="text-900 font-medium line-height-3">
                            {{ slotProps.data.description }}
                        </div>
                        <div v-if="slotProps.data.subject_type" class="text-xs text-600 mt-1 font-mono">
                            Target: <span class="text-primary font-bold">{{ cleanModelName(slotProps.data.subject_type) }}</span>
                            <span v-if="slotProps.data.subject_id"> #{{ slotProps.data.subject_id }}</span>
                        </div>
                    </template>
                </Column>

                <Column header="IP & Perangkat" style="width: 140px">
                    <template #body="slotProps">
                        <div class="text-xs font-mono text-700">
                            {{ slotProps.data.ip_address || '-' }}
                        </div>
                        <div class="text-xs text-500 white-space-nowrap overflow-hidden text-overflow-ellipsis" style="max-width: 130px;" :title="slotProps.data.user_agent">
                            {{ slotProps.data.user_agent || '-' }}
                        </div>
                    </template>
                </Column>

                <Column header="Audit Diff" style="width: 110px; text-align: center;">
                    <template #body="slotProps">
                        <Button
                            v-if="slotProps.data.old_values || slotProps.data.new_values"
                            icon="pi pi-eye"
                            label="Diff"
                            size="small"
                            class="p-button-outlined p-button-info font-bold"
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
import ToggleSwitch from 'primevue/toggleswitch';

const props = defineProps({
    logs: Object,
    filters: Object,
    counts: Object,
    availableRoles: Array,
    availableActions: Array,
});

const currentCategory = ref(props.filters.category || null);
const hideStudentLogins = ref(props.filters.hide_student_logins === 'true' || props.filters.hide_student_logins === true);

const filterForm = ref({
    search: props.filters.search || '',
    role: props.filters.role || null,
    action: props.filters.action || null,
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    category: props.filters.category || null,
    hide_student_logins: hideStudentLogins.value ? 'true' : 'false',
});

const selectCategory = (cat) => {
    currentCategory.value = cat;
    filterForm.value.category = cat;
    applyFilter();
};

const toggleHideStudentLogins = () => {
    filterForm.value.hide_student_logins = hideStudentLogins.value ? 'true' : 'false';
    applyFilter();
};

const toggleHideStudentLoginsDirectly = () => {
    hideStudentLogins.value = !hideStudentLogins.value;
    toggleHideStudentLogins();
};

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
        list.push({ label: formatActionLabel(act), value: act });
    });
    return list;
});

const applyFilter = () => {
    router.get(route('admin.activity-logs.index'), {
        ...filterForm.value,
        category: currentCategory.value,
        hide_student_logins: hideStudentLogins.value ? 'true' : 'false',
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    currentCategory.value = null;
    hideStudentLogins.value = true;
    filterForm.value = {
        search: '',
        role: null,
        action: null,
        date_from: '',
        date_to: '',
        category: null,
        hide_student_logins: 'true',
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

const formatActionLabel = (action) => {
    if (!action) return '-';
    return action.replace(/_/g, ' ');
};

const getActionSeverity = (action) => {
    if (!action) return 'primary';
    const a = action.toUpperCase();
    if (a.includes('DELETE') || a.includes('FAIL') || a.includes('RESET') || a.includes('DISABLE')) return 'danger';
    if (a.includes('CREATE') || a.includes('IMPORT') || a.includes('START') || a.includes('ENABLE') || a === 'LOGIN') return 'success';
    if (a.includes('UPDATE') || a.includes('GRADE') || a.includes('TOGGLE')) return 'warn';
    if (a.includes('PROCTOR') || a.includes('CBT') || a.includes('TOKEN') || a.includes('REOPEN') || a.includes('REENTER')) return 'help';
    if (a === 'LOGOUT') return 'secondary';
    return 'info';
};

const cleanModelName = (str) => {
    if (!str) return '';
    const parts = str.split('\\');
    return parts[parts.length - 1];
};
</script>

