<template>
    <!-- Discipline Module Navigation Chain -->
    <div class="discipline-nav mb-4">
        <div class="flex align-items-center gap-0 border-round overflow-hidden shadow-1" style="background: var(--surface-card);">
            <button
                v-for="(tab, idx) in tabs"
                :key="tab.key"
                class="discipline-nav-btn flex-1 flex flex-column align-items-center gap-1 py-3 px-2 border-none cursor-pointer transition-all transition-duration-200"
                :class="[
                    active === tab.key
                        ? 'bg-primary text-white'
                        : 'surface-card text-600 hover:surface-100',
                    idx < tabs.length - 1 ? 'border-right-1 surface-border' : ''
                ]"
                @click="navigate(tab)"
            >
                <div class="flex align-items-center gap-2">
                    <i :class="[tab.icon, 'text-base', active === tab.key ? 'text-white' : tab.iconColor]"></i>
                    <span class="font-bold text-sm hidden md:inline">{{ tab.label }}</span>
                </div>
                <span class="text-xs hidden md:inline" :class="active === tab.key ? 'text-white opacity-80' : 'text-400'">
                    {{ tab.desc }}
                </span>
                <!-- Mobile: only short label -->
                <span class="text-xs md:hidden font-semibold">{{ tab.short }}</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    active: {
        type: String,
        required: true
        // 'violations' | 'student-violations' | 'sanctions' | 'reports'
    },
    // role prefix: 'guru.kesiswaan' or 'admin.kesiswaan'
    routePrefix: {
        type: String,
        default: null
    }
});

const page = usePage();

// Auto-detect prefix dari current URL jika tidak diset manual
const prefix = computed(() => {
    if (props.routePrefix) return props.routePrefix;
    const path = window.location.pathname ?? '';
    return path.includes('/admin/') ? 'admin.kesiswaan' : 'guru.kesiswaan';
});

const tabs = [
    {
        key: 'violations',
        label: 'Katalog Aturan',
        short: 'Katalog',
        desc: 'Daftar pasal & level pelanggaran',
        icon: 'pi pi-book',
        iconColor: 'text-blue-500',
        routeSuffix: 'discipline.violations.index',
    },
    {
        key: 'student-violations',
        label: 'Catatan Pelanggaran',
        short: 'Catatan',
        desc: 'Pencatatan per siswa',
        icon: 'pi pi-exclamation-circle',
        iconColor: 'text-red-500',
        routeSuffix: 'discipline.student-violations.index',
    },
    {
        key: 'educational-sanctions',
        label: 'Master Sanksi',
        short: 'Sanksi',
        desc: 'Master sanksi edukatif terstandar',
        icon: 'pi pi-bolt',
        iconColor: 'text-orange-500',
        routeSuffix: 'discipline.educational-sanctions.index',
    },
    {
        key: 'student-sps',
        label: 'Surat Peringatan',
        short: 'SP Siswa',
        desc: 'Terbit & status SP1, SP2, SP3',
        icon: 'pi pi-file-edit',
        iconColor: 'text-red-500',
        routeSuffix: 'discipline.student-sps.index',
    },
    {
        key: 'bk-queue',
        label: 'Antrean BK',
        short: 'Penanganan BK',
        desc: 'Siswa ber-SP Aktif & Jurnal BK',
        icon: 'pi pi-comments',
        iconColor: 'text-purple-500',
        routeSuffix: 'bk.queue',
    },
    {
        key: 'bk-services',
        label: 'Layanan BK',
        short: 'Bimbingan',
        desc: 'Pencatatan 4 Komponen BK',
        icon: 'pi pi-users',
        iconColor: 'text-teal-500',
        routeSuffix: 'bk.service-records.index',
    },
    {
        key: 'sp-rules',
        label: 'Aturan SP',
        short: 'Aturan SP',
        desc: 'Ambang batas pemicu SP',
        icon: 'pi pi-sliders-h',
        iconColor: 'text-orange-500',
        routeSuffix: 'discipline.sp-rules.index',
    },
    {
        key: 'merits',
        label: 'Merit Siswa',
        short: 'Merit',
        desc: 'Sistem penghargaan',
        icon: 'pi pi-star-fill',
        iconColor: 'text-yellow-500',
        routeSuffix: 'discipline.merits.index',
    },
    {
        key: 'reports',
        label: 'Laporan',
        short: 'Laporan',
        desc: 'Statistik & rekapitulasi',
        icon: 'pi pi-chart-bar',
        iconColor: 'text-green-500',
        routeSuffix: 'discipline.reports.index',
    },
];

const navigate = (tab) => {
    const fullRouteName = `${prefix.value}.${tab.routeSuffix}`;
    router.get(route(fullRouteName));
};
</script>

<style scoped>
.discipline-nav-btn {
    outline: none;
}
.discipline-nav-btn:active {
    transform: scale(0.98);
}
</style>
