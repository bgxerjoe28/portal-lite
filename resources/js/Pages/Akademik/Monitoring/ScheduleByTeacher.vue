<template>
    <AppLayout>
        <div class="card">
            <div class="flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center mb-4 gap-3 no-print">
                <div>
                    <h2 class="text-2xl font-bold m-0">Jadwal Mengajar Guru</h2>
                    <span class="text-500">Tahun Ajaran: {{ activeYear.name }}</span>
                </div>
                
                <div class="flex flex-column sm:flex-row gap-2 w-full md:w-auto">
                    <Link :href="route('admin.monitoring.schedule.class')">
                        <Button label="Mode Kelas" icon="pi pi-th-large" severity="help" outlined />
                    </Link>
                    <Button label="Reset Jadwal" icon="pi pi-refresh" severity="danger" @click="openResetModal" />
                    <Button label="Cetak" icon="pi pi-print" severity="secondary" @click="printSchedule" :disabled="!selectedTeacher" />
                    
                    <div class="w-full sm:w-20rem">
                        <Select v-model="selectedTeacherId" :options="teachers" optionLabel="full_name" optionValue="id" 
                            filter placeholder="Pilih Guru..." class="w-full" @change="loadSchedule">
                            <template #option="slotProps">
                                <div class="flex justify-content-between align-items-center w-full">
                                    <span>{{ slotProps.option.full_name }}</span>
                                    <Tag :value="slotProps.option.total_jp + ' JP'" severity="info" />
                                </div>
                            </template>
                        </Select>
                    </div>
                </div>
            </div>

            <div id="printable-area">
                <div v-if="selectedTeacher">
                    <div class="mb-4 flex justify-content-between align-items-end border-bottom-1 border-300 pb-3">
                        <div>
                            <div class="text-xl font-bold text-primary">{{ selectedTeacher.full_name }}</div>
                            <div class="text-600 print-only">Tahun Ajaran: {{ activeYear.name }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-500">Total Beban Mengajar:</div>
                            <div class="text-2xl font-bold text-indigo-600">{{ selectedTeacher.total_jp }} <span class="text-sm font-normal text-500">JP / Minggu</span></div>
                        </div>
                    </div>

                    <div class="overflow-auto border border-300 border-round">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-3 border border-300 text-center" style="width: 120px;">Hari</th>
                                    <th v-for="i in 10" :key="i" class="p-2 border border-300 text-center w-3rem">{{ i }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="day in days" :key="day.value">
                                    <td class="font-bold p-3 border border-300 bg-gray-50 capitalize text-center">{{ day.label }}</td>
                                    <td v-for="slot in 10" :key="slot" class="border border-300 p-1 text-center h-5rem vertical-align-middle">
                                        <div v-if="getSchedule(day.value, slot)" 
                                             class="text-xs p-1 border-round h-full flex flex-column justify-content-center bg-indigo-50 text-indigo-900 border-left-3 border-indigo-500 shadow-1">
                                            <span class="font-bold block text-sm">{{ getSchedule(day.value, slot).classroom }}</span>
                                            <span class="text-500" style="font-size: 10px;">{{ getSchedule(day.value, slot).subject }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="hidden print-only mt-8 flex flex-column align-items-end" style="padding-right: 50px;">
                        <div class="text-center" style="min-width: 250px;">
                            <p class="mb-1">Semarang, {{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                            <p class="mb-8">Mengetahui,<br>Kepala Sekolah</p>
                            <p class="font-bold underline m-0">......................................................</p>
                            <p class="m-0 text-sm text-500">NIP. .............................................</p>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center p-8 text-500 no-print">
                    <i class="pi pi-users text-4xl mb-3"></i>
                    <p class="text-xl">Pilih nama guru untuk melihat rincian beban mengajar.</p>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="isResetModalVisible" modal header="Reset Jadwal Guru" :style="{ width: '50rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
            <div class="mb-3 text-red-600 font-bold p-3 bg-red-50 border-round">
                <i class="pi pi-exclamation-triangle mr-2"></i> Peringatan: Tindakan ini akan menghapus rincian jadwal (hari dan jam) untuk guru-guru yang dipilih. Plotting beban mengajar (jam mengajar/kelas) tidak akan ikut dihapus.
            </div>
            
            <DataTable v-bind="$pagination({ label: 'teachers' })" v-model:selection="selectedTeachers" :value="teachers" dataKey="id">
                <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                <Column field="full_name" header="Nama Guru" sortable></Column>
                <Column field="total_jp" header="Total JP" sortable></Column>
            </DataTable>
            
            <template #footer>
                <Button label="Batal" icon="pi pi-times" @click="isResetModalVisible = false" text severity="secondary" />
                <Button label="Reset Terpilih" icon="pi pi-check" @click="submitReset" severity="danger" :disabled="!selectedTeachers.length || resetForm.processing" :loading="resetForm.processing" />
            </template>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    teachers: Array,
    schedules: Array,
    filters: Object,
    selectedTeacher: Object,
    activeYear: Object
});

const selectedTeacherId = ref(props.filters.teacher_id ? parseInt(props.filters.teacher_id) : null);

const days = [
    { label: 'Senin', value: 'senin' },
    { label: 'Selasa', value: 'selasa' },
    { label: 'Rabu', value: 'rabu' },
    { label: 'Kamis', value: 'kamis' },
    { label: 'Jumat', value: 'jumat' },
    { label: 'Sabtu', value: 'sabtu' },
];

const loadSchedule = () => {
    router.get(route('admin.monitoring.schedule.teacher'), { teacher_id: selectedTeacherId.value }, { preserveState: true });
};

const getSchedule = (day, slot) => {
    return scheduleMap.value[`${day}-${slot}`] || null;
};
const scheduleMap = computed(() => {
    const map = {};

    if (!props.schedules) return map;

    props.schedules.forEach(s => {
        for (let slot = s.start_slot; slot <= s.end_slot; slot++) {
            map[`${s.day}-${slot}`] = s;
        }
    });

    return map;
});
// Fungsi Cetak
const printSchedule = () => {
    window.print();
};

// Logika Reset Jadwal
const isResetModalVisible = ref(false);
const selectedTeachers = ref([]);

const openResetModal = () => {
    isResetModalVisible.value = true;
    selectedTeachers.value = [];
};

const resetForm = useForm({
    teacher_ids: []
});

const submitReset = () => {
    resetForm.teacher_ids = selectedTeachers.value.map(t => t.id);
    resetForm.post(route('admin.monitoring.schedule.reset'), {
        onSuccess: () => {
            isResetModalVisible.value = false;
        }
    });
};
</script>

<style scoped>
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