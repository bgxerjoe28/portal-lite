<script setup>
import { ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { useConfirm } from 'primevue/useconfirm'

import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import SelectButton from 'primevue/selectbutton'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Checkbox from 'primevue/checkbox'


/* ================= PROPS ================= */
const props = defineProps({
    academicYear: Object, // { id, start_date, end_date }
    events: Array         // [{ id, title, type, start_date, end_date }]
})

/* ================= CONFIRM ================= */
const confirm = useConfirm()

/* ================= STATE ================= */
const mode = ref('single') // single | range
const dateRange = ref(null)
const singleDate = ref(null)  

const form = useForm({
    academic_year_id: props.academicYear.id,
    title: '',
    type: 'holiday',
    is_holiday: false,
    start_date: null,
    end_date: null,
})

/* ================= OPTIONS ================= */
const modeOptions = [
    { label: 'Tunggal', value: 'single' },
    { label: 'Rentang', value: 'range' },
]

const typeOptions = [
    { label: 'Libur', value: 'holiday' },
    { label: 'Kegiatan', value: 'event' },
    { label: 'Ujian', value: 'exam' },
    { label: 'Info', value: 'info' },
]

const labelMap = {
    holiday: 'Libur',
    event: 'Kegiatan',
    exam: 'Ujian',
    info: 'Info',
}

const typeSeverity = {
    holiday: 'danger',
    event: 'success',
    exam: 'warning',
    info: 'info',
}

/* ================= DATE HELPERS (FINAL) ================= */
/**
 * Parse tanggal (YYYY-MM-DD atau ISO UTC) sebagai LOCAL Date.
 *
 * Masalah: App timezone Asia/Jakarta (UTC+7).
 * - Carbon::parse("2026-08-17") dengan timezone Jakarta = 2026-08-17T00:00:00+07:00
 * - toJson() → "2026-08-16T17:00:00.000000Z" (UTC) ← menyebabkan geser 1 hari!
 * - slice(0,10) dari string UTC tsb = "2026-08-16" ← SALAH
 *
 * Solusi robust:
 * - Jika string berisi 'T' atau 'Z' (UTC ISO datetime), parse dengan new Date()
 *   lalu ambil LOCAL date parts (getFullYear/Month/Date) → hasilnya tetap benar
 * - Jika string plain YYYY-MM-DD, gunakan local constructor langsung
 */
const parseYmd = (value) => {
    if (!value) return null

    const str = String(value).trim()

    // Case 1: UTC ISO datetime string (e.g., "2026-08-16T17:00:00.000000Z")
    // new Date("2026-08-16T17:00:00Z") = UTC 17:00 Aug 16 = WIB midnight Aug 17
    // getDate() in WIB = 17 ← BENAR
    if (str.includes('T') || str.endsWith('Z')) {
        const d = new Date(str)
        if (Number.isNaN(d.getTime())) return null
        // Ambil local date parts dari hasil parse, lalu buat local-midnight Date
        return new Date(d.getFullYear(), d.getMonth(), d.getDate())
    }

    // Case 2: Plain YYYY-MM-DD (e.g., "2026-08-17")
    // JANGAN pakai new Date("2026-08-17") karena browser parse sebagai UTC!
    // Gunakan local constructor: new Date(y, m-1, d)
    const ymd = str.slice(0, 10)
    if (!/^\d{4}-\d{2}-\d{2}$/.test(ymd)) return null

    const [y, m, d] = ymd.split('-').map(Number)
    return new Date(y, m - 1, d) // LOCAL time, bukan UTC
}

/**
 * Konversi Date object ke YYYY-MM-DD menggunakan local date parts
 */
const toYmd = (date) => {
    if (!date) return null
    if (typeof date === 'string') return date.slice(0, 10) // ambil YYYY-MM-DD saja
    if (!(date instanceof Date)) return null

    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
}

/**
 * Format tanggal untuk tampilan tabel
 */
const formatTanggal = (start, end) => {
    const startDt = parseYmd(start)
    const endDt   = parseYmd(end)

    if (!startDt) return '-'

    const fmt = (d) =>
        new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        }).format(d)

    // Bandingkan sebagai string agar tidak ada masalah object equality
    const startStr = toYmd(startDt)
    const endStr   = toYmd(endDt)

    if (!endDt || startStr === endStr) {
        return fmt(startDt)
    }

    return `${fmt(startDt)} – ${fmt(endDt)}`
}

/* ================= SUBMIT ================= */
const submit = () => {
    if (!form.title) {
        alert('Judul wajib diisi')
        return
    }

    if (mode.value === 'single') {
        if (!singleDate.value) {
            alert('Tanggal belum dipilih')
            return
        }
        const ymd = toYmd(singleDate.value)
        form.start_date = ymd
        form.end_date = ymd  
    } else {
        if (!dateRange.value || dateRange.value.length !== 2) {
            alert('Rentang tanggal belum lengkap')
            return
        }
        form.start_date = toYmd(dateRange.value[0]) 
        form.end_date   = toYmd(dateRange.value[1]) 
    }

    // 🔥 INI YANG BENAR
    form.post(route('admin.calendar.store'),{
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            form.type = 'holiday'
            form.is_holiday = false
            singleDate.value = null           
            mode.value = 'single'
            dateRange.value = null
        }
    })
}


