<template>
    <SiswaLayout title="Tugas Siswa">
        <div class="card p-4 surface-card border-round-xl shadow-2">
            <!-- Header Bar -->
            <div class="flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center mb-4 gap-3 border-bottom-1 border-200 pb-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-file-edit text-primary text-2xl"></i>
                        Daftar Tugas Siswa
                    </h2>
                    <p class="text-500 text-sm m-0 mt-1">Daftar penugasan mata pelajaran yang dibebankan kepada Anda.</p>
                </div>
                <Button 
                    label="Kembali ke Dashboard" 
                    icon="pi pi-arrow-left" 
                    severity="secondary" 
                    outlined 
                    @click="router.get(route('student.dashboard'))" 
                />
            </div>

            <!-- Filter Status Bar -->
            <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
                <Button 
                    :label="'Semua (' + assignments.length + ')'" 
                    :severity="activeFilter === 'all' ? 'primary' : 'secondary'" 
                    :outlined="activeFilter !== 'all'"
                    size="small" 
                    @click="activeFilter = 'all'" 
                />
                <Button 
                    :label="'Belum Dikerjakan (' + pendingCountCalculated + ')'" 
                    :severity="activeFilter === 'pending' ? 'warn' : 'secondary'" 
                    :outlined="activeFilter !== 'pending'"
                    size="small" 
                    @click="activeFilter = 'pending'" 
                />
                <Button 
                    v-if="lockedCountCalculated > 0"
                    :label="'Terkunci (' + lockedCountCalculated + ')'" 
                    :severity="activeFilter === 'locked' ? 'help' : 'secondary'" 
                    :outlined="activeFilter !== 'locked'"
                    size="small" 
                    @click="activeFilter = 'locked'" 
                />
                <Button 
                    :label="'Sudah Dikumpulkan (' + submittedCountCalculated + ')'" 
                    :severity="activeFilter === 'submitted' ? 'success' : 'secondary'" 
                    :outlined="activeFilter !== 'submitted'"
                    size="small" 
                    @click="activeFilter = 'submitted'" 
                />
            </div>

            <!-- Grid List Tugas -->
            <div v-if="filteredAssignments.length > 0" class="grid">
                <div 
                    v-for="item in filteredAssignments" 
                    :key="item.id" 
                    class="col-12 md:col-6"
                >
                    <div 
                        class="p-3 surface-card border-round-xl shadow-1 border border-200 flex flex-column justify-content-between h-full transition-all hover:shadow-3"
                        :class="{
                            'border-left-4 border-gray-400 bg-gray-50/50 opacity-80': item.is_locked,
                            'border-left-4 border-blue-500 bg-blue-50/20': item.is_draft && !item.is_locked,
                            'border-left-4 border-red-500 bg-red-50/20': item.is_editable && !item.is_locked && !item.is_draft,
                            'border-left-4 border-orange-500 bg-orange-50/20': !item.has_submitted && !item.is_editable && !item.is_locked && !item.is_draft,
                            'border-left-4 border-green-500 bg-green-50/20': item.has_submitted && !item.is_editable && !item.is_locked
                        }"
                    >
                        <div>
                            <div class="flex justify-content-between align-items-start mb-2 gap-2">
                                <span class="text-xs font-bold text-primary uppercase">{{ item.subject_name || 'Mata Pelajaran' }}</span>
                                <Tag 
                                    v-if="item.is_locked" 
                                    value="Terkunci" 
                                    severity="secondary" 
                                    icon="pi pi-lock"
                                    class="font-bold text-[10px] px-2 py-1" 
                                />
                                <Tag 
                                    v-else-if="item.is_draft" 
                                    value="Draf Tersimpan" 
                                    severity="info" 
                                    icon="pi pi-file-edit"
                                    class="font-bold text-[10px] px-2 py-1" 
                                />
                                <Tag 
                                    v-else-if="!item.has_submitted" 
                                    value="Belum Dikerjakan" 
                                    severity="warn" 
                                    class="font-bold text-[10px] px-2 py-1" 
                                />
                                <Tag 
                                    v-else-if="item.is_editable" 
                                    value="Perlu Perbaikan" 
                                    severity="danger" 
                                    icon="pi pi-exclamation-triangle"
                                    class="font-bold text-[10px] px-2 py-1" 
                                />
                                <Tag 
                                    v-else-if="item.is_grades_published && item.total_score !== null" 
                                    :value="'Nilai: ' + item.total_score + '/100'" 
                                    severity="success" 
                                    class="font-bold text-[10px] px-2 py-1" 
                                />
                                <Tag 
                                    v-else 
                                    value="Sudah Dikumpulkan" 
                                    severity="success" 
                                    class="font-bold text-[10px] px-2 py-1" 
                                />
                            </div>

                            <h3 class="text-lg font-bold text-900 m-0 mb-1 leading-tight line-clamp-2">
                                {{ item.title }}
                            </h3>

                            <div class="flex flex-column gap-1 text-xs text-600 mb-2">
                                <span class="flex align-items-center gap-1">
                                    <i class="pi pi-calendar text-red-500"></i>
                                    <strong>Due:</strong> {{ item.due_at || '-' }}
                                </span>
                                <span v-if="item.is_locked" class="flex align-items-center gap-1 text-amber-700 font-bold bg-amber-50 p-1 border-round w-fit">
                                    <i class="pi pi-lock"></i>
                                    Prasyarat: {{ item.prerequisite_title }}
                                </span>
                            </div>

                            <button 
                                type="button" 
                                @click="toggleExpand(item.id)" 
                                class="p-0 border-none bg-transparent text-primary font-bold text-xs cursor-pointer flex align-items-center gap-1 my-2 hover:underline"
                            >
                                <i :class="expandedTasks[item.id] ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"></i>
                                <span>{{ expandedTasks[item.id] ? 'Sembunyikan Detail' : 'Lihat Detail' }}</span>
                            </button>

                            <!-- Collapsible Detail Area -->
                            <div v-show="expandedTasks[item.id]" class="mt-2 pt-2 border-top-1 border-100 transition-all">
                                <div class="flex flex-column gap-1 text-xs text-600 mb-3">
                                    <span><i class="pi pi-user mr-1 text-primary"></i>Guru: <strong>{{ item.teacher_name || '-' }}</strong></span>
                                </div>
                                <div v-if="item.description" class="p-2 surface-50 border-round-lg border border-300 text-700 text-xs whitespace-pre-wrap">
                                    <span class="block font-bold text-900 text-[10px] mb-1 uppercase"><i class="pi pi-info-circle mr-1"></i>Petunjuk:</span>
                                    {{ item.description }}
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 border-top-1 border-100 flex justify-content-between align-items-center mt-2">
                            <span class="text-xs text-500" v-if="item.is_draft && item.last_saved_at">
                                Draf: {{ item.last_saved_at }}
                            </span>
                            <span class="text-xs text-500" v-else-if="item.has_submitted && item.submitted_at">
                                Dikumpulkan: {{ item.submitted_at }}
                            </span>
                            <span class="text-xs text-600" v-else>
                                Aksi:
                            </span>

                            <Button 
                                v-if="item.is_locked"
                                label="Terkunci" 
                                icon="pi pi-lock" 
                                severity="secondary" 
                                size="small" 
                                disabled
                                class="font-bold py-1 px-2 text-xs opacity-60"
                            />
                            <Button 
                                v-else-if="item.is_draft"
                                label="Lanjutkan Pengerjaan" 
                                icon="pi pi-pencil" 
                                severity="info" 
                                size="small" 
                                raised 
                                class="font-bold py-1 px-2 text-xs"
                                @click="router.get(route('student.assignments.show', item.id))" 
                            />
                            <Button 
                                v-else
                                :label="item.has_submitted ? 'Lihat Tugas' : 'Kerjakan'" 
                                :icon="item.has_submitted ? 'pi pi-eye' : 'pi pi-file-edit'" 
                                :severity="item.has_submitted ? 'secondary' : 'warning'" 
                                size="small" 
                                raised 
                                class="font-bold py-1 px-2 text-xs"
                                @click="router.get(route('student.assignments.show', item.id))" 
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center p-5 surface-100 border-round-xl border-dashed border-2 text-500">
                <i class="pi pi-inbox text-500 text-4xl block mb-2 opacity-60"></i>
                <h3 class="text-900 font-bold m-0">Tidak Ada Tugas</h3>
                <p class="text-600 text-sm mt-1">Belum ada tugas yang tersedia sesuai filter yang Anda pilih.</p>
            </div>
        </div>
    </SiswaLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
    assignments: Array,
    pendingCount: Number,
});

const activeFilter = ref('all');

const pendingCountCalculated = computed(() => {
    if (!props.assignments) return 0;
    return props.assignments.filter(a => !a.has_submitted && !a.is_locked).length;
});

const submittedCountCalculated = computed(() => {
    if (!props.assignments) return 0;
    return props.assignments.filter(a => a.has_submitted).length;
});

const lockedCountCalculated = computed(() => {
    if (!props.assignments) return 0;
    return props.assignments.filter(a => a.is_locked).length;
});

const filteredAssignments = computed(() => {
    if (!props.assignments) return [];
    if (activeFilter.value === 'pending') {
        return props.assignments.filter(a => !a.has_submitted && !a.is_locked);
    }
    if (activeFilter.value === 'locked') {
        return props.assignments.filter(a => a.is_locked);
    }
    if (activeFilter.value === 'submitted') {
        return props.assignments.filter(a => a.has_submitted);
    }
    return props.assignments;
});

const expandedTasks = ref({});
const toggleExpand = (id) => {
    expandedTasks.value[id] = !expandedTasks.value[id];
};
</script>

