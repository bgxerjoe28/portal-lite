<template>
    <MobileLayout title="Dashboard Staf">
        <div class="p-3">
            <!-- Header Section -->
            <div class="flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-2xl font-bold m-0 text-900">
                        Halo, {{ user?.name.split(' ')[0] }}!
                    </h2>
                    <p class="text-600 m-0">SMA Negeri 16 Semarang</p>
                    <div class="flex align-items-center gap-2 mt-2 text-primary font-medium">
                        <i class="pi pi-clock"></i>
                        <span class="text-sm">{{ displayDateTime }}</span>
                    </div>
                </div>
                <Avatar icon="pi pi-shield" size="large" shape="circle" class="bg-primary-100 text-primary" />
            </div>

            <!-- Active Academic Year Banner -->
            <div class="surface-card p-3 border-round shadow-1 mb-4 flex align-items-center justify-content-between bg-blue-50 border-left-3 border-blue-500">
                <div class="flex align-items-center gap-3">
                    <i class="pi pi-calendar text-blue-600 text-2xl"></i>
                    <div>
                        <div class="text-900 font-bold">Tahun Ajaran Aktif</div>
                        <div class="text-700 text-sm">
                            {{ activeYear ? `${activeYear.name} - Semester ${activeYear.semester.toUpperCase()}` : 'Belum ada tahun ajaran aktif' }}
                        </div>
                    </div>
                </div>
                <Tag severity="info" value="Aktif" />
            </div>

            <!-- CASE 1: SPMB IS OPEN -->
            <div v-if="!spmbClosed">
                <!-- Stats Grid Title -->
                <div class="flex justify-content-between align-items-center mb-3">
                    <h3 class="text-lg font-bold m-0 text-900">Progres Daftar Ulang</h3>
                    <span class="text-xs text-500 font-medium">Tahun Ajaran {{ activeYear?.name || '-' }}</span>
                </div>

                <!-- Statistics Cards Grid -->
                <div class="grid mb-4">
                    <div class="col-6">
                        <div class="surface-card p-3 border-round shadow-1 border-left-3 border-blue-500 h-full">
                            <div class="flex justify-content-between align-items-start mb-2">
                                <span class="text-500 font-medium text-xs">Total Calon Siswa</span>
                                <div class="bg-blue-50 text-blue-500 p-1 border-round">
                                    <i class="pi pi-users text-xs"></i>
                                </div>
                            </div>
                            <div class="text-900 font-bold text-xl">{{ stats.total }}</div>
                            <small class="text-500 text-xs block mt-1">Siswa diimpor</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="surface-card p-3 border-round shadow-1 border-left-3 border-orange-500 h-full">
                            <div class="flex justify-content-between align-items-start mb-2">
                                <span class="text-500 font-medium text-xs">Belum Daftar</span>
                                <div class="bg-orange-50 text-orange-500 p-1 border-round">
                                    <i class="pi pi-user-minus text-xs"></i>
                                </div>
                            </div>
                            <div class="text-900 font-bold text-xl">{{ stats.imported }}</div>
                            <small class="text-500 text-xs block mt-1">Belum login</small>
                        </div>
                    </div>
                    <div class="col-6 mt-3">
                        <div class="surface-card p-3 border-round shadow-1 border-left-3 border-purple-500 h-full">
                            <div class="flex justify-content-between align-items-start mb-2">
                                <span class="text-500 font-medium text-xs">Proses Pengisian</span>
                                <div class="bg-purple-50 text-purple-500 p-1 border-round">
                                    <i class="pi pi-pencil text-xs"></i>
                                </div>
                            </div>
                            <div class="text-900 font-bold text-xl">{{ stats.filling + stats.uploading }}</div>
                            <small class="text-500 text-xs block mt-1">Mengisi biodata</small>
                        </div>
                    </div>
                    <div class="col-6 mt-3">
                        <div class="surface-card p-3 border-round shadow-1 border-left-3 border-yellow-500 h-full">
                            <div class="flex justify-content-between align-items-start mb-2">
                                <span class="text-500 font-medium text-xs">Perlu Revisi</span>
                                <div class="bg-yellow-50 text-yellow-500 p-1 border-round">
                                    <i class="pi pi-exclamation-triangle text-xs"></i>
                                </div>
                            </div>
                            <div class="text-900 font-bold text-xl">{{ stats.revision }}</div>
                            <small class="text-500 text-xs block mt-1">Revisi data</small>
                        </div>
                    </div>
                    <div class="col-6 mt-3">
                        <div class="surface-card p-3 border-round shadow-1 border-left-3 border-teal-500 h-full">
                            <div class="flex justify-content-between align-items-start mb-2">
                                <span class="text-500 font-medium text-xs">Menunggu Verifikasi</span>
                                <div class="bg-teal-50 text-teal-500 p-1 border-round">
                                    <i class="pi pi-hourglass text-xs"></i>
                                </div>
                            </div>
                            <div class="text-900 font-bold text-xl">{{ stats.registered }}</div>
                            <small class="text-500 text-xs block mt-1">Perlu verifikasi</small>
                        </div>
                    </div>
                    <div class="col-6 mt-3">
                        <div class="surface-card p-3 border-round shadow-1 border-left-3 border-green-500 h-full">
                            <div class="flex justify-content-between align-items-start mb-2">
                                <span class="text-500 font-medium text-xs">Terverifikasi / Migrasi</span>
                                <div class="bg-green-50 text-green-500 p-1 border-round">
                                    <i class="pi pi-check-circle text-xs"></i>
                                </div>
                            </div>
                            <div class="text-900 font-bold text-xl">{{ stats.verified + stats.migrated }}</div>
                            <small class="text-500 text-xs block mt-1">Lolos verifikasi</small>
                        </div>
                    </div>
                </div>

                <!-- Registration Progress Chart -->
                <div class="surface-card p-3 border-round shadow-1 mb-4">
                    <h3 class="text-base font-bold m-0 mb-3 text-900 border-bottom-1 border-100 pb-2 flex align-items-center">
                        <i class="pi pi-chart-pie mr-2 text-primary"></i>Grafik Progres Daftar Ulang
                    </h3>
                    <div style="height: 280px; position: relative;" class="flex justify-content-center">
                        <Chart type="doughnut" :data="registrationChartData" :options="registrationChartOptions" class="h-full w-full" />
                    </div>
                </div>
            </div>

            <!-- CASE 2: SPMB IS CLOSED -->
            <div v-else>
                <!-- 1. DATA SISWA SUMMARY -->
                <div class="flex justify-content-between align-items-center mb-3">
                    <h3 class="text-lg font-bold m-0 text-900">Data Siswa Aktif</h3>
                    <span class="text-xs text-500 font-medium">Tahun Ajaran {{ activeYear?.name || '-' }}</span>
                </div>
                <div class="grid mb-4">
                    <div class="col-6">
                        <div class="surface-card p-3 border-round shadow-1 border-left-3 border-blue-500 h-full">
                            <div class="text-500 font-medium text-xs mb-1">Total Siswa</div>
                            <div class="text-900 font-bold text-2xl">{{ studentStats.total_siswa }}</div>
                            <small class="text-600 text-xs">Siswa Aktif</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="surface-card p-3 border-round shadow-1 border-left-3 border-green-500 h-full">
                            <div class="text-500 font-medium text-xs mb-1">Total Kelas</div>
                            <div class="text-900 font-bold text-2xl">{{ studentStats.total_kelas }}</div>
                            <small class="text-600 text-xs">Kelas Aktif</small>
                        </div>
                    </div>
                </div>

                <!-- 2. KELAS LP STATS (Stacked Horizontal Bar Chart) -->
                <div class="surface-card p-3 border-round shadow-1 mb-4">
                    <h3 class="text-base font-bold m-0 mb-3 text-900 border-bottom-1 border-100 pb-2 flex align-items-center">
                        <i class="pi pi-users mr-2 text-primary"></i>Statistik Gender per Kelas
                    </h3>
                    <div style="height: 380px; position: relative;">
                        <Chart type="bar" :data="classroomChartData" :options="classroomChartOptions" class="h-full w-full" />
                    </div>
                </div>

                <!-- 3. RELIGION STATS (Doughnut Chart) -->
                <div class="surface-card p-3 border-round shadow-1 mb-4">
                    <h3 class="text-base font-bold m-0 mb-3 text-900 border-bottom-1 border-100 pb-2 flex align-items-center">
                        <i class="pi pi-heart-fill mr-2 text-red-500"></i>Statistik Agama Siswa
                    </h3>
                    <div style="height: 250px; position: relative;" class="flex justify-content-center">
                        <Chart type="doughnut" :data="religionChartData" :options="religionChartOptions" class="h-full w-full" />
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <h3 class="text-lg font-bold mb-3 text-900">Aksi Cepat</h3>
            <div class="grid text-left mb-4">
                <!-- 1. Daftar Ulang (Only if SPMB is open) -->
                <div v-if="!spmbClosed" class="col-12" @click="router.get('/admin/daftar-ulang')">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform cursor-pointer hover:surface-50 border-top-3 border-primary flex align-items-center gap-3">
                        <div class="bg-primary-100 p-2 border-round flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="pi pi-id-card text-primary text-xl"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-900">Daftar Ulang</div>
                            <small class="text-500">Verifikasi berkas calon siswa</small>
                        </div>
                    </div>
                </div>

                <!-- 2. Lapor Rusak -->
                <div class="col-12" @click="router.get(route('facility.reports.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform cursor-pointer hover:surface-50 border-top-3 border-orange-500 flex align-items-center gap-3">
                        <div class="bg-orange-100 p-2 border-round flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="pi pi-wrench text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-900">Lapor Rusak</div>
                            <small class="text-500">Laporkan kerusakan fasilitas sekolah</small>
                        </div>
                    </div>
                </div>

                <!-- 3. Kelola Sarpras (Conditional) -->
                <div v-if="$page.props.auth.permissions.includes('manage-facility-reports') || user?.roles.some(r => r.name === 'admin')" class="col-12" @click="router.get(route('admin.facility.reports.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform cursor-pointer hover:surface-50 border-top-3 border-purple-500 flex align-items-center gap-3">
                        <div class="bg-purple-100 p-2 border-round flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="pi pi-shield text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-900">Kelola Sarpras</div>
                            <small class="text-500">Verifikasi & tangani laporan sarpras</small>
                        </div>
                    </div>
                </div>

                <!-- 4. Input Izin / Permits (Conditional) -->
                <div v-if="$page.props.auth.permissions.includes('manage-permits') || user?.roles.some(r => ['admin', 'pegawai', 'guru bk'].includes(r.name))" class="col-12" @click="router.get(route('permits.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform cursor-pointer hover:surface-50 border-top-3 border-teal-500 flex align-items-center gap-3">
                        <div class="bg-teal-100 p-2 border-round flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="pi pi-check-square text-teal-600 text-xl"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-900">Input Izin / Permits</div>
                            <small class="text-500">Input izin & dispensasi meninggalkan kelas</small>
                        </div>
                    </div>
                </div>

                <!-- 5. Profil Saya -->
                <div class="col-12" @click="router.get('/profile')">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform cursor-pointer hover:surface-50 border-top-3 border-primary flex align-items-center gap-3">
                        <div class="bg-primary-100 p-2 border-round flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="pi pi-user-edit text-primary text-xl"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-900">Profil Saya</div>
                            <small class="text-500">Update data diri & password</small>
                        </div>
                    </div>
                </div>

                <!-- 6. Modul Surat -->
                <div v-if="$page.props.auth.permissions.includes('access-surat') || $page.props.auth.permissions.includes('access-penugasan') || user?.roles.some(r => ['admin', 'pegawai'].includes(r.name))" class="col-12" @click="router.get('/surat/dashboard')">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform cursor-pointer hover:surface-50 border-top-3 border-green-500 flex align-items-center gap-3">
                        <div class="bg-green-100 p-2 border-round flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="pi pi-envelope text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-900">Modul Surat</div>
                            <small class="text-500">Kelola surat masuk, keluar, & penugasan</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import Chart from 'primevue/chart';

