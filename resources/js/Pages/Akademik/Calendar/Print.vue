<script setup>
import { computed, onMounted, ref } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  academicYear: Object,
  events: Array,
  type: String,
  month: Number,
})

const eventColors = {
  holiday: '#ef4444',
  event: '#22c55e',
  exam: '#f97316',
  info: '#3b82f6',
}

// Generate months based on start_date and end_date
const monthsToRender = computed(() => {
    if (props.type === 'month') {
        // Find the year for the requested month. 
        // We assume the month is within the academic year.
        const start = new Date(props.academicYear.start_date);
        const end = new Date(props.academicYear.end_date);
        
        let targetYear = start.getFullYear();
        if (props.month < start.getMonth() + 1 && props.month <= end.getMonth() + 1) {
            // It might be in the next year (e.g., Jan-Jun)
            targetYear = end.getFullYear();
        } else if (props.month < start.getMonth() + 1 && start.getFullYear() !== end.getFullYear()) {
             targetYear = end.getFullYear();
        }

        const dateStr = `${targetYear}-${String(props.month).padStart(2, '0')}-01`;
        return [dateStr];
    } else {
        // Semester - iterate from start_date to end_date
        const months = [];
        let curr = new Date(props.academicYear.start_date);
        // Set to 1st of the month
        curr.setDate(1);
        const end = new Date(props.academicYear.end_date);
        
        while (curr <= end) {
            const y = curr.getFullYear();
            const m = String(curr.getMonth() + 1).padStart(2, '0');
            months.push(`${y}-${m}-01`);
            curr.setMonth(curr.getMonth() + 1);
        }
        return months;
    }
})

const getCalendarOptions = (initialDate) => {
    return {
        plugins: [dayGridPlugin],
        initialView: 'dayGridMonth',
        initialDate: initialDate,
        height: 'auto',
        headerToolbar: {
            left: '',
            center: 'title',
            right: ''
        },
        events: props.events.map(e => ({
            title: e.title,
            start: e.start_date, // use start_date instead of date
            end: e.end_date !== e.start_date ? addOneDay(e.end_date) : undefined,
            allDay: true,
            backgroundColor: eventColors[e.type] ?? '#64748b',
            borderColor: 'transparent',
        })),
    }
}

// FullCalendar 'end' date is exclusive, so we need to add 1 day for ranges
const addOneDay = (dateStr) => {
    if (!dateStr) return null;
    const d = new Date(dateStr);
    d.setDate(d.getDate() + 1);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

const printPage = () => {
    window.print();
}

onMounted(() => {
    // Automatically open print dialog after a short delay for rendering
    setTimeout(() => {
        printPage();
    }, 1000);
});
</script>

<template>
    <div class="print-container">
        <!-- HEADER -->
        <div class="text-center mb-4 print-header">
            <h1 class="text-3xl font-bold m-0">Kalender Pendidikan</h1>
            <p class="text-lg text-600 mt-1 mb-0">
                Tahun Ajaran {{ academicYear.name }} ({{ academicYear.semester }})
            </p>
        </div>

        <!-- Legend -->
        <div class="flex justify-content-center gap-4 mb-4 print-legend font-bold text-sm">
            <span class="flex align-items-center gap-2">
                <span class="w-1rem h-1rem border-round block" style="background:#ef4444"></span> Libur
            </span>
            <span class="flex align-items-center gap-2">
                <span class="w-1rem h-1rem border-round block" style="background:#22c55e"></span> Kegiatan
            </span>
            <span class="flex align-items-center gap-2">
                <span class="w-1rem h-1rem border-round block" style="background:#f97316"></span> Ujian
            </span>
            <span class="flex align-items-center gap-2">
                <span class="w-1rem h-1rem border-round block" style="background:#3b82f6"></span> Info
            </span>
        </div>

        <!-- Calendars -->
        <div class="grid">
            <div 
                v-for="(monthDate, index) in monthsToRender" 
                :key="monthDate" 
                :class="type === 'semester' ? 'col-12 md:col-6 print-col-6' : 'col-12'"
            >
                <div class="calendar-wrapper mb-4">
                    <FullCalendar :options="getCalendarOptions(monthDate)" />
                </div>
            </div>
        </div>
        
        <!-- Print Button (Hidden in Print) -->
        <div class="fixed bottom-0 left-0 w-full p-3 surface-overlay shadow-2 flex justify-content-center no-print" style="z-index: 1000">
            <button @click="printPage" class="p-button p-component p-button-primary border-none text-white p-3 font-bold cursor-pointer border-round" style="background: var(--primary-color)">
                <span class="p-button-icon p-button-icon-left pi pi-print mr-2"></span>
                <span class="p-button-label">Cetak Sekarang</span>
            </button>
            <button @click="router.get(route('calendar.view'))" class="p-button p-component p-button-secondary border-none text-white p-3 font-bold cursor-pointer border-round ml-2" style="background: var(--surface-500)">
                <span class="p-button-icon p-button-icon-left pi pi-arrow-left mr-2"></span>
                <span class="p-button-label">Kembali</span>
            </button>
        </div>
    </div>
</template>

<style>
/* Styling specifically for printing */
body {
    background-color: white !important;
}
.print-container {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
    background: white;
}
.w-1rem { width: 1rem; }
.h-1rem { height: 1rem; }
.block { display: inline-block; }

/* Force exact colors in print mode */
@media print {
    @page {
        size: A4 portrait; /* Use portrait for month, maybe landscape for semester if fit, but portrait works */
        margin: 1cm;
    }
    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background: white !important;
        margin: 0;
        padding: 0;
    }
    .print-container {
        padding: 0;
        max-width: 100%;
    }
    .no-print {
        display: none !important;
    }
    
    .print-col-6 {
        width: 50% !important;
        float: left;
        padding: 0 10px;
        box-sizing: border-box;
    }
    .grid {
        display: block; /* Disable flex/grid for better page breaks in print */
    }
    .grid::after {
        content: "";
        display: table;
        clear: both;
    }
    .calendar-wrapper {
        page-break-inside: avoid;
    }
    
    /* Make FullCalendar look cleaner in print */
    .fc-theme-standard .fc-scrollgrid {
        border: 1px solid #ddd;
    }
    .fc-daygrid-event {
        font-size: 0.7rem;
    }
    .fc-toolbar-title {
        font-size: 1.25em !important;
    }
}
</style>
