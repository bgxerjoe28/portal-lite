<template>
    <MobileLayout title="Rekap Presensi Siswa">
        <div class="p-3">
            <!-- TOP TAB MENU UNTUK GURU -->
            <TeacherTabMenu />

            <!-- HEADER & INFO -->
            <div class="flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center gap-3 mb-4">
                <div>
                    <h2 class="text-xl md:text-2xl font-bold m-0 text-900 flex align-items-center gap-2">
                        <i class="pi pi-list-check text-primary text-xl"></i>
                        Rekap Presensi Siswa
                    </h2>
                    <p class="text-500 text-sm m-0 mt-1">
                        Rekapitulasi kehadiran per mata pelajaran & kelas · Tahun Ajaran {{ activeYear.name }}
                    </p>
                </div>

                <!-- ACTION BUTTONS (PDF & EXCEL) -->
                <div v-if="selectedSchedule" class="flex flex-wrap gap-2 w-full md:w-auto">
                    <Button 
                        label="Cetak PDF" 
                        icon="pi pi-file-pdf" 
                        severity="danger" 
                        outlined
                        size="small"
                        class="flex-1 md:flex-initial"
                        @click="handleExport('pdf')" 
                    />
                    <Button 
                        label="Export Excel" 
                        icon="pi pi-file-excel" 
                        severity="success" 
                        outlined
                        size="small"
                        class="flex-1 md:flex-initial"
                        @click="handleExport('excel')" 
                    />
                </div>
            </div>

            <!-- FILTER SECTION -->
            <div class="surface-card p-3 md:p-4 border-round-xl shadow-1 mb-4 border-1 surface-border">
                <div class="grid align-items-end">
                    <!-- 1. PILIH KELAS & MAPEL -->
                    <div class="col-12 md:col-4">
                        <label class="block text-xs font-bold text-700 uppercase mb-1">
                            Mata Pelajaran & Kelas
                        </label>
                        <Select 
                            v-model="formFilters.schedule_id" 
                            :options="schedules" 
                            optionLabel="label" 
                            optionValue="id" 
                            placeholder="Pilih Jadwal Mengajar"
                            class="w-full"
                            @change="applyFilter"
                        />
                    </div>

                    <!-- 2. PILIH TIPE FILTER (BULAN / RENTANG TANGGAL) -->
                    <div class="col-12 sm:col-4 md:col-3">
                        <label class="block text-xs font-bold text-700 uppercase mb-1">
                            Mode Cakupan
                        </label>
                        <div class="flex gap-2">
                            <Button 
                                :label="'Bulanan'" 
                                size="small"
                                :severity="formFilters.filter_type === 'month' ? 'primary' : 'secondary'"
                                :outlined="formFilters.filter_type !== 'month'"
                                class="flex-1 text-xs py-2"
                                @click="setFilterType('month')"
                            />
                            <Button 
                                :label="'Rentang Tgl'" 
                                size="small"
                                :severity="formFilters.filter_type === 'range' ? 'primary' : 'secondary'"
                                :outlined="formFilters.filter_type !== 'range'"
                                class="flex-1 text-xs py-2"
                                @click="setFilterType('range')"
                            />
                        </div>
                    </div>

                    <!-- 3. PILIHAN BULAN & TAHUN -->
                    <div v-if="formFilters.filter_type === 'month'" class="col-12 sm:col-8 md:col-5 flex gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-700 uppercase mb-1">Bulan</label>
                            <Select 
                                v-model="formFilters.month" 
                                :options="monthOptions" 
                                optionLabel="label" 
                                optionValue="val" 
                                class="w-full"
                                @change="applyFilter"
                            />
                        </div>
                        <div class="w-7rem">
                            <label class="block text-xs font-bold text-700 uppercase mb-1">Tahun</label>
                            <Select 
                                v-model="formFilters.year" 
                                :options="yearOptions" 
                                optionLabel="label" 
                                optionValue="val" 
                                class="w-full"
                                @change="applyFilter"
                            />
                        </div>
                    </div>

                    <!-- 3. PILIHAN RENTANG TANGGAL -->
                    <div v-else class="col-12 sm:col-8 md:col-5 flex gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-700 uppercase mb-1">Tgl Mulai</label>
                            <DatePicker 
                                v-model="startDateObj" 
                                dateFormat="dd/mm/yy" 
                                showIcon 
                                iconDisplay="input"
                                class="w-full"
                                @date-select="onDateChange"
                            />
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-700 uppercase mb-1">Tgl Selesai</label>
                            <DatePicker 
                                v-model="endDateObj" 
                                dateFormat="dd/mm/yy" 
                                showIcon 
                                iconDisplay="input"
                                class="w-full"
                                @date-select="onDateChange"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMPTY STATE: JIKA BELUM ADA JADWAL -->
            <div v-if="!selectedSchedule" class="surface-card p-6 border-round-xl shadow-1 text-center border-1 surface-border my-4">
                <i class="pi pi-calendar-times text-500 text-5xl mb-3"></i>
                <h3 class="text-lg font-bold text-900 m-0 mb-1">Tidak Ada Jadwal Mengajar</h3>
                <p class="text-500 text-sm m-0">Anda belum memiliki jadwal mengajar terdaftar pada tahun ajaran aktif ini.</p>
            </div>

            <div v-else>
                <!-- SUMMARY STATS CARDS -->
                <div class="grid mb-4">
                    <div class="col-6 md:col-3">
                        <div class="surface-card p-3 border-round-xl shadow-1 border-left-4 border-blue-500">
                            <div class="text-500 text-xs font-bold uppercase mb-1">Kelas & Mapel</div>
                            <div class="text-900 font-bold text-base line-clamp-1" :title="classroom?.name + ' - ' + subject?.name">
                                {{ classroom?.name }} · {{ subject?.name }}
                            </div>
                        </div>
                    </div>
                    <div class="col-6 md:col-3">
                        <div class="surface-card p-3 border-round-xl shadow-1 border-left-4 border-indigo-500">
                            <div class="text-500 text-xs font-bold uppercase mb-1">Total Siswa</div>
                            <div class="text-900 font-bold text-xl">{{ students.length }} Siswa</div>
                        </div>
                    </div>
                    <div class="col-6 md:col-3">
                        <div class="surface-card p-3 border-round-xl shadow-1 border-left-4 border-orange-500">
                            <div class="text-500 text-xs font-bold uppercase mb-1">Total Pertemuan</div>
                            <div class="text-900 font-bold text-xl">{{ agendaHeaders.length }} Sesi</div>
                        </div>
                    </div>
                    <div class="col-6 md:col-3">
                        <div class="surface-card p-3 border-round-xl shadow-1 border-left-4 border-green-500">
                            <div class="text-500 text-xs font-bold uppercase mb-1">Rata-rata Hadir</div>
                            <div class="text-900 font-bold text-xl">{{ averageAttendanceRate }}%</div>
                        </div>
                    </div>
                </div>

                <!-- EMPTY STATE: JIKA BELUM ADA AGENDA PADA PERIODE INI -->
                <div v-if="agendaHeaders.length === 0" class="surface-card p-5 border-round-xl shadow-1 text-center border-1 surface-border my-4">
                    <i class="pi pi-inbox text-400 text-4xl mb-2 block"></i>
                    <h4 class="text-base font-bold text-800 m-0 mb-1">Belum Ada Agenda / Sesi KBM</h4>
                    <p class="text-500 text-xs m-0">
                        Tidak ditemukan agenda mengajar untuk kelas <strong>{{ classroom?.name }}</strong> pada periode <strong>{{ periodLabel }}</strong>.
                    </p>
                </div>

                <!-- TABEL MATRIKS PRESENSI -->
                <div class="surface-card border-round-xl shadow-1 border-1 surface-border overflow-hidden mb-4">
                    <div class="p-3 bg-50 border-bottom-1 surface-border flex flex-column sm:flex-row justify-content-between align-items-start sm:align-items-center gap-2">
                        <div>
                            <span class="font-bold text-900 text-sm">Matriks Kehadiran Siswa</span>
                            <span class="text-xs text-500 ml-2">({{ periodLabel }})</span>
                        </div>
                        <div class="text-xs text-600">
                            Geser horizontal untuk melihat seluruh tanggal pertemuan ➔
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="matrix-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="sticky-col col-no">NO</th>
                                    <th rowspan="2" class="sticky-col col-name">NAMA SISWA</th>
                                    <th rowspan="2" class="col-nisn">NISN</th>
                                    
                                    <!-- KOLOM TANGGAL SESI AGENDA -->
                                    <th 
                                        v-for="h in agendaHeaders" 
                                        :key="h.id" 
                                        rowspan="2" 
                                        class="col-agenda"
                                        :title="`${h.full_date} (Jam Ke-${h.jam_ke})\nMateri: ${h.materi || '-'}`"
                                    >
                                        <div class="text-xs font-bold text-primary">P{{ h.meeting_no }}</div>
                                        <div class="text-2xs font-normal text-600">{{ h.formatted_date }}</div>
                                    </th>

                                    <th v-if="agendaHeaders.length === 0" rowspan="2" class="text-center text-400 text-xs">-</th>

                                    <!-- KOLOM REKAPITULASI -->
                                    <th rowspan="2" class="col-recap bg-green-50 text-green-700">H</th>
                                    <th colspan="3" class="col-th bg-pink-50 text-pink-700">TH (TIDAK HADIR)</th>
                                    <th rowspan="2" class="col-recap bg-blue-50 text-blue-700">JML</th>
                                    <th rowspan="2" class="col-ket">KET</th>
                                </tr>
                                <tr>
                                    <th class="col-sub-th bg-pink-50 text-blue-700">S</th>
                                    <th class="col-sub-th bg-pink-50 text-orange-700">I</th>
                                    <th class="col-sub-th bg-pink-50 text-red-700">A</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(student, idx) in students" :key="student.id" class="hover:surface-50 transition-colors">
                                    <td class="sticky-col col-no text-center font-bold text-600">{{ idx + 1 }}</td>
                                    <td class="sticky-col col-name font-bold text-800 text-left">
                                        <div class="line-clamp-1" :title="student.full_name">{{ student.full_name }}</div>
                                    </td>
                                    <td class="col-nisn text-center text-xs text-500 font-mono">{{ student.nisn }}</td>

                                    <!-- STATUS PER AGENDA -->
                                    <td 
                                        v-for="h in agendaHeaders" 
                                        :key="h.id" 
                                        class="col-agenda text-center"
                                    >
                                        <span 
                                            class="status-badge"
                                            :class="getStatusClass(student.presensi[h.id])"
                                        >
                                            {{ student.presensi[h.id] || '-' }}
                                        </span>
                                    </td>

                                    <td v-if="agendaHeaders.length === 0" class="text-center text-400">-</td>

                                    <!-- TOTAL H, S, I, A, JML, KET -->
                                    <td class="col-recap text-center font-bold text-green-700 bg-green-50">{{ student.h_count }}</td>
                                    <td class="col-sub-th text-center" :class="{'font-bold text-blue-600': student.s_count > 0}">{{ student.s_count }}</td>
                                    <td class="col-sub-th text-center" :class="{'font-bold text-orange-600': student.i_count > 0}">{{ student.i_count }}</td>
                                    <td class="col-sub-th text-center" :class="{'font-bold text-red-600': student.a_count > 0}">{{ student.a_count }}</td>
                                    <td class="col-recap text-center font-bold text-900 bg-blue-50">{{ student.total_count }}</td>
                                    <td class="col-ket text-center font-semibold text-xs">
                                        <Tag 
                                            :value="student.percentage" 
                                            :severity="student.percentage_num >= 80 ? 'success' : (student.percentage_num >= 60 ? 'warn' : 'danger')" 
                                            class="text-2xs px-2 py-0"
                                        />
                                    </td>
                                </tr>

                                <tr v-if="students.length === 0">
                                    <td :colspan="8 + Math.max(1, agendaHeaders.length)" class="text-center p-4 text-500">
                                        Tidak ada siswa terdaftar di kelas ini.
                                    </td>
                                </tr>
                            </tbody>

                            <!-- FOOTER REKAP PER PERTEMUAN -->
                            <tfoot v-if="students.length > 0 && agendaHeaders.length > 0">
                                <tr class="bg-surface-50 font-bold border-top-2 surface-border">
                                    <td colspan="3" class="sticky-col text-right pr-3 text-xs text-700">Total Hadir (H):</td>
                                    <td v-for="sum in agendaSummary" :key="sum.agenda_id" class="text-center text-xs text-green-700">
                                        {{ sum.hadir }}
                                    </td>
                                    <td colspan="6" class="text-center text-xs text-500">
                                        {{ students.length }} Siswa Total
                                    </td>
                                </tr>
                                <tr class="bg-surface-50 font-bold">
                                    <td colspan="3" class="sticky-col text-right pr-3 text-xs text-700">Total Tidak Hadir (TH):</td>
                                    <td v-for="sum in agendaSummary" :key="sum.agenda_id" class="text-center text-xs text-red-700">
                                        {{ sum.tidak_hadir }}
                                    </td>
                                    <td colspan="6"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- KETERANGAN / LEGENDA STATUS -->
                <div class="surface-card p-3 border-round-xl shadow-1 border-1 surface-border text-xs text-600 mb-5">
                    <div class="font-bold text-900 mb-2 flex align-items-center gap-1">
                        <i class="pi pi-info-circle text-primary"></i>
                        Keterangan Singkatan Presensi:
                    </div>
                    <div class="grid">
                        <div class="col-6 sm:col-4 md:col-2 flex align-items-center gap-2">
                            <span class="status-badge bg-green-100 text-green-800 font-bold">H</span>
                            <span>Hadir</span>
                        </div>
                        <div class="col-6 sm:col-4 md:col-2 flex align-items-center gap-2">
                            <span class="status-badge bg-blue-100 text-blue-800 font-bold">S</span>
                            <span>Sakit</span>
                        </div>
                        <div class="col-6 sm:col-4 md:col-2 flex align-items-center gap-2">
                            <span class="status-badge bg-yellow-100 text-yellow-900 font-bold">I</span>
                            <span>Izin / Dispen</span>
                        </div>
                        <div class="col-6 sm:col-4 md:col-2 flex align-items-center gap-2">
                            <span class="status-badge bg-red-100 text-red-800 font-bold">A</span>
                            <span>Alpa (Tanpa Ket.)</span>
                        </div>
                        <div class="col-6 sm:col-4 md:col-2 flex align-items-center gap-2">
                            <span class="status-badge bg-pink-100 text-pink-800 font-bold">TH</span>
                            <span>Tidak Hadir (S+I+A)</span>
                        </div>
                        <div class="col-6 sm:col-4 md:col-2 flex align-items-center gap-2">
                            <span class="status-badge bg-slate-100 text-slate-800 font-bold">Jml</span>
                            <span>Total Pertemuan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import TeacherTabMenu from '@/Components/TeacherTabMenu.vue';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import DatePicker from 'primevue/datepicker';

