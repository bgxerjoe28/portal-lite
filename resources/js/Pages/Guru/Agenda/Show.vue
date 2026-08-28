<template>
    <AppLayout  title="Lihat Agenda">
        <div class="card">
            <div class="flex justify-content-between align-items-start mb-4">
                <div class="flex-1">
                    <Button icon="pi pi-arrow-left" label="Kembali" text severity="secondary" @click="router.get(route('guru.agenda.index'))" />
                    <h2 class="text-2xl font-bold mt-2">Detail Agenda</h2>
                    <div class="text-sm text-500 mt-1 flex align-items-center gap-2">
                        <Tag severity="info" :value="agenda.classroom?.name" />
                        <Tag severity="secondary" :value="agenda.subject?.name" />
                        <span class="font-bold" v-if="form.tp_id">TP {{ selectedTpCode }}</span>
                    </div>
                    <div class="text-sm mt-1">
                        <i class="pi pi-calendar mr-1"></i> {{ formatDate(agenda.date) }} ·
                        <i class="pi pi-clock mr-1 text-xs"></i> Jam {{ jamKe }}
                    </div>
                </div>
                <Button label="Simpan Perubahan" icon="pi pi-save" severity="primary" class="p-button-lg" :loading="loading" @click="submit" />
            </div>

            <div class="grid mb-4">
                <div :class="selfieAttachment ? 'col-12 md:col-4' : 'col-12 md:col-6'">
                    <Panel header="Tujuan Pembelajaran (TP)" toggleable>
                        <div class="p-2">
                            <Select
                                v-model="form.tp_id"
                                :options="tps"
                                optionLabel="label"
                                optionValue="id"
                                placeholder="Pilih Tujuan Pembelajaran"
                                class="w-full text-700"
                                showClear
                                filter
                            />
                        </div>
                    </Panel>
                </div>
                <div :class="selfieAttachment ? 'col-12 md:col-4' : 'col-12 md:col-6'">
                    <Panel header="Materi & Keterangan Mengajar" toggleable>
                        <div class="p-2 flex flex-column gap-3">
                            <div>
                                <label class="block mb-1 font-semibold text-700 text-sm">Materi Pembelajaran <span class="text-red-500">*</span></label>
                                <Textarea
                                    v-model="form.materi"
                                    rows="2"
                                    class="w-full"
                                    placeholder="Ringkasan materi atau aktivitas pembelajaran"
                                    autoResize
                                />
                            </div>
                            <div>
                                <label class="block mb-1 font-semibold text-700 text-sm">Keterangan / Catatan Tambahan</label>
                                <InputText
                                    v-model="form.keterangan"
                                    class="w-full"
                                    placeholder="Keterangan tambahan (opsional)"
                                />
                            </div>
                        </div>
                    </Panel>
                </div>
                <div class="col-12 md:col-4" v-if="selfieAttachment">
                    <Panel header="Foto Presensi (Selfie Guru)" toggleable>
                        <div class="flex justify-content-center align-items-center p-2">
                            <img :src="'/storage/' + selfieAttachment.file_path" class="max-h-15rem border-round shadow-2" style="max-width: 100%; object-fit: contain;" alt="Selfie Guru" />
                        </div>
                    </Panel>
                </div>
            </div>

            <div class="surface-card shadow-2 border-round overflow-hidden">
                <DataTable :value="rows" stripedRows responsiveLayout="stack">
                    <Column header="No" style="width:60px" class="pl-4">
                        <template #body="{ index }">{{ index + 1 }}</template>
                    </Column>

                    <Column header="Nama Siswa">
                        <template #body="{ data }">
                            <div class="font-medium text-900">{{ data.student.full_name }}</div>
                            <div class="text-xs text-500 uppercase">{{ data.student.nis }}</div>
                        </template>
                    </Column>

                    <Column header="Status Presensi Kelas" style="width:220px" class="pr-4">
                        <template #body="{ data }">
                            <div v-if="['S', 'I', 'A'].includes(data.note)" class="flex flex-column gap-1">
                                <Tag :value="getPermitLabel(data.note)" :severity="getSeverity(data.note)" class="w-full py-2" />
                                <small v-if="data.keterangan" class="text-xs italic text-500 text-center">{{ data.keterangan }}</small>
                            </div>

                            <div v-else class="flex flex-column gap-2 w-full">
                                <Tag v-if="data.note === 'T'" value="TERLAMBAT" severity="warning" icon="pi pi-clock" />
                                <Tag v-if="data.note === 'D'" value="DISPENSASI" severity="info" icon="pi pi-info-circle" />
                                <Button
                                    :label="presensi[data.id] === 'H' ? 'HADIR' : 'ABSEN'"
                                    :icon="presensi[data.id] === 'H' ? 'pi pi-check-circle' : 'pi pi-times-circle'"
                                    :severity="presensi[data.id] === 'H' ? 'success' : 'danger'"
                                    :outlined="presensi[data.id] !== 'H'"
                                    class="w-full font-bold"
                                    @click="presensi[data.id] = presensi[data.id] === 'H' ? 'A' : 'H'"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <div class="flex flex-wrap gap-4 p-4 bg-gray-50 border-top-1 border-200 font-bold">
                    <span class="text-700">Total: {{ rows.length }}</span>
                    <span class="text-green-600">Hadir: {{ counts.H }}</span>
                    <span class="text-red-600">Tidak Hadir: {{ counts.totalAbsen }}</span>

                    <span class="text-blue-600 font-bold">Sakit:{{ counts.S }}</span>
                    <span class="text-orange-600 font-bold">Izin: {{ counts.I }}</span>
                    <span class="text-purple-600-600 font-bold">Dispen: {{ counts.D }}</span>
                    <span class="text-red-600 font-bold">Tanpa Keterangan: {{ counts.A }}</span>
                    <span v-if="counts.T > 0" class="text-indigo-600 font-bold">Terlambat: {{ counts.T }}</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import ToggleButton from 'primevue/togglebutton'
