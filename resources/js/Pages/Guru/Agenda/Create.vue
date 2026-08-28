<template>
    <AppLayout title="Buat Agenda">
        <div class="card max-w-4xl mx-auto">

            <!-- HEADER -->
            <div class="mb-5">
                <h2 class="text-2xl font-bold text-900 flex align-items-center gap-2">
                    <i class="pi pi-calendar-plus text-primary"></i>
                    Buat Agenda Mengajar
                </h2>
                <p class="text-500 text-sm mt-1">
                    Isi agenda pembelajaran sebelum melakukan presensi siswa
                </p>
            </div>

            <!-- FORM CARD -->
            <div class="surface-card p-4 border-round-lg shadow-2">

                <!-- SECTION: TANGGAL & JADWAL -->
                <div class="mb-4">
                    <h4 class="font-semibold text-700 mb-3">
                        Informasi Jadwal
                    </h4>

                    <div class="grid gap-3">
                        <div class="col-12 md:col-6">
                            <label class="block mb-2 font-semibold text-700">
                                Tanggal KBM
                            </label>
                            <InputText
                                :value="formattedDate"
                                class="w-full"
                                disabled
                            />
                            <small class="p-error" v-if="errors.agenda_date">
                                {{ errors.agenda_date }}
                            </small>
                        </div>

                        <div class="col-12 md:col-6">
                            <label class="block mb-2 font-semibold text-700">
                                Jadwal Mengajar
                            </label>
                            <Select
                                v-model="form.schedule_id"
                                :options="schedules"
                                optionLabel="label"
                                optionValue="id"
                                placeholder="Pilih kelas & mapel"
                                class="w-full"
                            />
                            <small class="p-error" v-if="errors.schedule_id">
                                {{ errors.schedule_id }}
                            </small>
                        </div>
                    </div>
                </div>

                <!-- SECTION: TP -->
                <div class="mb-4">
                    <h4 class="font-semibold text-700 mb-3">
                        Tujuan Pembelajaran
                    </h4>

                    <div class="flex align-items-center gap-2 mb-2" v-if="detectedFase">
                        <Tag
                            severity="info"
                            :value="'Fase ' + detectedFase"
                            class="text-sm"
                        />
                        <span class="text-500 text-sm">
                            TP disesuaikan dengan fase kelas
                        </span>
                    </div>

                    <Select
                        v-model="form.tp_id"
                        :options="tps"
                        optionLabel="label"
                        optionValue="id"
                        :placeholder="tps.length
                            ? 'Pilih Tujuan Pembelajaran'
                            : 'Tidak ada TP untuk fase ini'"
                        class="w-full"
                        :disabled="tps.length === 0"
                        filter
                    />

                    <small class="p-error" v-if="errors.tp_id">
                        {{ errors.tp_id }}
                    </small>
                </div>

                <!-- SECTION: MATERI -->
                <div class="mb-5">
                    <h4 class="font-semibold text-700 mb-2">
                        Materi Pembelajaran
                    </h4>
                    <Textarea
                        v-model="form.materi"
                        rows="3"
                        class="w-full"
                        placeholder="Ringkasan materi atau aktivitas pembelajaran"
                        autoResize
                    />
                    <small class="p-error" v-if="errors.materi">
                        {{ errors.materi }}
                    </small>
                </div>

                <!-- ACTION -->
                <div class="flex justify-content-end">
                    <Button
                        label="Lanjut ke Presensi"
                        icon="pi pi-users"
                        severity="primary"
                        :disabled="!readyForPresensi"
                        @click="openPresensi"
                    />
                </div>
            </div>
        </div>

        <!-- MODAL PRESENSI -->
        <PresensiDialog
            v-model:visible="showPresensi"
            :students="students"
            :show-selfie="true"
            @submit="submitAgenda"
        />
    </AppLayout>
</template>
<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { router, usePage, useForm } from '@inertiajs/vue3'
import axios from 'axios'

import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

import PresensiDialog from './PresensiDialog.vue'
//import { use } from 'react'

const page = usePage()
const errors = computed(() => page.props.errors || {})

const schedules = ref([])
const tps = ref([])
const students = ref([])

const showPresensi = ref(false)

const form = useForm({
    agenda_date: new Date(),
    schedule_id: null,
    tp_id: null,
    materi: '',
    presensi:{},
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

/* ===============================
   LOAD JADWAL BY DATE
================================ */
watch(() => form.agenda_date, async (date) => {
    if (!date) return

    // Konversi objek Date ke string YYYY-MM-DD sesuai waktu lokal laptop
    const localDate = new Date(date).toLocaleDateString('en-CA'); 

    const res = await axios.get(
        route('guru.agenda.schedules-by-date'),
        { params: { date: localDate } } // Kirim "2026-01-08"
    )

    schedules.value = res.data
    form.schedule_id = null
    tps.value = []
    students.value = []
}, { immediate: true })

/* ===============================
   READY CHECK
================================ */
const readyForPresensi = computed(() =>
    form.agenda_date &&
    form.schedule_id &&
    form.materi
)

/* ===============================
   PRESENSI
================================ */
const openPresensi = () => {
    showPresensi.value = true
}

// Di Create.vue, ubah fungsi submitAgenda
const submitAgenda = (data) => {
    const presensiReactive = data.presensi !== undefined ? data.presensi : data
    const selfieFile = data.selfie !== undefined ? data.selfie : null

    // Buat salinan data agar tidak merusak objek Date di form asli
    const payload = {
        ...form.data(),
        agenda_date: form.agenda_date ? new Date(form.agenda_date).toLocaleDateString('en-CA') : null,
        presensi: JSON.parse(JSON.stringify(presensiReactive)),
        selfie: selfieFile
    }

    // Gunakan router secara langsung untuk kontrol lebih baik
    router.post(route('guru.agenda.store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            showPresensi.value = false; // Tutup modal setelah sukses
        },
        onError: () => {
            showPresensi.value = false; // Tutup modal agar user bisa lihat error di form utama
        }
    })
}
/* ===============================
   LOAD TP + STUDENTS BY SCHEDULE
================================ */
/* Per Fase an*/
const detectedFase = ref('')

watch(() => [form.schedule_id, form.agenda_date], async ([id, newDate]) => {
    if (!id || !newDate) {
        students.value = []
        return
    }

    const formattedDate = new Date(newDate).toLocaleDateString('en-CA');

    const selectedSchedule = schedules.value.find(s => s.id === id)
    if (selectedSchedule) {
        detectedFase.value = selectedSchedule.label.includes('X ') ? 'E' : 'F'
    }

    const [tpRes, studentRes] = await Promise.all([
        axios.get(route('guru.agenda.tp-by-schedule', id), {
            params: { date: formattedDate }
        }),
        // 🔑 PERBAIKAN DI SINI: Tambahkan params date agar sampai ke Controller
        axios.get(route('guru.agenda.students', id), {
            params: { date: formattedDate } 
        }),
    ])

    tps.value = tpRes.data
    students.value = studentRes.data
})
</script>