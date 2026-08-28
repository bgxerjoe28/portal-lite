<template>
    <AppLayout>
        <div class="card">
            <div class="flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-2xl font-bold m-0">Capaian Pembelajaran (CP)</h2>
                    <span class="text-500 text-sm">
                        Data master CP per Mata Pelajaran & Fase
                    </span>
                </div>
                <div class="flex gap-2 align-items-center">
                    <Button
                        :label="isTrash ? 'Lihat Data Aktif' : 'Data Arsip'"
                        :icon="isTrash ? 'pi pi-list' : 'pi pi-trash'"
                        outlined
                        @click="
                            router.get(route('akademik.cp.index'), {
                                trash: !isTrash ? 'true' : null,
                                search
                            })
                        "
                    />

                    <InputText
                        v-model="search"
                        placeholder="Cari CP / Mapel..."
                        v-if="!isTrash"
                    />
                </div>
                <div class="flex gap-2">
                    <Button label="Import" icon="pi pi-file-excel" severity="success" @click="showImport = true" />
                    <Button label="Tambah CP" icon="pi pi-plus" severity="primary" @click="openCreate" />
                </div>
            </div>

            <div class="surface-card p-4 shadow-2 border-round">
                <DataTable :value="cps.data" stripedRows>
                    <Column header="Mapel">
                        <template #body="{ data }">
                            {{ data.subject?.name || '-' }}
                        </template>
                    </Column>

                    <Column field="fase" header="Fase" style="width: 80px" />

                    <Column field="judul_cp" header="Judul CP / Element" />

                    <Column header="Aksi" style="width: 200px">
                        <template #body="{ data }">
                            <template v-if="!data.deleted_at">
                            <!-- 🔹 MASUK KE TP -->
                                <Button
                                    icon="pi pi-list"
                                    severity="info"
                                    text
                                    v-tooltip.top="'Kelola TP'"
                                    @click="goToTP(data)"
                                />
                                <Button icon="pi pi-pencil" text severity="warning" @click="openEdit(data)" />
                                <Button icon="pi pi-trash" text severity="danger" @click="remove(data)" />
                            </template>

                            <template v-else>
                                <Button
                                    icon="pi pi-refresh"
                                    text
                                    severity="success"
                                    @click="restore(data)"
                                    v-tooltip.top="'Pulihkan CP'"
                                />
                            </template>
                        </template>
                    </Column>

                </DataTable>
            </div>
        </div>

        <!-- FORM CP -->
        <Dialog v-model:visible="showForm" header="Form CP" modal style="width: 600px">
            <form @submit.prevent="submit">
                <div class="field mb-3">
                    <label class="font-medium">Mata Pelajaran</label>
                    <Select v-model="form.subjects_id"
                            :options="subjects"
                            optionLabel="name"
                            optionValue="id"
                            class="w-full" />
                </div>

                <div class="field mb-3">
                    <label class="font-medium">Fase</label>
                    <Select v-model="form.fase" :options="['E','F']" class=""/>
                </div>

                <div class="field mb-3">
                    <label class="font-medium">Judul CP / Element</label>
                    <InputText v-model="form.judul_cp" class="w-full" />
                </div>

                <div class="field mb-3">
                    <label class="font-medium">Rumusan CP</label>
                    <Textarea v-model="form.rumusan_cp" rows="4" class="w-full" />
                </div>

                <div class="field mb-4">
                    <label class="font-medium">Kata Kunci (pisahkan koma)</label>
                    <InputText v-model="form.kata_kunci" class="w-full" />
                </div>

                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" text @click="showForm=false" />
                    <Button label="Simpan" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>

        <!-- IMPORT -->
        <ImportDialog v-model:visible="showImport" />
    </AppLayout>
</template>

<script setup>
import { ref,watch } from 'vue'
import { useForm,usePage,router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import ConfirmDialog from 'primevue/confirmdialog'

import ImportDialog from './ImportDialog.vue'

const props = defineProps({
    cps: Object,      // ⬅️ BUKAN Array
    subjects: Array,
    filters: Object,
})

const toast = useToast()
const page = usePage()
const confirm = useConfirm()

const showForm = ref(false)
const showImport = ref(false)
const editingId = ref(null)

const search = ref(props.filters?.search || '')
const isTrash = ref(props.filters?.trash === 'true')

const form = useForm({
    subjects_id: null,
    fase: 'E',
    judul_cp: '',
    rumusan_cp: '',
    kata_kunci: '',
})

const openCreate = () => {
    editingId.value = null
    form.reset()
    showForm.value = true
}
const goToTP = (cp) => {
    router.get(route('akademik.tp.index', cp.id))
}
const openEdit = (cp) => {
    editingId.value = cp.id
    form.subjects_id = cp.subjects_id
    form.fase = cp.fase
    form.judul_cp = cp.judul_cp
    form.rumusan_cp = cp.rumusan_cp
    form.kata_kunci = (cp.kata_kunci || []).join(', ')
    showForm.value = true
}

const submit = () => {
    if (editingId.value) {
        form.put(route('akademik.cp.update', editingId.value), {
            onSuccess: () => showForm.value = false
        })
    } else {
        form.post(route('akademik.cp.store'), {
            onSuccess: () => showForm.value = false
        })
    }
}
const restore = (cp) => {
    confirm.require({
        message: `Pulihkan CP <b>${cp.judul_cp}</b>?`,
        header: 'Konfirmasi Restore',
        icon: 'pi pi-refresh',
        acceptClass: 'p-button-success',
        accept: () => {
            form.put(route('akademik.cp.restore', cp.id))
        }
    })
}
const remove = (cp) => {
    confirm.require({
        message: `Hapus CP <b>${cp.judul_cp}</b>?<br/>Tindakan ini tidak dapat dibatalkan.`,
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Hapus',
        rejectLabel: 'Batal',

        accept: () => {
            form.delete(route('akademik.cp.destroy', cp.id))
        }
    })
}
watch(
    () => page.props.errors,
    (errors) => {
        if (errors?.judul_cp) {
            toast.add({
                severity: 'error',
                summary: 'Gagal menyimpan CP',
                detail: errors.judul_cp,
                life: 4000,
            })
        }
    }
)
watch(search, (val) => {
    router.get(
        route('akademik.cp.index'),
        {
            search: val,
            trash: isTrash.value ? 'true' : null
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true
        }
    )
})

</script>
