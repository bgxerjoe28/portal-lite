<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, watch } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
// Import Komponen Eksplisit (Biar tidak Failed to Resolve)
import TreeTable from 'primevue/treetable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import ToggleSwitch from 'primevue/toggleswitch';


const confirm = useConfirm();
const props = defineProps({
    menus: Array,
    roles: Array,
    parentOptions: Array
});

const nodes = ref([]);
const displayDialog = ref(false);
const isEdit = ref(false);

const toggleStatus = (nodeData) => {
    // Kirim request patch ke server
    router.patch(route('admin.menus.toggle', nodeData.id), {}, {
        preserveScroll: true, // Agar scrollbar tabel tidak loncat ke atas setelah klik
        onSuccess: () => {
            
        },
        onError: () => {
            
        }
    });
}
// FUNGSI KUNCI: Mengubah data Laravel menjadi format Tree PrimeVue
const transformToTreeNodes = (data) => {
    return data.map(item => ({
        key: item.id,
        data: {
            id: item.id,
            label: item.label,
            icon: item.icon,
            to: item.to,
            sort_order: item.sort_order,
            parent_id: item.parent_id,
            roles: item.roles || [] ,
            is_active: Boolean(item.is_active),
            is_separator: Boolean(item.is_separator)
        },
        // Jika punya anak, panggil fungsi ini lagi (recursive)
        children: item.children && item.children.length > 0 
                  ? transformToTreeNodes(item.children) 
                  : []
    }));
};

// Update table saat data datang pertama kali atau saat Inertia reload
const updateTable = () => {
    nodes.value = transformToTreeNodes(props.menus);
};

onMounted(() => updateTable());
watch(() => props.menus, () => updateTable(), { deep: true });

const form = useForm({
    id: null,
    label: '',
    icon: 'pi pi-folder',
    to: '',
    parent_id: null,
    sort_order: 0,
    is_active: true,
    is_separator: false,
    role_ids: []
});

const openNew = () => {
    form.reset();
    isEdit.value = false;
    displayDialog.value = true;
};

const editMenu = (nodeData) => {
    form.id = nodeData.id;
    form.label = nodeData.label;
    form.icon = nodeData.icon;
    form.to = nodeData.to;
    form.parent_id = nodeData.parent_id;
    form.sort_order = nodeData.sort_order;
    form.role_ids = nodeData.roles ? nodeData.roles.map(r => r.id) : [];
    form.is_active = !!nodeData.is_active;
    form.is_separator = !!nodeData.is_separator;
    isEdit.value = true;
    displayDialog.value = true;
};

