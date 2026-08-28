<template>
    <AppLayout title="Dashboard Guru">
        <div class="p-3">
            <div class="flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-2xl font-bold m-0 text-900">
                        Halo, {{ teacher_info.nama_panggilan }} {{ user?.name.split(' ')[0] }}!
                    </h2>
                    <p class="text-600 m-0">SMA Negeri 16 Semarang</p>
                    <div class="flex align-items-center gap-2 mt-2 text-primary font-medium">
                        <i class="pi pi-clock"></i>
                        <span class="text-sm">{{ displayDateTime }}</span>
                    </div>
                </div>
                <Avatar icon="pi pi-user" size="large" shape="circle" class="bg-primary-100 text-primary" />
            </div>

            <div class="grid mb-4">
                <div class="col-6">
                    <div class="surface-card p-3 border-round shadow-1 border-left-3 border-blue-500">
                        <div class="text-500 font-medium text-sm mb-2">Kelas Hari Ini</div>
                        <div class="text-900 font-bold text-xl">{{ stats.today_classes }} Sesi</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="surface-card p-3 border-round shadow-1 border-left-3 border-green-500">
                        <div class="text-500 font-medium text-sm mb-2">Progres Presensi</div>
                        <div class="text-900 font-bold text-xl">{{ stats.percentage }}%</div>
                    </div>
                </div>
            </div>

            <div v-if="activeAgenda" class="surface-card p-3 border-round shadow-2 mb-4 bg-primary text-white">
                <div class="flex justify-content-between align-items-center mb-3">
                    <span class="font-bold uppercase text-xs opacity-80">Update Terakhir</span>
                    <Tag severity="warn" :value="'Jam Ke ' + (activeAgenda.start_slot ?? activeAgenda.schedule_detail?.start_slot ?? '-')" />
                </div>
                <h3 class="text-xl font-bold m-0 mb-1">
                    {{ activeAgenda.classroom?.name }} - {{ activeAgenda.subject?.name }}
                </h3>
                <p class="m-0 text-sm opacity-90 mb-3 line-height-3">
                    {{ activeAgenda.materi_pembelajaran || 'Belum ada ringkasan materi' }}
                </p>
                <Button 
                    label="Lihat Detail" 
                    icon="pi pi-search" 
                    severity="secondary" 
                    size="small" 
                    fluid 
                    @click="router.get(route('guru.agenda.show', activeAgenda.id))"
                />
            </div>
            
            <div v-else class="surface-card p-4 border-round shadow-1 mb-4 text-center border-1 border-dashed border-300">
                <i class="pi pi-calendar-times text-400 text-3xl mb-2"></i>
                <p class="m-0 text-500 text-sm">Belum ada agenda yang diisi hari ini.</p>
            </div>

            <h3 class="text-lg font-bold mb-3 text-900">Aksi Cepat</h3>
            <div class="grid text-center mb-4">

                <!-- Selalu tampil: Isi Agenda -->
                <div class="col-3" @click="router.get(route('guru.agenda.create'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform">
                        <i class="pi pi-calendar-plus text-primary text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Isi Agenda</span>
                </div>

                <!-- Selalu tampil: Penugasan (PR) -->
                <div class="col-3" @click="router.get(route('guru.assignments.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-indigo-50">
                        <i class="pi pi-file-edit text-indigo-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Tugas</span>
                </div>

                <!-- Selalu tampil: Cetak Agenda -->
                <div class="col-3" @click="router.get(route('guru.reports.personal'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform">
                        <i class="pi pi-print text-primary text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Cetak Agenda</span>
                </div>

                <!-- Selalu tampil: Rekap Presensi Siswa Mapel -->
                <div class="col-3" @click="router.get(route('guru.attendance-recap.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-green-50">
                        <i class="pi pi-list-check text-green-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Rekap Siswa</span>
                </div>

                <!-- Hanya Wali Kelas: Jurnal Kelas -->
                <div v-if="is_walikelas" class="col-3" @click="router.get(route('guru.reports.classroom'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-blue-50">
                        <i class="pi pi-users text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Jurnal Kelas</span>
                </div>

                <!-- Hanya Wali Kelas: Rekap Absen -->
                <div v-if="is_walikelas" class="col-3" @click="router.get(route('guru.monitoring.absences.weekly'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-teal-50">
                        <i class="pi pi-calendar text-teal-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Rekap Absen</span>
                </div>

                <!-- Selalu tampil: Pinjam Sarpras & Aset Guru -->
                <div class="col-3" @click="router.get(route('guru.sarpras.reservations.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-cyan-50">
                        <i class="pi pi-box text-cyan-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Pinjam Aset</span>
                </div>

                <!-- Selalu tampil: Lapor Rusak -->
                <div class="col-3" @click="router.get(route('facility.reports.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-orange-50 relative">
                        <i class="pi pi-wrench text-orange-600 text-xl"></i>
                        <span v-if="unread_facility_reports > 0" class="absolute top-0 right-0 bg-red-500 text-white border-circle flex align-items-center justify-content-center text-xs font-bold" style="width: 18px; height: 18px; transform: translate(30%, -30%)">
                            {{ unread_facility_reports }}
                        </span>
                    </div>
                    <span class="text-xs font-bold text-700">Lapor Rusak</span>
                </div>

                <!-- Hanya manage-facility-reports: Kelola Sarpras -->
                <div v-if="hasPermission('manage-facility-reports')" class="col-3" @click="router.get(route('admin.facility.reports.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-purple-50">
                        <i class="pi pi-shield text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Kelola Sarpras</span>
                </div>

                <!-- Hanya manage-discipline: Tata Tertib -->
                <div v-if="hasPermission('manage-discipline')" class="col-3" @click="router.get(route('guru.kesiswaan.discipline.violations.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-red-50">
                        <i class="pi pi-exclamation-circle text-red-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Tata Tertib</span>
                </div>

                <!-- Hanya manage-permits: Perizinan -->
                <div v-if="hasPermission('manage-permits')" class="col-3" @click="router.get(route('permits.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-green-50">
                        <i class="pi pi-file-check text-green-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Perizinan</span>
                </div>

                <!-- Hanya manage-lates: Terlambat -->
                <div v-if="hasPermission('manage-lates')" class="col-3" @click="router.get(route('lates.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-yellow-50">
                        <i class="pi pi-clock text-yellow-700 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Terlambat</span>
                </div>

            </div>

            <div class="flex justify-content-between align-items-center mb-3">
                <h3 class="text-lg font-bold m-0 text-900">Riwayat Terakhir</h3>
                <Button label="Semua" text size="small" @click="router.get(route('guru.agenda.index'))" />
            </div>
            
            <div class="flex flex-column gap-2 mb-5">
                <div 
                    v-for="agenda in recentAgendas" 
                    :key="agenda.id" 
                    class="flex align-items-center gap-3 p-3 surface-card border-round shadow-1 active:surface-50"
                    @click="router.get(route('guru.agenda.show', agenda.id))"
                >
                    <div class="bg-blue-50 p-2 border-round">
                        <i class="pi pi-book text-blue-500"></i>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-bold text-900">{{ agenda.classroom?.name }}</div>
                        <div class="text-xs text-500">
                            {{ formatDate(agenda.date) }} · {{ agenda.hadir_count }} Siswa Hadir
                        </div>
                    </div>
                    <i class="pi pi-chevron-right text-300"></i>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'; // 👈 Tambahkan ref, onMounted, onUnmounted
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
    teacher_info: Object,
    stats: Object,
    activeAgenda: Object,
    is_walikelas: Boolean,
    recentAgendas: Array,
    unread_facility_reports: Number,
    guru_permissions: Array, // ['manage-discipline', 'manage-permits', ...]
});

// Helper: cek apakah guru memiliki permission tertentu
const hasPermission = (perm) => {
    if (!props.guru_permissions) return false;
    return props.guru_permissions.includes(perm);
};

const page = usePage();
const user = computed(() => page.props.auth.user);

// 🕒 LOGIK JAM REAL-TIME
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
        second: '2-digit',
    }) + ' WIB';
});

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', { 
        day: 'numeric', 
        month: 'short' 
    });
}
</script>