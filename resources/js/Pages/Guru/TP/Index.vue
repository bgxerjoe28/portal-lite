<template>
    <AppLayout title="Atur TP">
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

                    <Column header="TP">
                        <template #body="{ data }">
                            <div class="font-bold">{{ data.kode_tp }}</div>
                            <div class="text-sm text-500">Urutan: {{ data.urutan }}</div>
                        </template>
                    </Column>

                    <Column header="Rumusan TP">
                        <template #body="{ data }">
                            <span :class="{ 'text-500': !data.is_active }">
                                {{ data.rumusan_tp }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Status" style="width: 180px">
                        <template #body="{ data }">
                            <div class="flex align-items-center gap-2">
                                <!-- TAG STATUS -->
                                <Tag
                                    :value="data.is_active ? 'Aktif' : 'Nonaktif'"
                                    :severity="data.is_active ? 'success' : 'secondary'"
                                />

                                <!-- TOGGLE -->
                                <ToggleButton
                                    :modelValue="data.is_active"
                                    onLabel=""
                                    offLabel=""
                                    onIcon="pi pi-check"
                                    offIcon="pi pi-times"
                                    class="p-button-sm"
                                    @change="toggleStatus(data)"
                                />
                            </div>
                        </template>
                    </Column>


                    <Column header="Aksi" style="width: 120px">
                        <template #body="{ data }">
                            <Button
                                icon="pi pi-pencil"
                                text
                                severity="warning"
                                @click="openEdit(data)"
                            />
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
                        <InputText v-model="form.kode_tp" readonly class="w-full" />
                    </div>
                </div>

                <div class="field mb-3">
                    <label class="font-medium">Rumusan TP</label>
                    <Textarea v-model="form.rumusan_tp" rows="4" class="w-full" />
                </div>

                <div class="field mb-3">
                    <label class="font-medium">Urutan</label>
                    <InputNumber v-model="form.urutan" />
                </div>

                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" text @click="showForm = false" />
                    <Button label="Simpan" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>

        <ImportDialog
            v-model:visible="showImport"
            :cp-id="cp.id"
        />
    </AppLayout>
</template>
<script setup>
import { ref, watch } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
//import { useToast } from 'primevue/usetoast'

import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'
import ToggleButton from 'primevue/togglebutton'
import Tag from 'primevue/tag'


import ImportDialog from './ImportDialog.vue'

const props = defineProps({
    cp: Object,
    tps: Array,
    cp_urut: Number,
})

const page = usePage()
//const toast = useToast()

const showForm = ref(false)
const showImport = ref(false)
const editingId = ref(null)

const subjectCode = props.cp.subject?.code || ''
const fase = props.cp.fase
const cpUrut = props.cp_urut ?? ''

const form = useForm({
    nomor_tp: '',
    kode_tp: '',
    rumusan_tp: '',
    urutan: 0,
    is_active: true,
})

const backToCP = () => {
    router.get(route('guru.cp.index'))
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
        form.put(route('guru.tp.update', editingId.value), {
            onSuccess: () => showForm.value = false
        })
    } else {
        form.post(route('guru.tp.store', props.cp.id), {
            onSuccess: () => showForm.value = false
        })
    }
}

const toggleStatus = (tp) => {
    router.put(route('guru.tp.toggle', tp.id))
}

/* Preview kode TP (UX saja) */
watch(() => form.nomor_tp, (val) => {
    if (!val || !subjectCode || !fase || !cpUrut) {
        form.kode_tp = ''
        return
    }
    form.kode_tp = `${subjectCode}.${fase}.${cpUrut}.${val}`
})
watch(
    () => page.props.errors,
    (errors) => {
        if (!errors) return

        Object.values(errors).forEach((msg) => {
            toast.add({
                severity: 'error',
                summary: 'Gagal Import TP',
                detail: msg,
                life: 6000,
            })
        })
    },
    { deep: true }
)
</script>

