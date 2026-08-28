<script setup>
import { ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'

import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import FileUpload from 'primevue/fileupload'

/* v-model visible */
const visible = defineModel('visible')

const props = defineProps({
    cpId: {
        type: Number,
        required: true,
    }
})

const page = usePage()
const toast = useToast()

const file = ref(null)
const loading = ref(false)

/* Toast dari backend */
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {

        }

        if (flash?.error) {
            const msg = Array.isArray(flash.error)
                ? flash.error.join('\n')
                : flash.error

            toast.add({

            })
        }
    }
)

/* Saat pilih file */
const onSelect = (event) => {
    file.value = event.files[0]
}

/* Proses Import */
const submit = () => {
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
    form.append('file', file.value)

    loading.value = true

    router.post(route('guru.tp.import', props.cpId), form, {
        onFinish: () => loading.value = false,
        onSuccess: () => {
            visible.value = false
            file.value = null
        }
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
                File Excel <b>tidak perlu berisi kode TP</b>.  
                Kode akan dibuat otomatis oleh sistem berdasarkan CP.
            </div>

            <!-- TEMPLATE -->
            <a :href="route('guru.tp.template', cpId)" class="no-underline">
                <Button
                    label="Download Template Excel"
                    icon="pi pi-download"
                    outlined
                    class="w-full"
                />
            </a>

            <Divider />

            <!-- Pilih File -->
            <FileUpload
                mode="basic"
                accept=".xlsx,.xls"
                chooseLabel="Pilih File Excel"
                customUpload
                @select="onSelect"
            />

            <!-- Tombol -->
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

