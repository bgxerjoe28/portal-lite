<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { computed, ref } from 'vue'

// FullCalendar
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'

const props = defineProps({
  academicYear: Object,
  events: Array,
  effectiveDays: Number,
})

const displayDialog = ref(false)
const selectedEvent = ref(null)

const printCalendar = (type) => {
    const d = new Date();
    const currentMonth = d.getMonth() + 1; // 1-12
    router.get(route('calendar.print'), { type: type, month: currentMonth });
}

const eventColors = {
  holiday: '#ef4444', // Merah (Non-KBM / Libur)
  event: '#10b981',   // Hijau (Ada KBM)
  exam: '#f97316',    // Oranye (Ada KBM / Ujian)
  info: '#3b82f6',    // Biru (Info)
}

const typeLabels = {
  holiday: 'Libur / Non-KBM',
  event: 'Kegiatan Sekolah (Ada KBM)',
  exam: 'Ujian / Asesmen (Ada KBM)',
  info: 'Informasi / Pengumuman',
}

const addOneDay = (dateStr) => {
  if (!dateStr) return null;
  const d = new Date(dateStr);
  d.setDate(d.getDate() + 1);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

const formatDateIndo = (dateStr) => {
  if (!dateStr) return '-';
  const parts = dateStr.split('-');
  if (parts.length === 3) {
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    return d.toLocaleDateString('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });
  }
  return dateStr;
}

const holidayCount = computed(() => props.events.filter(e => e.is_holiday || e.type === 'holiday').length);
const kbmEventCount = computed(() => props.events.filter(e => !e.is_holiday && e.type !== 'holiday').length);

const handleEventClick = (info) => {
  const ext = info.event.extendedProps;
  selectedEvent.value = {
    id: info.event.id,
    title: ext.originalTitle || info.event.title,
    type: ext.type,
    is_holiday: ext.is_holiday,
    start_date: ext.rawStartDate,
    end_date: ext.rawEndDate,
  };
  displayDialog.value = true;
}

const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  height: 'auto',
  locale: 'id',
  eventDisplay: 'block', // Selalu buat bentuk block solid
  buttonText: {
    today: 'Hari Ini',
    month: 'Bulan',
    week: 'Minggu',
    day: 'Hari',
  },
  eventClick: handleEventClick,
  events: props.events.map(e => {
    const isNoKbm = Boolean(e.is_holiday || e.type === 'holiday');
    const badgeText = isNoKbm ? ' [Non-KBM]' : ' [Ada KBM]';
    return {
      id: e.id,
      title: `${e.title}${badgeText}`,
      start: e.start_date,
      end: e.end_date !== e.start_date ? addOneDay(e.end_date) : undefined,
      allDay: true,
      backgroundColor: isNoKbm ? '#ef4444' : (eventColors[e.type] ?? '#10b981'),
      borderColor: 'transparent',
      textColor: '#ffffff',
      extendedProps: {
        type: e.type,
        is_holiday: isNoKbm,
        originalTitle: e.title,
        rawStartDate: e.start_date,
        rawEndDate: e.end_date,
      }
    };
  }),
}))
</script>

