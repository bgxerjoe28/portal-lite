<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Divider from 'primevue/divider'

const props = defineProps({
    academicYear: Object
})

const dayOptions = [
    { label: 'Senin', value: 'mon' },
    { label: 'Selasa', value: 'tue' },
    { label: 'Rabu', value: 'wed' },
    { label: 'Kamis', value: 'thu' },
    { label: 'Jumat', value: 'fri' },
    { label: 'Sabtu', value: 'sat' },
]

const form = useForm({
    school_days: props.academicYear.school_days ?? [
        'mon','tue','wed','thu','fri','sat'
    ]
})

const submit = () => {
    if (form.school_days.length === 0) {
        alert('Minimal pilih satu hari sekolah')
        return
    }

    form.put(
        route('admin.academic-years.update-school-days', props.academicYear.id),
        {
            preserveScroll: true
        }
    )
}
</script>

<template>
<AppLayout>
    <div class="surface-ground py-6 px-4">

        <div class="max-w-3xl mx-auto">

            <!-- CARD -->
            <div class="surface-card border-round-2xl shadow-2 p-6">

                <!-- HEADER -->
                <div class="mb-4 pb-3 border-bottom-1 surface-border">
                    <h2 class="text-2xl font-bold text-900 mb-1">
                        Pengaturan Hari Sekolah Efektif
                    </h2>
                    <p class="text-600 text-sm">
                        Tentukan hari operasional kegiatan belajar mengajar
                        untuk tahun ajaran ini.
                    </p>
                </div>

                <!-- INFO TAHUN AJARAN -->
                <div class="surface-100 border-round-lg p-3 mb-5 text-sm">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-calendar text-primary"></i>
                        <span class="font-medium">
                            Tahun Ajaran:
                            <b>{{ academicYear.name }}</b>
                        </span>
                    </div>
                </div>

                <!-- CHECKBOX GRID -->
                <div class="mb-5">
                    <h4 class="font-semibold text-900 mb-3">
                        Pilih Hari Sekolah
                    </h4>

                    <div class="grid">
                        <div
                            v-for="day in dayOptions"
                            :key="day.value"
                            class="col-6 md:col-4"
                        >
                            <div
                                class="flex align-items-center gap-2 p-3 border-round-lg border surface-border hover:surface-50 transition-colors"
                            >
                                <Checkbox
                                    v-model="form.school_days"
                                    :inputId="day.value"
                                    :value="day.value"
                                />
                                <label
                                    :for="day.value"
                                    class="font-medium cursor-pointer"
                                >
                                    {{ day.label }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CATATAN -->
                <div class="surface-50 border-left-3 border-primary p-3 border-round mb-5 text-sm">
                    <div class="font-medium mb-1">
                        Catatan Penting
                    </div>
                    <ul class="pl-3 m-0">
                        <li>Hari yang tidak dipilih tidak dihitung sebagai hari efektif.</li>
                        <li>Pengaturan ini memengaruhi perhitungan hari efektif dan JP.</li>
                        <li>Dapat diubah sewaktu-waktu sesuai keputusan operasional.</li>
                    </ul>
                </div>

                <!-- FOOTER ACTION -->
                <div class="flex justify-content-end gap-2">
                    <Button
                        label="Simpan Pengaturan"
                        icon="pi pi-save"
                        class="px-5"
                        :loading="form.processing"
                        @click="submit"
                    />
                </div>

            </div>
        </div>
    </div>
</AppLayout>
</template>