const props = defineProps({
    stats: Object,
    activeYear: Object,
    spmbClosed: Boolean,
    studentStats: Object,
    classroomsLp: Array,
    religionsStats: Array
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

// 🕒 Real-time clock logic
const now = ref(new Date());
let timer;

onMounted(() => {
    timer = setInterval(() => {
        now.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    clearInterval(timer);
});

// Format: "Minggu, 25 Januari 2026 • 17:15:01 WIB"
const displayDateTime = computed(() => {
    return now.value.toLocaleString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }) + ' WIB';
});

// === CHART CONFIGURATIONS ===

// 1. Chart Progres Daftar Ulang (Ketika SPMB dibuka)
const registrationChartData = computed(() => {
    if (!props.stats) return null;
    return {
        labels: ['Belum Login', 'Mengisi Biodata/Berkas', 'Perlu Revisi', 'Menunggu Verifikasi', 'Terverifikasi'],
        datasets: [
            {
                data: [
                    props.stats.imported,
                    props.stats.filling + props.stats.uploading,
                    props.stats.revision,
                    props.stats.registered,
                    props.stats.verified + props.stats.migrated
                ],
                backgroundColor: ['#F97316', '#8B5CF6', '#F59E0B', '#14B8A6', '#10B981'],
                hoverBackgroundColor: ['#EA580C', '#7C3AED', '#D97706', '#0D9488', '#059669']
            }
        ]
    };
});

const registrationChartOptions = {
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                boxWidth: 10,
                font: {
                    size: 10,
                    weight: 'bold'
                }
            }
        }
    },
    maintainAspectRatio: false
};

