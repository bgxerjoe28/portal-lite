<template>
    <div v-if="links.length > 3" class="flex flex-wrap justify-content-center mt-4 gap-2">
        <template v-for="(link, key) in links" :key="key">
            <div 
                v-if="link.url === null" 
                class="p-button p-component p-button-text p-button-plain p-disabled" 
                v-html="link.label"
            ></div>

            <Link 
                v-else 
                :href="link.url" 
                class="p-button p-component p-button-sm"
                :class="{ 'p-button-outlined': !link.active, 'p-button-primary': link.active }"
            >
                <span v-html="link.label"></span>
            </Link>
        </template>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array
});
</script>

<style scoped>
/* Sedikit styling agar tombol tidak terlalu nempel */
.p-button {
    min-width: 2.5rem;
    justify-content: center;
}
</style>