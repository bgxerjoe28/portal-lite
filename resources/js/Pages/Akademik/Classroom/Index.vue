<template>
    <AppLayout>
        
        <!-- ===== CARD HEADER ===== -->
        <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
            <div class="flex justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">
                        {{ filters.trash === 'true' ? 'Arsip Kelas (Dihapus)' : 'Manajemen Kelas' }}
                    </h2>
                    <span class="text-600 text-sm block mt-1" v-if="activeYear">
                        Tahun Ajaran Aktif: <b>{{ activeYear.name }}</b> ({{ activeYear.semester }})
                    </span>
                </div>
                
                <div class="flex gap-2 align-items-center">
                    <Button 
                        :label="filters.trash === 'true' ? 'Lihat Data Aktif' : 'Data Arsip'" 
                        :icon="filters.trash === 'true' ? 'pi pi-list' : 'pi pi-trash'" 
                        :severity="filters.trash === 'true' ? 'primary' : 'secondary'" 
                        outlined
                        v-tooltip.top="filters.trash === 'true' ? 'Kembali ke data kelas aktif' : 'Lihat data kelas yang diarsipkan'"
                        @click="toggleTrash"
                    />

                    <IconField iconPosition="left" v-if="filters.trash !== 'true'">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="search" placeholder="Cari Kelas..." />
                    </IconField>

                    <Button 
                        label="Salin Kelas" 
                        icon="pi pi-copy" 
                        severity="secondary" 
                        outlined
                        v-tooltip.top="'Salin kelas dari tahun ajaran sebelumnya'" 
                        @click="confirmCopyPreviousYear" 
                        v-if="filters.trash !== 'true' && classrooms.data.length === 0 && activeYear" />
                    
                    <Link :href="route('admin.classrooms.promotion.index')" v-if="filters.trash !== 'true'">
                        <Button 
                            label="Kenaikan Kelas" 
                            icon="pi pi-arrow-up-right" 
                            severity="help" 
                            outlined
                            v-tooltip.top="'Proses kenaikan kelas / kelulusan siswa secara massal'" />
                    </Link>
                    <Button 
                        label="Plotting " 
                        icon="pi pi-users" 
                        severity="help" 
                        v-tooltip.top="'Plotting Siswa ke Kelas Secara Massal'"
                        @click="openMemberImportModal" 
                        v-if="filters.trash !== 'true'" />
                    <Button 
                        label="Import " 
                        icon="pi pi-file-excel" 
                        severity="success"
                        v-tooltip.top="'Import Data Kelas dari File Excel'" 
                        @click="openImportModal" 
                        v-if="filters.trash !== 'true'" />
                    <Button 
                        label="Buat " 
                        icon="pi pi-plus" 
                        severity="primary"
                        v-tooltip.top="'Buat Kelas Baru'"
                        @click="openCreateModal" 
                        :disabled="!activeYear" 
                        v-if="filters.trash !== 'true'" />
                </div>
                
            </div>

        </div>

        <!-- ===== CARD TABLE ===== -->
        <div class="surface-card p-4 border-round-lg shadow-1">
                <DataTable :value="classrooms.data" :rows="10" stripedRows tableStyle="min-width: 50rem" dataKey="id">
                    <template #empty> Belum ada data kelas. </template>

                    <Column header="Status" style="width: 10%">
                        <template #body="{ data }">
                            <Tag :severity="data.deleted_at ? 'danger' : 'success'" :value="data.deleted_at ? 'Non-Aktif' : 'Aktif'" />
                        </template>
                    </Column>

                    <Column field="level" header="Tingkat" sortable style="width: 10%">
                        <template #body="{ data }">
                            <Tag :value="data.level" severity="info" />
                        </template>
                    </Column>

                    <Column field="name" header="Nama Kelas" sortable>
                        <template #body="{ data }">
                            <span class="font-bold text-lg">{{ data.name }}</span>
                            <div class="text-sm text-500" v-if="data.major">{{ data.major }}</div>
                        </template>
                    </Column>

                    <Column header="Wali Kelas">
                        <template #body="{ data }">
                            <div v-if="data.teacher" class="flex align-items-center gap-2">
                                <i class="pi pi-user text-primary"></i>
                                <span>
                                    {{data.teacher.full_name}}
                                </span>
                            </div>
                            <span v-else class="text-red-500 italic text-sm">Belum ditentukan</span>
                        </template>
                    </Column>
                    <Column header="Jml Siswa" style="width: 10%">
                        <template #body="{ data }">
                            <div class="flex justify-content-center">
                                <Tag 
                                    :value="data.students_count" 
                                    :severity="data.students_count > 0 ? 'info' : 'warning'" 
                                    v-tooltip.top="'Jumlah Siswa Aktif'"
                                />
                            </div>
                        </template>
                    </Column>

                    <Column header="Tahun Ajaran">
                        <template #body="{ data }">
                           {{ data.academic_year?.name }}
                        </template>
                    </Column>
                    
                    <Column header="Aksi" style="width: 15%">
                        <template #body="{ data }">
                            <div class="flex gap-2" :key="data.id + (data.deleted_at ? '-trash' : '-active')">
                                <template v-if="!data.deleted_at">
                                    
                                    <Link :href="route('admin.classrooms.show', data.id)">
                                        <Button icon="pi pi-users" severity="info" text rounded v-tooltip.top="'Detail Anggota'" />
                                    </Link>
                                    
                                    <Button icon="pi pi-pencil" severity="warning" text rounded @click="openEditModal(data)" />
                                    <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmDelete(data)" />
                                </template>
                                <template v-else>
                                    <Button icon="pi pi-refresh" severity="success" text rounded @click="confirmRestore(data)" />
                                    <Button icon="pi pi-times" severity="danger" text rounded @click="confirmForceDelete(data)" v-tooltip.top="'Hapus Permanen'" />
                                </template>
                                </div>
                        </template>
                    </Column>
                </DataTable>
                <Pagination :links="classrooms.links" />
            </div>

        <Dialog v-model:visible="displayModal" :header="isEditing ? 'Edit Kelas' : 'Buat Kelas Baru'" :modal="true" :style="{ width: '600px' }">
            <form @submit.prevent="submitForm">
                
                <div class="field mb-3">
                    <label class="font-medium">Tingkat Kelas</label>
                    <div class="flex gap-3">
                        <div v-for="lvl in [10, 11, 12]" :key="lvl" class="flex align-items-center">
                            <RadioButton v-model="form.level" :inputId="'lvl'+lvl" name="level" :value="lvl" />
                            <label :for="'lvl'+lvl" class="ml-2">{{ lvl }}</label>
                        </div>
                    </div>
                </div>

                <div class="formgrid grid">
                    <div class="field col-8 mb-3">
                        <label for="name" class="font-medium">Nama Kelas (Contoh: X IPA 1)</label>
                        <InputText id="name" v-model="form.name" class="w-full" :class="{'p-invalid': form.errors.name}" />
                        <small class="p-error">{{ form.errors.name }}</small>
                    </div>
                    
                    <div class="field col-4 mb-3">
                        <label for="major" class="font-medium">Jurusan</label>
                        <InputText id="major" v-model="form.major" class="w-full" placeholder="IPA/IPS/MERDEKA" />
                    </div>
                </div>

                <div class="field mb-4">
                    <label class="font-medium mb-2 block">Wali Kelas</label>
                    <Select 
                        v-model="form.teacher_id" 
                        :options="teachers" 
                        optionLabel="full_name" 
                        optionValue="id" 
                        placeholder="Pilih Wali Kelas" 
                        class="w-full"
                        filter
                        showClear
                    >
                        <template #option="slotProps">
                            <span>{{ slotProps.option.full_name }}</span>
                        </template>
                    </Select>
                </div>

                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" severity="secondary" text @click="displayModal = false" />
                    <Button :label="isEditing ? 'Simpan Perubahan' : 'Buat Kelas'" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
        <Dialog v-model:visible="displayImportModal" header="Import Data Kelas" :modal="true" :style="{ width: '400px' }">
            <div class="flex flex-column gap-3">
                <p class="text-sm text-600">
                    Pastikan NIP Wali Kelas sudah terdaftar di Data Guru. Kelas akan otomatis masuk ke <b>Tahun Ajaran Aktif</b>.
                </p>
                
                <a :href="route('admin.classrooms.template')" class="no-underline">
                    <Button label="Download Template Excel" icon="pi pi-download" severity="secondary" outlined class="w-full" />
                </a>

                <Divider />

                <form @submit.prevent="submitImport">
                    <div class="field">
                        <label class="font-medium mb-2 block">Pilih File Excel (.xlsx)</label>
                        <input type="file" @change="handleFileUpload" class="w-full p-2 border border-300 border-round" accept=".xlsx, .xls" />
                        <small class="p-error" v-if="importForm.errors.file">{{ importForm.errors.file }}</small>
                    </div>
                    
                    <div class="flex justify-content-end mt-3">
                        <Button label="Proses Import" type="submit" :loading="importForm.processing" />
                    </div>
                </form>
            </div>
        </Dialog>
        <Dialog v-model:visible="displayMemberModal" :header="'Tambah Siswa ke ' + selectedClassroomName" :modal="true" :style="{ width: '500px' }">
            <div class="mb-3 text-sm text-600">
                Pilih siswa baru / pindahan yang belum memiliki kelas untuk dimasukkan ke kelas ini.
            </div>

            <form @submit.prevent="submitMember">
                <div class="field">
                    <label class="font-bold mb-2 block">Pilih Siswa</label>
                    <MultiSelect 
                        v-model="memberForm.student_ids" 
                        :options="availableStudents" 
                        optionLabel="full_name" 
                        optionValue="id" 
                        placeholder="Pilih Siswa (Bisa banyak)" 
                        display="chip" 
                        filter
                        class="w-full"
                    >
                        <template #option="slotProps">
                            <div class="flex flex-column">
                                <span class="font-bold">{{ slotProps.option.full_name }}</span>
                                <span class="text-sm text-500">NIS: {{ slotProps.option.nis || '-' }}</span>
                            </div>
                        </template>
                    </MultiSelect>
                    <small class="block mt-2" v-if="availableStudents.length === 0">
                        Semua siswa sudah punya kelas! Tambah siswa baru dulu di menu Siswa.
                    </small>
                </div>

                <div class="flex justify-content-end gap-2 mt-4">
                    <Button label="Tutup" severity="secondary" text @click="displayMemberModal = false" />
                    <Button label="Simpan Anggota" icon="pi pi-check" type="submit" :loading="memberForm.processing" :disabled="memberForm.student_ids.length === 0" />
                </div>
            </form>
        </Dialog>
        <Dialog v-model:visible="displayMemberImportModal" header="Plotting Siswa ke Kelas" :modal="true" :style="{ width: '450px' }">
            <div class="flex flex-column gap-3">
                <div class="text-sm text-600 surface-ground p-3 border-round">
                    <i class="pi pi-info-circle mr-2 text-primary"></i>
                    Fitur ini untuk memasukkan siswa ke dalam kelas secara massal berdasarkan <b>NISN</b>.
                    <ul class="pl-3 mt-2 mb-0">
                        <li>Pastikan <b>NISN</b> siswa sudah terdaftar.</li>
                        <li>Pastikan <b>Nama Kelas</b> (ex: X IPA 1) sudah dibuat.</li>
                    </ul>
                </div>
                
                <a :href="route('admin.classrooms.members.template')" class="no-underline">
                    <Button label="Download Template Plotting" icon="pi pi-download" severity="secondary" outlined class="w-full" />
                </a>

                <Divider />

                <form @submit.prevent="submitMemberImport">
                    <div class="field">
                        <label class="font-medium mb-2 block">Pilih File Excel (.xlsx)</label>
                        <input type="file" @change="handleMemberFileUpload" class="w-full p-2 border border-300 border-round" accept=".xlsx, .xls" />
                        <small class="p-error" v-if="memberImportForm.errors.file">{{ memberImportForm.errors.file }}</small>
                    </div>
                    
                    <div class="flex justify-content-end mt-3">
                        <Button label="Mulai Plotting" type="submit" severity="help" :loading="memberImportForm.processing" />
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

