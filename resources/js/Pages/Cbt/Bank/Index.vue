<template>
    <AppLayout title="Manajemen Bank Soal">
        <CbtTabMenu />
        
        <div class="card">
            <div class="flex justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Bank Soal CBT</h2>
                    <span class="text-500 block mt-1">Buat dan kelola bank soal untuk ujian sekolah</span>
                </div>
                
                <div class="flex gap-2 align-items-center">
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="search" placeholder="Cari Bank Soal..." />
                    </IconField>
                    <Button label="Buat Bank Soal" icon="pi pi-plus" severity="primary" @click="openCreateModal" />
                </div>
            </div>

            <div class="surface-card p-4 shadow-2 border-round">
                <DataTable :value="banks.data" :rows="10" stripedRows tableStyle="min-width: 50rem" dataKey="id">
                    <template #empty> Belum ada data bank soal. </template>

                    <Column field="name" header="Nama Bank Soal" sortable>
                        <template #body="{ data }">
                            <span class="font-bold text-lg text-900">{{ data.name }}</span>
                            <div class="text-sm text-600 mt-1" v-if="data.description">{{ data.description }}</div>
                        </template>
                    </Column>

                    <Column header="Mata Pelajaran">
                        <template #body="{ data }">
                            <span class="font-semibold">{{ data.subject?.name || '-' }}</span>
                        </template>
                    </Column>

                    <Column header="Pembuat">
                        <template #body="{ data }">
                            <span class="text-600">{{ data.teacher?.full_name || '-' }}</span>
                        </template>
                    </Column>

                    <Column header="Jumlah Soal" style="width: 10%">
                        <template #body="{ data }">
                            <Tag :value="data.questions_count + ' Soal'" :severity="data.questions_count > 0 ? 'success' : 'warn'" />
                        </template>
                    </Column>

                    <Column header="Total Poin" style="width: 10%">
                        <template #body="{ data }">
                            <span class="font-bold text-green-600">{{ data.questions_sum_score ? Math.round(data.questions_sum_score) : 0 }} Pts</span>
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 25%">
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Link :href="route('cbt.bank.questions', data.id)">
                                    <Button icon="pi pi-search-plus" severity="info" text rounded v-tooltip.top="'Kelola Soal'" />
                                </Link>
                                <Link :href="route('cbt.bank.analytics', data.id)">
                                    <Button icon="pi pi-chart-bar" severity="help" text rounded v-tooltip.top="'Analisis Butir Soal & IRT'" />
                                </Link>
                                <Button icon="pi pi-file-word" severity="success" text rounded v-tooltip.top="'Import Soal'" @click="openImportModal(data)" />
                                <Button icon="pi pi-pencil" severity="warning" text rounded v-tooltip.top="'Edit'" @click="openEditModal(data)" />
                                <Button icon="pi pi-eraser" severity="danger" text rounded v-tooltip.top="'Kosongkan Soal'" @click="confirmClearQuestions(data)" :disabled="data.questions_count === 0" />
                                <Button icon="pi pi-trash" severity="danger" text rounded v-tooltip.top="'Hapus Bank Soal'" @click="confirmDelete(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
                <Pagination :links="banks.links" class="mt-4" />
            </div>
        </div>

        <!-- Create / Edit Dialog -->
        <Dialog v-model:visible="displayModal" :header="isEditing ? 'Edit Bank Soal' : 'Buat Bank Soal Baru'" :modal="true" :style="{ width: '500px' }">
            <form @submit.prevent="submitForm" class="p-fluid">
                <div class="field mb-3">
                    <label for="name" class="font-medium">Nama Bank Soal <span class="text-red-500">*</span></label>
                    <InputText id="name" v-model="form.name" class="w-full" :class="{'p-invalid': form.errors.name}" placeholder="Contoh: Penilaian Harian Matematika X" />
                    <small class="p-error" v-if="form.errors.name">{{ form.errors.name }}</small>
                </div>

                <div class="field mb-3">
                    <label for="subject" class="font-medium">Mata Pelajaran <span class="text-red-500">*</span></label>
                    <Select 
                        v-model="form.subject_id" 
                        :options="subjects" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Pilih Mata Pelajaran" 
                        class="w-full"
                        filter
                        :class="{'p-invalid': form.errors.subject_id}"
                    />
                    <small class="p-error" v-if="form.errors.subject_id">{{ form.errors.subject_id }}</small>
                </div>

                <div class="field mb-4">
                    <label for="description" class="font-medium">Deskripsi / Catatan</label>
                    <Textarea id="description" v-model="form.description" rows="3" class="w-full" placeholder="Masukkan deskripsi singkat..." />
                </div>

                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" severity="secondary" text @click="displayModal = false" />
                    <Button :label="isEditing ? 'Simpan' : 'Buat'" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>

        <!-- Import Soal Dialog -->
        <Dialog 
            v-model:visible="displayImportModal" 
            :header="'Import Soal — ' + selectedBankName" 
            :modal="true" 
            :style="{ width: '520px' }"
            @hide="resetImportForm"
        >
            <div class="flex flex-column gap-4">

                <!-- Panduan -->
                <div class="p-3 bg-blue-50 border-round border border-blue-200">
                    <p class="text-sm text-blue-800 m-0 font-semibold mb-2">
                        <i class="pi pi-info-circle mr-1"></i> Cara Import Soal
                    </p>
                    <ol class="text-sm text-blue-700 m-0 pl-4 line-height-3">
                        <li>Download template <strong>File Soal</strong> dan <strong>File Kunci</strong> di bawah.</li>
                        <li>Isi kedua file sesuai format.</li>
                        <li>Pastikan jumlah soal dan kode sub-ID di kedua file <strong>sama persis</strong>.</li>
                        <li>Upload kedua file lalu klik <strong>Proses Import</strong>.</li>
                    </ol>
                </div>

                <!-- Download Template -->
                <div class="flex flex-column gap-2">
                    <p class="text-sm font-semibold text-600 m-0">Download Template:</p>
                    <div class="flex gap-2">
                        <a :href="route('cbt.bank.template', { format: 'word' })" class="no-underline flex-1">
                            <Button label="Template Soal (.docx)" icon="pi pi-file-word" severity="info" outlined class="w-full" size="small" />
                        </a>
                        <a :href="route('cbt.bank.template', { format: 'excel' })" class="no-underline flex-1">
                            <Button label="Template Kunci (.xlsx)" icon="pi pi-file-excel" severity="success" outlined class="w-full" size="small" />
                        </a>
                    </div>
                </div>

                <Divider class="my-0" />

                <!-- Form Upload -->
                <form @submit.prevent="submitImport" class="p-fluid flex flex-column gap-3">

                    <!-- File Soal -->
                    <div class="field mb-0">
                        <label class="font-semibold mb-2 block text-sm">
                            <i class="pi pi-file-word text-blue-500 mr-1"></i>
                            File Soal <span class="text-red-500">*</span>
                            <span class="text-400 font-normal ml-1">(.docx)</span>
                        </label>
                        <div 
                            class="border-2 border-dashed border-round p-3 text-center cursor-pointer transition-colors duration-200"
                            :class="fileSoal ? 'border-blue-400 bg-blue-50' : 'border-300 hover:border-blue-300'"
                            @click="$refs.inputSoal.click()"
                            @dragover.prevent
                            @drop.prevent="handleSoalDrop"
                        >
                            <input 
                                ref="inputSoal"
                                type="file" 
                                class="hidden" 
                                accept=".docx"
                                @change="handleSoalUpload" 
                            />
                            <div v-if="fileSoal" class="flex align-items-center justify-content-center gap-2 text-blue-700">
                                <i class="pi pi-check-circle text-green-500"></i>
                                <span class="text-sm font-medium">{{ fileSoal.name }}</span>
                                <button 
                                    type="button" 
                                    class="ml-2 text-red-400 border-none bg-transparent cursor-pointer p-0"
                                    @click.stop="clearSoal"
                                >
                                    <i class="pi pi-times-circle"></i>
                                </button>
                            </div>
                            <div v-else class="text-400 text-sm">
                                <i class="pi pi-upload text-xl mb-1 block"></i>
                                Klik atau seret file soal .docx ke sini
                            </div>
                        </div>
                        <small class="p-error" v-if="importErrors.file_soal">{{ importErrors.file_soal }}</small>
                    </div>

                    <!-- File Kunci -->
                    <div class="field mb-0">
                        <label class="font-semibold mb-2 block text-sm">
                            <i class="pi pi-file-excel text-green-500 mr-1"></i>
                            File Kunci Jawaban <span class="text-red-500">*</span>
                            <span class="text-400 font-normal ml-1">(.xlsx)</span>
                        </label>
                        <div 
                            class="border-2 border-dashed border-round p-3 text-center cursor-pointer transition-colors duration-200"
                            :class="fileKunci ? 'border-green-400 bg-green-50' : 'border-300 hover:border-green-300'"
                            @click="$refs.inputKunci.click()"
                            @dragover.prevent
                            @drop.prevent="handleKunciDrop"
                        >
                            <input 
                                ref="inputKunci"
                                type="file" 
                                class="hidden" 
                                accept=".xlsx,.xls"
                                @change="handleKunciUpload" 
                            />
                            <div v-if="fileKunci" class="flex align-items-center justify-content-center gap-2 text-green-700">
                                <i class="pi pi-check-circle text-green-500"></i>
                                <span class="text-sm font-medium">{{ fileKunci.name }}</span>
                                <button 
                                    type="button" 
                                    class="ml-2 text-red-400 border-none bg-transparent cursor-pointer p-0"
                                    @click.stop="clearKunci"
                                >
                                    <i class="pi pi-times-circle"></i>
                                </button>
                            </div>
                            <div v-else class="text-400 text-sm">
                                <i class="pi pi-upload text-xl mb-1 block"></i>
                                Klik atau seret file kunci .xlsx ke sini
                            </div>
                        </div>
                        <small class="p-error" v-if="importErrors.file_kunci">{{ importErrors.file_kunci }}</small>
                    </div>

                    <!-- Status kecocokan -->
                    <div v-if="fileSoal && fileKunci" class="flex align-items-center gap-2 p-2 border-round bg-green-50 border border-green-200">
                        <i class="pi pi-check text-green-600"></i>
                        <span class="text-sm text-green-800">Kedua file siap diproses. Validasi kecocokan akan dilakukan di server.</span>
                    </div>

                    <small class="p-error block" v-if="importErrors.general">{{ importErrors.general }}</small>

                    <div class="flex justify-content-end gap-2 mt-2">
                        <Button label="Batal" severity="secondary" text type="button" @click="displayImportModal = false" />
                        <Button 
                            label="Proses Import" 
                            icon="pi pi-upload"
                            type="submit" 
                            :loading="isImporting"
                            :disabled="!fileSoal || !fileKunci"
                        />
                    </div>
                </form>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

