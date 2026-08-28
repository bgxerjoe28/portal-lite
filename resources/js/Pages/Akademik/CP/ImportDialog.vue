<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Divider from 'primevue/divider'

const visible = defineModel('visible')

// Form import (pakai Inertia, SAMA seperti kelas)
const importForm = useForm({
    file: null
})

const handleFileUpload = (event) => {
    importForm.file = event.target.files[0]
}

const submitImport = () => {
    if (!importForm.file) return

    importForm.post(route('akademik.cp.import'), {
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset()
            visible.value = false
        }
    })
}
</script>

<template>
    <Dialog 
        v-model:visible="visible" 
        header="Import Capaian Pembelajaran (CP)" 
        :modal="true" 
        :style="{ width: '450px' }"
        class="p-fluid"
    >
        <div class="surface-100 p-3 border-round mb-4">
            <div class="flex align-items-start gap-3">
                <i class="pi pi-info-circle text-blue-500 mt-1" style="font-size: 1.2rem"></i>
                <div>
                    <span class="font-semibold block mb-1">Langkah 1: Unduh Template</span>
                    <p class="text-sm text-600 m-0 mb-3">
                        Gunakan file template resmi kami untuk memastikan format data sesuai dengan sistem. untuk acuan CP dapat membuka <a href="https://guru.kemendikdasmen.go.id/kurikulum/referensi-penerapan/capaian-pembelajaran/" target="_blank" class="text-blue-600 underline">dokumen resmi dari Kemdikbud</a>.
                    </p>
                    <a :href="route('akademik.cp.export')" class="no-underline">
                        <Button 
                            label="Download Template.xlsx" 
                            icon="pi pi-file-excel" 
                            severity="success" 
                            outlined 
                            size="small" 
                            class="w-auto"
                        />
                    </a>
                </div>
            </div>
        </div>

        <form @submit.prevent="submitImport">
            <div class="field mb-4">
                <label class="font-semibold block mb-2 text-900">
                    Langkah 2: Pilih File (.xlsx)
                </label>
                
                <div class="flex flex-column gap-2">
                    <input 
                        type="file" 
                        id="file-upload"
                        class="hidden" 
                        accept=".xlsx,.xls"
                        @change="handleFileUpload"
                    />
                    <label 
                        for="file-upload" 
                        class="flex align-items-center justify-content-center border-2 border-dashed border-300 border-round p-4 cursor-pointer hover:surface-50 transition-colors"
                        :class="{'border-primary': importForm.file}"
                    >
                        <div class="text-center">
                            <i :class="importForm.file ? 'pi pi-file-excel text-primary' : 'pi pi-upload text-400'" style="font-size: 2rem"></i>
                            <p class="mt-2 mb-0 text-sm">
                                {{ importForm.file ? importForm.file.name : 'Klik untuk cari file atau drop di sini' }}
                            </p>
                        </div>
                    </label>

                    <small class="p-error" v-if="importForm.errors.file">
                        {{ importForm.errors.file }}
                    </small>
                </div>
            </div>

            <div class="flex justify-content-end gap-2 pt-3 border-top-1 border-200">
                <Button 
                    label="Batal" 
                    icon="pi pi-times" 
                    text 
                    severity="secondary" 
                    @click="visible = false" 
                />
                <Button 
                    label="Proses Import" 
                    icon="pi pi-check" 
                    type="submit" 
                    :loading="importForm.processing"
                    :disabled="!importForm.file"
                    class="px-4"
                />
            </div>
        </form>
    </Dialog>
</template>