const submitForm = () => {
    const action = isEdit.value 
        ? route('admin.menus.update', form.id) 
        : route('admin.menus.store');

    const method = isEdit.value ? 'put' : 'post';

    form[method](action, {
        onSuccess: () => {
            displayDialog.value = false;
            form.reset();
        }
    });
};
const deleteMenu = (id) => {
    confirm.require({
        message: 'Apakah Anda yakin ingin menghapus menu ini beserta anak-anaknya?',
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            form.delete(route('admin.menus.destroy', id), {
                onSuccess: () => { }
            });
        }
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Menu" />
          
        <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
            <div class="flex justify-content-between align-items-center">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">
                        Struktur Menu (Tree View)
                    </h2>
                    <span class="text-600 text-sm">
                        Kelola struktur menu aplikasi
                    </span>
                </div>

                <Button
                    label="Tambah Menu"
                    icon="pi pi-plus"
                    severity="primary"
                    @click="openNew"
                />
            </div>
        </div>
        
        <div class="surface-card p-4 border-round-lg shadow-1">
            <TreeTable :value="nodes" :paginator="false" class="p-treetable-sm">
                <Column field="label" header="Nama Menu" expander style="width: 35%"></Column>
                <Column header="Icon" style="width: 10%">
                    <template #body="slotProps">
                        <i :class="slotProps.node.data.icon" class="text-xl text-primary"></i>
                    </template>
                </Column>
                <Column field="to" header="Route / Link" style="width: 20%"></Column>
                <Column header="Akses Role" style="width: 20%">
                    <template #body="slotProps">
                        <div class="flex flex-wrap gap-1">
                            <Tag v-for="role in slotProps.node.data.roles" :key="role.id" 
                                 :value="role.name" severity="secondary" />
                        </div>
                    </template>
                </Column>
                <Column header="Aksi" style="width: 15%">
                    <template #body="slotProps">
                        <Button icon="pi pi-pencil" class="p-button-text p-button-success p-mr-2" 
                                @click="editMenu(slotProps.node.data)" />
                        <Button icon="pi pi-trash" class="p-button-text p-button-danger" 
                                @click="deleteMenu(slotProps.node.data.id)" />
                    </template>
                </Column>
                <Column header="Status" style="width: 12%">
                    <template #body="slotProps">
                        <div class="flex align-items-center gap-2">
                            <ToggleSwitch 
                                :modelValue="Boolean(slotProps.node.data.is_active)" 
                                @change="toggleStatus(slotProps.node.data)" 
                            />
                            <span :class="slotProps.node.data.is_active ? 'text-success' : 'text-danger'" class="text-xs font-bold">
                                {{ slotProps.node.data.is_active ? 'ON' : 'OFF' }}
                            </span>
                        </div>
                    </template>
                </Column>
            </TreeTable>
        </div>

        <Dialog v-model:visible="displayDialog" :header="isEdit ? '📝 Edit Menu' : '✨ Menu Baru'" 
            :modal="true" class="p-fluid" style="width: 500px">
            <div class="flex flex-column gap-3 mt-2">
                <div class="grid">
                    <div class="col-12 md:col-6">
                        <label class="block mb-1 font-bold">Nama Menu</label>
                        <InputText v-model="form.label" placeholder="Contoh: Input Nilai" />
                    </div>
                    <div class="col-12 md:col-6">
                    <label class="block mb-1 font-bold">Icon 
                        <span class="p-inputgroup-addon"><i :class="form.icon"></i></span> 
                    </label>
                    <div class="p-inputgroup">
                        <InputText v-model="form.icon" placeholder="pi pi-file" />
                    </div>
                </div>
                </div>
                
                <div class="grid">
                    <div class="col-12 md:col-6">
                        <label class="block mb-2 font-bold">Induk Menu</label>
                        <Select 
                            v-model="form.parent_id" 
                            :options="parentOptions" 
                            optionLabel="label" 
                            optionValue="id" 
                            placeholder="Menu Utama" 
                            showClear 
                            class="w-full"/>
                    </div>
                    <div class="col-12 md:col-6">
                        <label class="block mb-2 font-bold">Urutan</label>
                        <InputNumber 
                            v-model="form.sort_order" 
                            showButtons :min="0" 
                            class="w-full"
                            inputClass="w-full"/>
                    </div>
                </div>
                <div class="grid">
                    <div class="col-12 md:col-6">
                        <label class="block mb-1 font-bold">Route URL</label>
                        <InputText v-model="form.to" placeholder="/academic/grades" />
                    </div>
                   
                    <div class="col-12 md:col-6">
                        <label class="block mb-1 font-bold">Hak Akses</label>
                        <MultiSelect 
                            v-model="form.role_ids" 
                            :options="roles" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Pilih Role" 
                            display="chip" 
                            class="w-full"/>
                    </div>
                </div>    
                <div class="field col-12 border border-300 border-round p-3 surface-50 mb-3">
                    <div class="flex align-items-center justify-content-between">
                        <div class="flex flex-column gap-1">
                            <label class="font-bold">Status Publikasi</label>
                            <small class="text-secondary">Jika non-aktif, menu akan disembunyikan dari sidebar.</small>
                        </div>
                        <ToggleSwitch v-model="form.is_active" />
                    </div>
                </div>
                <div class="field col-12 border border-300 border-round p-3 surface-50">
                    <div class="flex align-items-center justify-content-between">
                        <div class="flex flex-column gap-1">
                            <label class="font-bold">Sebagai Separator (Pemisah)</label>
                            <small class="text-secondary">Jika aktif, menu ini hanya akan tampil sebagai label pemisah.</small>
                        </div>
                        <ToggleSwitch v-model="form.is_separator" />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Batal" icon="pi pi-times" text @click="displayDialog = false" />
                <Button label="Simpan" icon="pi pi-check" @click="submitForm" :loading="form.processing" />
            </template>
        </Dialog>
    </AppLayout>
</template>