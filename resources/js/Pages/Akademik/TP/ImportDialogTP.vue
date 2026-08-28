<script setup>
import { ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'

import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import FileUpload from 'primevue/fileupload'

/* PROPS */
const props = defineProps({
    cpId: {
        type: Number,
        required: true,
    },
})

/* v-model */
const visible = defineModel('visible')

const page = usePage()
const toast = useToast()

const file = ref(null)
const loading = ref(false)

/* FLASH MESSAGE */
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.add({
                severity: 'success',
                summary: 'Import TP',
                detail: flash.success,
                life: 4000,
            })
        }
        if (flash?.error) {
            toast.add({
                severity: 'error',
                summary: 'Import TP Gagal',
                detail: flash.error,
                life: 5000,
            })
        }
    }
)

/* PILIH FILE */
const onSelect = (event) => {
    file.value = event.files[0]
}

/* SUBMIT */
const submit = () => {
    if (!file.value) {
        toast.add({
            severity: 'warn',
            summary: 'File belum dipilih',
            detail: 'Silakan pilih file Excel',
            life: 3000,
        })
        return
    }

    const form = new FormData()
    form.append('file', file.value)

    loading.value = true

    router.post(route('akademik.tp.import', props.cpId), form, {
        onFinish: () => (loading.value = false),
        onSuccess: () => {
            visible.value = false
            file.value = null
        },
    })
}
</script>

<template>
    <Dialog
        v-model:visible="visible"
        header="Import Tujuan Pembelajaran (TP)"
        modal
        style="width: 450px"
    >
        <div class="flex flex-column gap-3">
            <div class="text-sm text-600">
                File Excel <b>tidak perlu Kode TP</b>.  
                Kode akan dibuat otomatis oleh sistem.
            </div>

            <a :href="route('akademik.tp.template', props.cpId)" class="no-underline">
                <Button
                    label="Download Template Excel"
                    icon="pi pi-download"
                    outlined
                    class="w-full"
                />
            </a>

            <Divider />

            <FileUpload
                mode="basic"
                accept=".xlsx,.xls"
                chooseLabel="Pilih File Excel"
                customUpload
                @select="onSelect"
            />

            <div class="flex justify-content-end gap-2 mt-3">
                <Button label="Batal" text @click="visible = false" />
                <Button
                    label="Simpan / Proses Import"
                    icon="pi pi-check"
                    severity="success"
                    :loading="loading"
                    @click="submit"
                />
            </div>
        </div>
    </Dialog>
</template>
