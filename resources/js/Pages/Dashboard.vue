<template>
    <AppLayout :title="'Dashboard ' + (user?.roles[0]?.name || '')">
        <div class="max-w-7xl mx-auto p-4">
            
            <!-- HEADER SECTION -->
            <div class="flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center mb-5 gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-home text-primary text-3xl"></i>
                        Selamat Datang, {{ user.name }}!
                    </h1>
                    <div class="mt-2 text-600 flex align-items-center gap-2">
                        <span>Tahun Ajaran Aktif:</span>
                        <Tag severity="info" :value="activeYear?.name || '-'" />
                        <span class="ml-2">| Tanggal: {{ formatDateIndo(date) }}</span>
                    </div>
                </div>
            </div>

            <!-- STATISTIK GURU -->
            <div class="mb-5">
                <h2 class="text-xl font-bold text-800 m-0 mb-3">Statistik Presensi Guru (Hari Ini)</h2>
                <div class="grid">
                    <div class="col-12 md:col-4">
                        <div class="surface-card p-4 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer border-left-4 border-primary flex justify-content-between align-items-center h-full" @click="openTeacherModal('all', 'Semua Guru')">
                            <div>
                                <span class="block text-500 font-medium mb-1">Total Guru</span>
                                <div class="text-900 font-bold text-3xl">{{ teacherStats.total }}</div>
                            </div>
                            <div class="flex align-items-center justify-content-center bg-blue-100 text-blue-700 border-round-lg" style="width: 3.5rem; height: 3.5rem;">
                                <i class="pi pi-users text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 md:col-4">
                        <div class="surface-card p-4 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer border-left-4 border-green-500 flex justify-content-between align-items-center h-full" @click="openTeacherModal('mengajar', 'Guru Mengajar')">
                            <div>
                                <span class="block text-500 font-medium mb-1">Guru Mengajar</span>
                                <div class="text-900 font-bold text-3xl">{{ teacherStats.mengajar }}</div>
                                <span class="text-sm text-green-500 mt-1 block">Telah mengisi agenda</span>
                            </div>
                            <div class="flex align-items-center justify-content-center bg-green-100 text-green-700 border-round-lg" style="width: 3.5rem; height: 3.5rem;">
                                <i class="pi pi-check-circle text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 md:col-4">
                        <div class="surface-card p-4 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer border-left-4 border-orange-500 flex justify-content-between align-items-center h-full" @click="openTeacherModal('kosong', 'Guru Kosong')">
                            <div>
                                <span class="block text-500 font-medium mb-1">Guru Kosong</span>
                                <div class="text-900 font-bold text-3xl">{{ teacherStats.kosong }}</div>
                                <span class="text-sm text-orange-500 mt-1 block">Belum mengisi agenda / Tidak ada jadwal</span>
                            </div>
                            <div class="flex align-items-center justify-content-center bg-orange-100 text-orange-700 border-round-lg" style="width: 3.5rem; height: 3.5rem;">
                                <i class="pi pi-minus-circle text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STATISTIK SISWA -->
            <div class="mb-5">
                <h2 class="text-xl font-bold text-800 m-0 mb-3">Statistik Presensi Siswa (Hari Ini)</h2>
                <div class="grid">
                    <div class="col-12 sm:col-6 lg:col-4 flex-1">
                        <div class="surface-card p-4 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer border-left-4 border-primary flex justify-content-between align-items-center h-full" @click="openStudentModal('H', 'Siswa Hadir')">
                            <div>
                                <span class="block text-500 font-medium mb-1">Total Hadir</span>
                                <div class="text-900 font-bold text-3xl">{{ attendanceStats.hadir }}</div>
                            </div>
                            <div class="flex align-items-center justify-content-center bg-blue-100 text-blue-700 border-round-lg" style="width: 3.5rem; height: 3.5rem;">
                                <i class="pi pi-check-circle text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 sm:col-6 lg:col-4 flex-1">
                        <div class="surface-card p-4 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer border-left-4 border-cyan-500 flex justify-content-between align-items-center h-full" @click="openStudentModal('D', 'Siswa Dispensasi')">
                            <div>
                                <span class="block text-500 font-medium mb-1">Dispensasi</span>
                                <div class="text-900 font-bold text-3xl">{{ attendanceStats.dispen }}</div>
                            </div>
                            <div class="flex align-items-center justify-content-center bg-cyan-100 text-cyan-700 border-round-lg" style="width: 3.5rem; height: 3.5rem;">
                                <i class="pi pi-briefcase text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 sm:col-6 lg:col-4 flex-1">
                        <div class="grid grid-nogutter gap-3 h-full">
                            <div class="col-12">
                                <div class="surface-card p-3 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer border-left-4 border-yellow-500 flex justify-content-between align-items-center h-full" @click="openStudentModal('S', 'Siswa Sakit')">
                                    <div>
                                        <span class="block text-500 font-medium mb-1">Sakit</span>
                                        <div class="text-900 font-bold text-2xl">{{ attendanceStats.sakit }}</div>
                                    </div>
                                    <div class="flex align-items-center justify-content-center bg-yellow-100 text-yellow-700 border-round-lg" style="width: 3rem; height: 3rem;">
                                        <i class="pi pi-plus-circle text-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="surface-card p-3 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer border-left-4 border-blue-500 flex justify-content-between align-items-center h-full" @click="openStudentModal('I', 'Siswa Izin')">
                                    <div>
                                        <span class="block text-500 font-medium mb-1">Izin</span>
                                        <div class="text-900 font-bold text-2xl">{{ attendanceStats.izin }}</div>
                                    </div>
                                    <div class="flex align-items-center justify-content-center bg-blue-100 text-blue-700 border-round-lg" style="width: 3rem; height: 3rem;">
                                        <i class="pi pi-info-circle text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 sm:col-6 lg:col-4 flex-1">
                        <div class="grid grid-nogutter gap-3 h-full">
                            <div class="col-12">
                                <div class="surface-card p-3 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer border-left-4 border-orange-500 flex justify-content-between align-items-center h-full" @click="openStudentModal('T', 'Siswa Terlambat')">
                                    <div>
                                        <span class="block text-500 font-medium mb-1">Terlambat</span>
                                        <div class="text-900 font-bold text-2xl">{{ attendanceStats.telat }}</div>
                                    </div>
                                    <div class="flex align-items-center justify-content-center bg-orange-100 text-orange-700 border-round-lg" style="width: 3rem; height: 3rem;">
                                        <i class="pi pi-clock text-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="surface-card p-3 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer border-left-4 border-red-500 flex justify-content-between align-items-center h-full" @click="openStudentModal('A', 'Siswa Tidak Hadir (TH)')">
                                    <div>
                                        <span class="block text-500 font-medium mb-1">Tidak Hadir (TH)</span>
                                        <div class="text-900 font-bold text-2xl">{{ attendanceStats.alfa }}</div>
                                    </div>
                                    <div class="flex align-items-center justify-content-center bg-red-100 text-red-700 border-round-lg" style="width: 3rem; height: 3rem;">
                                        <i class="pi pi-times-circle text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AKSES CEPAT -->
            <div class="mb-4">
                <h2 class="text-xl font-bold text-800 m-0 mb-3">Akses Cepat (Pendataan)</h2>
                <div class="grid">
                    <div class="col-6 md:col-3">
                        <div class="surface-card p-3 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer h-full flex flex-column align-items-center text-center" @click="navigate('/admin/academic-years')">
                            <div class="flex align-items-center justify-content-center bg-indigo-100 text-indigo-600 border-round-full mb-2 mt-2" style="width: 4rem; height: 4rem;">
                                <i class="pi pi-calendar text-3xl"></i>
                            </div>
                            <span class="text-900 font-bold mb-1">Tahun Ajaran</span>
                        </div>
                    </div>
                    
                    <div class="col-6 md:col-3">
                        <div class="surface-card p-3 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer h-full flex flex-column align-items-center text-center" @click="navigate('/admin/teachers')">
                            <div class="flex align-items-center justify-content-center bg-teal-100 text-teal-600 border-round-full mb-2 mt-2" style="width: 4rem; height: 4rem;">
                                <i class="pi pi-id-card text-3xl"></i>
                            </div>
                            <span class="text-900 font-bold mb-1">Data Guru</span>
                        </div>
                    </div>

                    <div class="col-6 md:col-3">
                        <div class="surface-card p-3 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer h-full flex flex-column align-items-center text-center" @click="navigate('/admin/students')">
                            <div class="flex align-items-center justify-content-center bg-blue-100 text-blue-600 border-round-full mb-2 mt-2" style="width: 4rem; height: 4rem;">
                                <i class="pi pi-users text-3xl"></i>
                            </div>
                            <span class="text-900 font-bold mb-1">Data Siswa</span>
                        </div>
                    </div>

                    <div class="col-6 md:col-3">
                        <div class="surface-card p-3 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer h-full flex flex-column align-items-center text-center" @click="navigate('/admin/classrooms')">
                            <div class="flex align-items-center justify-content-center bg-purple-100 text-purple-600 border-round-full mb-2 mt-2" style="width: 4rem; height: 4rem;">
                                <i class="pi pi-building text-3xl"></i>
                            </div>
                            <span class="text-900 font-bold mb-1">Data Kelas</span>
                        </div>
                    </div>

                    <div class="col-6 md:col-3">
                        <div class="surface-card p-3 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer h-full flex flex-column align-items-center text-center relative" @click="navigate('/admin/facility-reports')">
                            <div class="flex align-items-center justify-content-center bg-orange-100 text-orange-600 border-round-full mb-2 mt-2" style="width: 4rem; height: 4rem;">
                                <i class="pi pi-wrench text-3xl"></i>
                            </div>
                            <span class="text-900 font-bold mb-1">Laporan Sarpras</span>
                            <span v-if="pendingFacilityReportsCount > 0" class="absolute top-0 right-0 bg-red-500 text-white border-circle flex align-items-center justify-content-center text-xs font-bold" style="width: 22px; height: 22px; transform: translate(15%, -15%)">
                                {{ pendingFacilityReportsCount }}
                            </span>
                        </div>
                    </div>

                    <div v-if="page.props.auth.permissions.includes('access-surat') || page.props.auth.permissions.includes('access-penugasan')" class="col-6 md:col-3">
                        <div class="surface-card p-3 border-round-xl shadow-2 hover:shadow-4 transition-all transition-duration-200 cursor-pointer h-full flex flex-column align-items-center text-center" @click="navigate('/surat/dashboard')">
                            <div class="flex align-items-center justify-content-center bg-green-100 text-green-600 border-round-full mb-2 mt-2" style="width: 4rem; height: 4rem;">
                                <i class="pi pi-envelope text-3xl"></i>
                            </div>
                            <span class="text-900 font-bold mb-1">Modul Surat</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modals -->
        <Dialog v-model:visible="showTeacherModal" modal :header="teacherModalTitle" :style="{ width: '50rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
            <div v-if="loadingTeachers" class="flex justify-content-center p-4">
                <ProgressSpinner />
            </div>
            <DataTable v-bind="$pagination({ label: 'teachersdata' })" v-else :value="teachersData" dataKey="id" class="p-datatable-sm">
                <Column field="name" header="Nama Guru" sortable></Column>
                <Column field="kelas_jam" header="Keterangan (Kelas & Jam)"></Column>
                <template #empty>
                    <div class="text-center p-3">Tidak ada data.</div>
                </template>
            </DataTable>
        </Dialog>

        <Dialog v-model:visible="showStudentModal" modal :header="studentModalTitle" :style="{ width: '60rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
            <div class="mb-3 text-500 text-sm">
                <i class="pi pi-info-circle mr-1"></i> Data di bawah ini adalah rekapitulasi presensi per-mata pelajaran hari ini.
            </div>
            <div v-if="loadingStudents" class="flex justify-content-center p-4">
                <ProgressSpinner />
            </div>
            <DataTable v-bind="$pagination({ label: 'studentsdata' })" v-else :value="studentsData" dataKey="id" class="p-datatable-sm">
                <Column field="student_name" header="Nama Siswa" sortable></Column>
                <Column field="classroom_name" header="Kelas" sortable></Column>
                <Column field="subject_name" header="Mata Pelajaran" sortable></Column>
                <template #empty>
                    <div class="text-center p-3">Tidak ada data presensi dengan status ini.</div>
                </template>
            </DataTable>
        </Dialog>

    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ProgressSpinner from 'primevue/progressspinner';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const page = usePage();
