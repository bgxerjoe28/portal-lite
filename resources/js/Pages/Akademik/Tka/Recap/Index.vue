<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeacherTabMenu from '@/Components/TeacherTabMenu.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    classrooms: Array,
    selected_classroom_id: [Number, String],
    students: Array,
    summary: Object,
    active_year: Object,
});

const dt = ref(null);
const filterStatus = ref('all');
const filters = ref({
    global: { value: null, matchMode: 'contains' },
});

const selectedClass = ref(props.selected_classroom_id || (props.classrooms && props.classrooms.length > 0 ? props.classrooms[0].id : 'all'));

const classOptions = computed(() => {
    const list = props.classrooms ? props.classrooms.map(c => ({ id: c.id, name: c.name })) : [];
    return [
        { id: 'all', name: 'Semua Kelas XII' },
        ...list
    ];
});

const onClassroomChange = () => {
    router.get(route('akademik.tka-recap.index'), {
        classroom_id: selectedClass.value
    }, {
        preserveScroll: true,
        preserveState: true
    });
};

const filteredStudents = computed(() => {
    if (!props.students) return [];
    if (filterStatus.value === 'complete') {
        return props.students.filter(s => s.is_complete);
    }
    if (filterStatus.value === 'incomplete') {
        return props.students.filter(s => s.chosen_count === 1);
    }
    if (filterStatus.value === 'not_chosen') {
        return props.students.filter(s => s.chosen_count === 0);
    }
    if (filterStatus.value === 'chosen') {
        return props.students.filter(s => s.has_chosen);
    }
    return props.students;
});

const exportCSV = () => {
    if (dt.value) {
        dt.value.exportCSV();
    }
};
</script>

