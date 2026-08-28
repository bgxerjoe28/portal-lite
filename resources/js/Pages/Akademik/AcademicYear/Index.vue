<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import SelectButton from 'primevue/selectbutton'
import DatePicker from 'primevue/datepicker'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'

const confirm = useConfirm();
const props = defineProps({
    academicYears: Array,   // ⬅️ COLLECTION (BUKAN paginator)
    activeYear: Object
})
const formatDateId = (dateString, withDay = false) => {
    if (!dateString) return '-'

    const date = new Date(dateString)

    const options = {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    }

    if (withDay) {
        options.weekday = 'long'
    }

    return new Intl.DateTimeFormat('id-ID', options).format(date)
}
const displayModal = ref(false)
const isEditing = ref(false)
const editId = ref(null)

const semesterOptions = [
    { label: 'Ganjil', value: 'ganjil' },
    { label: 'Genap', value: 'genap' }
]

// ---- FORM ----
const form = useForm({
    name: '',
    semester: 'ganjil',
    start_date: null,
    end_date: null
})

// ---- HELPER (ANTI BUG TANGGAL) ----
const toYmd = (d) => {
    if (!d) return null
    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

// ---- OPEN MODAL ----
const openCreate = () => {
    isEditing.value = false
    editId.value = null
    form.reset()
    displayModal.value = true
}

const openEdit = (row) => {
    isEditing.value = true
    editId.value = row.id

    form.name = row.name
    form.semester = row.semester
    form.start_date = row.start_date ? new Date(row.start_date) : null
    form.end_date   = row.end_date   ? new Date(row.end_date)   : null

    displayModal.value = true
}

// ---- SUBMIT ----
const submit = () => {
    const payload = {
        name: form.name,
        semester: form.semester,
        start_date: toYmd(form.start_date),
        end_date: toYmd(form.end_date)
    }

    if (isEditing.value) {
        router.put(
            route('admin.academic-years.update', editId.value),
            payload,
            { onSuccess: () => displayModal.value = false }
        )
    } else {
        router.post(
            route('admin.academic-years.store'),
            payload,
            { onSuccess: () => displayModal.value = false }
        )
    }
}

const confirmActivate = (data) => {
    confirm.require({
        message: `Aktifkan tahun ajaran <b>${data.name}</b>?<br/>Tahun ajaran lain akan dinonaktifkan.`,
        header: 'Konfirmasi Aktivasi',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-success',
        accept: () => {
            router.put(
                route('admin.academic-years.activate', data.id),
                {},
                { preserveScroll: true }
            )
        }
})
}

// ==== ROLLOVER LOGIC ====
const displayRolloverModal = ref(false)
const rolloverType = ref('semester') // 'semester' (ganjil->genap) atau 'year' (genap->ganjil)
const candidates = ref({ kelas_10_11: [], kelas_12: [] })
const isLoadingCandidates = ref(false)

const rolloverForm = useForm({
    old_year_id: null,
    new_year_id: null,
    type: 'semester'
})

const openRolloverModal = () => {
    if (!props.activeYear) {
        // Tampilkan alert error
        return
    }

    rolloverForm.old_year_id = props.activeYear.id
    rolloverType.value = props.activeYear.semester === 'ganjil' ? 'semester' : 'year'
    rolloverForm.type = rolloverType.value
    
    // Cari tahun ajaran baru secara default (opsi yang belum aktif)
    const inactiveYears = props.academicYears.filter(y => !y.is_active)
    if (inactiveYears.length > 0) {
        rolloverForm.new_year_id = inactiveYears[0].id
    } else {
        rolloverForm.new_year_id = null
    }

    if (rolloverType.value === 'year') {
        fetchCandidates()
    }

    displayRolloverModal.value = true
}

const fetchCandidates = async () => {
    if (!props.activeYear) return
    isLoadingCandidates.value = true
    try {
        const response = await axios.get(route('akademik.academic-years.candidates', { year_id: props.activeYear.id }))
        candidates.value = response.data
    } catch (error) {
        console.error('Failed to fetch candidates', error)
    } finally {
        isLoadingCandidates.value = false
    }
}

const submitRollover = () => {
    rolloverForm.post(route('akademik.academic-years.rollover'), {
        preserveScroll: true,
        onSuccess: () => {
            displayRolloverModal.value = false
        }
    })
}

const quickPromote = (student, status) => {
    confirm.require({
        message: `Apakah Anda yakin ingin mengubah status ${student.name} menjadi ${status === 'lulus' ? 'Lulus' : 'Naik Kelas'}?`,
        header: 'Konfirmasi',
        icon: 'pi pi-exclamation-triangle',
        accept: async () => {
            try {
                await axios.post(route('admin.classrooms.promotion.store'), {
                    source_year_id: props.activeYear.id,
                    source_classroom_id: student.classroom_id,
                    promotions: [
                        { student_id: student.id, status: status }
                    ]
                });
                // Refresh candidates list
                fetchCandidates();
            } catch (error) {
                console.error('Failed to update student status', error);
            }
        }
    });
}
</script>
<template>
<AppLayout>

    <!-- ===== CARD FORM ===== -->
    <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
        <div class="flex justify-content-between align-items-center">
            <div>
                <h2 class="text-2xl font-bold text-900 m-0">
                    Tahun Ajaran
                </h2>
                <span class="text-600 text-sm">
                    Kelola periode akademik sekolah
                </span>
            </div>

            <div class="flex gap-2">
                <Button
                    v-if="activeYear"
                    label="Proses Perpindahan"
                    icon="pi pi-forward"
                    severity="warning"
                    @click="openRolloverModal"
                />
                <Button
                    label="Tambah Tahun Ajaran"
                    icon="pi pi-plus"
                    severity="primary"
                    @click="openCreate"
                />
            </div>
        </div>
    </div>


    <!-- ===== CARD TABLE ===== -->
    <div class="surface-card p-4 border-round-lg shadow-1">
        <DataTable
            :value="academicYears"
            stripedRows
            showGridlines
            class="p-datatable-sm academic-table"
        >
            <Column field="name" header="Tahun Ajaran" />

            <Column header="Semester">
                <template #body="{ data }">
                    <Tag
                        :value="data.semester"
                        :severity="data.semester === 'ganjil' ? 'info' : 'success'"
                    />
                </template>
            </Column>

            <Column header="Periode">
                <template #body="{ data }">
                    <div class="text-sm">
                        <span class="font-medium">
                            {{ formatDateId(data.start_date) }}
                        </span>
                        <span class="mx-1">–</span>
                        <span class="font-medium">
                        {{ formatDateId(data.end_date) }}
                        </span>
                    </div>
                </template>
            </Column>

            <Column header="Status">
                <template #body="{ data }">
                    <Tag
                        :value="data.is_active ? 'Aktif' : 'Nonaktif'"
                        :severity="data.is_active ? 'success' : 'secondary'"
                    />
                </template>
            </Column>

            <Column header="Aksi" style="width:160px">
                <template #body="{ data }">
                    <div class="flex gap-2 justify-content-center align-items-center">

                        <!-- STATUS AKTIF -->
                        <i
                            v-if="data.is_active === true || data.is_active === 1"
                            class="pi pi-check-circle text-blue-500 text-xl"
                            v-tooltip.top="'Tahun ajaran aktif'"
                        ></i>

                        <!-- AKTIFKAN -->
                        <Button
                            v-else
                            icon="pi pi-check"
                            severity="success"
                            size="small"
                            outlined
                            v-tooltip.top="'Aktifkan tahun ajaran ini'"
                            @click="confirmActivate(data)"
                        />

                        <!-- SETTING HARI SEKOLAH -->
                        <Button
                            icon="pi pi-calendar"
                            severity="secondary"
                            size="small"
                            text
                            v-tooltip.top="
                            data.school_days
                                ? 'Hari sekolah: ' + data.school_days.join(', ')
                                : 'Hari sekolah belum diatur'
                            "
                            @click="router.get(
                                route('admin.academic-years.school-days', data.id)
                            )"
                        />

                    </div>
                </template>
            </Column>

        </DataTable>
    </div>


    <!-- ===== MODAL FORM ===== -->
    <Dialog
        v-model:visible="displayModal"
        :header="isEditing ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran'"
        modal
        :style="{ width: '480px' }"
    >
        <div class="surface-card p-4 border-round-lg">

            <div class="field mb-3">
                <label class="font-medium mb-2 block">
                    Nama Tahun Ajaran
                </label>
                <InputText
                    v-model="form.name"
                    class="w-full"
                    placeholder="Contoh: 2025 / 2026"
                />
            </div>

            <div class="field mb-3">
                <label class="font-medium mb-2 block">
                    Semester
                </label>
                <SelectButton
                    v-model="form.semester"
                    :options="semesterOptions"
                    optionLabel="label"
                    optionValue="value"
                />
            </div>

            <div class="field mb-3">
                    <label class="font-medium mb-2 block">
                        Tanggal Mulai
                    </label>
                    <DatePicker
                        v-model="form.start_date"
                        showIcon
                        class="w-full"
                    />
            </div>
            <div class="field mb-3">
                    <label class="font-medium mb-2 block">
                        Tanggal Selesai
                    </label>
                    <DatePicker
                        v-model="form.end_date"
                        showIcon
                        class="w-full"
                    />
            </div>
            

            <div class="flex justify-content-end gap-2 mt-4 pt-3 border-top-1 border-200">
                <Button
                    label="Batal"
                    severity="secondary"
                    text
                    @click="displayModal = false"
                />
                <Button
                    label="Simpan"
                    icon="pi pi-save"
                    severity="primary"
                    @click="submit"
                />
            </div>

        </div>
    </Dialog>

    <!-- ===== MODAL ROLLOVER ===== -->
    <Dialog
        v-model:visible="displayRolloverModal"
        :header="rolloverType === 'semester' ? 'Pindah Semester (Ganjil ke Genap)' : 'Ganti Tahun Ajaran (Genap ke Ganjil)'"
        modal
        :style="{ width: '700px' }"
    >
        <div class="surface-card p-4 border-round-lg">
            
            <div class="field mb-4">
                <label class="font-medium mb-2 block text-xl">Tahun Ajaran Tujuan</label>
                <div class="text-600 mb-2">Pilih Tahun Ajaran / Semester berikutnya. Pastikan Anda sudah membuatnya terlebih dahulu.</div>
                <select v-model="rolloverForm.new_year_id" class="w-full p-2 border border-300 border-round">
                    <option :value="null" disabled>-- Pilih Tahun Ajaran Baru --</option>
                    <option v-for="y in academicYears.filter(y => !y.is_active)" :key="y.id" :value="y.id">
                        {{ y.name }} - Semester {{ y.semester }}
                    </option>
                </select>
            </div>

            <!-- JIKA PINDAH SEMESTER (GANJIL -> GENAP) -->
            <div v-if="rolloverType === 'semester'" class="p-3 bg-blue-50 text-blue-900 border-round mb-4 border border-blue-200">
                <div class="font-bold mb-2">ℹ️ Informasi Proses Pindah Semester:</div>
                <ul class="m-0 pl-3">
                    <li>Semua <b>Kelas</b> akan disalin ke semester baru beserta <b>Wali Kelasnya</b>.</li>
                    <li>Semua <b>Siswa</b> akan di-plot secara otomatis ke kelas yang sama.</li>
                    <li>Tidak ada proses Naik/Tinggal Kelas atau Kelulusan.</li>
                </ul>
            </div>

            <!-- JIKA GANTI TAHUN AJARAN (GENAP -> GANJIL) -->
            <div v-if="rolloverType === 'year'" class="mb-4">
                <div class="p-3 bg-orange-50 text-orange-900 border-round mb-4 border border-orange-200">
                    <div class="font-bold mb-2">⚠️ Informasi Proses Ganti Tahun Ajaran:</div>
                    <ul class="m-0 pl-3">
                        <li>Semua <b>Kelas</b> akan disalin, tetapi <b>Wali Kelas dikosongkan</b>.</li>
                        <li><b>Siswa tidak akan di-plot</b> secara otomatis (Plotting kosong). Admin harus mem-plot ulang di halaman Plotting Kelas.</li>
                        <li>Siswa yang sudah diset <b>LULUS</b> otomatis menjadi ALUMNI dan akunnya dinonaktifkan.</li>
                        <li>Pastikan Anda sudah mengecek daftar konfirmasi di bawah ini.</li>
                    </ul>
                </div>

                <div v-if="isLoadingCandidates" class="flex justify-content-center p-4">
                    <i class="pi pi-spin pi-spinner text-3xl"></i>
                </div>
                
                <div v-else>
                    <!-- TAB UNTUK KELAS 10 & 11 (TINGGAL KELAS) -->
                    <div class="font-bold text-lg mb-2">1. Daftar Siswa Tinggal Kelas (X & XI)</div>
                    <div class="max-h-15rem overflow-y-auto mb-4 border border-200 border-round p-0">
                        <DataTable :value="candidates.kelas_10_11.filter(s => s.status === 'retained')" size="small" class="p-datatable-sm">
                            <Column header="No" style="width: 3rem">
                                <template #body="{ index }">{{ index + 1 }}</template>
                            </Column>
                            <Column field="nis" header="NIS"></Column>
                            <Column field="name" header="Nama Siswa"></Column>
                            <Column field="classroom_name" header="Kelas Asal"></Column>
                            <Column header="Aksi" style="width: 8rem">
                                <template #body="{ data }">
                                    <Button label="Naik Kelas" size="small" severity="success" outlined @click="quickPromote(data, 'naik')" />
                                </template>
                            </Column>
                        </DataTable>
                        <div v-if="candidates.kelas_10_11.filter(s => s.status === 'retained').length === 0" class="p-3 text-center text-500">Tidak ada siswa yang diset Tinggal Kelas.</div>
                    </div>

                    <!-- TAB UNTUK KELAS 12 (TIDAK LULUS) -->
                    <div class="font-bold text-lg mb-2">2. Daftar Siswa Tidak Lulus (XII)</div>
                    <div class="max-h-15rem overflow-y-auto border border-200 border-round p-0">
                        <DataTable :value="candidates.kelas_12.filter(s => s.status === 'retained')" size="small" class="p-datatable-sm">
                            <Column header="No" style="width: 3rem">
                                <template #body="{ index }">{{ index + 1 }}</template>
                            </Column>
                            <Column field="nis" header="NIS"></Column>
                            <Column field="name" header="Nama Siswa"></Column>
                            <Column field="classroom_name" header="Kelas Asal"></Column>
                            <Column header="Aksi" style="width: 8rem">
                                <template #body="{ data }">
                                    <Button label="Lulus" size="small" severity="success" outlined @click="quickPromote(data, 'lulus')" />
                                </template>
                            </Column>
                        </DataTable>
                        <div v-if="candidates.kelas_12.filter(s => s.status === 'retained').length === 0" class="p-3 text-center text-500">Tidak ada siswa kelas XII yang diset Tidak Lulus.</div>
                    </div>
                </div>
            </div>

            <div class="flex justify-content-end gap-2 mt-4 pt-3 border-top-1 border-200">
                <Button label="Batal" severity="secondary" text @click="displayRolloverModal = false" />
                <Button 
                    label="Eksekusi Perpindahan" 
                    icon="pi pi-forward" 
                    severity="warning" 
                    @click="submitRollover" 
                    :loading="rolloverForm.processing"
                    :disabled="!rolloverForm.new_year_id"
                />
            </div>

        </div>
    </Dialog>

</AppLayout>
</template>
<style>
.academic-table .p-datatable-thead > tr > th {
    background: var(--surface-100);
    color: var(--text-color);
    font-weight: 600;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--surface-border);
}
</style>