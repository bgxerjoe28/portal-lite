<template>
<AppLayout>

    <!-- ===== CARD HEADER ===== -->
    <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
        <div class="flex justify-content-between align-items-center">
            <div>
                <h2 class="text-2xl font-bold text-900 m-0">
                    {{ filters.trash === 'true' ? 'Arsip Guru (Non-Aktif)' : 'Data Guru Aktif' }}
                </h2>
                <span class="text-600 text-sm">
                    Kelola data guru dan staff sekolah
                </span>
            </div>

            <div class="flex gap-3 align-items-center">
                <Button 
                    :label="filters.trash === 'true' ? 'Lihat Data Aktif' : 'Arsip Guru (Non-Aktif)'" 
                    :icon="filters.trash === 'true' ? 'pi pi-list' : 'pi pi-trash'" 
                    :severity="filters.trash === 'true' ? 'primary' : 'secondary'" 
                    outlined
                    @click="toggleTrash"
                />

                <IconField iconPosition="left">
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="search" placeholder="Cari..." />
                </IconField>

                <template v-if="filters.trash !== 'true'">
                    <Button label="Import" icon="pi pi-file-excel" severity="success" @click="openImportModal" />
                    <Button label="Tambah" icon="pi pi-plus" @click="openCreateModal" />
                </template>
            </div>
        </div>
    </div>

    <!-- ===== CARD TABLE ===== -->
    <div class="surface-card p-4 border-round-lg shadow-1">
                <DataTable v-bind="$pagination({ label: 'guru', rows: teachers.per_page, options: [10, 50, 100] })"
                    :value="teachers.data"                     
                    dataKey="id"
                    stripedRows 
                    tableStyle="min-width: 50rem"
                    :lazy="true"
                    :totalRecords="teachers.total"
                    :first="(teachers.current_page - 1) * teachers.per_page"
                    @page="onPage"
                >
                    <template #empty> 
                        <div class="text-center p-4">
                            <i class="pi pi-search text-500 text-xl mb-3 block"></i>
                            Data tidak ditemukan. 
                        </div>
                    </template>

                    <Column header="Status" style="width: 10%">
                        <template #body="{ data }">
                            <Tag :severity="data.deleted_at ? 'danger' : 'success'" :value="data.deleted_at ? 'Non-Aktif' : 'Aktif'" />
                        </template>
                    </Column>

                    <Column field="nip" header="NIP" sortable>
                        <template #body="{ data }"> {{ data.nip || '-' }} </template>
                    </Column>

                    <Column field="full_name" header="Nama Guru" sortable>
                        <template #body="{ data }">
                            <span v-if="data.gelar_depan">{{ data.gelar_depan }} </span>
                            <span class="font-bold">{{ data.full_name }}</span>
                            <span v-if="data.gelar_belakang">, {{ data.gelar_belakang }}</span>
                        </template>
                    </Column>

                    <Column field="phone" header="Telephone">
                        <template #body="{ data }"> {{ data.phone || '-' }} </template>
                    </Column>                    

                    <Column header="Akun Login">
                        <template #body="{ data }"> {{ data.user?.email }} </template>
                    </Column>

                    <Column field="gender" header="L/P">
                        <template #body="{ data }">
                            <Tag :value="data.gender ? 'L':'P'" :severity="data.gender ? 'info' : 'danger'" />                             
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 15%">
                        <template #body="{ data }">
                            <div class="flex gap-2" :key="data.id + (data.deleted_at ? '-trash' : '-active')">
                                
                                <template v-if="!data.deleted_at">
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
            </div>
        <Dialog v-model:visible="displayModal" :header="isEditing ? 'Edit Data Guru' : 'Tambah Guru Baru'" :modal="true" :style="{ width: '500px' }">
             <form @submit.prevent="submitForm">
                <div class="field mb-3">
                    <label for="full_name" class="font-medium">Nama Lengkap</label>
                    <InputText id="full_name" v-model="form.full_name" class="w-full" :class="{'p-invalid': form.errors.full_name}" />
                    <small class="p-error" v-if="form.errors.full_name">{{ form.errors.full_name }}</small>
                </div>
                
                <div class="formgrid grid">
                    <div class="field col-6">
                        <label for="gelar_depan" class="font-medium">Gelar Depan</label>
                        <InputText id="gelar_depan" v-model="form.gelar_depan" class="w-full" />
                    </div>
                    <div class="field col-6">
                        <label for="gelar_belakang" class="font-medium">Gelar Belakang</label>
                        <InputText id="gelar_belakang" v-model="form.gelar_belakang" class="w-full" />
                    </div>
                </div>

                <div class="field mb-3">
                    <label for="phone" class="font-medium">Telephone</label>
                    <InputText id="phone" v-model="form.phone" class="w-full" />
                </div>                

                <div class="field mb-3">
                    <label for="nip" class="font-medium">NIP</label>
                    <InputText id="nip" v-model="form.nip" class="w-full" :class="{'p-invalid': form.errors.nip}" />
                    <small class="p-error" v-if="form.errors.nip">{{ form.errors.nip }}</small>
                </div>

                <div class="field mb-3">
                    <label for="email" class="font-medium">Email Login</label>
                    <InputText id="email" v-model="form.email" type="email" class="w-full" :class="{'p-invalid': form.errors.email}" />
                    <small class="p-error" v-if="form.errors.email">{{ form.errors.email }}</small>
                </div>

                <div class="field mb-4">
                    <label class="font-medium block mb-2">Jenis Kelamin</label>
                    <div class="flex gap-3">
                        <div class="flex align-items-center">
                            <RadioButton v-model="form.gender" inputId="genderL" name="gender" :value="true" />
                            <label for="genderL" class="ml-2">Laki-laki</label>
                        </div>
                        <div class="flex align-items-center">
                            <RadioButton v-model="form.gender" inputId="genderP" name="gender" :value="false" />
                            <label for="genderP" class="ml-2">Perempuan</label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" severity="secondary" text @click="displayModal = false" />
                    <Button :label="isEditing ? 'Simpan Perubahan' : 'Simpan Data'" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>

       <Dialog v-model:visible="displayImportModal" header="Import Data Guru" :modal="true" :style="{ width: '400px' }">
            <div class="flex flex-column gap-3">
                <p class="text-sm text-600">
                    Silakan unduh template terlebih dahulu, isi data, lalu upload kembali file Excel tersebut.
                </p>
                
                <a :href="route('admin.teachers.export.template')" class="no-underline">
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
                        <Button label="Import Data" type="submit" :loading="importForm.processing" />
                    </div>
                </form>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';

