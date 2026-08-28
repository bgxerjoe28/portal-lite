<script setup>
import { ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'

import AppLayout from '@/Layouts/AppLayout.vue'
import TeacherTabMenu from '@/Components/TeacherTabMenu.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import ImportDialog from './ImportDialog.vue'
import { useToast } from 'primevue/usetoast';
const props = defineProps({
    cps: Object,        // pagination
    subjects: Array,    // mapel guru saja
    filters: Object,
})

const toast = useToast();
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editingId = ref(null)
const page = usePage()
const showImport = ref(false)

const form = useForm({
    subjects_id: null,
    fase: 'E',
    judul_cp: '',
    rumusan_cp: '',
    kata_kunci: '',
})


/* SEARCH */
watch(search, (val) => {
    router.get(route('guru.cp.index'), { search: val }, { preserveState: true, replace: true })
})
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {

        }

        if (flash?.error) {
            const messages = Array.isArray(flash.error)
                ? flash.error.join('\n')
                : flash.error

            toast.add({
                severity: 'error',
                summary: 'Sebagian data gagal diimport',
                detail: messages,
                life: 8000,
            })
        }
    }
)


const openCreate = () => {
    editingId.value = null
    form.reset()
    showForm.value = true
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


/*Submit dan reset formreset form*/
const submit = () => {
    const actionOptions = {
        onSuccess: () => {
            showForm.value = false;
            form.reset(); // 🔑 Me-reset form ke data awal (kosong/default)
            editingId.value = null;
            
            // Tambahkan Toast sukses manual jika ingin langsung muncul
           
        },
    };

    if (editingId.value) {
        form.put(route('guru.cp.update', editingId.value), actionOptions);
    } else {
        form.post(route('guru.cp.store'), actionOptions);
    }
}
const closeForm = () => {
    showForm.value = false;
    form.reset();
    editingId.value = null;
}
</script>

<template>
    <AppLayout title="Atur CP">
        <TeacherTabMenu />
        <div class="card">
            <!-- HEADER -->
            <div class="flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-2xl font-bold m-0">Capaian Pembelajaran (CP)</h2>
                    <span class="text-500 text-sm">
                        CP sesuai mapel yang Anda ampu
                    </span>
                </div>

                <div class="flex gap-2">
                    <InputText v-model="search" placeholder="Cari CP / Mapel..." />
                    <Button
                        label="Import CP"
                        icon="pi pi-file-excel"
                        severity="success"
                        @click="showImport = true"
                    />
                    <Button label="Tambah CP" icon="pi pi-plus" severity="primary" @click="openCreate" />
                </div>
            </div>

            <!-- TABLE -->
            <div class="surface-card p-4 shadow-2 border-round">
                <DataTable :value="cps.data" stripedRows>
                    <Column header="Mapel">
                        <template #body="{ data }">
                            {{ data.subject?.name }}
                        </template>
                    </Column>

                    <Column field="fase" header="Fase" style="width:80px" />

                    <Column field="judul_cp" header="Judul CP / Elemen" />

                    <Column header="Aksi" style="width:180px">
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Button
                                    label="TP"
                                    icon="pi pi-list"
                                    size="small"
                                    @click="router.get(route('guru.tp.index', data.id))"
                                />
                                <Button
                                    icon="pi pi-pencil"
                                    severity="warning"
                                    text
                                    @click="openEdit(data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- FORM CP -->
        <Dialog v-model:visible="showForm" header="Form CP" modal style="width:600px">
            <form @submit.prevent="submit">
                <div class="field mb-3">
                    <label class="font-medium">Mata Pelajaran</label>
                    <Select
                        v-model="form.subjects_id"
                        :options="subjects"
                        optionLabel="name"
                        optionValue="id"
                        class="w-full"
                    />
                </div>

                <div class="field mb-3">
                    <label class="font-medium">Fase</label>
                    <Select v-model="form.fase" :options="['E','F']" />
                </div>

                <div class="field mb-3">
                    <label class="font-medium">Judul CP</label>
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
                    <Button label="Batal" text @click="closeForm" />
                    <Button label="Simpan" type="submit" />
                </div>
            </form>
        </Dialog>
        <ImportDialog
            v-model:visible="showImport"
            :subjects="subjects"
        />
    </AppLayout>
</template>
