<template>
    <AppLayout title="Kalender Jadwal Peminjaman Ruang & Sarpras">
        <div class="p-4">
            <!-- Header -->
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-calendar text-primary text-3xl"></i>
                        Kalender Jadwal Ruangan & Sarpras
                    </h2>
                    <p class="text-600 m-0 mt-1">Visualisasi jadwal pemakaian ruang sekolah secara real-time untuk mencegah tabrakan agenda.</p>
                </div>
                <div class="flex gap-2">
                    <Button 
                        label="Daftar Tabel Peminjaman" 
                        icon="pi pi-list" 
                        class="p-button-outlined" 
                        @click="router.get(route('admin.sarpras.reservations.index'))"
                    />
                </div>
            </div>

            <!-- Filter Room Bar -->
            <div class="surface-card p-3 border-round-xl shadow-1 mb-4 flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="flex align-items-center gap-2">
                    <label class="font-bold text-sm text-700">Filter Ruangan:</label>
                    <Select 
                        v-model="selectedRoomId" 
                        :options="rooms" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Semua Ruangan" 
                        class="w-16rem"
                        showClear
                        @change="refetchEvents"
                    />
                </div>

                <!-- Legend -->
                <div class="flex flex-wrap gap-3 text-xs font-semibold">
                    <div class="flex align-items-center gap-1">
                        <span class="w-1rem h-1rem border-round inline-block" style="background-color: #f59e0b;"></span>
                        <span>Menunggu Approval</span>
                    </div>
                    <div class="flex align-items-center gap-1">
                        <span class="w-1rem h-1rem border-round inline-block" style="background-color: #3b82f6;"></span>
                        <span>Disetujui (Terkunci)</span>
                    </div>
                    <div class="flex align-items-center gap-1">
                        <span class="w-1rem h-1rem border-round inline-block" style="background-color: #8b5cf6;"></span>
                        <span>Sedang Digunakan</span>
                    </div>
                    <div class="flex align-items-center gap-1">
                        <span class="w-1rem h-1rem border-round inline-block" style="background-color: #10b981;"></span>
                        <span>Selesai</span>
                    </div>
                </div>
            </div>

            <!-- Calendar Container -->
            <div class="surface-card p-4 border-round-xl shadow-2">
                <FullCalendar ref="calendarRef" :options="calendarOptions" />
            </div>
        </div>

        <!-- Event Detail Dialog -->
        <Dialog v-model:visible="showEventModal" header="Detail Jadwal Peminjaman" :modal="true" class="w-full md:w-5">
            <div v-if="selectedEvent" class="flex flex-column gap-3 pt-2">
                <div class="surface-100 p-3 border-round-lg border-1 border-300">
                    <span class="font-mono text-xs font-bold text-primary">{{ selectedEvent.extendedProps?.code }}</span>
                    <h3 class="text-xl font-bold text-900 m-0 mt-1">{{ selectedEvent.title }}</h3>
                    <div class="text-sm text-600 mt-1">
                        <strong>Pemohon: </strong> {{ selectedEvent.extendedProps?.borrower }}
                    </div>
                    <div v-if="selectedEvent.extendedProps?.room" class="text-sm text-600 mt-1">
                        <strong>Ruangan: </strong> {{ selectedEvent.extendedProps.room }}
                    </div>
                </div>

                <div class="flex align-items-center gap-2 text-sm text-700">
                    <i class="pi pi-clock text-primary"></i>
                    <span>{{ formatEventTime(selectedEvent.start, selectedEvent.end) }}</span>
                </div>

                <div class="flex justify-content-end gap-2 mt-3 pt-2 border-top-1 border-200">
                    <Button label="Tutup" icon="pi pi-times" class="p-button-text" @click="showEventModal = false" />
                    <Button 
                        v-if="selectedEvent.extendedProps?.show_url" 
                        label="Buka Halaman Detail" 
                        icon="pi pi-external-link" 
                        @click="router.get(selectedEvent.extendedProps.show_url)" 
                    />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Dialog from 'primevue/dialog';

import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

const props = defineProps({
    rooms: Array,
});

const calendarRef = ref(null);
const selectedRoomId = ref(null);
const showEventModal = ref(false);
const selectedEvent = ref(null);

const handleEventClick = (info) => {
    selectedEvent.value = info.event;
    showEventModal.value = true;
};

const calendarOptions = ref({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,dayGridWeek',
    },
    locale: 'id',
    events: (fetchInfo, successCallback, failureCallback) => {
        const url = new URL(route('admin.sarpras.reservations.calendar-events'), window.location.origin);
        url.searchParams.append('start', fetchInfo.startStr);
        url.searchParams.append('end', fetchInfo.endStr);
        if (selectedRoomId.value) {
            url.searchParams.append('room_id', selectedRoomId.value);
        }

        fetch(url.toString(), {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(events => successCallback(events))
        .catch(err => failureCallback(err));
    },
    eventClick: handleEventClick,
    height: 'auto',
});

const refetchEvents = () => {
    if (calendarRef.value) {
        const calendarApi = calendarRef.value.getApi();
        calendarApi.refetchEvents();
    }
};

const formatEventTime = (start, end) => {
    if (!start) return '-';
    const s = new Date(start).toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'short' });
    const e = end ? new Date(end).toLocaleTimeString('id-ID', { timeStyle: 'short' }) : '';
    return `${s} s/d ${e} WIB`;
};
</script>