<template>
  <AppLayout>
    <div class="surface-ground p-4 md:p-6">
      <!-- HEADER -->
      <div class="surface-card p-4 mb-4 border-round-xl shadow-1">
        <div class="flex flex-column md:flex-row md:justify-content-between md:align-items-center gap-3">
          <div>
            <h2 class="text-2xl font-bold m-0 flex align-items-center gap-2">
              <i class="pi pi-calendar text-primary text-2xl"></i>
              Kalender Akademik
            </h2>

            <div class="mt-2 text-sm text-600">
              Tahun Ajaran: <b>{{ academicYear.name }}</b> ({{ academicYear.semester }})
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
              <Tag severity="info" class="px-3 py-2 text-sm">
                <i class="pi pi-check-circle mr-2"></i>Hari Efektif KBM: <b class="ml-1 text-base">{{ effectiveDays }}</b> Hari
              </Tag>

              <Tag severity="danger" class="px-3 py-2 text-sm">
                <i class="pi pi-times-circle mr-2"></i>Non-KBM / Libur: <b class="ml-1 text-base">{{ holidayCount }}</b> Event
              </Tag>

              <Tag severity="success" class="px-3 py-2 text-sm">
                <i class="pi pi-calendar-plus mr-2"></i>Kegiatan KBM: <b class="ml-1 text-base">{{ kbmEventCount }}</b> Event
              </Tag>
            </div>
          </div>
          
          <div class="flex flex-wrap gap-2">
            <Button 
              label="Cetak Semester" 
              icon="pi pi-print" 
              class="p-button-outlined"
              @click="printCalendar('semester')"
            />
            <Button 
              label="Cetak Bulan Ini" 
              icon="pi pi-print" 
              class="p-button-outlined p-button-secondary"
              @click="printCalendar('month')"
            />
          </div>
        </div>
      </div>

      <!-- Legend & Info Status -->
      <div class="surface-card p-3 mb-4 border-round-lg shadow-1 flex flex-wrap align-items-center justify-content-between gap-3 text-sm">
        <div class="flex flex-wrap align-items-center gap-4">
          <span class="font-bold text-700">Keterangan:</span>
          <span class="flex align-items-center gap-2">
            <span class="w-1rem h-1rem border-round" style="background:#ef4444"></span>
            <b>Libur / Non-KBM</b>
          </span>
          <span class="flex align-items-center gap-2">
            <span class="w-1rem h-1rem border-round" style="background:#10b981"></span>
            <b>Kegiatan (Ada KBM)</b>
          </span>
          <span class="flex align-items-center gap-2">
            <span class="w-1rem h-1rem border-round" style="background:#f97316"></span>
            <b>Ujian (Ada KBM)</b>
          </span>
          <span class="flex align-items-center gap-2">
            <span class="w-1rem h-1rem border-round" style="background:#3b82f6"></span>
            <b>Info / Pengumuman</b>
          </span>
        </div>
        <small class="text-500 font-italic">
          <i class="pi pi-info-circle mr-1"></i>Klik pada kotak event untuk melihat rincian detail kegiatan.
        </small>
      </div>

      <!-- Calendar Container -->
      <div class="surface-card p-4 border-round-xl shadow-1">
        <FullCalendar :options="calendarOptions" />
      </div>

      <!-- MODAL DETAIL EVENT -->
      <Dialog 
        v-model:visible="displayDialog" 
        header="Detail Kegiatan Kalender" 
        :modal="true" 
        class="w-11 md:w-30rem"
        :dismissableMask="true"
      >
        <div v-if="selectedEvent" class="p-2">
          <div class="mb-3">
            <Tag 
              :severity="selectedEvent.is_holiday ? 'danger' : (selectedEvent.type === 'exam' ? 'warning' : 'success')" 
              class="px-3 py-1 text-sm uppercase font-bold"
            >
              {{ selectedEvent.is_holiday ? 'LIBUR / NON-KBM' : 'ADA KBM' }}
            </Tag>
          </div>

          <h3 class="text-xl font-bold text-900 m-0 mb-3">
            {{ selectedEvent.title }}
          </h3>

          <div class="surface-100 p-3 border-round-lg mb-3">
            <div class="mb-2">
              <span class="text-500 text-xs block font-semibold uppercase">Jenis Agenda:</span>
              <span class="font-bold text-700">{{ typeLabels[selectedEvent.type] || selectedEvent.type }}</span>
            </div>

            <div>
              <span class="text-500 text-xs block font-semibold uppercase">Tanggal:</span>
              <span v-if="selectedEvent.start_date === selectedEvent.end_date" class="font-bold text-blue-700">
                {{ formatDateIndo(selectedEvent.start_date) }}
              </span>
              <span v-else class="font-bold text-blue-700">
                {{ formatDateIndo(selectedEvent.start_date) }} — {{ formatDateIndo(selectedEvent.end_date) }}
              </span>
            </div>
          </div>

          <div class="flex justify-content-end">
            <Button label="Tutup" severity="secondary" @click="displayDialog = false" />
          </div>
        </div>
      </Dialog>

    </div>
  </AppLayout>
</template>

<style scoped>
:deep(.fc-event) {
  white-space: normal !important;
  word-break: break-word !important;
  cursor: pointer !important;
  padding: 3px 6px !important;
  margin-bottom: 3px !important;
  border-radius: 6px !important;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12) !important;
}

:deep(.fc-event-title) {
  white-space: normal !important;
  word-break: break-word !important;
  font-weight: 600 !important;
  font-size: 0.825rem !important;
  line-height: 1.25 !important;
  display: block !important;
}

:deep(.fc-daygrid-event-harness) {
  margin-bottom: 2px !important;
}
</style>


