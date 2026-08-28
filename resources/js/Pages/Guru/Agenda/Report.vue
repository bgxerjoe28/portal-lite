<template>
    <AppLayout title="Rekap Presensi">
        <div class="card no-print mb-4">
            <h2 class="font-bold text-xl mb-4">Cetak Rekap Presensi Bulanan</h2>
            <div class="flex gap-3 align-items-end">
                <div class="field m-0">
                    <label class="block text-sm font-bold mb-1">Bulan</label>
                    <Select v-model="filter.month" :options="months" optionLabel="label" optionValue="val" class="w-12rem" />
                </div>
                <Button label="Tampilkan" icon="pi pi-search" @click="refreshData" />
                <Button label="Cetak PDF / Print" icon="pi pi-print" severity="secondary" @click="printReport" />
            </div>
        </div>

        <div id="printable-report" class="surface-card p-5 border-round shadow-2">
            <div class="text-center mb-5">
                <h2 class="m-0 uppercase">REKAPITULASI PRESENSI SISWA</h2>
                <h3 class="m-0 uppercase">SMA NEGERI 16 SEMARANG</h3>
                <p class="mt-2">
                    Mapel: <b>{{ schedule.subject.name }}</b> | Kelas: <b>{{ schedule.classroom.name }}</b> | 
                    Bulan: <b>{{ filter.month }} / {{ filter.year }}</b>
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2" style="min-width: 200px">Nama Siswa</th>
                            <th :colspan="filter.daysInMonth">Tanggal</th>
                            <th colspan="2">Total</th>
                        </tr>
                        <tr>
                            <th v-for="d in filter.daysInMonth" :key="d" class="date-col">{{ d }}</th>
                            <th>H</th>
                            <th>A</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(student, idx) in students" :key="student.id">
                            <td class="text-center">{{ idx + 1 }}</td>
                            <td>{{ student.full_name }}</td>
                            <td v-for="d in filter.daysInMonth" :key="d" class="text-center">
                                {{ getStatus(student.id, d) }}
                            </td>
                            <td class="text-center font-bold text-green-600">{{ countHadir(student.id) }}</td>
                            <td class="text-center font-bold text-red-600">{{ countAbsen(student.id) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-content-end mt-6">
                <div class="text-center" style="width: 250px">
                    <p>Semarang, {{ todayDate }}</p>
                    <p class="mb-8">Guru Mata Pelajaran,</p>
                    <p class="font-bold underline">{{ $page.props.auth.user.name }}</p>
                    <p>NIP. ...........................</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
// ... import PrimeVue ...

const props = defineProps({
    schedule: Object,
    students: Array,
    agendas: Array,
    filter: Object,
    months: Array
});

// Logika mencari status absen siswa di tanggal tertentu
const getStatus = (studentId, day) => {
    // Cari agenda yang tanggalnya sama dengan 'day'
    const agenda = props.agendas.find(a => new Date(a.date).getDate() === day);
    if (!agenda) return ''; // Tidak ada jam pelajaran

    const attendance = agenda.attendances.find(att => att.student_id === studentId);
    if (!attendance) return '-';
    
    return attendance.is_present ? 'H' : 'A';
};

const printReport = () => { window.print(); };
</script>

<style scoped>
.report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}
.report-table th, .report-table td {
    border: 1px solid #333;
    padding: 4px;
}
.date-col { width: 25px; font-size: 9px; }

@media print {
    .no-print { display: none !important; }
    body { background: white; }
    .card { border: none; shadow: none; }
    @page { size: landscape; margin: 1cm; }
}
</style>