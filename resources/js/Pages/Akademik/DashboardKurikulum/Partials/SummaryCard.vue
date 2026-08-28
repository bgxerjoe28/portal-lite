<template>
<!-- SUMMARY -->
<div class="grid mb-5">
    <div class="col-12 md:col-3">
        <div class="surface-card p-4 text-center border-round shadow-1">
            <div class="text-3xl font-bold">{{ summary.total }}</div>
            <div class="text-600 mt-1">Total Guru</div>
        </div>
    </div>

    <div class="col-12 md:col-3">
        <div class="surface-card p-4 text-center border-round shadow-1 border-left-3 border-green-500">
            <div class="text-3xl font-bold text-green-600">{{ summary.balanced }}</div>
            <div class="text-600 mt-1">Seimbang</div>
        </div>
    </div>

    <div class="col-12 md:col-3">
        <div class="surface-card p-4 text-center border-round shadow-1 border-left-3 border-red-500">
            <div class="text-3xl font-bold text-red-500">{{ summary.overload }}</div>
            <div class="text-600 mt-1">Overload</div>
        </div>
    </div>

    <div class="col-12 md:col-3">
        <div class="surface-card p-4 text-center border-round shadow-1 border-left-3 border-orange-500">
            <div class="text-3xl font-bold text-orange-500">{{ summary.underload }}</div>
            <div class="text-600 mt-1">Underload</div>
        </div>
    </div>
</div>
</template>

<script setup>
import { computed } from 'vue'


const props = defineProps({
    label: String,
    value: [String, Number],
    color: {
        type: String,
        default: null, // green | red | orange | null
    }
})

const borderClass = computed(() => {
    if (!props.color) return ''
    return `border-left-3 border-${props.color}-500`
})

const textClass = computed(() => {
    if (!props.color) return ''
    return `text-${props.color}-600`
})
</script>