import Pagination from '@/Components/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import Select from 'primevue/select';
import ConfirmDialog from 'primevue/confirmdialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import MultiSelect from 'primevue/multiselect';
import axios from 'axios'; // Kita butuh ini untuk fetch data siswa via AJAX
import FloatingVue from 'floating-vue'; 
import 'floating-vue/dist/style.css';
import Divider from 'primevue/divider';


const props = defineProps({
    classrooms: Object, // Ubah jadi Object (Pagination)
    teachers: Array,
    activeYear: Object,
    filters: Object
});


const confirm = useConfirm();
const toast = useToast();
const displayModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const search = ref(props.filters?.search || '');
const displayImportModal = ref(false);
const importForm = useForm({ file: null });

// SEARCH
let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('classrooms.index'), { 
            search: val,
            trash: props.filters?.trash // Keep filter
        }, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});

const form = useForm({
    name: '',
    level: 10,
    major: '',
    teacher_id: null,
    academic_year_id: props.activeYear?.id 
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.academic_year_id = props.activeYear?.id; 
    displayModal.value = true;
};

const openEditModal = (data) => {
    isEditing.value = true;
    editId.value = data.id;
    form.name = data.name;
    form.level = data.level;
    form.major = data.major;
    form.teacher_id = data.teacher_id;
    form.academic_year_id = data.academic_year_id;
    displayModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.classrooms.update', editId.value), {
            onSuccess: () => { 
                displayModal.value = false; 
                form.reset();
            }
        });
    } else {
        form.post(route('admin.classrooms.store'), {
            onSuccess: () => { displayModal.value = false; form.reset(); }
        });
    }
};
const displayMemberModal = ref(false);
const availableStudents = ref([]); // Data siswa yg belum punya kelas
const memberForm = useForm({
    classroom_id: null,
    student_ids: []
});
const selectedClassroomName = ref('');