// Components
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import ConfirmDialog from 'primevue/confirmdialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Divider from 'primevue/divider'; // Jangan lupa import Divider
import Pagination from '@/Components/Pagination.vue';
import FloatingVue from 'floating-vue'; 
import 'floating-vue/dist/style.css';

// PROPS
// Ganti teachers jadi Object karena pagination mengembalikan object {data, links, etc}
const props = defineProps({ 
    teachers: Object, 
    filters: Object 
});


const confirm = useConfirm();
const displayModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const displayImportModal = ref(false);
const search = ref(props.filters?.search || '');

// SEARCH
let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.teachers.index'), { 
            search: value,
            trash: props.filters?.trash, // Pertahankan state trash saat searching
            page: 1, // Reset halaman ke-1 saat mencari
            per_page: props.teachers?.per_page
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true
        });
    }, 300);
});

// PAGINATION HANDLER
const onPage = (event) => {
    router.get(route('admin.teachers.index'), {
        page: event.page + 1,
        per_page: event.rows,
        search: search.value,
        trash: props.filters?.trash
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

// FORM STATE
const form = useForm({
    full_name: '',
    gelar_depan: '',
    gelar_belakang: '',
    nip: '',
    email: '',
    gender: true,
    phone: ''
});

// ACTIONS
const openCreateModal = () => {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    displayModal.value = true;
};

const openEditModal = (data) => {
    isEditing.value = true;
    editId.value = data.id;
    form.full_name = data.full_name;
    form.gelar_depan = data.gelar_depan;
    form.gelar_belakang = data.gelar_belakang;
    form.nip = data.nip;
    form.email = data.user?.email;
    form.gender = data.gender;
    form.phone = data.phone;
    form.clearErrors();
    displayModal.value = true;
};
const handleSuccess = (message) => {
    displayModal.value = false;
    form.reset();

};

const handleError = (errors) => {
    let msg = 'Terjadi kesalahan.';

    if (errors?.email) msg = errors.email;
    if (errors?.nip) msg = errors.nip;


};

// SUBMIT
const submitForm = () => {
    // Debugging
    console.log("Submitting...", isEditing.value ? "Edit" : "Create");

    if (isEditing.value) {
        // MODE EDIT (Pakai Manual URL dulu agar aman)
        form.put(`/admin/teachers/${editId.value}`, {
            onSuccess: () => {
                handleSuccess('Data guru berhasil diperbarui');
            },
            onError: () => {
                handleError(form.errors+' Cek Kembali Inormasi yang dimasukkan.');
            }
        });
    } else {
        // MODE CREATE
        form.post('/admin/teachers', {
            onSuccess: () => {
                handleSuccess('Guru baru berhasil ditambahkan');
            },
            onError: (errors) => {
                let msg = "Terjadi kesalahan.";
                if(errors.email) msg = errors.email;
                if(errors.nip) msg = errors.nip;
                handleError(msg)
            }
        });
    }
};

// --- LOGIC YANG TADI HILANG ---

// 1. Toggle Trash (Pindah mode sampah/aktif)
const toggleTrash = () => {
    const isTrash = props.filters?.trash === 'true';
    router.get(route('admin.teachers.index'), { 
        trash: !isTrash ? 'true' : null, 
        search: search.value,
        page: 1, // Reset halaman saat filter ganti
        per_page: props.teachers?.per_page
    }, { 
        preserveState: true, 
        preserveScroll: true 
    });
};

// 2. Confirm Restore (Pulihkan data)
const confirmRestore = (data) => {
    confirm.require({
        message: `Pulihkan data guru <b>${data.full_name}</b>? Akun login akan aktif kembali.`,
        header: 'Konfirmasi Restore',
        icon: 'pi pi-refresh',
        acceptClass: 'p-button-success',
        accept: () => {
            router.put(route('admin.teachers.restore', data.id), {}, {
                onSuccess: () => handleSuccess('Guru aktif kembali')
            });
        }
    });
};

// 3. Confirm Delete (Hapus ke sampah)
const confirmDelete = (data) => {
    confirm.require({
        message: `Apakah Anda yakin ingin menghapus data guru <b>${data.full_name}</b>?`,
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.teachers.destroy', data.id), {
                onSuccess: () => {
                    handleSuccess('Guru dipindahkan ke arsip (non-aktif)');
                }
            });
        }
    });
};

// IMPORT LOGIC
const importForm = useForm({ file: null });

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
        
        return;
    }
    // Pakai Ziggy jika sudah jalan, atau manual '/admin/teachers/import'
    importForm.post(route('admin.teachers.import'), {
        onSuccess: () => {
            handleSuccess('Data guru berhasil diimport');
        },
        onError: () => {
            handleError(importForm.errors+' Cek Kembali file yang diupload.');
        }
    });
};
const confirmForceDelete = (data) => {
    confirm.require({
        message: `PERINGATAN: Anda akan menghapus data <b>${data.full_name}</b> secara PERMANEN. Data tidak bisa dikembalikan. Lanjutkan?`,
        header: 'Hapus Permanen',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.teachers.force-delete', data.id), {
                onSuccess: () => handleSuccess('Data guru telah dihapus secara permanen')    
            });
        }
    });
};
</script>