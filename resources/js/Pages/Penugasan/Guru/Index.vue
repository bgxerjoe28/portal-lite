<template>
    <AppLayout title="Penugasan Mata Pelajaran (PR)">
        <div class="card p-4 surface-card border-round-xl shadow-2">
            <!-- Header Bar -->
            <div class="flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center mb-4 gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-file-edit text-primary text-2xl"></i>
                        Penugasan Mata Pelajaran
                    </h2>
                    <p class="text-600 text-sm mt-1 mb-0">Kelola soal penugasan, batas waktu, rekap penyelesaian, dan intervensi nilai siswa.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button 
                        label="Daftar Nilai" 
                        icon="pi pi-table" 
                        severity="secondary" 
                        outlined 
                        class="font-bold border-round-lg"
                        @click="router.get(route('penilaian.grades.index'))" 
                    />
                    <Button 
                        label="Buat Tugas Baru" 
                        icon="pi pi-plus" 
                        severity="primary" 
                        raised 
                        class="font-bold border-round-lg"
                        @click="router.get(route('guru.assignments.create'))" 
                    />
                </div>
            </div>

            <!-- Filters -->
            <div class="grid mb-4 surface-100 p-3 border-round-xl">
                <div class="col-12 md:col-5 field mb-0">
                    <label class="block font-semibold mb-1 text-xs text-600 uppercase">Filter Kelas</label>
                    <Select 
                        v-model="filterForm.classroom_id" 
                        :options="filteredClassrooms" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Semua Kelas" 
                        showClear 
                        class="w-full"
                        @change="onClassroomFilterChange"
                    />
                </div>
                <div class="col-12 md:col-5 field mb-0">
                    <label class="block font-semibold mb-1 text-xs text-600 uppercase">Filter Mata Pelajaran</label>
                    <Select 
                        v-model="filterForm.subject_id" 
                        :options="filteredSubjects" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Semua Mapel" 
                        showClear 
                        class="w-full"
                        @change="onSubjectFilterChange"
                    />
                </div>
                <div class="col-12 md:col-2 flex align-items-end">
                    <Button 
                        label="Reset" 
                        icon="pi pi-refresh" 
                        severity="secondary" 
                        outlined 
                        class="w-full"
                        @click="resetFilter" 
                    />
                </div>
            </div>

            <!-- Grid List Tugas (Menggantikan DataTable) -->
            <DataView v-bind="$pagination({ label: 'assignments' })" :value="assignments" layout="grid">
                <template #empty>
                    <div class="text-center p-5 text-500 w-full">
                        <i class="pi pi-inbox text-4xl block mb-2 opacity-50"></i>
                        Belum ada data penugasan mata pelajaran.
                    </div>
                </template>
                <template #grid="slotProps">
                    <div class="grid grid-nogutter w-full">
                        <div 
                            v-for="(item, index) in slotProps.items" 
                            :key="item.id" 
                            class="col-12 md:col-6 p-2"
                        >
                            <div class="p-3 surface-card border-round-xl shadow-1 border border-200 flex flex-column justify-content-between h-full transition-all hover:shadow-3"
                                 :class="item.is_published ? 'border-left-4 border-green-500 bg-green-50/10' : 'border-left-4 border-slate-400 bg-slate-50/30'"
                            >
                                <div>
                                    <div class="flex justify-content-between align-items-start mb-2 gap-2">
                                        <div class="flex flex-column gap-1">
                                            <span class="text-xs font-bold text-primary uppercase">{{ item.subject?.name || 'Mata Pelajaran' }}</span>
                                            <span class="text-[10px] font-semibold text-600">Kelas: {{ item.classroom?.name || '-' }}</span>
                                        </div>
                                        <div class="flex align-items-center gap-2">
                                            <span class="text-[10px] font-bold" :class="item.is_published ? 'text-green-600' : 'text-slate-500'">
                                                {{ item.is_published ? 'Aktif' : 'Draft' }}
                                            </span>
                                            <ToggleSwitch 
                                                :modelValue="item.is_published" 
                                                @change="togglePublish(item.id)" 
                                            />
                                        </div>
                                    </div>

                                    <h3 class="text-lg font-bold text-900 m-0 mb-1 leading-tight line-clamp-2">
                                        {{ item.title }}
                                    </h3>

                                    <div class="flex flex-column gap-1 text-xs text-600 mb-2">
                                        <span class="flex align-items-center gap-1">
                                            <i class="pi pi-clock text-red-500"></i>
                                            <strong>Due:</strong> {{ formatDate(item.due_at) }}
                                        </span>
                                    </div>

                                    <button 
                                        type="button" 
                                        @click="toggleExpand(item.id)" 
                                        class="p-0 border-none bg-transparent text-primary font-bold text-xs cursor-pointer flex align-items-center gap-1 my-2 hover:underline"
                                    >
                                        <i :class="expandedTasks[item.id] ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"></i>
                                        <span>{{ expandedTasks[item.id] ? 'Sembunyikan Detail' : 'Lihat Detail Lengkap' }}</span>
                                    </button>

                                    <!-- Collapsible Detail Area -->
                                    <div v-show="expandedTasks[item.id]" class="mt-2 pt-2 border-top-1 border-100 transition-all">
                                        <div class="flex flex-column gap-1 text-xs text-600 mb-2">
                                            <span><i class="pi pi-calendar-plus mr-1 text-green-600"></i><strong>Start:</strong> {{ formatDate(item.start_at) }}</span>
                                            <span><i class="pi pi-list mr-1 text-blue-500"></i><strong>Kategori:</strong> {{ item.grading_component?.name || 'Umum' }}</span>
                                            <span><i class="pi pi-question-circle mr-1 text-purple-500"></i><strong>Jumlah:</strong> {{ item.questions_count || 0 }} Soal</span>
                                        </div>
                                        <div v-if="item.description" class="p-2 surface-50 border-round-lg border border-300 text-700 text-xs whitespace-pre-wrap mb-2">
                                            <span class="block font-bold text-900 text-[10px] mb-1 uppercase"><i class="pi pi-info-circle mr-1"></i>Deskripsi:</span>
                                            {{ item.description }}
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-2 border-top-1 border-100 flex flex-wrap gap-2 justify-content-end mt-2">
                                    <template v-if="item.type === 'performance'">
                                        <Button 
                                            icon="pi pi-chart-bar" 
                                            label="Nilai" 
                                            severity="help" 
                                            size="small"
                                            class="py-1 px-2 text-xs font-bold"
                                            v-tooltip="'Penilaian Kinerja / Praktik'"
                                            @click="router.get(route('guru.assignments.performance_grading', item.id))" 
                                        />
                                    </template>
                                    <template v-else>
                                        <Button 
                                            icon="pi pi-users" 
                                            label="Hasil" 
                                            severity="success" 
                                            size="small"
                                            class="py-1 px-2 text-xs font-bold"
                                            v-tooltip="'Rekap Penyelesaian & Penilaian'"
                                            @click="router.get(route('guru.assignments.submissions', item.id))" 
                                        />
                                    </template>
                                    <Button 
                                        icon="pi pi-pencil" 
                                        severity="warn" 
                                        size="small"
                                        outlined
                                        class="py-1 px-2"
                                        v-tooltip="'Edit Soal/Tugas'"
                                        @click="router.get(route('guru.assignments.edit', item.id))" 
                                    />
                                    <Button 
                                        icon="pi pi-copy" 
                                        severity="secondary" 
                                        size="small"
                                        outlined
                                        class="py-1 px-2"
                                        v-tooltip="'Duplikat / Salin Tugas ke Kelas Lain'"
                                        @click="router.get(route('guru.assignments.duplicate', item.id))" 
                                    />
                                    <Button 
                                        icon="pi pi-trash" 
                                        severity="danger" 
                                        size="small"
                                        outlined
                                        class="py-1 px-2"
                                        v-tooltip="'Hapus Tugas'"
                                        @click="confirmDelete(item)" 
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </DataView>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataView from 'primevue/dataview';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';