import Panel from 'primevue/panel'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import InputText from 'primevue/inputtext'
import dayjs from 'dayjs'
import 'dayjs/locale/id'

dayjs.locale('id')

const props = defineProps({
    agenda: Object,
})

const loading = ref(false)
const rows = props.agenda.attendances

const tps = ref([])
const form = ref({
    tp_id: props.agenda.learning_objective_tp_id,
    materi: props.agenda.materi_pembelajaran,
    keterangan: props.agenda.keterangan || '',
})

onMounted(async () => {
    const detailId = props.agenda.schedule_detail_id;
    if (detailId) {
        try {
            const res = await axios.get(route('guru.agenda.tp-by-schedule', detailId))
            tps.value = res.data
        } catch (e) {
            console.error("Gagal load TP:", e)
        }
    }
})

const selectedTpCode = computed(() => {
    const selected = tps.value.find(tp => tp.id === form.value.tp_id)
    return selected ? selected.label.split(' — ')[0] : (props.agenda.tp?.kode_tp || '-')
})

/**
 * 1. Inisialisasi State
 * Menggunakan kolom 'note' sebagai acuan utama
 */
const presensi = ref(
    Object.fromEntries(
        rows.map(a => [
            a.id, 
            (a.note === 'H' || a.is_present) ? 'H' : (a.note || 'A')
        ])
    )
)

/**
 * 2. Logika Toggle
 * Memastikan nilai yang tersimpan tetap string 'H' atau 'A'
 */
const handleToggle = (id) => {
    presensi.value[id] = presensi.value[id] === 'hadir' ? 'absen' : 'hadir';
}
const toggleStatus = (id) => {
    presensi.value[id] = presensi.value[id] === 'hadir' ? 'absen' : 'hadir';
}

/**
 * 3. Hitung Rekap (Computed)
 */
const counts = computed(() => {
    const vals = Object.values(presensi.value);
    return {
        H: vals.filter(v => v === 'H').length,
        S: vals.filter(v => v === 'S').length,
        I: vals.filter(v => v === 'I').length,
        A: vals.filter(v => v === 'A' || v === 'absen').length,
        D: vals.filter(v => v === 'D').length,
        totalAbsen: vals.filter(v => v !== 'H').length,
        T: rows.filter(r => r.note === 'T').length
    }
})

// Konfigurasi Label & Warna
const getPermitLabel = (t) => ({ 'S': 'SAKIT', 'I': 'IZIN', 'D': 'DISPEN', 'A': 'TANPA KETERANGAN (A)' }[t] || t);
const getSeverity = (t) => ({ 'S': 'info', 'I': 'warning', 'D': 'help', 'A': 'danger' }[t] || 'secondary');

const submit = () => {
    if (!form.value.materi) {
        return
    }
    loading.value = true

    const mappedPresensi = {}
    rows.forEach(a => {
        mappedPresensi[a.student_id] = presensi.value[a.id]
    })

    router.put(
        route('guru.agenda.update', props.agenda.id),
        {
            tp_id: form.value.tp_id,
            materi: form.value.materi,
            keterangan: form.value.keterangan,
            presensi: mappedPresensi
        },
        { 
            onFinish: () => (loading.value = false) 
        }
    )
}

const formatDate = (val) => val ? dayjs(val).format('dddd, DD MMM YYYY') : '-'

const jamKe = computed(() => {
    const d = props.agenda.schedule?.details?.[0]
    return d ? `${d.start_slot}-${d.end_slot}` : '-'
})

const selfieAttachment = computed(() => {
    return props.agenda.attachments?.find(a => a.type === 'photo');
})
</script>
<style scoped>
/* Memastikan tombol HADIR (H) selalu punya background penuh */
:deep(.p-button.p-button-success:not(.p-button-outlined)) {
    background-color: #22c55e !important;
    color: #ffffff !important;
    opacity: 1;
}
</style>