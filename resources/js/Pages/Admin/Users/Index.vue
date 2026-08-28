<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, Link, usePage, useForm } from '@inertiajs/vue3'
import { ref, watch,computed } from 'vue'
import { route } from 'ziggy-js'
import { useConfirm } from 'primevue/useconfirm'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import InputText from 'primevue/inputtext'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import Password from 'primevue/password'
import Message from 'primevue/message'

const showCreateModal = ref(false)
const showEditModal = ref(false)
const editUser = ref(null)

/* ================= Form ================= */
const form = useForm({
    name: '',
    email: '',
    role: '',
    password: '',
})
const editForm = useForm({
    name: '',
    email: '',
    role: '',
})
const roleOptions = [
    { label: 'Administrator', value: 'admin' },
    { label: 'Pegawai / Staff', value: 'pegawai' },
    { label: 'Kepala Sekolah', value: 'kepala sekolah' },
    { label: 'Guru BK', value: 'guru bk' },
]
const editRoleOptions = [
    { label: 'Administrator', value: 'admin' },
    { label: 'Pegawai / Staff', value: 'pegawai' },
    { label: 'Kepala Sekolah', value: 'kepala sekolah' },
    { label: 'Guru', value: 'guru' },
    { label: 'Guru BK', value: 'guru bk' },
    { label: 'Siswa', value: 'siswa' },
]
const submitCreate = () => {
    form.post(route('admin.users.store'), {
        onSuccess: () => {
            form.reset()
            showCreateModal.value = false
        }
    })
}
const openEditModal = (user) => {
    editUser.value = user
    editForm.name = user.name
    editForm.email = user.email
    editForm.role = user.roles?.[0]?.name || ''
    showEditModal.value = true
}
const submitEdit = () => {
    editForm.put(route('admin.users.update', editUser.value.id), {
        onSuccess: () => {
            showEditModal.value = false
            editUser.value = null
            editForm.reset()
        }
    })
}
const formatRelativeTime = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    
    // Bapak bisa gunakan library Day.js atau Intl.RelativeTimeFormat bawaan JS
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};
/* ================= PROPS ================= */
const props = defineProps({
    users: Object,
    activeTab: String,
    filters: Object,
    counts: Object
})

/* ================= STATE ================= */
const confirm = useConfirm()
const search = ref(props.filters?.search ?? '')
const selectedUsers = ref([])

/* ================= TABS ================= */
const tabs = [
    { label: 'Semua', key: 'all' },
    { label: 'Administrator', key: 'admin' },
    { label: 'Pegawai', key: 'pegawai' },
    { label: 'Kepala Sekolah', key: 'kepala sekolah' },
    { label: 'Guru', key: 'guru' },
    { label: 'Guru BK', key: 'guru bk' },
    { label: 'Siswa', key: 'siswa' },
    { label: 'Alumni', key: 'alumni' },
]

const activeIndex = ref(
    Math.max(tabs.findIndex(t => t.key === props.activeTab), 0)
)

const changeTab = (index) => {
    router.get(
        route('admin.users.index'),
        { role: tabs[index].key, search: search.value },
        { replace: true }
    )
}

/* ================= SEARCH ================= */
watch(search, (value) => {
    router.get(
        route('admin.users.index'),
        { role: tabs[activeIndex.value].key, search: value },
        { preserveState: true, replace: true }
    )
})

/* ================= ACTIONS ================= */
const toggleStatus = (user) => {
    confirm.require({
        message: `Ubah status user ${user.name}?`,
        header: 'Konfirmasi',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.patch(route('admin.users.toggle', user.id))
        }
    })
}
const confirmResetPassword = (user) => {
    confirm.require({
        message: `Reset password ${user.name}?`,
        header: 'Konfirmasi',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.put(route('admin.users.reset-password', user.id))
        }
    })
}

const batchDisable = () => {
    confirm.require({
        message: `Nonaktifkan ${selectedUsers.value.length} user terpilih?`,
        header: 'Konfirmasi Batch',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.post(route('admin.users.batch-disable'), {
                ids: selectedUsers.value.map(u => u.id)
            })
        }
    })
}
const batchEnable = () => {
    confirm.require({
        message: `Aktifkan ${selectedUsers.value.length} user terpilih?`,
        header: 'Konfirmasi Batch',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.post(route('admin.users.batch-enable'), {
                ids: selectedUsers.value.map(u => u.id)
            })
        }
    })
}  

// ================= Impersonate ================= //

