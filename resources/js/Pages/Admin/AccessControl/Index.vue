<template>
    <AppLayout>
        <div class="card border-0 shadow-sm">
            <div class="flex justify-content-between align-items-center mb-4">
                <h2 class="text-2xl font-bold text-teal-700 m-0">Pengaturan Hak Akses Fitur</h2>
                <div class="flex gap-2">
                    <Button label="Sinkronisasi Fitur Modul" icon="pi pi-sync" severity="secondary" @click="syncModules" />
                    <Button label="Tambah Fitur Baru" icon="pi pi-plus" severity="teal" @click="showAddPermission = true" />
                </div>
            </div>

            <DataTable :value="permissions" stripedRows responsiveLayout="stack">
                <Column field="name" header="Nama Fitur / Permission">
                    <template #body="{ data }">
                        <span class="font-bold text-lg text-teal-600 uppercase">{{ data.name }}</span>
                    </template>
                </Column>                
                <Column header="Personil Berwenang">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-2">
                            <Tag v-for="u in data.users" :key="u.id" 
                                :severity="u.role_type.toLowerCase().includes('guru') ? 'info' : 'warning'" 
                                class="px-3 py-2 border-round-lg shadow-1">
                                <div class="flex align-items-center gap-2">
                                    <i :class="u.role_type.toLowerCase().includes('guru') ? 'pi pi-book' : 'pi pi-user'"></i>
                                    <div class="flex flex-column">
                                        <span class="font-bold line-height-1">{{ u.name }}</span>
                                        <small class="text-xs opacity-80">{{ u.role_type }}</small>
                                    </div>
                                    <i class="pi pi-times-circle cursor-pointer hover:text-red-500 ml-1" 
                                    @click="revokeAccess(u.id, data.name)"></i>
                                </div>
                            </Tag>
                            
                            <Button icon="pi pi-plus-circle" text rounded severity="success" 
                                    @click="openAssignModal(data)" v-tooltip="'Tambah Personil'" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showAddPermission" header="Tambah Macam Fitur" modal class="p-fluid w-25rem">
            <div class="field mt-2">
                <label class="font-bold">Nama Fitur (Gunakan tanda hubung)</label>
                <InputText v-model="newPermName" placeholder="Contoh: manage-canteen" autofocus />
                <small class="text-500">Gunakan format kecil dan tanda hubung: manage-fitur</small>
            </div>
            <template #footer>
                <Button label="Simpan" icon="pi pi-check" @click="saveNewPermission" :disabled="!newPermName" />
            </template>
        </Dialog>

        <Dialog v-model:visible="showAssignModal" :header="'Tambah Petugas: ' + selectedPerm?.name" modal class="p-fluid w-30rem">
            <div class="field mt-2">
                <label class="font-bold">Pilih Guru / User</label>
                <MultiSelect 
                    v-model="selectedUserIds" 
                    :options="userOptions" 
                    optionLabel="label" 
                    optionValue="value" 
                    filter 
                    :filterFields="['label', 'name']"
                    placeholder="Pilih nama guru atau staf..."
                    display="chip"
                    class="w-full"
                >
                    <template #option="slotProps">
                        <div class="flex flex-column">
                            <span class="font-bold">{{ slotProps.option.name }}</span>
                            <small class="text-500">{{ slotProps.option.type }}</small>
                        </div>
                    </template>
                </MultiSelect>
            </div>
            <template #footer>
                <Button label="Berikan Akses" icon="pi pi-user-plus" severity="success" @click="assignTeacher" :disabled="!selectedUserIds.length" />
            </template>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Dialog from 'primevue/dialog';


const props = defineProps({ permissions: Array, userOptions: Array });

const showAddPermission = ref(false);
const newPermName = ref('');
const showAssignModal = ref(false);
const selectedPerm = ref(null);
const selectedUserIds = ref([]);

const saveNewPermission = () => {
    router.post(route('admin.access-control.store-perm'), { name: newPermName.value }, {
        onSuccess: () => { showAddPermission.value = false; newPermName.value = ''; }
    });
};

const openAssignModal = (perm) => {
    selectedPerm.value = perm;
    showAssignModal.value = true;
};

const assignTeacher = () => {
    router.post(route('admin.access-control.assign'), {
        permission_id: selectedPerm.value.id,
        user_ids: selectedUserIds.value
    }, {
        onSuccess: () => { showAssignModal.value = false; selectedUserIds.value = []; }
    });
};

const revokeAccess = (userId, permName) => {
    if(confirm('Cabut akses guru ini dari fitur tersebut?')) {
        router.post(route('admin.access-control.revoke'), {
            user_id: userId,
            permission_name: permName
        });
    }
};

const syncModules = () => {
    router.post(route('admin.access-control.sync-modules'), {});
};
</script>