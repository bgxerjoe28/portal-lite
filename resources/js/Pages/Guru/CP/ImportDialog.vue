<script setup>
import { ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'

import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import FileUpload from 'primevue/fileupload'
import Select from 'primevue/select'

const visible = defineModel('visible')

const props = defineProps({
    subjects: {
        type: Array,
        required: true,
    }
})

const page = usePage()
const toast = useToast()

const selectedSubject = ref(null)
const file = ref(null)
const loading = ref(false)

/* Toast dari backend */
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {

        }

        if (flash?.error) {
            toast.add({
                severity: 'error',
                summary: 'Import CP Gagal',
                detail: flash.error,
                life: 5000,
            })
        }
    }
)

/* Pilih file */
const onSelect = (event) => {
    file.value = event.files[0]
}

/* Submit import */
const submit = () => {
    if (!selectedSubject.value) {
        toast.add({
            severity: 'warn',
            summary: 'Mapel belum dipilih',
            detail: 'Silakan pilih mata pelajaran terlebih dahulu',
            life: 3000,
        })
        return
    }

    if (!file.value) {
        toast.add({
            severity: 'warn',
            summary: 'File belum dipilih',
            detail: 'Silakan pilih file Excel terlebih dahulu',
            life: 3000,
        })
        return
    }

    const form = new FormData()
    form.append('subjects_id', selectedSubject.value)
    form.append('file', file.value)

    loading.value = true

    router.post(route('guru.cp.import'), form, {
        onFinish: () => loading.value = false,
        onSuccess: () => {
            visible.value = false
            file.value = null
            selectedSubject.value = null
        }
    })
}
</script>

<template>
    <Dialog
        v-model:visible="visible"
        header="Import Capaian Pembelajaran (CP)"
        modal
        style="width: 450px"
    >
        <div class="flex flex-column gap-3">
            <div class="text-sm text-600">
                CP akan diimport ke <b>satu mata pelajaran</b>.  
                <br />
                Template <b>tidak memuat kode mapel</b>.
            </div>

            <!-- PILIH MAPEL -->
            <div class="field">
                <label class="font-medium mb-2 block">Mata Pelajaran</label>
                <Select
                    v-model="selectedSubject"
                    :options="subjects"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Pilih Mata Pelajaran"
                    class="w-full"
                />
            </div>

            <!-- TEMPLATE -->
            <a :href="route('guru.cp.template')" class="no-underline">
                <Button
                    label="Download Template Excel"
                    icon="pi pi-download"
                    outlined
                    class="w-full"
                />
            </a>

            <Divider />

            <!-- FILE -->
            <FileUpload
                mode="basic"
                accept=".xlsx,.xls"
                chooseLabel="Pilih File Excel"
                customUpload
                @select="onSelect"
            />

            <!-- ACTION -->
            <div class="flex justify-content-end gap-2 mt-3">
                <Button
                    label="Batal"
                    severity="secondary"
                    text
                    @click="visible = false"
                />
                <Button
                    label="Simpan / Import"
                    icon="pi pi-check"
                    severity="success"
                    :loading="loading"
                    @click="submit"
                />
            </div>
        </div>
    </Dialog>
</template>
