<template>
    <AppLayout>
        <div class="card">
            <!-- HEADER -->
            <div class="flex justify-content-between align-items-center mb-4">
                <div class="flex flex-column gap-2">
                    <Button
                        icon="pi pi-arrow-left"
                        label="Kembali ke CP"
                        severity="secondary"
                        text
                        @click="backToCP"
                    />

                    <div>
                        <h2 class="text-2xl font-bold m-0">Tujuan Pembelajaran (TP)</h2>
                        <div class="text-sm text-500 mt-1">
                            {{ cp.subject?.name }} · Fase {{ cp.fase }}
                        </div>
                        <div class="text-sm mt-1">
                            <b>CP:</b> {{ cp.judul_cp }}
                        </div>
                    </div>
                </div>


                <div class="flex gap-2">
                    <Button
                        :label="isTrash ? 'Lihat Data Aktif' : 'Data Arsip'"
                        :icon="isTrash ? 'pi pi-list' : 'pi pi-trash'"
                        outlined
                        @click="toggleTrash"
                    />
                    <Button
                        label="Import TP"
                        icon="pi pi-file-excel"
                        severity="success"
                        @click="showImport = true"
                    />
                    <Button
                        label="Tambah TP"
                        icon="pi pi-plus"
                        severity="primary"
                        @click="openCreate"
                    />
                </div>
            </div>

            <!-- TABLE -->
            <div class="surface-card p-4 shadow-2 border-round">
                <DataTable :value="tps" stripedRows>
                    <Column field="nomor_tp" header="No" style="width: 80px" />

                    <Column field="kode_tp" header="Kode TP" style="width: 140px" />

                    <Column header="Rumusan TP">
                        <template #body="{ data }">
                            <span :class="{ 'text-500': !data.is_active }">
                                {{ data.rumusan_tp }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Status" style="width: 100px">
                        <template #body="{ data }">
                            <Tag
                                :value="data.is_active ? 'Aktif' : 'Nonaktif'"
                                :severity="data.is_active ? 'success' : 'secondary'"
                            />
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 150px">
                        <template #body="{ data }">
                            <template v-if="!data.deleted_at">
                                <Button
                                    icon="pi pi-pencil"
                                    text
                                    severity="warning"
                                    @click="openEdit(data)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    text
                                    severity="danger"
                                    @click="remove(data)"
                                />
                            </template>

                            <template v-else>
                                <Button
                                    icon="pi pi-refresh"
                                    text
                                    severity="success"
                                    v-tooltip.top="'Pulihkan TP'"
                                    @click="restore(data)"
                                />
                            </template>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- FORM TP -->
        <Dialog v-model:visible="showForm" header="Form TP" modal style="width: 600px">
            <form @submit.prevent="submit">
                <div class="formgrid grid">
                    <div class="field col-4">
                        <label class="font-medium">Nomor TP</label>
                        <InputText v-model="form.nomor_tp" class="w-full" />
                        <small class="p-error">{{ form.errors.nomor_tp }}</small>
                    </div>

                    <div class="field col-8">
                        <label class="font-medium">Kode TP</label>
                        <InputText v-model="form.kode_tp" readonly="true" class="w-full"/>
                        <small class="p-error">{{ form.errors.kode_tp }}</small>
                    </div>
                </div>

                <div class="field mb-3">
                    <label class="font-medium">Rumusan TP</label>
                    <Textarea v-model="form.rumusan_tp" rows="4" class="w-full" />
                    <small class="p-error">{{ form.errors.rumusan_tp }}</small>
                </div>

                <div class="field mb-3">
                    <label class="font-medium">Urutan</label>
                    <InputNumber v-model="form.urutan" />
                </div>

                <div class="field mb-3 flex align-items-center">
                    <label class="ml-1">Aktif</label>
                    <Checkbox v-model="form.is_active" :binary="true" class=""/>
                    
                </div>

                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" text @click="showForm = false" />
                    <Button label="Simpan" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
        <ImportDialog 
            v-if="cp?.id"
            v-model:visible="showImport" 
            :cp-id="cp.id"
        />
    </AppLayout>
</template>

<script setup>
import { ref,watch,computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useConfirm } from 'primevue/useconfirm'

import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'
import ConfirmDialog from 'primevue/confirmdialog'

import ImportDialog from './ImportDialogTP.vue'

const props = defineProps({
    cp: Object,
    tps: Array,
    filters: Object,
    cp_urut: Number,
})

const confirm = useConfirm()

const showForm = ref(false)
const editingId = ref(null)
const subjectCode = props.cp.subject?.code||''
const fase = props.cp.fase
const cpUrut = props.cp_urut 
const showImport = ref(false)

const isTrash = computed(() => props.filters?.trash === 'true')
const form = useForm({
    nomor_tp: '',
    kode_tp: '',
    rumusan_tp: '',
    urutan: 0,
    is_active: true
})
const backToCP = () => {
    router.get(route('akademik.cp.index'))
}
const openCreate = () => {
    editingId.value = null
    form.reset()
    showForm.value = true
}

const openEdit = (tp) => {
    editingId.value = tp.id
    form.nomor_tp = tp.nomor_tp
    form.kode_tp = tp.kode_tp
    form.rumusan_tp = tp.rumusan_tp
    form.urutan = tp.urutan
    form.is_active = tp.is_active
    showForm.value = true
}

const submit = () => {
    if (editingId.value) {
        form.put(route('akademik.tp.update', editingId.value), {
            onSuccess: () => showForm.value = false
        })
    } else {
        form.post(route('akademik.tp.store', props.cp.id), {
            onSuccess: () => showForm.value = false
        })
    }
}

const remove = (tp) => {
    confirm.require({
        message: `Arsipkan TP <b>${tp.kode_tp}</b>?`,
        header: 'Konfirmasi',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            form.delete(route('akademik.tp.destroy', tp.id))
        }
    })
}

const restore = (tp) => {
    confirm.require({
        message: `Pulihkan TP <b>${tp.kode_tp}</b>?`,
        header: 'Konfirmasi Restore',
        icon: 'pi pi-refresh',
        acceptClass: 'p-button-success',
        accept: () => {
            form.put(route('akademik.tp.restore', tp.id))
        }
    })
}

const toggleTrash = () => {
    router.get(
        route('akademik.tp.index', props.cp.id),
        { trash: !isTrash.value ? 'true' : null },
        { preserveState: true, preserveScroll: true }
    )
}
watch(() => form.nomor_tp, (val) => {
    if (!val || !subjectCode || !fase || !cpUrut) {
        form.kode_tp = ''
        return
    }
    form.kode_tp = `${subjectCode}.${fase}.${cpUrut}.${val}`
})
</script>