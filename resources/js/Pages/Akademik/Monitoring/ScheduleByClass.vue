<template>
    <AppLayout>
        <div class="card">
            <div class="flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center mb-4 gap-3">
                
                <div>
                    <h2 class="text-2xl font-bold m-0">Jadwal Pelajaran Per Kelas</h2>
                    <span class="text-500">Tahun Ajaran: {{ activeYear.name }}</span>
                </div>

                <div class="flex flex-column sm:flex-row gap-2 w-full md:w-auto no-print">
                    
                    <Link :href="route('admin.monitoring.schedule.teacher')" class="w-full sm:w-auto">
                        <Button label="Lihat Mode Guru" icon="pi pi-user" severity="help" outlined class="w-full" />
                    </Link>

                    <Button label="Cetak Jadwal" icon="pi pi-print" severity="secondary" @click="printSchedule" class="w-full sm:w-auto" :disabled="!selectedClassroom" />

                    <div class="w-full sm:w-20rem">
                        <Select v-model="selectedClassId" :options="classrooms" optionLabel="name" optionValue="id" 
                            filter placeholder="Pilih Kelas..." class="w-full" @change="loadSchedule" />
                    </div>
                </div>
            </div>

            <div id="printable-area">
                
                <div class="hidden print-only mb-4 text-center">
                    <h2 class="m-0">Jadwal Pelajaran - Kelas {{ selectedClassroom?.name }}</h2>
                    <p class="m-0 text-600">Tahun Ajaran: {{ activeYear.name }}</p>
                </div>

                <div v-if="selectedClassroom" class="overflow-auto">
                    <table class="w-full border-collapse border border-300">
                        <thead>
                            <tr>
                                <th class="bg-gray-100 p-3 border border-300 text-center" style="width: 100px;">Hari</th>
                                <th v-for="i in 10" :key="i" class="bg-gray-100 p-2 border border-300 text-center w-1rem">
                                    {{ i }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="day in days" :key="day.value">
                                <td class="font-bold p-3 border border-300 bg-gray-50 capitalize">{{ day.label }}</td>
                                
                                <td v-for="slot in 10" :key="slot" class="border border-300 p-1 text-center relative h-4rem vertical-align-middle">
                                    <div v-if="getSchedules(day.value, slot).length"
                                        class="h-full flex flex-column gap-1 justify-content-center">

                                        <div v-for="(item, i) in getSchedules(day.value, slot)"
                                            :key="i"
                                            class="text-xs p-1 border-round shadow-1"
                                            :class="item.color">

                                            <span class="font-bold block">{{ item.code }}</span>
                                            <span style="font-size: 10px;">
                                                {{ item.teacher }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="hidden print-only mt-8 flex flex-column align-items-end" style="padding-right: 50px;">
                        <div class="text-center" style="min-width: 250px;">
                            <p class="mb-1">Semarang, {{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                            <p class="mb-8">Mengetahui,<br>Kepala Sekolah</p>
                            
                            <p class="font-bold underline m-0">......................................................</p>
                            <p class="m-0 text-sm">NIP. .............................................</p>
                        </div>
                    </div>

                </div>

                <div v-else class="text-center p-5 text-500 bg-gray-50 border-round no-print">
                    <i class="pi pi-search text-3xl mb-3"></i>
                    <p>Silakan pilih kelas terlebih dahulu untuk melihat jadwal.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3'; // Jangan lupa import Link
import AppLayout from '@/Layouts/AppLayout.vue';
import Select from 'primevue/select';
import Button from 'primevue/button'; // Import Button

const props = defineProps({
    classrooms: Array,
    schedules: Array,
    filters: Object,
    selectedClassroom: Object,
    activeYear: Object
});

const selectedClassId = ref(props.filters.classroom_id ? parseInt(props.filters.classroom_id) : null);

const days = [
    { label: 'Senin', value: 'senin' },
    { label: 'Selasa', value: 'selasa' },
    { label: 'Rabu', value: 'rabu' },
    { label: 'Kamis', value: 'kamis' },
    { label: 'Jumat', value: 'jumat' },
    { label: 'Sabtu', value: 'sabtu' },
];

const loadSchedule = () => {
    router.get(route('admin.monitoring.schedule.class'), { classroom_id: selectedClassId.value }, { preserveState: true });
};

const getSchedules = (day, slot) => {
    return scheduleMap.value[`${day}-${slot}`] || [];
};

const exgetSchedule = (day, slot) => {
    return scheduleMap.value[`${day}-${slot}`] || null;
};

const scheduleMap = computed(() => {
    const map = {};

    if (!props.schedules) return map;

    props.schedules.forEach(s => {
        for (let slot = s.start_slot; slot <= s.end_slot; slot++) {
            const key = `${s.day}-${slot}`;

            if (!map[key]) {
                map[key] = [];
            }

            map[key].push(s);
        }
    });

    return map;
});

const exscheduleMap = computed(() => {
    const map = {};

    if (!props.schedules) return map;

    props.schedules.forEach(s => {
        for (let slot = s.start_slot; slot <= s.end_slot; slot++) {
            map[`${s.day}-${slot}`] = s;
        }
    });

    return map;
});

// Fungsi Cetak Browser
const printSchedule = () => {
    window.print();
};
</script>

<style scoped>
td {
    vertical-align: top;
    overflow-y: auto;
}
/* CSS KHUSUS CETAK */
@media print {
    /* 1. Sembunyikan SEMUA elemen di dalam body secara default */
    body * {
        visibility: hidden;
    }

    /* 2. Sembunyikan elemen layout utama yang bandel (Drawer, Topbar) */
    /* Sesuaikan selector ini jika template Anda punya nama class beda */
    .layout-sidebar, 
    .layout-menu, 
    .p-sidebar, 
    .layout-topbar, 
    nav, 
    aside, 
    header {
        display: none !important;
    }

    /* 3. Tampilkan HANYA kontainer #printable-area dan semua isinya */
    #printable-area, #printable-area * {
        visibility: visible;
    }

    /* 4. POSISI ABSOLUT: Paksa area cetak naik ke pojok kiri atas */
    /* Ini kuncinya agar tidak tertutup sidebar */
    #printable-area {
        position: fixed !important; /* Gunakan fixed agar menimpa segalanya */
        left: 0 !important;
        top: 0 !important;
        width: 100vw !important; /* Lebar penuh kertas */
        height: 100vh !important; /* Tinggi penuh */
        margin: 0 !important;
        padding: 20px !important; /* Beri jarak sedikit dari pinggir kertas */
        background-color: white !important;
        z-index: 999999 !important; /* Layer paling atas */
        overflow: visible !important;
    }

    /* Elemen yang memang mau disembunyikan di kertas (tombol filter, dll) */
    .no-print {
        display: none !important;
    }

    /* Munculkan elemen khusus print (Judul, Tanda tangan) */
    .print-only {
        display: block !important;
    }

    /* Paksa cetak warna background (opsional, tergantung browser) */
    .bg-gray-100, .bg-indigo-50 {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* Di layar biasa (bukan mode cetak), sembunyikan elemen print-only */
.print-only {
    display: none;
}
</style>