const impersonateUser = (user) => {
    confirm.require({
        message: `Login sebagai <strong>${user.name}</strong> (${user.roles?.map(r => r.name).join(', ')})?<br><small class="text-500">Anda dapat kembali ke akun admin kapan saja via banner merah di atas.</small>`,
        header: '⚠️ Konfirmasi Impersonation',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Ya, Login Sebagai User Ini',
        rejectLabel: 'Batal',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('admin.users.impersonate', user.id))
        },
    })
}
const canBatchDisable = computed(() => {
    return selectedUsers.value.some(user => user.is_active)
})
const canBatchEnable = computed(() => {
    return selectedUsers.value.some(user => !user.is_active)
})
// ================= Pagination Select ================= //
const perPage = ref(props.filters?.per_page ?? 10)
const perPageOptions = [
    { label: '10', value: 10 },
    { label: '25', value: 25 },
    { label: '50', value: 50 },
    { label: '100', value: 100 },
    { label: 'Semua', value: -1 },
]
watch(perPage, (value) => {
    router.get(
        route('admin.users.index'),
        {
            role: tabs[activeIndex.value].key,
            search: search.value,
            per_page: value,
        },
        {
            preserveState: true,
            replace: true,
        }
    )
})
</script>

<template>
<AppLayout title="Manajemen Pengguna">
    <Dialog
    v-model:visible="showCreateModal"
    header="Tambah User"
    modal
    class="w-full md:w-30rem"
>

    <!-- WARNING -->
    <Message severity="info" class="mb-3">
        Akun <b>Guru</b> dan <b>Siswa</b> ditambahkan melalui menu masing-masing.
    </Message>

    <div class="flex flex-column gap-3">

        <div>
            <label class="font-bold mb-1 block">Nama</label>
            <InputText
                v-model="form.name"
                class="w-full"
            />
            <small v-if="form.errors.name" class="text-red-500">
                {{ form.errors.name }}
            </small>
        </div>

        <div>
            <label class="font-bold mb-1 block">Email</label>
            <InputText
                v-model="form.email"
                class="w-full"
            />
            <small v-if="form.errors.email" class="text-red-500">
                {{ form.errors.email }}
            </small>
        </div>

        <div>
            <label class="font-bold mb-1 block">Role</label>
            <Select
                v-model="form.role"
                :options="roleOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Pilih Role"
                class="w-full"
            />
            <small v-if="form.errors.role" class="text-red-500">
                {{ form.errors.role }}
            </small>
        </div>

        <div>
            <label class="font-bold mb-1 block">Password (opsional)</label>
            <Password
                v-model="form.password"
                toggleMask
                class="w-full"
                inputClass="w-full"
            />
            <small class="text-500">
                Kosongkan untuk password otomatis.
            </small>
            <small v-if="form.errors.password" class="text-red-500">
                {{ form.errors.password }}
            </small>
        </div>

    </div>

    <!-- FOOTER -->
    <template #footer>
        <Button
            label="Batal"
            text
            @click="showCreateModal = false"
        />
        <Button
            label="Simpan"
            icon="pi pi-save"
            :loading="form.processing"
            @click="submitCreate"
        />
    </template>
</Dialog>

<Dialog
    v-model:visible="showEditModal"
    header="Edit User"
    modal
    class="w-full md:w-30rem"
>
    <div class="flex flex-column gap-3">
        <div>
            <label class="font-bold mb-1 block">Nama</label>
            <InputText
                v-model="editForm.name"
                class="w-full"
            />
            <small v-if="editForm.errors.name" class="text-red-500">
                {{ editForm.errors.name }}
            </small>
        </div>

        <div>
            <label class="font-bold mb-1 block">Email</label>
            <InputText
                v-model="editForm.email"
                class="w-full"
            />
            <small v-if="editForm.errors.email" class="text-red-500">
                {{ editForm.errors.email }}
            </small>
        </div>

        <div>
            <label class="font-bold mb-1 block">Role</label>
            <Select
                v-model="editForm.role"
                :options="editRoleOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Pilih Role"
                class="w-full"
            />
            <small v-if="editForm.errors.role" class="text-red-500">
                {{ editForm.errors.role }}
            </small>
        </div>
    </div>

    <!-- FOOTER -->
    <template #footer>
        <Button
            label="Batal"
            text
            @click="showEditModal = false"
        />
        <Button
            label="Simpan"
            icon="pi pi-save"
            :loading="editForm.processing"
            @click="submitEdit"
        />
    </template>
</Dialog>

