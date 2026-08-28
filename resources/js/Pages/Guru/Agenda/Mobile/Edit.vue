<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, usePage, useForm } from '@inertiajs/vue3'
import axios from 'axios'

import MobileLayout from '@/Layouts/MobileLayout.vue' // Pastikan path benar
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import PresensiDialog from './PresensiDialog.vue'


const props = defineProps({
    agenda: Object
})
// Debug: Cek di console browser, apakah materi & tp_id muncul?
//console.log("Data Agenda dari Props:", props.agenda);

const tps = ref([])
const students = ref([])
const showPresensi = ref(false)
const page = usePage()

const form = useForm({
    tp_id: props.agenda.learning_objective_tp_id,
    materi: props.agenda.materi_pembelajaran,
    keterangan: props.agenda.keterangan || '',
    presensi: Object.fromEntries(
        (props.agenda.attendances || []).map(a => {
            let status = a.is_present ? 'hadir' : 'tidak_hadir';
            if (a.note && ['H', 'S', 'I', 'D', 'A', 'T'].includes(a.note)) {
                status = a.note;
            } else if (a.note && a.note !== 'hadir' && a.note !== 'tidak_hadir') {
                status = a.note;
            }
            return [a.student_id, status];
        })
    )
})

onMounted(async () => {
    const detailId = props.agenda.schedule_detail_id;
    
    try {
        const [tpRes, studentRes] = await Promise.all([
            axios.get(route('guru.agenda.tp-by-schedule', detailId)),
            axios.get(route('guru.agenda.students', detailId), {
                params: { date: props.agenda.date }
            }),
        ])
        tps.value = tpRes.data
        students.value = studentRes.data

        // 🔑 RE-SYNC: Jika form masih kosong saat mounted, isi manual dari props
        if (!form.materi) form.materi = props.agenda.materi_pembelajaran;
        if (!form.tp_id) form.tp_id = props.agenda.learning_objective_tp_id;
    } catch (e) {
        console.error("Gagal load data pendukung:", e)
    }
})

// Hapus watch agenda_date dan schedule_id karena field dikunci (Read Only)

const submitUpdate = (presensiReactive) => {
    form.presensi = JSON.parse(JSON.stringify(presensiReactive))
    
    form.put(route('guru.agenda.update', props.agenda.id), {
        onSuccess: () => {
            showPresensi.value = false
        }
    })
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}
</script>

<template>
    <MobileLayout title="Edit Agenda Guru">
        <div class="p-3 mb-8">
            <div class="flex align-items-center gap-3 mb-4">
                <Button 
                    icon="pi pi-arrow-left" 
                    text 
                    rounded 
                    severity="secondary" 
                    @click="router.get(route('guru.agenda.show', agenda.id))" 
                />
                <div>
                    <h2 class="text-xl font-bold m-0 text-900">Edit Materi & Presensi</h2>
                    <p class="text-500 text-sm m-0">Data jadwal tidak dapat diubah.</p>
                </div>
            </div>

            <div class="surface-100 p-3 border-round mb-4 border-left-3 border-primary shadow-1">
                <div class="flex justify-content-between align-items-start mb-2">
                    <span class="text-xs font-bold text-600 uppercase">Informasi Jadwal</span>
                    <Tag severity="secondary" :value="agenda.day?.toUpperCase()" />
                </div>
                <div class="text-lg font-bold text-900">{{ agenda.classroom?.name }}</div>
                <div class="text-700 font-medium">{{ agenda.subject?.name }}</div>
                <div class="text-600 text-sm mt-2 flex align-items-center gap-2">
                    <i class="pi pi-calendar text-xs"></i>
                    {{ formatDate(agenda.date) }} | 
                    <i class="pi pi-clock text-xs ml-1"></i>
                    Jam Ke-{{ agenda.schedule_detail?.start_slot }}
                </div>
            </div>

            <div class="flex flex-column gap-4">
                <div class="field">
                    <label class="block font-bold mb-2 text-900 text-sm">Tujuan Pembelajaran (TP)</label>
                    <Select
                        v-model="form.tp_id"
                        :options="tps"
                        optionLabel="label"
                        optionValue="id"
                        placeholder="Pilih TP"
                        fluid
                        filter
                    />
                </div>

                <div class="field">
                    <label class="block font-bold mb-2 text-900 text-sm">Ringkasan Materi</label>
                    <Textarea v-model="form.materi" rows="5" fluid placeholder="Apa yang diajarkan hari ini?" />
                </div>

                <div class="field">
                    <label class="block font-bold mb-2 text-900 text-sm">Keterangan (Opsional)</label>
                    <InputText v-model="form.keterangan" fluid placeholder="Catatan tambahan..." />
                </div>
            </div>

            <div class="flex flex-column gap-2 mt-6">
                <Button
                    label="Koreksi Presensi"
                    icon="pi pi-users"
                    severity="warning"
                    fluid
                    size="large"
                    @click="showPresensi = true"
                />
                
                <Button
                    label="Batal / Kembali"
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    fluid
                    @click="router.get(route('guru.agenda.show', agenda.id))"
                />
            </div>
        </div>

        <PresensiDialog
            v-model:visible="showPresensi"
            :students="students"
            :initial-data="form.presensi"
            @submit="submitUpdate"
        />
    </MobileLayout>
</template>