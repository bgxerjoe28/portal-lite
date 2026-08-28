<template>
    <div class="teacher-tab-nav mb-4 no-print">
        <TabMenu :model="items" :active-index="activeIndex" class="surface-card border-round shadow-1" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import TabMenu from 'primevue/tabmenu';

const page = usePage();

/**
 * Determine if a given route or URL path is active.
 */
function isActive(pattern, urlPattern = null) {
    if (typeof route === 'function' && typeof route().current === 'function') {
        if (route().current(pattern)) return true;
        // Fallback check if wildcard doesn't work in older Ziggy
        const currentName = route().current();
        if (currentName && pattern.endsWith('.*')) {
            const basePattern = pattern.slice(0, -2);
            if (currentName.startsWith(basePattern)) return true;
        }
    }
    const currentUrl = page.url || window.location.pathname || '';
    if (urlPattern && currentUrl.includes(urlPattern)) {
        return true;
    }
    const clean = pattern.replace(/\.\*/g, '').replace(/\./g, '/');
    return currentUrl.includes(clean);
}

const items = computed(() => [
    {
        label: 'Dashboard Kurikulum',
        icon: 'pi pi-chart-pie',
        command: () => router.visit(route('guru.curriculum.dashboard')),
        class: isActive('guru.curriculum.dashboard*', '/curriculum/dashboard') ? 'p-highlight font-bold' : ''
    },
    {
        label: 'Jadwal Mengajar',
        icon: 'pi pi-calendar-times',
        command: () => router.visit(route('guru.teaching-schedules.index')),
        class: isActive('guru.teaching-schedules.*', '/teacher/my-schedules') ? 'p-highlight font-bold' : ''
    },
    {
        label: 'Capaian Pembelajaran (CP)',
        icon: 'pi pi-book',
        command: () => router.visit(route('guru.cp.index')),
        class: (isActive('guru.cp.*', '/teacher/cp') || isActive('guru.tp.*', '/teacher/cp/')) ? 'p-highlight font-bold' : ''
    },
    {
        label: 'Agenda Mengajar',
        icon: 'pi pi-calendar',
        command: () => router.visit(route('guru.agenda.index')),
        class: isActive('guru.agenda.*', '/teacher/agenda') ? 'p-highlight font-bold' : ''
    },
    {
        label: 'Rekap Presensi',
        icon: 'pi pi-list-check',
        command: () => router.visit(route('guru.attendance-recap.index')),
        class: isActive('guru.attendance-recap.*', '/teacher/attendance-recap') ? 'p-highlight font-bold' : ''
    },
    {
        label: 'Rekap Siswa TKA',
        icon: 'pi pi-users',
        command: () => router.visit(route('akademik.tka-recap.index')),
        class: isActive('akademik.tka-recap.*', '/akademik/tka-recap') ? 'p-highlight font-bold' : ''
    },
]);

const activeIndex = computed(() => {
    const idx = items.value.findIndex(item => item.class?.includes('p-highlight'));
    return idx >= 0 ? idx : 0;
});
</script>

<style scoped>
.teacher-tab-nav :deep(.p-tabmenu .p-tabmenu-nav) {
    flex-wrap: wrap;
    background: transparent;
}
.teacher-tab-nav :deep(.p-tabmenu-item.p-highlight .p-menuitem-link) {
    border-color: var(--primary-color, #3b82f6);
    color: var(--primary-color, #3b82f6);
}
</style>
