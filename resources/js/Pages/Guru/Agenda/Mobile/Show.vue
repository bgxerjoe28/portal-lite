<template>
    <MobileLayout title="Lihat Agenda Guru">
        <div class="p-3 mb-8">
            <div class="flex align-items-center gap-3 mb-4">
                <Button 
                    icon="pi pi-arrow-left" 
                    text 
                    rounded 
                    severity="secondary" 
                    @click="router.get(route('guru.agenda.index'))" 
                />
                <h2 class="text-xl font-bold m-0 text-900">Detail Agenda</h2>
            </div>

            <div class="surface-card p-4 shadow-1 border-round mb-3">
                <div class="flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-sm font-bold text-500 uppercase mb-1">
                            {{ formatDate(agenda.date) }}
                        </div>
                        <h1 class="text-2xl font-bold text-900 m-0">{{ agenda.classroom?.name }}</h1>
                    </div>
                    <Tag 
                        severity="info" 
                        :value="`Jam ${jamKe}`" 
                        class="px-3 py-2"
                    />
                </div>
                <Divider />
                <div class="flex align-items-center gap-2 text-700">
                    <i class="pi pi-book"></i>
                    <span class="font-medium">{{ agenda.subject?.name }}</span>
                </div>
            </div>

            <div class="surface-card p-4 shadow-1 border-round mb-3">
                <div class="mb-4" v-if="agenda.tp">
                    <label class="block text-xs font-bold text-500 uppercase mb-2">Tujuan Pembelajaran</label>
                    <div class="p-3 surface-100 border-round border-left-3 border-primary">
                        <span class="font-bold text-primary">{{ agenda.tp?.kode_tp }}:</span>
                        <p class="m-0 mt-1 text-700 line-height-3">{{ agenda.tp?.rumusan_tp }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-500 uppercase mb-2">Materi / Ringkasan</label>
                    <p class="m-0 text-800 line-height-4">{{ agenda.materi_pembelajaran }}</p>
                </div>

                <div v-if="agenda.keterangan" class="mt-4 pt-3 border-top-1 border-50">
                    <label class="block text-xs font-bold text-500 uppercase mb-1 text-400">Keterangan</label>
                    <p class="m-0 text-sm italic text-600">{{ agenda.keterangan }}</p>
                </div>
            </div>

            <!-- Foto Selfie Guru -->
            <div class="surface-card p-4 shadow-1 border-round mb-3" v-if="selfieAttachment">
                <label class="block text-xs font-bold text-500 uppercase mb-2">Foto Presensi (Selfie Guru)</label>
                <div class="flex justify-content-center p-2">
                    <img :src="'/storage/' + selfieAttachment.file_path" class="max-w-full border-round shadow-2" style="max-height: 250px" alt="Selfie Guru" />
                </div>
            </div>

            <div class="surface-card shadow-1 border-round overflow-hidden">
                <div class="p-4 bg-primary-50 flex justify-content-between align-items-center">
                    <span class="font-bold text-900">Presensi Siswa</span>
                    <div class="flex gap-2">
                        <Tag severity="success" :value="hadirCount + ' Hadir'" />
                        <Tag severity="danger" :value="tidakHadirCount + ' Absen'" />
                    </div>
                </div>

                <ul class="list-none p-0 m-0">
                    <li 
                        v-for="att in agenda.attendances" 
                        :key="att.id" 
                        class="flex justify-content-between align-items-center p-3 border-top-1 border-50"
                    >
                        <div class="flex flex-column">
                            <span class="text-sm font-medium text-900">{{ att.student?.full_name }}</span>
                            <small class="text-500">{{ att.student?.nis }}</small>
                        </div>
                        <i 
                            :class="[
                                att.is_present ? 'pi pi-check-circle text-green-500' : 'pi pi-times-circle text-red-500',
                                'text-xl'
                            ]"
                        ></i>
                    </li>
                </ul>
            </div>

            <div class="flex gap-2 mt-4">
                <Button 
                    label="Edit Agenda" 
                    icon="pi pi-pencil" 
                    class="flex-1" 
                    outlined 
                    @click="router.get(route('guru.agenda.edit', agenda.id))"
                />
                </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'
import { ref, computed } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/id'

dayjs.locale('id')
const props = defineProps({
    agenda: Object
})
const loading = ref(false)
// Jam ke
const jamKe = computed(() => {
    const d = props.agenda.schedule?.details?.[0]
    return d ? `${d.start_slot}-${d.end_slot}` : '-'
})

const selfieAttachment = computed(() => {
    return props.agenda.attachments?.find(a => a.type === 'photo');
})

// rows presensi
const rows = props.agenda.attendances || []

const presensi = ref(
    Object.fromEntries(
        rows.map(a => [a.id, a.is_present])
    )
)

const hadirCount = computed(() => {
    return rows.filter(a => a.is_present).length
})

const tidakHadirCount = computed(() => {
    return rows.filter(a => !a.is_present).length
})

const formatDate = (val) =>
    val ? dayjs(val).format('dddd, DD MMM YYYY') : '-'
</script>

<style scoped>
/* Pengaturan font khusus mobile agar lebih nyaman dibaca */
.line-height-4 {
    line-height: 1.6rem;
}
</style>