// 2. Chart Kelas LP (Laki-laki & Perempuan, Stacked Horizontal Bar Chart)
const classroomChartData = computed(() => {
    if (!props.classroomsLp || props.classroomsLp.length === 0) return null;
    return {
        labels: props.classroomsLp.map(c => c.name),
        datasets: [
            {
                label: 'Laki-laki (L)',
                backgroundColor: '#3B82F6',
                data: props.classroomsLp.map(c => c.l)
            },
            {
                label: 'Perempuan (P)',
                backgroundColor: '#EC4899',
                data: props.classroomsLp.map(c => c.p)
            }
        ]
    };
});

const classroomChartOptions = {
    indexAxis: 'y', // Makes it a horizontal bar chart
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                boxWidth: 10,
                font: {
                    size: 10,
                    weight: 'bold'
                }
            }
        }
    },
    scales: {
        x: {
            stacked: true,
            grid: {
                display: false
            }
        },
        y: {
            stacked: true,
            grid: {
                display: false
            }
        }
    }
};

// 3. Chart Statistik Agama (Ketika SPMB ditutup)
const religionChartData = computed(() => {
    if (!props.religionsStats || props.religionsStats.length === 0) return null;
    
    const colors = [
        '#3B82F6', // Blue (Islam)
        '#10B981', // Green (Protestan)
        '#F59E0B', // Orange (Katolik)
        '#8B5CF6', // Purple (Hindu)
        '#EF4444', // Red (Buddha)
        '#EC4899'  // Pink (Konghucu)
    ];

    return {
        labels: props.religionsStats.map(r => r.name),
        datasets: [
            {
                data: props.religionsStats.map(r => r.total),
                backgroundColor: colors.slice(0, props.religionsStats.length),
                hoverBackgroundColor: colors.slice(0, props.religionsStats.length)
            }
        ]
    };
});

const religionChartOptions = {
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                boxWidth: 10,
                font: {
                    size: 10,
                    weight: 'bold'
                }
            }
        }
    },
    maintainAspectRatio: false
};
</script>