import Pagination from '@/Components/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CbtTabMenu from '../CbtTabMenu.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Divider from 'primevue/divider';

const props = defineProps({
    banks: Object,
    subjects: Array,
    filters: Object,
});

const confirm = useConfirm();
const toast = useToast();
const displayModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const search = ref(props.filters?.search || '');
const displayImportModal = ref(false);
const selectedBankName = ref('');
const selectedBankId = ref(null);

// Import state
const fileSoal = ref(null);
const fileKunci = ref(null);
const isImporting = ref(false);
const importErrors = ref({});

const form = useForm({
    name: '',
    subject_id: null,
    description: '',
});

// SEARCH TIMEOUT
let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('cbt.bank.index'), { search: val }, { preserveState: true, replace: true, preserveScroll: true });
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
    form.subject_id = data.subject_id;
    form.description = data.description || '';
    displayModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('cbt.bank.update', editId.value), {
            onSuccess: () => {
                displayModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('cbt.bank.store'), {
            onSuccess: () => {
                displayModal.value = false;
                form.reset();
            }
        });
    }
};

const openImportModal = (data) => {
    selectedBankName.value = data.name;
    selectedBankId.value = data.id;
    resetImportForm();
    displayImportModal.value = true;
};

const resetImportForm = () => {
    fileSoal.value = null;
    fileKunci.value = null;
    importErrors.value = {};
    isImporting.value = false;
};