const props = defineProps({
    assignments: Array,
    classrooms: Array,
    subjects: Array,
    schedules: Array,
    filters: Object,
});

const confirm = useConfirm();

const expandedTasks = ref({});
const toggleExpand = (id) => {
    expandedTasks.value[id] = !expandedTasks.value[id];
};

const filterForm = ref({
    classroom_id: props.filters?.classroom_id ? Number(props.filters.classroom_id) : null,
    subject_id: props.filters?.subject_id ? Number(props.filters.subject_id) : null,
});

// Dependent Classrooms Filter
const filteredClassrooms = computed(() => {
    if (!props.schedules || props.schedules.length === 0) return props.classrooms || [];
    if (!filterForm.value.subject_id) {
        const classMap = new Map();
        props.schedules.forEach(s => {
            if (s.classroom) classMap.set(s.classroom.id, s.classroom);
        });
        return Array.from(classMap.values());
    }
    const classMap = new Map();
    props.schedules.filter(s => s.subject_id === filterForm.value.subject_id).forEach(s => {
        if (s.classroom) classMap.set(s.classroom.id, s.classroom);
    });
    return Array.from(classMap.values());
});

// Dependent Subjects Filter
const filteredSubjects = computed(() => {
    if (!props.schedules || props.schedules.length === 0) return props.subjects || [];
    if (!filterForm.value.classroom_id) {
        const subMap = new Map();
        props.schedules.forEach(s => {
            if (s.subject) subMap.set(s.subject.id, s.subject);
        });
        return Array.from(subMap.values());
    }
    const subMap = new Map();
    props.schedules.filter(s => s.classroom_id === filterForm.value.classroom_id).forEach(s => {
        if (s.subject) subMap.set(s.subject.id, s.subject);
    });
    return Array.from(subMap.values());
});

const onClassroomFilterChange = () => {
    if (filterForm.value.subject_id) {
        const isValid = filteredSubjects.value.some(s => s.id === filterForm.value.subject_id);
        if (!isValid) {
            filterForm.value.subject_id = null;
        }
    }
    applyFilter();
};

const onSubjectFilterChange = () => {
    if (filterForm.value.classroom_id) {
        const isValid = filteredClassrooms.value.some(c => c.id === filterForm.value.classroom_id);
        if (!isValid) {
            filterForm.value.classroom_id = null;
        }
    }
    applyFilter();
};

const applyFilter = () => {
    router.get(route('guru.assignments.index'), filterForm.value, { preserveState: true, replace: true });
};

const resetFilter = () => {
    filterForm.value.classroom_id = null;
    filterForm.value.subject_id = null;
    applyFilter();
};

const togglePublish = (id) => {
    router.post(route('guru.assignments.publish', id), {}, { preserveScroll: true });
};

const confirmDelete = (item) => {
    confirm.require({
        message: `Apakah Anda yakin ingin menghapus tugas "${item.title}"?`,
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('guru.assignments.destroy', item.id));
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
