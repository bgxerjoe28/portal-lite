<template>
    <AppLayout>
        
        <!-- ===== CARD HEADER ===== -->
        <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
            <div class="flex justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Master Mata Pelajaran</h2>
                    <span class="text-600 text-sm">Atur nama mapel dan distribusinya.</span>
                </div>

                <div class="flex gap-2 align-items-center">
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search" />
                        <InputText 
                            v-model="search" 
                            placeholder="Cari Kode/Nama..." 
                            @input="handleSearch" 
                            class="w-full md:w-15rem" 
                        />
                    </IconField>
                    
                    <Button 
                        v-if="!filters.trash"
                        icon="pi pi-trash" 
                        label="Data Arsip" 
                        severity="danger" 
                        outlined 
                        @click="toggleTrash" 
                    />
                    <Button 
                        v-else
                        icon="pi pi-arrow-left" 
                        label="Kembali" 
                        severity="secondary" 
                        outlined 
                        @click="toggleTrash" 
                    />

                    <Button 
                        v-if="!filters.trash" 
                        label="Import Excel" 
                        icon="pi pi-file-excel" 
                        severity="success" 
                        outlined 
                        @click="openImportModal" 
                    />
                    
                    <Button 
                        v-if="!filters.trash" 
                        label="Tambah" 
                        icon="pi pi-plus" 
                        @click="openCreateModal" 
                    />
                </div>
            </div>
        </div>

        <!-- ===== CARD TABLE ===== -->
        <div class="surface-card p-4 border-round-lg shadow-1">
            <DataTable :value="subjects.data" stripedRows showGridlines tableStyle="min-width: 50rem">
                <template #empty>Belum ada data mata pelajaran.</template>

                <Column field="code" header="Kode" style="width: 10%">
                    <template #body="{ data }">
                        <Tag :value="data.code" severity="info" />
                    </template>
                </Column>
                
                <Column field="name" header="Nama Mata Pelajaran" sortable></Column>
                
                <Column header="Sebaran" style="width: 25%">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Badge v-if="hasMapping(data, 10)" value="X" severity="info" v-tooltip.top="'Diajarkan di Kelas 10'" />
                            <Badge v-else value="X" severity="secondary" style="opacity: 0.3" />

                            <Badge v-if="hasMapping(data, 11)" value="XI" severity="info" v-tooltip.top="'Diajarkan di Kelas 11'" />
                            <Badge v-else value="XI" severity="secondary" style="opacity: 0.3" />

                            <Badge v-if="hasMapping(data, 12)" value="XII" severity="info" v-tooltip.top="'Diajarkan di Kelas 12'" />
                            <Badge v-else value="XII" severity="secondary" style="opacity: 0.3" />
                        </div>
                    </template>
                </Column>
                <Column header="Jenis" style="width: 12%">
                    <template #body="{ data }">
                        <Tag
                            v-if="data.is_religion"
                            value="Agama"
                            severity="warning"
                            icon="pi pi-book"
                        />
                        <Tag
                            v-else
                            value="Umum"
                            severity="secondary"
                            icon="pi pi-tags"
                        />
                    </template>
                </Column>

                <Column header="Aksi" style="width: 15%">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            
                            <template v-if="!filters.trash">
                                <Button icon="pi pi-cog" severity="help" text rounded v-tooltip.top="'Atur Mapping'" @click="openMappingModal(data)" />
                                <Button icon="pi pi-pencil" severity="warning" text rounded @click="openEditModal(data)" />
                                <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmDelete(data)" />
                            </template>

                            <template v-else>
                                <Button icon="pi pi-refresh" severity="success" text rounded v-tooltip.top="'Pulihkan'" @click="confirmRestore(data)" />
                                <Button icon="pi pi-times" severity="danger" text rounded v-tooltip.top="'Hapus Permanen'" @click="confirmForceDelete(data)" />
                            </template>

                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="mt-4 flex justify-content-center" v-if="subjects.links">
                <template v-for="(link, k) in subjects.links" :key="k">
                    <Link v-if="link.url" :href="link.url" class="p-button p-component p-button-sm mx-1" :class="{'p-button-outlined': !link.active}" v-html="link.label" />
                </template>
            </div>
        </div>

        <Dialog v-model:visible="displayModal" :header="isEditing ? 'Edit Mapel' : 'Tambah Mapel'" :modal="true" :style="{ width: '450px' }">
            <form @submit.prevent="submitForm">
                <div class="field mb-3">
                    <label class="font-bold">Kode Mapel</label>
                    <InputText v-model="form.code" placeholder="Contoh: MTK" class="w-full" :class="{'p-invalid': form.errors.code}" />
                    <small class="p-error">{{ form.errors.code }}</small>
                </div>
                <div class="field mb-4">
                    <label class="font-bold">Nama Mata Pelajaran</label>
                    <InputText v-model="form.name" class="w-full" :class="{'p-invalid': form.errors.name}" />
                    <small class="p-error">{{ form.errors.name }}</small>
                </div>
                <div class="field-checkbox">
                    <Checkbox 
                        v-model="form.is_religion" 
                        :binary="true" 
                    />
                    <label class="ml-2">Mapel Agama (paralel per agama)</label>
                </div>
                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" severity="secondary" text @click="displayModal = false" />
                    <Button :label="isEditing ? 'Simpan' : 'Tambah'" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>

        <Dialog v-model:visible="displayMappingModal" header="Mapping Mata Pelajaran" :modal="true" :style="{ width: '500px' }">
            <div v-if="selectedSubject" class="mb-4">
                <div class="text-xl font-bold text-primary">{{ selectedSubject.name }} ({{ selectedSubject.code }})</div>
                <p class="text-sm text-600 m-0">Tentukan kelompok mapel ini untuk setiap tingkat kelas.</p>
            </div>

            <form @submit.prevent="submitMapping">
                <div v-for="(item, index) in mappingForm.mappings" :key="item.level" class="surface-ground p-3 border-round mb-3">
                    <div class="font-bold mb-2">Kelas {{ item.level }} ({{ getRoman(item.level) }})</div>
                    
                    <div class="flex align-items-center gap-2">
                        <Select 
                            v-model="item.group_id" 
                            :options="groups" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Tidak diajarkan di tingkat ini" 
                            class="w-full" 
                            showClear
                        />
                    </div>
                </div>

                <div class="flex justify-content-end gap-2 mt-4">
                    <Button label="Tutup" severity="secondary" text @click="displayMappingModal = false" />
                    <Button label="Simpan Mapping" icon="pi pi-check" type="submit" :loading="mappingForm.processing" />
                </div>
            </form>
        </Dialog>

        <Dialog v-model:visible="displayImportModal" header="Import Master Mapel" :modal="true" :style="{ width: '400px' }">
            <div class="text-sm text-600 mb-3">
                <p>File Excel harus memiliki kolom: <b>kode</b> dan <b>nama_mapel</b>.</p>
            <a :href="route('admin.subjects.template')" class="no-underline">
                <Button label="Download Template Excel" icon="pi pi-download" severity="secondary" outlined class="w-full" />
            </a>
                <a href="#" class="text-primary no-underline hover:underline">Download Template</a>
            </div>
            
            <form @submit.prevent="submitImport">
                <div class="field">
                    <input type="file" @change="handleFileUpload" class="w-full p-2 border border-300 border-round" accept=".xlsx, .xls" />
                    <small class="p-error" v-if="importForm.errors.file">{{ importForm.errors.file }}</small>
                </div>
                <div class="flex justify-content-end mt-3">
                    <Button label="Upload" type="submit" severity="success" :loading="importForm.processing" />
                </div>
            </form>
        </Dialog>

    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import debounce from 'lodash/debounce';

