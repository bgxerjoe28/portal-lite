<template>
    <div class="penilaian-tab-nav mb-4 no-print">
        <TabMenu :model="items" :active-index="activeIndex" class="surface-card border-round shadow-1" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import TabMenu from 'primevue/tabmenu';

const page = usePage();

function isActive(pattern, urlPattern = null) {
    if (typeof route === 'function' && typeof route().current === 'function') {
        if (route().current(pattern)) return true;
    }
    const currentUrl = page.url || window.location.pathname;
    if (urlPattern && currentUrl.includes(urlPattern)) {
        return true;
    }
    const clean = pattern.replace(/\.\*/g, '').replace(/\./g, '/');
    return currentUrl.includes(clean);
}

const items = computed(() => [
    {
        label: 'Komponen Penilaian',
        icon: 'pi pi-sliders-h',
        command: () => router.visit(route('penilaian.components.index')),
        class: isActive('penilaian.components.*', '/penilaian/components') ? 'p-highlight font-bold' : ''
    },
    {
        label: 'Daftar & Input Nilai',
        icon: 'pi pi-file-edit',
        command: () => router.visit(route('penilaian.grades.index')),
        class: (isActive('penilaian.grades.index') || isActive('penilaian.grades.create') || isActive('penilaian.grades.edit') || (page.url.includes('/penilaian/grades') && !page.url.includes('/penilaian/grades/recap'))) ? 'p-highlight font-bold' : ''
    },
    {
        label: 'Rekapitulasi Nilai',
        icon: 'pi pi-chart-bar',
        command: () => router.visit(route('penilaian.grades.recap')),
        class: isActive('penilaian.grades.recap*', '/penilaian/grades/recap') ? 'p-highlight font-bold' : ''
    },
]);

const activeIndex = computed(() => {
    const idx = items.value.findIndex(item => item.class?.includes('p-highlight'));
    return idx >= 0 ? idx : 0;
});
</script>

<style scoped>
.penilaian-tab-nav :deep(.p-tabmenu .p-tabmenu-nav) {
    flex-wrap: wrap;
    background: transparent;
}
.penilaian-tab-nav :deep(.p-tabmenu-item.p-highlight .p-menuitem-link) {
    border-color: var(--primary-color, #3b82f6);
    color: var(--primary-color, #3b82f6);
}
</style>
