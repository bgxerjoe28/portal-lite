<template>
    <MobileLayout title="Atur Jadwal"> 
        <div class="max-w-3xl mx-auto px-2 py-3">
            <div class="surface-card shadow-3 border-round overflow-hidden">

                <!-- CARD HEADER -->
                <div class="surface-50 px-5 py-4 border-bottom-1 border-200">
                    <Link
                        :href="route('guru.teaching-schedules.index')"
                        class="text-sm text-500 no-underline hover:text-primary flex align-items-center gap-2"
                    >
                        <i class="pi pi-arrow-left"></i> Kembali
                    </Link>

                    <h2 class="text-2xl font-bold mt-3 mb-1">
                        {{ schedule.subject.name }}
                    </h2>

                    <div class="flex align-items-center gap-2 text-600">
                        <i class="pi pi-users"></i>
                        {{ schedule.classroom.name }}
                    </div>

                    <div class="mt-3">
                        <Tag
                            :value="'Target Beban: ' + schedule.quota + ' JP'"
                            severity="info"
                            class="text-base"
                        />
                    </div>
                </div>

                <!-- CARD BODY -->
                <div class="p-5">

                    <!-- EMPTY STATE -->
                    <div
                        v-if="details.length === 0"
                        class="text-center p-6 border-1 border-dashed border-300 border-round text-500"
                    >
                        <i class="pi pi-calendar-times text-4xl mb-3 block"></i>
                        Belum ada jadwal.<br />
                        Klik <b>Tambah Hari / Sesi</b> untuk mulai.
                    </div>

                    <!-- SESSION CARDS -->
                    <div
                        v-for="(row, i) in details"
                        :key="row.uid"
                        class="surface-card border-1 border-200 border-round p-4 mb-4 relative"
                    >
                        <!-- DELETE -->
                        <Button
                            icon="pi pi-trash"
                            text
                            severity="danger"
                            rounded
                            class="absolute top-0 right-0 m-2"
                            v-tooltip.top="'Hapus sesi'"
                            @click="removeRow(row.uid)"
                        />

                        <!-- SESSION TITLE -->
                        <div class="flex justify-between align-items-center mb-3">
                            <div class="font-bold text-primary">
                                Sesi {{ i + 1 }}
                            </div>

                            <Tag
                                :value="calcJP(row) + ' JP'"
                                severity="success"
                                class="text-sm"
                            />
                        </div>

                        <!-- FORM GRID -->
                        <div class="grid">
                            <div class="col-12 md:col-4">
                                <label class="block text-sm font-bold mb-1">Hari</label>
                                <Select
                                    v-model="row.day"
                                    :options="days"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full"
                                />
                            </div>

                            <div class="col-12 sm:col-6 md:col-3">
                                <label class="block text-sm font-bold mb-1">Jam Ke</label>
                                <InputNumber
                                    v-model="row.start_slot"
                                    :min="1"
                                    :max="15"
                                    showButtons
                                    class="w-full"
                                />
                            </div>

                            <div class="col-12 sm:col-6 md:col-3">
                                <label class="block text-sm font-bold mb-1">Sampai</label>
                                <InputNumber
                                    v-model="row.end_slot"
                                    :min="1"
                                    :max="15"
                                    showButtons
                                    class="w-full"
                                />
                            </div>

                            <div class="col-12 md:col-2 text-center">
                                <div class="text-xs text-500 mb-1">Total</div>
                                <div class="text-xl font-bold text-primary">
                                    {{ calcJP(row) }} JP
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ADD BUTTON -->
                    <Button
                        label="Tambah Hari / Sesi"
                        icon="pi pi-plus"
                        severity="secondary"
                        outlined
                        class="w-full mt-2"
                        @click="addRow"
                    />
                </div>

                <!-- CARD FOOTER -->
                <div class="surface-100 px-5 py-4 border-top-1 border-200 flex justify-between align-items-center">
                    <div>
                        <div class="text-sm text-600 mb-1">Total Direncanakan</div>
                        <div class="text-2xl font-bold" :class="statusColor">
                            {{ totalJP }} / {{ schedule.quota }} JP
                        </div>
                    </div>

                    <Button
                        label="Simpan Jadwal"
                        icon="pi pi-save"
                        size="large"
                        :loading="form.processing"
                        :disabled="totalJP > schedule.quota"
                        :severity="totalJP === schedule.quota ? 'primary' : 'warning'"
                        @click="save"
                    />
                </div>

            </div>
        </div>
    </MobileLayout>
</template>


<script setup>
import { computed, ref, onMounted } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

/* ================= UTILS ================= */
const generateUUID = () => {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }
    // Fallback for non-secure HTTP contexts
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

/* ================= PROPS ================= */
const props = defineProps({
    schedule: Object,
    existingDetails: Array
})

const toast = useToast()

/* ================= OPTIONS ================= */
const days = [
    { label: 'Senin', value: 'senin' },
    { label: 'Selasa', value: 'selasa' },
    { label: 'Rabu', value: 'rabu' },
    { label: 'Kamis', value: 'kamis' },
    { label: 'Jumat', value: 'jumat' },
    { label: 'Sabtu', value: 'sabtu' }
]

/* ================= SAFE LOCAL STATE ================= */
// ⛔ JANGAN PAKAI props.existingDetails LANGSUNG
const details = ref(
    props.existingDetails.length
        ? props.existingDetails.map(d => ({
              uid: generateUUID(),
              day: d.day,
              start_slot: d.start_slot,
              end_slot: d.end_slot
          }))
        : [
              {
                  uid: generateUUID(),
                  day: 'senin',
                  start_slot: 1,
                  end_slot: 2
              }
          ]
)

/* ================= FORM ================= */
const form = useForm({
    details: details.value
})

/* ================= HELPERS ================= */
const calcJP = row => {
    const v = row.end_slot - row.start_slot + 1
    return v > 0 ? v : 0
}

const totalJP = computed(() =>
    details.value.reduce((sum, r) => sum + calcJP(r), 0)
)

const statusColor = computed(() => {
    if (totalJP.value > props.schedule.quota) return 'text-red-600'
    if (totalJP.value === props.schedule.quota) return 'text-green-600'
    return 'text-orange-500'
})

/* ================= ACTIONS ================= */
const addRow = () => {
    details.value.push({
        uid: generateUUID(),
        day: 'senin',
        start_slot: 1,
        end_slot: 2
    })
}

const removeRow = uid => {
    details.value = details.value.filter(r => r.uid !== uid)
    form.details = details.value
}

const save = () => {
    form.details = details.value

    form.put(route('guru.teaching-schedules.update', props.schedule.id), {
        preserveScroll: true,
        onSuccess: page => {
            const flash = page.props.flash || {}
            
        }
    })
}
</script>