import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge'; // Butuh ini untuk indikator X/XI/XII
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Checkbox from 'primevue/checkbox';

const props = defineProps({
    subjects: Object,
    filters: Object,
    groups: Array // Data Master Kelompok (Wajib A, B, C, Mulok)
});

const toast = useToast();
const confirm = useConfirm();
const search = ref(props.filters.search || '');

// --- STATE MODALS ---
const displayModal = ref(false); // Create/Edit
const displayImportModal = ref(false); // Import
const displayMappingModal = ref(false); // Mapping

const isEditing = ref(false);
const selectedSubject = ref(null);

// --- FORM UTAMA (NAMA & KODE) ---
const form = useForm({
    id: null,
    code: '',
    name: '',
    is_religion: false,
});

// --- FORM MAPPING ---
const mappingForm = useForm({
    mappings: [] // Akan berisi array object [{level: 10, group_id: 1}, ...]
});

// --- FORM IMPORT ---
const importForm = useForm({
    file: null
});

// =================================================================
// HELPER FUNCTIONS
// =================================================================
const getRoman = (num) => {
    if (num == 10) return 'X';
    if (num == 11) return 'XI';
    if (num == 12) return 'XII';
    return num;
};

// Cek apakah mapel ini punya mapping di level tertentu (untuk UI Badge)
const hasMapping = (subject, level) => {
    if (!subject.mappings) return false;
    return subject.mappings.some(m => m.level == level);
};