<template>
    <AppLayout title="Rekap Pilihan TKA Siswa">
        <TeacherTabMenu />
        <!-- ===== CARD HEADER ===== -->
        <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
            <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3">
                <div>
                    <div class="flex align-items-center gap-2 mb-1">
                        <i class="pi pi-list text-primary text-xl"></i>
                        <h2 class="text-2xl font-bold text-900 m-0">Rekap Daftar Siswa TKA</h2>
                    </div>
                    <span class="text-600 text-sm">
                        Daftar siswa Kelas XII beserta mata pelajaran TKA yang dipilih pada Tahun Ajaran <strong class="text-900">{{ active_year?.name || '-' }}</strong>.
                    </span>
                </div>

                <div class="flex flex-wrap gap-2 align-items-center">
                    <Dropdown 
                        v-model="selectedClass" 
                        :options="classOptions" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Pilih Kelas XII" 
                        class="w-full md:w-15rem"
                        @change="onClassroomChange" 
                    />
                    <Button 
                        v-if="!$page.props.auth?.user?.roles?.some(r => r.name === 'guru') || $page.props.auth?.user?.roles?.some(r => r.name === 'admin')"
                        label="Kembali ke Master TKA" 
                        icon="pi pi-arrow-left" 
                        severity="secondary" 
                        text 
                        @click="router.get(route('akademik.tka-subjects.index'))" 
                    />
                </div>
            </div>
        </div>

        <!-- ===== KPI CARDS ===== -->
        <div class="grid mb-4">
            <div class="col-12 md:col-6 lg:col-3">
                <div class="surface-card p-3 border-round-lg shadow-1 flex justify-content-between align-items-center">
                    <div>
                        <span class="block text-500 font-medium mb-1">Total Siswa</span>
                        <div class="text-900 font-bold text-2xl">{{ summary?.total_students || 0 }} Siswa</div>
                    </div>
                    <div class="flex align-items-center justify-content-center bg-blue-100 border-round" style="width: 2.5rem; height: 2.5rem">
                        <i class="pi pi-users text-blue-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 md:col-6 lg:col-3">
                <div class="surface-card p-3 border-round-lg shadow-1 flex justify-content-between align-items-center border-left-3 border-green-500">
                    <div>
                        <span class="block text-500 font-medium mb-1">Lengkap (2 Mapel)</span>
                        <div class="text-green-700 font-bold text-2xl">{{ summary?.complete_count || 0 }} Siswa</div>
                    </div>
                    <div class="flex align-items-center justify-content-center bg-green-100 border-round" style="width: 2.5rem; height: 2.5rem">
                        <i class="pi pi-check-circle text-green-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 md:col-6 lg:col-3">
                <div class="surface-card p-3 border-round-lg shadow-1 flex justify-content-between align-items-center border-left-3 border-yellow-500">
                    <div>
                        <span class="block text-500 font-medium mb-1">Kurang (1 Mapel)</span>
                        <div class="text-yellow-700 font-bold text-2xl">{{ summary?.incomplete_count || 0 }} Siswa</div>
                    </div>
                    <div class="flex align-items-center justify-content-center bg-yellow-100 border-round" style="width: 2.5rem; height: 2.5rem">
                        <i class="pi pi-exclamation-triangle text-yellow-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 md:col-6 lg:col-3">
                <div class="surface-card p-3 border-round-lg shadow-1 flex justify-content-between align-items-center border-left-3 border-red-500">
                    <div>
                        <span class="block text-500 font-medium mb-1">Belum Memilih</span>
                        <div class="text-red-700 font-bold text-2xl">{{ summary?.not_chosen_count || 0 }} Siswa</div>
                    </div>
                    <div class="flex align-items-center justify-content-center bg-red-100 border-round" style="width: 2.5rem; height: 2.5rem">
                        <i class="pi pi-times-circle text-red-500 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== MAPEL BREAKDOWN CHIPS ===== -->
        <div class="surface-card p-3 mb-4 border-round-lg shadow-1" v-if="summary?.subject_counts && summary.subject_counts.length > 0">
            <div class="flex flex-wrap align-items-center gap-2">
                <span class="font-semibold text-700 mr-2 text-sm">
                    <i class="pi pi-chart-pie mr-1 text-primary"></i> Sebaran Pilihan Mapel:
                </span>
                <Tag 
                    v-for="sub in summary.subject_counts" 
                    :key="sub.id" 
                    :value="`${sub.name}: ${sub.count} siswa`" 
                    severity="info" 
                    class="text-sm px-3 py-1 font-semibold" 
                />
            </div>
        </div>

        <!-- ===== CARD TABLE ===== -->
        <div class="surface-card p-4 border-round-lg shadow-1">
            <DataTable v-bind="$pagination({ label: 'filteredstudents' })" 
                ref="dt"
                :value="filteredStudents" 
                stripedRows 
                showGridlines 
                tableStyle="min-width: 55rem"
                v-model:filters="filters" 
                dataKey="id" 
                :globalFilterFields="['full_name', 'name', 'nisn', 'nis', 'classroom_name', 'tka_subject_name', 'tka_subject_1_name', 'tka_subject_2_name']"
            >
                <template #header>
                    <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3">
                        <!-- Quick status filters -->
                        <div class="flex flex-wrap gap-2">
                            <Button 
                                label="Semua" 
                                size="small"
                                :severity="filterStatus === 'all' ? 'primary' : 'secondary'" 
                                :text="filterStatus !== 'all'" 
                                @click="filterStatus = 'all'" 
                            />
                            <Button 
                                label="Lengkap (2 Mapel)" 
                                icon="pi pi-check-circle"
                                size="small"
                                :severity="filterStatus === 'complete' ? 'success' : 'secondary'" 
                                :text="filterStatus !== 'complete'" 
                                @click="filterStatus = 'complete'" 
                            />
                            <Button 
                                label="Kurang (1 Mapel)" 
                                icon="pi pi-exclamation-triangle"
                                size="small"
                                :severity="filterStatus === 'incomplete' ? 'warning' : 'secondary'" 
                                :text="filterStatus !== 'incomplete'" 
                                @click="filterStatus = 'incomplete'" 
                            />
                            <Button 
                                label="Belum Memilih" 
                                icon="pi pi-minus"
                                size="small"
                                :severity="filterStatus === 'not_chosen' ? 'danger' : 'secondary'" 
                                :text="filterStatus !== 'not_chosen'" 
                                @click="filterStatus = 'not_chosen'" 
                            />
                        </div>

                        <!-- Search & Export -->
                        <div class="flex gap-2 align-items-center">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters['global'].value" placeholder="Cari siswa, NISN, mapel..." class="p-inputtext-sm" />
                            </IconField>
                            <Button 
                                label="Export CSV" 
                                icon="pi pi-file-excel" 
                                severity="success" 
                                size="small"
                                outlined 
                                @click="exportCSV" 
                                v-tooltip.top="'Unduh rekap excel'"
                            />
                        </div>
                    </div>
                </template>

                <template #empty>
                    <div class="text-center py-4 text-500">Tidak ada data siswa ditemukan untuk kelas ini.</div>
                </template>

                <Column header="No" style="width: 5%">
                    <template #body="slotProps">
                        {{ slotProps.index + 1 }}
                    </template>
                </Column>

                <Column field="nisn" header="NISN / NIS" style="width: 15%">
                    <template #body="slotProps">
                        <div class="font-semibold text-700">{{ slotProps.data.nisn || '-' }}</div>
                        <small class="text-500">NIS: {{ slotProps.data.nis || '-' }}</small>
                    </template>
                </Column>

                <Column field="full_name" header="Nama Siswa" sortable>
                    <template #body="slotProps">
                        <div class="flex align-items-center gap-2">
                            <span class="font-bold text-800">{{ slotProps.data.full_name }}</span>
                            <Tag 
                                :value="slotProps.data.gender_label" 
                                :severity="slotProps.data.gender_label === 'L' ? 'info' : 'warning'" 
                                class="text-xs" 
                            />
                        </div>
                    </template>
                </Column>

                <Column field="classroom_name" header="Kelas" style="width: 12%" sortable>
                    <template #body="slotProps">
                        <span class="font-medium">{{ slotProps.data.classroom_name }}</span>
                    </template>
                </Column>

                <Column field="tka_subject_1_name" header="Pilihan TKA 1" style="width: 18%" sortable>
                    <template #body="slotProps">
                        <div v-if="slotProps.data.tka_subject_1_name && slotProps.data.tka_subject_1_name !== '-'" class="flex align-items-center gap-2">
                            <Tag severity="success" :value="slotProps.data.tka_subject_1_name" class="text-sm px-3 py-1 font-bold" />
                            <small class="text-500 font-semibold" v-if="slotProps.data.tka_subject_1_code">({{ slotProps.data.tka_subject_1_code }})</small>
                        </div>
                        <div v-else class="flex align-items-center">
                            <span class="text-400 font-bold text-xl ml-2">-</span>
                        </div>
                    </template>
                </Column>

                <Column field="tka_subject_2_name" header="Pilihan TKA 2" style="width: 18%" sortable>
                    <template #body="slotProps">
                        <div v-if="slotProps.data.tka_subject_2_name && slotProps.data.tka_subject_2_name !== '-'" class="flex align-items-center gap-2">
                            <Tag severity="info" :value="slotProps.data.tka_subject_2_name" class="text-sm px-3 py-1 font-bold" />
                            <small class="text-500 font-semibold" v-if="slotProps.data.tka_subject_2_code">({{ slotProps.data.tka_subject_2_code }})</small>
                        </div>
                        <div v-else class="flex align-items-center">
                            <span class="text-400 font-bold text-xl ml-2">-</span>
                        </div>
                    </template>
                </Column>

                <Column field="is_complete" header="Status" style="width: 15%" sortable>
                    <template #body="slotProps">
                        <Tag 
                            v-if="slotProps.data.is_complete"
                            severity="success" 
                            value="Lengkap (2 Mapel)" 
                            icon="pi pi-check-circle"
                        />
                        <Tag 
                            v-else-if="slotProps.data.chosen_count === 1"
                            severity="warning" 
                            value="Kurang (1 Mapel)" 
                            icon="pi pi-exclamation-triangle"
                        />
                        <Tag 
                            v-else
                            severity="danger" 
                            value="Belum Memilih" 
                            icon="pi pi-times-circle"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
