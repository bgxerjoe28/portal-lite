<template>
    <AppLayout>
        <div class="surface-card p-4 shadow-2 border-round mb-4 no-print flex align-items-center gap-3">
            <div class="flex-1">
                <h2 class="text-xl font-bold m-0">Laporan Jurnal Mengajar Kelas</h2>
                <small class="text-600 text-sm">Rekapitulasi agenda kelas <b>{{ classroom?.name }}</b></small>
            </div>
            <div class="flex gap-2">
                <Select 
                    v-model="selectedMonth" 
                    :options="months" 
                    optionLabel="label" 
                    optionValue="val" 
                    placeholder="Bulan"
                    @change="handleFilter"
                    class="w-full md:w-12rem"
                />
                
                <Select 
                    v-model="selectedYear" 
                    :options="yearOptions" 
                    optionLabel="label" 
                    optionValue="val" 
                    placeholder="Tahun"
                    @change="handleFilter"
                    class="w-full md:w-8rem"
                />
                <Button 
                    label="Cetak PDF" 
                    icon="pi pi-print" 
                    @click="printPdf" 
                    severity="info" />
            </div>
        </div>

        <div id="printable-report" class="bg-white p-4">
            <div class="text-center mb-5 print-only">
                <h2 class="m-0 text-2xl font-bold">JURNAL KELAS</h2>
                <h3 class="m-0 uppercase text-xl">KELAS: {{ classroom?.name }}</h3>
                <h3 class="m-0 uppercase">SMA NEGERI 16 SEMARANG</h3>
                <p class="mt-2 text-lg">Bulan: <b>{{ selectedMonthLabel }} {{ filters.year }}</b></p>
                <hr class="border border-900 mt-4" />
            </div>

            <div v-for="(dayAgendas, date) in reports" :key="date" class="mb-5">
                <div class="bg-gray-100 p-2 font-bold border border-300 border-bottom-none">
                    <i class="pi pi-calendar mr-2"></i> {{ formatDateIndo(date) }}
                </div>
                <table class="w-full border-collapse border border-300">
                    <thead class="bg-gray-50">
                        <tr class="text-xs uppercase text-center font-bold">
                            <th class="p-2 border border-300 w-4rem" rowspan="2">Jam</th>
                            <th class="p-2 border border-300 w-12rem" rowspan="2">Guru & Mapel</th> 
                            <th class="p-2 border border-300" rowspan="2">Materi & Tujuan Pembelajaran</th>                            
                            <th class="p-2 border border-300" colspan="6">Presensi Siswa</th>
                            <th class="p-2 border border-300 w-16rem" rowspan="2">Siswa Tidak Hadir</th>
                        </tr>
                        <tr class="text-xs uppercase text-center font-bold bg-gray-100">
                            <th class="p-2 border border-300 w-3rem">Jml</th>
                            <th class="p-2 border border-300 w-3rem text-green-700">H</th>
                            <th class="p-2 border border-300 w-3rem text-blue-700">S</th>
                            <th class="p-2 border border-300 w-3rem text-orange-700">I</th>
                            <th class="p-2 border border-300 w-3rem text-purple-700">D</th>
                            <th class="p-2 border border-300 w-3rem text-red-700">A</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="agenda in dayAgendas" :key="agenda.id" class="text-sm">
                            <td class="p-2 border border-300 text-center font-bold">Ke-{{ agenda.start_slot ?? agenda.schedule_detail?.start_slot ?? '-' }}</td>
                            <td class="p-2 border border-300">
                                <div class="font-bold">{{ agenda.teacher?.full_name }}</div>
                                <div class="text-xs text-600">{{ agenda.subject?.name }}</div>
                            </td>
                            <td class="p-2 border border-300">
                                <div v-if="agenda.tp?.kode_tp" class="font-bold text-primary text-xs mb-1">{{ agenda.tp.kode_tp }}</div>
                                <div>{{ agenda.materi_pembelajaran }}</div>
                            </td>
                            
                            <td class="p-2 border border-300 text-center font-bold text-900">
                                {{ agenda.attendances_count }}
                            </td>
                            <td class="p-2 border border-300 text-center font-bold text-green-600">
                                {{ agenda.hadir_count ?? countHadir(agenda) }}
                            </td>
                            <td class="p-2 border border-300 text-center font-semibold text-blue-600">
                                {{ agenda.sakit_count ?? 0 }}
                            </td>
                            <td class="p-2 border border-300 text-center font-semibold text-orange-600">
                                {{ agenda.izin_count ?? 0 }}
                            </td>
                            <td class="p-2 border border-300 text-center font-semibold text-purple-600">
                                {{ agenda.dispen_count ?? 0 }}
                            </td>
                            <td class="p-2 border border-300 text-center font-bold text-red-600">
                                {{ agenda.alpa_count ?? 0 }}
                            </td>
                            <td class="p-2 border border-300 text-xs">
                                <template v-if="agenda.absent_students && agenda.absent_students.length > 0">
                                    <ul class="m-0 pl-3">
                                        <li v-for="(st, sIdx) in agenda.absent_students" :key="sIdx" class="mb-1">
                                            {{ st }}
                                        </li>
                                    </ul>
                                </template>
                                <span v-else class="text-green-600 italic">- (Hadir Semua)</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Select from 'primevue/select';