// --- LOGIC TRASH & RESTORE (BARU) ---

const toggleTrash = () => {
    const isTrash = props.filters?.trash === 'true';
    router.get(route('admin.classrooms.index'), { 
        trash: !isTrash ? 'true' : null, 
        search: search.value 
    }, { preserveState: true, preserveScroll: true });
};

const confirmRestore = (data) => {
    confirm.require({
        message: `Pulihkan kelas <b>${data.name}</b>?`,
        header: 'Konfirmasi Restore',
        icon: 'pi pi-refresh',
        acceptClass: 'p-button-success',
        accept: () => {
            router.put(route('admin.classrooms.restore', data.id));
        }
    });
};

const confirmDelete = (data) => {
    confirm.require({
        message: `Hapus kelas <b>${data.name}</b>? (Masuk Arsip)`,
        header: 'Konfirmasi',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.classrooms.destroy', data.id));
        }
    });
};
const confirmForceDelete = (data) => {
    confirm.require({
        message: `Hapus permanen kelas <b>${data.name}</b>? Tindakan ini tidak dapat dibatalkan.`,
        header: 'Konfirmasi Hapus Permanen',
        icon: 'pi pi-times-circle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.classrooms.force-delete', data.id));
        }
    });
};

const confirmCopyPreviousYear = () => {
    confirm.require({
        message: 'Salin semua data kelas dari tahun ajaran sebelumnya ke tahun ajaran aktif saat ini?',
        header: 'Konfirmasi Salin Kelas',
        icon: 'pi pi-copy',
        acceptClass: 'p-button-primary',
        accept: () => {
            router.post(route('admin.classrooms.copy-previous-year'), {}, {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Berhasil menyalin kelas', life: 3000 });
                }
            });
        }
    });
};

