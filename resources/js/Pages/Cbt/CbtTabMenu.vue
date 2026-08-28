<template>
    <div class="mb-4">
        <TabMenu :model="items" :active-index="activeIndex" class="surface-card border-round shadow-1" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import TabMenu from 'primevue/tabmenu';

// Inertia page props (contains current URL)
const page = usePage();

/**
 * Determine if a given route pattern is active.
 * First tries Laravel's route().current (handles wildcard patterns).
 * If unavailable, falls back to a simple substring check on the current URL.
 */
function isActive(pattern) {
    // Laravel route helper exists globally when using @inertiajs/inertia-vue3 + ziggy
    if (typeof route === 'function' && typeof route().current === 'function') {
        // @ts-ignore – route() is injected by Ziggy
        if (route().current(pattern)) return true;
    }
    // Fallback: strip wildcard and check URL string
    const clean = pattern.replace(/\.\*/g, '').replace(/\./g, '/');
    return page.url?.includes(clean) ?? false;
}

const items = computed(() => [
    {
        label: 'Bank Soal',
        icon: 'pi pi-folder',
        url: route('cbt.bank.index'),
        class: isActive('cbt.bank.*') ? 'p-highlight font-bold' : ''
    },
    {
        label: 'Jadwal Ujian',
        icon: 'pi pi-calendar',
        url: route('cbt.exams.index'),
        class: isActive('cbt.exams.*') ? 'p-highlight font-bold' : ''
    },
    {
        label: 'Jadwal Pengawas',
        icon: 'pi pi-clock',
        url: route('cbt.proctor-schedules.index'),
        class: isActive('cbt.proctor-schedules.*') ? 'p-highlight font-bold' : ''
    },
    {
        label: 'Monitor / Pengawas',
        icon: 'pi pi-desktop',
        url: route('cbt.proctor.index'),
        class: isActive('cbt.proctor.*') ? 'p-highlight font-bold' : ''
    },
]);

const activeIndex = computed(() => {
    return items.value.findIndex(item => item.class?.includes('p-highlight')) ?? -1;
});
</script>
