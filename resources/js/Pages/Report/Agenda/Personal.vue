<template>
    <AppLayout>
        <div class="surface-card p-4 shadow-2 border-round mb-4 no-print flex flex-column md:flex-row align-items-center gap-3">
            <div class="flex-1">
                <h2 class="text-xl font-bold m-0">Laporan Jurnal Mengajar</h2>
                <small class="text-600">Rekapitulasi agenda bulan <b>{{ selectedMonthLabel }}</b></small>
            </div>
            <div class="flex gap-2 w-full md:w-auto">
                <Select 
                    v-model="selectedMonth" 
                    :options="months" 
                    optionLabel="label" 
                    optionValue="val" 
                    @change="handleFilter"
                    class="flex-1 md:w-12rem"
                />
                <Select 
                    v-model="selectedYear" 
                    :options="yearOptions" 
                    optionLabel="label" 
                    optionValue="val" 
                    @change="handleFilter"
                    class="w-8rem"
                />
                <Button label="Cetak PDF" icon="pi pi-print" severity="info" @click="printReport" />
            </div>
        </div>

        <div id="printable-report" class="bg-white p-2 md:p-4 border-round shadow-1">
            
            <div class="text-center mb-6 print-only">
                <h2 class="m-0 font-bold">JURNAL MENGAJAR GURU</h2>
                <h3 class="m-0 uppercase">SMA NEGERI 16 SEMARANG</h3>
                <p class="mt-2 text-lg">Periode: <b>{{ selectedMonthLabel }} {{ filters.year }}</b></p>
                <div class="border-top-2 border-900 mt-4 w-full"></div>
            </div>

            <div v-if="Object.keys(reports).length === 0" class="text-center p-5 text-500">
                Belum ada data agenda pada periode ini.
            </div>

            <div v-for="(dayAgendas, date) in reports" :key="date" class="mb-5 page-break-avoid">
                <div class="bg-gray-100 p-2 font-bold border border-300 border-bottom-none flex align-items-center">
                    <i class="pi pi-calendar mr-2 no-print"></i> 
                    {{ formatDateIndo(date) }}
                </div>
                
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-xs uppercase font-bold">
                            <th class="p-2 border border-300 text-center w-3rem" rowspan="2">Jam</th>
                            <th class="p-2 border border-300 text-center w-6rem" rowspan="2">Kelas</th>
                            <th class="p-2 border border-300" rowspan="2">Materi & Tujuan Pembelajaran</th>
                            <th class="p-2 border border-300 text-center" colspan="3">Presensi Siswa</th>
                        </tr>
                        <tr class="bg-gray-100 text-xs uppercase font-bold">
                            <th class="p-2 border border-300 w-3rem">Jml</th>
                            <th class="p-2 border border-300 w-3rem">H</th>
                            <th class="p-2 border border-300 w-3rem">A</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="agenda in dayAgendas" :key="agenda.id" class="text-sm">
                            <td class="p-2 border border-300 text-center">Ke-{{ agenda.start_slot ?? agenda.schedule_detail?.start_slot }}</td>
                            <td class="p-2 border border-300 text-center font-bold">{{ agenda.classroom?.name }}</td>
                            <td class="p-2 border border-300">
                                <div class="font-bold text-primary mb-1 no-print">{{ agenda.tp?.kode_tp }}</div>
                                <div>{{ agenda.materi_pembelajaran }}</div>
                            </td>
                            <td class="p-2 border border-300 text-center font-bold text-blue-700">
                                {{ agenda.attendances_count }}
                            </td>
                            <td class="p-2 border border-300 text-center font-bold text-green-700">
                                {{ countHadir(agenda) }}
                            </td>
                            <td class="p-2 border border-300 text-center font-bold text-red-700">
                                {{ agenda.attendances_count - countHadir(agenda) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-content-end mt-8 print-only">
                <div class="text-center" style="width: 250px">
                    <p>Semarang, {{ new Date().toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'}) }}</p>
                    <p class="mb-8">Guru Mata Pelajaran,</p>
                    <br><br><br>
                    <p class="font-bold underline">{{ $page.props.auth.user.name }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Select from 'primevue/select';

const props = defineProps({ 
    reports: Object, 
    filters: Object, 
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
    router.get(route('guru.reports.personal'), {
        month: selectedMonth.value,
        year: selectedYear.value
    }, { preserveState: true });
};

const formatDateIndo = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', { 
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' 
    });
};

const countHadir = (agenda) => {
    if (!agenda.attendances) return 0;
    return agenda.attendances.filter(a => a.is_present === true || a.is_present === 't').length;
};

const printReport = () => {
    // Arahkan ke route PDF dengan membawa parameter filter
    const url = route('guru.reports.personal.pdf', {
        month: selectedMonth.value,
        year: selectedYear.value
    });
    
    window.open(url, '_blank');
};
</script>

<style>
/* Utility CSS */
.print-only { display: none; }

@media print {
    /* Sembunyikan elemen dashboard */
    .no-print, .layout-sidebar, .layout-topbar, .layout-footer {
        display: none !important;
    }

    /* Reset layout container agar tabel bisa melebar penuh */
    .layout-main-container, .layout-main, .layout-content-wrapper, body, html {
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        height: auto !important;
        position: static !important;
    }

    .print-only { display: block !important; }

    #printable-report {
        display: block !important;
        width: 100% !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    table { 
        border-collapse: collapse !important;
        width: 100% !important;
        page-break-inside: auto;
    }

    tr { page-break-inside: avoid; page-break-after: auto; }
    
    th, td { 
        border: 1px solid black !important; /* Paksa border muncul */
        padding: 6px !important;
    }

    .page-break-avoid {
        page-break-inside: avoid;
    }

    @page {
        size: A4 portrait;
        margin: 1.5cm;
    }
}
</style>