import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'

const props = defineProps({ 
    reports: Object, 
    filters: Object, 
    classroom: Object,
    yearOptions: Array 
});

const selectedMonth = ref(props.filters.month);
const selectedYear = ref(props.filters.year);
const months = [
    { val: 1, label: 'Januari' }, { val: 2, label: 'Februari' }, { val: 3, label: 'Maret' },
    { val: 4, label: 'April' }, { val: 5, label: 'Mei' }, { val: 6, label: 'Juni' },
    { val: 7, label: 'Juli' }, { val: 8, label: 'Agustus' }, { val: 9, label: 'September' },
    { val: 10, label: 'Oktober' }, { val: 11, label: 'November' }, { val: 12, label: 'Desember' }
];

const selectedMonthLabel = computed(() => {
    const month = months.find(m => m.val === props.filters.month);
    return month ? month.label : '';
});

const handleFilter = () => {
    router.get(route('guru.reports.classroom'), {
        month: selectedMonth.value,
        year: selectedYear.value
    }, {
        preserveState: true,
        replace: true
    });
};

const formatDateIndo = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', { 
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' 
    });
};

const printPdf = () => {
    const url = route('guru.reports.classroom.pdf', {
        month: selectedMonth.value,
        year: selectedYear.value
    });
    window.open(url, '_blank');
};

const countHadir = (agenda) => {
    if (!agenda.attendances) return 0;
    return agenda.attendances.filter(a => a.is_present === true || a.is_present === 't').length;
};
</script>

<style>
/* CSS ini TIDAK boleh 'scoped' agar bisa menembus AppLayout */
@media print {
    /* 1. Sembunyikan SELURUH elemen di dalam body */
    body * {
        visibility: hidden;
        -webkit-print-color-adjust: exact !important; /* Agar warna/background muncul saat cetak */
        print-color-adjust: exact !important;
    }

    /* 2. Tampilkan kembali area laporan dan semua isinya */
    #printable-report, #printable-report * {
        visibility: visible;
    }

    /* 3. Paksa area laporan pindah ke pojok kiri atas kertas */
    #printable-report {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 0;
        margin: 0;
        background-color: white;
    }

    /* 4. Bersihkan layout PrimeVue yang biasanya menyisakan margin/padding */
    .layout-main-container, .layout-main, .layout-content-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        position: static !important;
    }

    /* 5. Sembunyikan elemen navigasi browser (opsional) */
    @page {
        size: auto;
        margin: 1cm;
    }
}
</style>