const openImportModal = () => {
    importForm.reset();
    importForm.clearErrors();
    displayImportModal.value = true;
};

const handleFileUpload = (event) => {
    importForm.file = event.target.files[0];
};

const submitImport = () => {
    if (!importForm.file) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Pilih file terlebih dahulu', life: 3000 });
        return;
    }

    importForm.post(route('admin.classrooms.import'), {
        onSuccess: () => {
            displayImportModal.value = false;   // ⬅️ TUTUP MODAL
            importForm.reset();                 // ⬅️ RESET FORM
        }
    });
};
// Buka Modal & Fetch Data Siswa
const openMemberModal = async (data) => {
    selectedClassroomName.value = data.name;
    memberForm.classroom_id = data.id;
    memberForm.student_ids = []; // Reset pilihan
    
    // Fetch data siswa yang belum punya kelas via API
    try {
        const response = await axios.get(route('admin.students.available'));
        availableStudents.value = response.data;
        displayMemberModal.value = true;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal mengambil data siswa', life: 3000 });
    }
};

// Submit Form
const submitMember = () => {
    memberForm.post(route('admin.classrooms.add-members', memberForm.classroom_id), {
        onSuccess: () => {
            displayMemberModal.value = false;
            memberForm.reset();
        }
    });
};
// State Baru
const displayMemberImportModal = ref(false);
const memberImportForm = useForm({ file: null });

// Function Buka Modal
const openMemberImportModal = () => {
    memberImportForm.reset();
    memberImportForm.clearErrors();
    displayMemberImportModal.value = true;
};

// Function Handle File
const handleMemberFileUpload = (event) => {
    memberImportForm.file = event.target.files[0];
};

// Function Submit
const submitMemberImport = () => {
    if (!memberImportForm.file) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Pilih file terlebih dahulu', life: 3000 });
        return;
    }
    memberImportForm.post(route('admin.classrooms.members.import'),{
        onSuccess: () => {
            displayMemberImportModal.value = false;   // ⬅️ TUTUP MODAL
            memberImportForm.reset();                 // ⬅️ RESET FORM
        }
    });
};
</script>