/* ================= DELETE ================= */
const confirmDelete = (event) => {
    confirm.require({
        header: 'Konfirmasi Hapus',
        message: `Hapus event <b>${event.title}</b>?`,
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.calendar.destroy', event.id), {
                preserveScroll: true
            })
        }
    })
}
watch(() => form.type, (val) => {
    if (val === 'holiday') {
        form.is_holiday = true
    }
})
</script>

<template>
<AppLayout>

    <!-- ===== CARD HEADER ===== -->
    <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
        <div class="flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center gap-3">
            <div>
                <h2 class="text-2xl font-bold text-900 m-0">Kalender Akademik</h2>
                <span class="text-600 text-sm" v-if="academicYear">
                    Tahun Ajaran Aktif: <b>{{ academicYear.name }}</b> ({{ academicYear.semester }})
                </span>
            </div>
            <div>
                <Button 
                    label="Lihat & Cetak Kalender" 
                    icon="pi pi-calendar" 
                    class="p-button-primary"
                    @click="router.get(route('calendar.view'))"
                />
            </div>
        </div>
    </div>

    <!-- ===== FORM ===== -->
    <div class="surface-card border-round-lg shadow-1 p-4 mb-4">
        <div class="mb-4 pb-3 border-bottom-1 surface-border">
            <h3 class="text-xl font-semibold m-0">Tambah Event Kalender</h3>
        </div>

        <div class="formgrid grid">

            <div class="field col-12 md:col-8">
                <label class="font-medium block mb-1">Judul</label>
                <InputText v-model="form.title" class="w-full" />
            </div>

            <div class="field col-12 md:col-4">
                <label class="font-medium block mb-1">Jenis</label>
                <Select
                    v-model="form.type"
                    :options="typeOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full"
                    
                />
            </div>

            <div class="field col-12 md:col-4">
                <label class="font-medium block mb-1">Mode Tanggal</label>
                <SelectButton
                    v-model="mode"
                    :options="modeOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full"
                    
                />
            </div>

            <div v-if="mode === 'single'" class="field col-12 md:col-4">
                <label class="font-medium block mb-1">Tanggal</label>
                <DatePicker
                    v-model="singleDate"
                    :minDate="parseYmd(academicYear.start_date)"
                    :maxDate="parseYmd(academicYear.end_date)"
                    showIcon
                    class="w-full"
                    dateFormat="dd MM yy"
                />
            </div>

            <div v-else class="field col-12 md:col-4">
                <label class="font-medium block mb-1">Rentang Tanggal</label>
                <DatePicker
                    v-model="dateRange"
                    selectionMode="range"
                    :minDate="parseYmd(academicYear.start_date)"
                    :maxDate="parseYmd(academicYear.end_date)"
                    showIcon
                    class="w-full"
                    dateFormat="dd MM yy"
                />
            </div>
            <div class="field">
                <label class="font-bold">Status KBM</label>
                <div class="flex align-items-center gap-2 mt-2">
                    <Checkbox v-model="form.is_holiday" :binary="true" />
                    <span>Tidak ada KBM (Hari Libur)</span>
                </div>
            </div>

            <div class="field col-12 md:col-4 flex align-items-end">
                <Button
                    label="Simpan Event"
                    icon="pi pi-save"
                    class="w-full"
                    @click="submit"
                />
            </div>
        </div>
    </div>

    <!-- ===== TABLE ===== -->
    <div class="surface-card border-round-lg shadow-1 p-4">
        <div class="mb-4">
            <h3 class="text-xl font-semibold m-0">Daftar Event</h3>
            <span class="text-600 text-sm">Event dalam tahun ajaran aktif</span>
        </div>

        <DataTable :value="events || []" stripedRows class="p-datatable-sm">
            <Column header="Tanggal" style="width:320px">
                <template #body="{ data }">
                    {{ formatTanggal(data.start_date, data.end_date) }}
                </template>
            </Column>

            <Column header="Keterangan">
            <template #body="{ data }">
                <span v-if="data">
                    {{ data.title }}
                </span>
                <span v-else>-</span>
            </template>
            </Column>

            <Column header="Jenis" style="width:120px">
                <template #body="{ data }">
                    <Tag
                        :value="labelMap[data.type]"
                        :severity="typeSeverity[data.type]"
                    />
                </template>
            </Column>
            <Column header="KBM" class="text-center" style="width: 8%">
                <template #body="{ data }">
                    <i
                        :class="data.is_holiday
                            ? 'pi pi-ban text-red-500'
                            : 'pi pi-check text-green-600'"
                        v-tooltip.top="data.is_holiday ? 'Non-KBM' : 'KBM'"
                    />
                </template>
            </Column>

            <Column header="Aksi" style="width:90px">
                <template #body="{ data }">
                    <Button
                        icon="pi pi-trash"
                        severity="danger"
                        text
                        @click="confirmDelete(data)"
                    />
                </template>
            </Column>
        </DataTable>
    </div>

</AppLayout>
</template>