// File Soal handlers
const handleSoalUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        fileSoal.value = file;
        importErrors.value = { ...importErrors.value, file_soal: null };
    }
};
const handleSoalDrop = (event) => {
    const file = event.dataTransfer.files[0];
    if (file && file.name.endsWith('.docx')) {
        fileSoal.value = file;
        importErrors.value = { ...importErrors.value, file_soal: null };
    } else {
        toast.add({ severity: 'warn', summary: 'Format Salah', detail: 'File Soal harus berformat .docx', life: 3000 });
    }
};
const clearSoal = () => { fileSoal.value = null; };

// File Kunci handlers
const handleKunciUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        fileKunci.value = file;
        importErrors.value = { ...importErrors.value, file_kunci: null };
    }
};
const handleKunciDrop = (event) => {
    const file = event.dataTransfer.files[0];
    if (file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls'))) {
        fileKunci.value = file;
        importErrors.value = { ...importErrors.value, file_kunci: null };
    } else {
        toast.add({ severity: 'warn', summary: 'Format Salah', detail: 'File Kunci harus berformat .xlsx', life: 3000 });
    }
};
const clearKunci = () => { fileKunci.value = null; };

const submitImport = async () => {
    importErrors.value = {};

    if (!fileSoal.value) {
        importErrors.value.file_soal = 'File Soal (.docx) wajib dipilih.';
    }
    if (!fileKunci.value) {
        importErrors.value.file_kunci = 'File Kunci (.xlsx) wajib dipilih.';
    }
    if (!fileSoal.value || !fileKunci.value) return;

    isImporting.value = true;

    const formData = new FormData();
    formData.append('file_soal', fileSoal.value);
    formData.append('file_kunci', fileKunci.value);
    formData.append('_method', 'POST');

    try {
        await axios.post(route('cbt.bank.import', selectedBankId.value), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Soal berhasil di-import!', life: 4000 });
        displayImportModal.value = false;
        resetImportForm();
        router.reload({ only: ['banks'] });

    } catch (error) {
        const resp = error.response;
        if (resp?.status === 422 && resp?.data?.errors) {
            const errs = resp.data.errors;
            importErrors.value = {
                file_soal: errs.file_soal?.[0] || null,
                file_kunci: errs.file_kunci?.[0] || null,
                general: errs.general?.[0] || null,
            };
        } else {
            const msg = resp?.data?.message || resp?.data?.error || 'Terjadi kesalahan saat import.';
            importErrors.value.general = msg;
            toast.add({ severity: 'error', summary: 'Gagal Import', detail: msg, life: 6000 });
        }
    } finally {
        isImporting.value = false;
    }
};

const confirmClearQuestions = (data) => {
    confirm.require({
        message: `Apakah Anda yakin ingin mengosongkan/menghapus <b>semua soal</b> di dalam bank soal <b>${data.name}</b>? Tindakan ini tidak dapat dibatalkan.`,
        header: 'Kosongkan Bank Soal',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('cbt.bank.clear', data.id), {}, {
                onSuccess: () => {}
            });
        }
    });
};

const confirmDelete = (data) => {
    confirm.require({
        message: `Hapus bank soal <b>${data.name}</b> secara permanen? Semua soal di dalamnya juga akan terhapus.`,
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('cbt.bank.destroy', data.id), {
                onSuccess: () => {}
            });
        }
    });
};
</script>