const user = computed(() => page.props.auth?.user);

const props = defineProps({
    activeYear: Object,
    date: String,
    teacherStats: Object,
    attendanceStats: Object,
    pendingFacilityReportsCount: Number
});

const navigatingTo = ref(null);

const navigate = (url) => {
    navigatingTo.value = url;
    router.get(url, {}, {
        onFinish: () => {
            navigatingTo.value = null;
        }
    });
};

const formatDateIndo = (dateStr) => {
    if (!dateStr) return '';
    return dayjs(dateStr).format('dddd, D MMMM YYYY');
};

const showTeacherModal = ref(false);
const teacherModalTitle = ref('');
const teachersData = ref([]);
const loadingTeachers = ref(false);

const showStudentModal = ref(false);
const studentModalTitle = ref('');
const studentsData = ref([]);
const loadingStudents = ref(false);

const openTeacherModal = async (type, title) => {
    showTeacherModal.value = true;
    teacherModalTitle.value = title;
    loadingTeachers.value = true;
    teachersData.value = [];
    try {
        const response = await axios.get(route('admin.dashboard.api.teachers', { type }));
        teachersData.value = response.data;
    } catch (e) {
        console.error('Failed to load teacher data', e);
    } finally {
        loadingTeachers.value = false;
    }
};

const openStudentModal = async (type, title) => {
    showStudentModal.value = true;
    studentModalTitle.value = title;
    loadingStudents.value = true;
    studentsData.value = [];
    try {
        const response = await axios.get(route('admin.dashboard.api.students', { type }));
        studentsData.value = response.data;
    } catch (e) {
        console.error('Failed to load student data', e);
    } finally {
        loadingStudents.value = false;
    }
};
</script>