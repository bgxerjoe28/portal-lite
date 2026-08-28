<template>
    <MobileLayout title="Buat Agenda Guru">
        <div class="p-3 mb-8">
            <div class="mb-4">
                <h2 class="text-xl font-bold m-0 text-900">Buat Agenda</h2>
                <p class="text-500 text-sm">Silakan lengkapi data KBM hari ini.</p>
            </div>

            <div class="flex flex-column gap-4">
                <div class="field">
                    <label class="block font-bold mb-2 text-900">Tanggal KBM</label>
                    <InputText
                        :value="formattedDate"
                        fluid
                        disabled
                    />
                    <small class="p-error block mt-1" v-if="errors.agenda_date">{{ errors.agenda_date }}</small>
                </div>

                <div class="field" v-if="form.agenda_date">
                    <label class="block font-bold mb-2 text-900">Jadwal Mengajar</label>
                    <div class="relative">
                        <Select
                            v-model="form.schedule_id"
                            :options="schedules"
                            optionLabel="label"
                            optionValue="id"
                            placeholder="Pilih Sesi Jadwal"
                            fluid
                            :loading="loadingSchedules"
                        />
                        <i v-if="loadingSchedules" class="pi pi-spin pi-spinner absolute" style="right: 2.5rem; top: 1rem;"></i>
                    </div>
                    <small class="p-error block mt-1" v-if="errors.schedule_id">{{ errors.schedule_id }}</small>

                    <small v-if="!loadingSchedules && schedules.length === 0" class="text-orange-500 mt-2 block">
                        <i class="pi pi-exclamation-triangle mr-1 text-xs"></i> Tidak ada jadwal mengajar di hari ini.
                    </small>
                </div>

                <div class="field" v-if="form.schedule_id">
                    <label class="block font-bold mb-2 text-900">Tujuan Pembelajaran (TP) <Tag v-if="detectedFase" :value="'Fase ' + detectedFase" severity="info" /></label>
                    
                    <Select
                        v-model="form.tp_id"
                        :options="tps"
                        optionLabel="label"
                        optionValue="id"
                        :placeholder="tps.length > 0 ? 'Pilih TP' : 'Tidak ada TP untuk Fase ' + detectedFase"
                        fluid
                        :disabled="tps.length === 0"
                        filter
                    />
                    <small class="p-error block mt-1" v-if="errors.tp_id">{{ errors.tp_id }}</small>
                </div>

                <div class="field" v-if="form.schedule_id">
                    <label class="block font-bold mb-2 text-900">Ringkasan Materi</label>
                    <Textarea
                        v-model="form.materi"
                        rows="4"
                        fluid
                        placeholder="Tuliskan materi yang disampaikan..."
                    />
                    <small class="p-error block mt-1" v-if="errors.materi">{{ errors.materi }}</small>
                </div>
            </div>

            <div class="mt-6">
                <Button
                    label="Lanjut ke Presensi Siswa"
                    icon="pi pi-users"
                    iconPos="right"
                    severity="primary"
                    fluid
                    size="large"
                    :disabled="!readyForPresensi"
                    @click="openPresensi"
                />
            </div>
        </div>

        <PresensiDialog
            v-model:visible="showPresensi"
            :students="students"
            :show-selfie="true"
            @submit="submitAgenda"
        />
    </MobileLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { router, usePage, useForm } from '@inertiajs/vue3'
import axios from 'axios'

import MobileLayout from '@/Layouts/MobileLayout.vue'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import PresensiDialog from './PresensiDialog.vue' // Gunakan yang sudah ada

const page = usePage()
const errors = computed(() => page.props.errors || {})

const schedules = ref([])
const tps = ref([])
const students = ref([])
const loadingSchedules = ref(false)
const showPresensi = ref(false)

const form = useForm({
    agenda_date: new Date(),
    schedule_id: null,
    tp_id: null,
    materi: '',
    keterangan: '',
    presensi: {},
    selfie: null,
})

const formattedDate = computed(() => {
    if (!form.agenda_date) return '';
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(form.agenda_date);
})

/* LOAD JADWAL BERDASARKAN TANGGAL */
watch(() => form.agenda_date, async (date) => {
    if (!date) return
    loadingSchedules.value = true
    
    const localDate = new Date(date).toLocaleDateString('en-CA'); 
    try {
        const res = await axios.get(route('guru.agenda.schedules-by-date'), { params: { date: localDate } })
        schedules.value = res.data
        form.schedule_id = null
        students.value = [] 
    } finally {
        loadingSchedules.value = false
    }
}, { immediate: true })




const readyForPresensi = computed(() => 
    form.agenda_date && form.schedule_id && form.materi
)

const openPresensi = () => {
    showPresensi.value = true
}

const submitAgenda = (data) => {
    const presensiReactive = data.presensi !== undefined ? data.presensi : data
    const selfieFile = data.selfie !== undefined ? data.selfie : null

    const payload = {
        ...form.data(),
        agenda_date: form.agenda_date ? new Date(form.agenda_date).toLocaleDateString('en-CA') : null,
        presensi: JSON.parse(JSON.stringify(presensiReactive)),
        selfie: selfieFile
    }

    router.post(route('guru.agenda.store'), payload, {
        preserveScroll: true,
        onSuccess: () => showPresensi.value = false,
    })
}
/* LOAD TP & SISWA BERDASARKAN JADWAL (DETAIL ID) */
const detectedFase = ref('')

watch(() => [form.schedule_id, form.agenda_date], async ([id, newDate]) => {
    if (!id || !newDate) {
        students.value = [] // 🔑 RESET SISWA JIKA JADWAL DIKOSONGKAN
        return
    }

    const formattedDate = new Date(newDate).toLocaleDateString('en-CA');

    // Cari data jadwal yang dipilih dari array schedules untuk cek nama kelasnya
    const selectedSchedule = schedules.value.find(s => s.id === id)
    if (selectedSchedule) {
        // Logika sederhana untuk tampilan di UI
        detectedFase.value = selectedSchedule.label.includes('X ') ? 'E' : 'F'
    }

    const [tpRes, studentRes] = await Promise.all([
        axios.get(route('guru.agenda.tp-by-schedule', id), {
            params: { date: formattedDate }
        }),
        axios.get(route('guru.agenda.students', id), {
            params: { date: formattedDate }
        }),
    ])

    tps.value = tpRes.data
    students.value = studentRes.data
})
</script>

<style scoped>
/* Membuat input terasa lebih besar untuk touch targets */
:deep(.p-inputtext), :deep(.p-select), :deep(.p-datepicker-input) {
    padding: 12px !important;
}

.field label {
    font-size: 0.9rem;
    color: #475569;
}
</style>