const props = defineProps({
    teacher: Object,
    activeYear: Object,
    schedules: Array,
    selectedSchedule: Object,
    classroom: Object,
    subject: Object,
    agendas: Array,
    agendaHeaders: Array,
    students: Array,
    agendaSummary: Array,
    filters: Object,
    periodLabel: String,
    periodSubLabel: String,
    monthOptions: Array,
    yearOptions: Array,
});

const formFilters = ref({
    schedule_id: props.filters?.schedule_id || props.schedules?.[0]?.id || null,
    filter_type: props.filters?.filter_type || 'month',
    month: props.filters?.month || new Date().getMonth() + 1,
    year: props.filters?.year || new Date().getFullYear(),
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const startDateObj = ref(props.filters?.start_date ? new Date(props.filters.start_date) : new Date());
const endDateObj = ref(props.filters?.end_date ? new Date(props.filters.end_date) : new Date());

const formatDateISO = (d) => {
    if (!d) return '';
    const date = new Date(d);
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
};

const setFilterType = (type) => {
    formFilters.value.filter_type = type;
    applyFilter();
};

const onDateChange = () => {
    formFilters.value.start_date = formatDateISO(startDateObj.value);
    formFilters.value.end_date = formatDateISO(endDateObj.value);
    applyFilter();
};

const applyFilter = () => {
    const params = {
        schedule_id: formFilters.value.schedule_id,
        filter_type: formFilters.value.filter_type,
    };

    if (formFilters.value.filter_type === 'month') {
        params.month = formFilters.value.month;
        params.year = formFilters.value.year;
    } else {
        params.start_date = formFilters.value.start_date || formatDateISO(startDateObj.value);
        params.end_date = formFilters.value.end_date || formatDateISO(endDateObj.value);
    }

    router.get(route('guru.attendance-recap.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleExport = (format) => {
    const params = new URLSearchParams({
        schedule_id: formFilters.value.schedule_id,
        filter_type: formFilters.value.filter_type,
    });

    if (formFilters.value.filter_type === 'month') {
        params.append('month', formFilters.value.month);
        params.append('year', formFilters.value.year);
    } else {
        params.append('start_date', formFilters.value.start_date || formatDateISO(startDateObj.value));
        params.append('end_date', formFilters.value.end_date || formatDateISO(endDateObj.value));
    }

    const routeName = format === 'pdf' ? 'guru.attendance-recap.pdf' : 'guru.attendance-recap.excel';
    window.open(`${route(routeName)}?${params.toString()}`, '_blank');
};

const getStatusClass = (status) => {
    switch (status) {
        case 'H':
        case 'T':
            return 'bg-green-100 text-green-800 font-bold';
        case 'S':
            return 'bg-blue-100 text-blue-800 font-bold';
        case 'I':
        case 'D':
            return 'bg-yellow-100 text-yellow-900 font-bold';
        case 'A':
            return 'bg-red-100 text-red-800 font-bold';
        default:
            return 'text-400 font-normal';
    }
};

const averageAttendanceRate = computed(() => {
    if (!props.students || props.students.length === 0) return 0;
    const validStudents = props.students.filter(s => s.total_count > 0);
    if (validStudents.length === 0) return 0;
    const totalPercentage = validStudents.reduce((acc, s) => acc + (s.percentage_num || 0), 0);
    return Math.round(totalPercentage / validStudents.length);
});
</script>

<style scoped>
.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.matrix-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
    white-space: nowrap;
}

.matrix-table th,
.matrix-table td {
    padding: 6px 8px;
    border: 1px solid var(--surface-border, #e2e8f0);
}

.matrix-table thead th {
    background-color: var(--surface-100, #f1f5f9);
    text-align: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-color, #334155);
}

.matrix-table tbody tr:nth-child(even) {
    background-color: var(--surface-50, #f8fafc);
}

.matrix-table tbody tr:hover {
    background-color: var(--primary-50, #eff6ff) !important;
}

.matrix-table tfoot td {
    border-top: 2px solid var(--surface-border, #cbd5e1);
}

/* Sticky column styling */
.sticky-col {
    position: sticky;
    background: inherit;
    z-index: 2;
}

.col-no {
    left: 0;
    width: 40px;
    min-width: 40px;
}

.col-name {
    left: 40px;
    min-width: 170px;
    max-width: 240px;
    box-shadow: 2px 0 4px -2px rgba(0,0,0,0.1);
}

.col-nisn {
    width: 85px;
    min-width: 85px;
}

.col-agenda {
    width: 48px;
    min-width: 48px;
}

.col-recap {
    width: 42px;
    min-width: 42px;
}

.col-th {
    width: 100px;
}

.col-sub-th {
    width: 34px;
    min-width: 34px;
}

.col-ket {
    width: 65px;
    min-width: 65px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.text-2xs {
    font-size: 0.65rem;
    line-height: 1;
}

.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;  
    overflow: hidden;
}
</style>