<div class="card">

    <!-- HEADER -->
    <div class="flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-2xl font-bold m-0">Manajemen Pengguna</h2>
            <span class="text-500">Kelola akun pengguna sistem</span>
        </div>

        <div class="flex gap-2">
            <Button
                label="Disable Terpilih"
                icon="pi pi-ban"
                severity="danger"
               
                :disabled="!canBatchDisable"
                @click="batchDisable"
            />
            <Button
                label="Enable Terpilih"
                icon="pi pi-check"
                severity="success"
                
                :disabled="!canBatchEnable"
                @click="batchEnable"
            />
            <Button
                label="Tambah User"
                icon="pi pi-user-plus"
                @click="showCreateModal = true"
            />
        </div>
    </div>

    <!-- SEARCH dan Pagination-->
        <div class="flex justify-content-between align-items-center mb-3">
            <InputText
                v-model="search"
                placeholder="Cari nama atau email..."
                class="w-full md:w-20rem"
            />

            <div class="flex align-items-center gap-2">
                <span class="text-sm text-500">Tampilkan</span>
                    <Select
                        v-model="perPage"
                        :options="perPageOptions"
                        optionLabel="label"
                        optionValue="value"
                        class="w-8rem"
                    />
            </div>
        </div>

    <!-- TABS -->
    <Tabs :value="activeIndex" @update:value="changeTab">

        <TabList>
            <Tab
                v-for="(tab, i) in tabs"
                :key="tab.key"
                :value="i"
            >
                {{ tab.label }}
                    <span class="text-xs text-500 ml-1">
                        ({{ counts[tab.key] ?? 0 }})
                    </span>
            </Tab>
        </TabList>

        <TabPanels>
            <TabPanel :value="activeIndex">

                <DataTable
                    :value="users.data"
                    v-model:selection="selectedUsers"
                    selectionMode="checkbox"
                    stripedRows
                    showGridlines
                    dataKey="id"
                >
                    <template #empty>
                        Tidak ada data user.
                    </template>

                    <Column selectionMode="multiple" style="width: 3rem" />

                    <Column field="name" header="Nama" sortable>
                        <template #body="{ data }">
                            <div class="font-bold">{{ data.name }}</div>
                            <div class="text-sm text-500">{{ data.email }}</div>
                        </template>
                    </Column>

                    <Column header="Role" style="width: 18%">
                        <template #body="{ data }">
                            <div class="flex gap-1 flex-wrap">
                                <Tag
                                    v-for="role in data.roles"
                                    :key="role.id"
                                    :value="role.name.toUpperCase()"
                                    severity="secondary"
                                />
                            </div>
                        </template>
                    </Column>
                    <Column header="Akses Terakhir" sortable field="last_login_at">
                        <template #body="{ data }">
                            <div v-if="data.last_login_at" class="flex flex-column">
                                <span class="font-medium text-900">
                                    {{ formatRelativeTime(data.last_login_at) }}
                                </span>
                                <small class="text-500 flex align-items-center gap-1">
                                    <i class="pi pi-desktop text-xs"></i> {{ data.last_login_ip }}
                                </small>
                            </div>
                            <span v-else class="text-400 italic">Belum pernah login</span>
                        </template>
                    </Column>

                    <Column header="Status" style="width: 12%" class="text-center">
                        <template #body="{ data }">
                            <Tag
                                :value="data.is_active ? 'Aktif' : 'Nonaktif'"
                                :severity="data.is_active ? 'success' : 'danger'"
                            />
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 20%" class="text-center">
                        <template #body="{ data }">
                            <Button
                                icon="pi pi-pencil"
                                text
                                severity="primary"
                                v-tooltip="'Edit nama / email / role'"
                                @click="openEditModal(data)"
                            />
                            <Button
                                icon="pi pi-power-off"
                                text
                                :severity="data.is_active ? 'danger' : 'success'"
                                v-tooltip="data.is_active ? 'Nonaktifkan user' : 'Aktifkan user'"
                                @click="toggleStatus(data)"
                            />
                            <Button
                               
                                icon="pi pi-refresh"
                                severity="warning"
                                v-tooltip="'Reset password ke default'"
                                @click="confirmResetPassword(data)"
                            />
                            <!-- Impersonate: hanya tampil untuk non-admin dan saat tidak sedang impersonating -->
                            <Button
                                v-if="!data.roles?.some(r => r.name === 'admin') && !$page.props.impersonating"
                                icon="pi pi-sign-in"
                                text
                                severity="help"
                                v-tooltip="'Login sebagai user ini'"
                                @click="impersonateUser(data)"
                            />
                        </template>
                    </Column>

                </DataTable>

            </TabPanel>
        </TabPanels>
    </Tabs>

    <!-- PAGINATION -->
    <div 
        v-if="users.links"
        class="flex justify-content-end mt-4">
            <Link
                v-for="(link, k) in users.links"
                :key="k"
                :href="link.url ?? '#'"
                class="p-button p-button-sm mx-1"
                :class="{ 'p-button-outlined': !link.active }"
                v-html="link.label"
            />
    </div>

</div>
</AppLayout>
</template>
