<template>
    <AppLayout>
        <div class="card">
            <div class="flex align-items-center justify-content-between mb-4">
                <div class="flex align-items-center">
                    <Link :href="route('admin.schedules.index')">
                        <Button icon="pi pi-arrow-left" text rounded severity="secondary" class="mr-2" />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold m-0">Plotting Guru: {{ classroom.name }}</h2>
                        <span class="text-500">Tahun Ajaran: {{ activeYear.name }}</span>
                    </div>
                </div>
                <Button label="Simpan Perubahan" icon="pi pi-save" @click="submitPlotting" :loading="form.processing" />
            </div>

            <div v-if="!mappings?.length" class="p-4 bg-yellow-50 text-yellow-700 border-round">
                <i class="pi pi-info-circle mr-2"></i> Belum ada mapel untuk tingkat ini.
            </div>

            <div v-else class="border border-300 border-round overflow-hidden">
                <DataTable 
                    :value="mappings"
                    rowGroupMode="subheader"
                    groupRowsBy="group.name"
                    sortMode="single"
                    sortField="group.name"
                    :sortOrder="1"
                    showGridlines
                    stripedRows
                    class="p-datatable-sm text-sm"
                    scrollable
                    scrollHeight="65vh"
                >
                    <template #groupheader="slotProps">
                        <div class="flex align-items-center gap-2 font-bold text-900 bg-bluegray-50 py-2 px-3">
                            <i class="pi pi-bookmark text-primary"></i>
                            <span>{{ slotProps.data.group?.name || 'Lainnya' }}</span>
                        </div>
                    </template>

                    <Column field="subject.code" header="Kode" style="width: 80px; min-width: 80px; text-align: center;">
                        <template #body="{ data }">
                            <Tag :value="data.subject.code" severity="secondary" class="text-xs" />
                        </template>
                    </Column>

                    <Column field="subject.name" header="Mata Pelajaran" style="min-width: 220px;">
                        <template #body="{ data }">
                            <div class="white-space-normal font-medium py-1">
                                {{ data.subject.name }}
                                <Tag
                                    v-if="data.subject.is_religion"
                                    value="Agama"
                                    severity="warning"
                                    class="ml-2 text-xs"
                                />
                            </div>
                        </template>
                    </Column>

                    <Column header="Guru Pengampu & JP" style="min-width: 420px;">
                        <template #body="{ data }">
                            <!-- MAPEL UMUM -->
                            <div v-if="!data.subject.is_religion" class="flex align-items-center gap-2">
                                <div class="flex-grow-1" style="max-width: 320px;">
                                    <Select
                                        v-model="form.schedules[sKey(data.subject_id, 0)].teacher_id"
                                        :options="teachers"
                                        optionLabel="name"
                                        optionValue="id"
                                        filter
                                        placeholder="-- Pilih Guru --"
                                        class="w-full p-inputtext-sm"
                                        showClear
                                        appendTo="body"
                                    />
                                </div>

                                <div style="width: 110px;">
                                    <div class="p-inputgroup">
                                        <InputNumber
                                            v-model="form.schedules[sKey(data.subject_id, 0)].quota"
                                            :min="0"
                                            :max="10"
                                            placeholder="0"
                                            inputClass="text-center font-bold p-inputtext-sm"
                                            v-tooltip.top="'Jumlah Jam Pelajaran'"
                                        />
                                        <span class="p-inputgroup-addon text-xs px-1">JP</span>
                                    </div>
                                </div>
                            </div>

                            <!-- MAPEL AGAMA (PARALEL PER AGAMA) -->
                            <div v-else class="flex flex-column gap-2">
                                <div
                                    v-for="religion in religions"
                                    :key="religion.id"
                                    class="flex align-items-center gap-2"
                                >
                                    <span class="w-10rem font-medium">
                                        {{ religion.name }}
                                    </span>

                                    <div class="flex-grow-1" style="max-width: 320px;">
                                        <Select
                                            v-model="form.schedules[sKey(data.subject_id, religion.id)].teacher_id"
                                            :options="teachers"
                                            optionLabel="name"
                                            optionValue="id"
                                            filter
                                            placeholder="Pilih Guru"
                                            class="w-full p-inputtext-sm"
                                            showClear
                                            appendTo="body"
                                        />
                                    </div>

                                    <div style="width: 110px;">
                                        <div class="p-inputgroup">
                                            <InputNumber
                                                v-model="form.schedules[sKey(data.subject_id, religion.id)].quota"
                                                :min="0"
                                                :max="10"
                                                placeholder="0"
                                                inputClass="text-center font-bold p-inputtext-sm"
                                                v-tooltip.top="'Jumlah Jam Pelajaran'"
                                            />
                                            <span class="p-inputgroup-addon text-xs px-1">JP</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <div class="flex justify-content-end mt-4">
                <Button label="Simpan Perubahan" icon="pi pi-save" @click="submitPlotting" :loading="form.processing" size="large" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

import AppLayout from '@/Layouts/AppLayout.vue'
import Select from 'primevue/select'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import InputNumber from 'primevue/inputnumber'

const props = defineProps({
    classroom: Object,
    activeYear: Object,
    mappings: Array,
    teachers: Array,
    religions: Array,
    existingSchedules: Object
})

/**
 * helper key: subjectId + religionId (0 = umum)
 */
const sKey = (subjectId, religionId = 0) => `${subjectId}_${religionId}`

/**
 * Build initial schedules ONCE (no onMounted, no mutation during render)
 */
const initialSchedules = computed(() => {
    const out = {}

    ;(props.mappings || []).forEach(map => {
        if (map.subject?.is_religion) {
            ;(props.religions || []).forEach(r => {
                const key = sKey(map.subject_id, r.id)
                const existing = props.existingSchedules?.[key]

                out[key] = {
                    subject_id: map.subject_id,
                    religion_id: r.id,
                    teacher_id: existing?.teacher_id ?? null,
                    quota: existing?.quota ?? 1
                }
            })
        } else {
            const key = sKey(map.subject_id, 0)
            const existing = props.existingSchedules?.[key]

            out[key] = {
                subject_id: map.subject_id,
                religion_id: null,
                teacher_id: existing?.teacher_id ?? null,
                quota: existing?.quota ?? 2
            }
        }
    })

    return out
})

/**
 * IMPORTANT:
 * Inertia useForm should receive a plain object once.
 * We take computed value at setup-time (props are already available in Inertia render).
 */
const form = useForm({
    schedules: initialSchedules.value
})

const submitPlotting = () => {
    form.put(route('admin.schedules.update', props.classroom.id))
}
</script>