// =================================================================
// CRUD HANDLERS
// =================================================================

// 1. CREATE / EDIT MASTER
const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    displayModal.value = true;
};

const openEditModal = (data) => {
    isEditing.value = true;
    form.id = data.id;
    form.code = data.code;
    form.name = data.name;
    form.is_religion = data.is_religion;
    form.clearErrors();
    displayModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.subjects.update', form.id), {
            onSuccess: () => { 
                displayModal.value = false;                 
                form.reset(); },
        });
    } else {
        form.post(route('admin.subjects.store'), {
            onSuccess: () => { 
                displayModal.value = false; 
                form.reset(); }
        });
    }
};
const confirmDelete = (data) => {
    confirm.require({
        message: `Hapus mapel ${data.name}?`,
        header: 'Konfirmasi',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.subjects.destroy', data.id));
        }
    });
};

// =================================================================
// MAPPING HANDLERS (LOGIC INTI)
// =================================================================

const openMappingModal = (subject) => {
    selectedSubject.value = subject;
    
    // Siapkan struktur form untuk kelas 10, 11, 12
    const levels = [10, 11, 12];
    
    // Map data yang sudah ada di database ke form
    const preparedMappings = levels.map(level => {
        // Cari apakah mapel ini sudah punya settingan untuk level ini?
        const existing = subject.mappings.find(m => m.level == level);
        
        return {
            level: level,
            // Jika ada, ambil group_id nya. Jika tidak, null.
            group_id: existing ? existing.subject_group_id : null
        };
    });

    mappingForm.mappings = preparedMappings;
    displayMappingModal.value = true;
};

const submitMapping = () => {
    mappingForm.put(route('admin.subjects.mapping.update', selectedSubject.value.id), {
        onSuccess: () => {
            displayMappingModal.value = false;
        }
    });
};

// =================================================================
// IMPORT HANDLERS
// =================================================================
const openImportModal = () => {
    importForm.reset();
    importForm.clearErrors();
    displayImportModal.value = true;
};

const handleFileUpload = (event) => {
    importForm.file = event.target.files[0];
};

const submitImport = () => {
    importForm.post(route('admin.subjects.import'), {
        onSuccess: () => {
            displayImportModal.value = false;            
        },
        onError: () => {
             toast.add({ severity: 'error', summary: 'Gagal', detail: 'Cek file excel anda', life: 5000 });
        }
    });
};

// SEARCH
const handleSearch = debounce(() => {
    router.get(route('admin.subjects.index'), { search: search.value }, { preserveState: true, replace: true });
}, 300);

// TOGGLE TRASH VIEW
const toggleTrash = () => {
    router.get(route('admin.subjects.index'), {
        trash: props.filters.trash ? null : 'true', // Toggle
        search: search.value
    }, { preserveState: true });
};

const confirmRestore = (data) => {
    confirm.require({
        message: `Pulihkan mapel <b>${data.name}</b>?<br>Mapping kelas juga akan dikembalikan.`,
        header: 'Konfirmasi Restore',
        icon: 'pi pi-refresh',
        acceptClass: 'p-button-success',
        accept: () => {
            router.put(route('admin.subjects.restore', data.id));
        }
    });
};

const confirmForceDelete = (data) => {
    confirm.require({
        message: `Hapus PERMANEN <b>${data.name}</b>?<br>Data tidak bisa kembali!`,
        header: 'Hapus Permanen',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.subjects.force-delete', data.id));
        }
    });
};
</script>