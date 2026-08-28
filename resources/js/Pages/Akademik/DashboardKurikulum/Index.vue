<template>
    <AppLayout>
        <div class="surface-ground p-4 md:p-6">
            <TeacherTabMenu v-if="$page.props.auth?.user?.roles?.some(r => r.name === 'guru') || $page.url.startsWith('/teacher')" />

            <!-- ================= HEADER ================= -->
            <div class="mb-5">
                <h2 class="text-2xl font-bold text-900 m-0">
                    Dashboard Kurikulum
                </h2>
                <span class="text-600">
                    Rekap Beban Mengajar Guru – Tahun Ajaran {{ year.name }}
                </span>
            </div>

            <!-- ================= SUMMARY ================= -->
            <div class="grid mb-5">
                <div class="col-12 md:col-3">
                    <div class="surface-card p-4 text-center border-round shadow-1">
                        <div class="text-3xl font-bold">{{ summary.total }}</div>
                        <div class="text-600 mt-1">Total Guru</div>
                    </div>
                </div>

                <div class="col-12 md:col-3">
                    <div class="surface-card p-4 text-center border-round shadow-1 border-left-3 border-green-500">
                        <div class="text-3xl font-bold text-green-600">
                            {{ summary.balanced }}
                        </div>
                        <div class="text-600 mt-1">Seimbang</div>
                    </div>
                </div>

                <div class="col-12 md:col-3">
                    <div class="surface-card p-4 text-center border-round shadow-1 border-left-3 border-red-500">
                        <div class="text-3xl font-bold text-red-500">
                            {{ summary.overload }}
                        </div>
                        <div class="text-600 mt-1">Overload</div>
                    </div>
                </div>

                <div class="col-12 md:col-3">
                    <div class="surface-card p-4 text-center border-round shadow-1 border-left-3 border-orange-500">
                        <div class="text-3xl font-bold text-orange-500">
                            {{ summary.underload }}
                        </div>
                        <div class="text-600 mt-1">Underload</div>
                    </div>
                </div>
            </div>

            <!-- ================= TABLE ================= -->
            <div class="surface-card p-4 border-round shadow-1">
                <h3 class="text-lg font-bold mb-3">
                    Rekap Beban Mengajar Guru
                </h3>
                <DataTable
                    :value="rowsSafe"
                    dataKey="teacher_id"
                    v-model:expandedRows="expandedRows"
                    stripedRows
                    showGridlines
                    size="small"
                >
                    <Column expander style="width:3rem" />

                    <Column field="name" header="Guru" />

                    <Column header="Target / Minggu">
                        <template #body="{ data }">
                            {{ data.target_weekly }}
                        </template>
                    </Column>

                    <Column header="Realisasi / Minggu">
                        <template #body="{ data }">
                            {{ data.realized_weekly }}
                        </template>
                    </Column>

                    <Column header="Status">
                        <template #body="{ data }">
                            {{ data.status }}
                        </template>
                    </Column>

                    <!-- 🔽 EXPANSION WAJIB DI DALAM -->
                    <template #expansion="{ data }">
                        <div class="p-4 surface-100 border-round">

                            <h4 class="mb-2 font-bold text-lg">
                                {{ data.name }}
                            </h4>
                            <p class="text-sm text-600 mb-3">
                                Target {{ data.target_weekly }} JP / minggu
                            </p>

                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="border-bottom-1 surface-200">
                                        <th class="p-2 text-left">Mapel</th>
                                        <th class="p-2 text-left">Kelas</th>
                                        <th class="p-2 text-center">JP / Minggu</th>
                                        <th class="p-2 text-left">Hari Mengajar</th>
                                        <th class="p-2 text-center">HEP</th>
                                        <th class="p-2 text-right">JP Semester</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="(d, i) in data.details"
                                        :key="i"
                                        class="border-bottom-1"
                                    >
                                        <td class="p-2">{{ d.subject }}</td>
                                        <td class="p-2">{{ d.classroom }}</td>
                                        <td class="p-2 text-center">{{ d.weekly_jp }}</td>
                                        <td class="p-2">
                                            {{ formatDayNames(d.occurrences) }}
                                        </td>
                                        <td class="p-2 text-center">
                                            {{ Object.values(d.occurrences).reduce((a,b)=>a+b,0) }}
                                        </td>
                                        <td class="p-2 text-right font-bold">
                                            {{ d.semester_jp }}
                                        </td>
                                    </tr>

                                    <!-- TOTAL -->
                                    <tr class="font-bold surface-200">
                                        <td colspan="5" class="p-2 text-right">
                                            Total Semester
                                        </td>
                                        <td class="p-2 text-right">
                                            {{ data.realized_semester }} JP
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>

                </DataTable>


            </div>



        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import TeacherTabMenu from '@/Components/TeacherTabMenu.vue'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

/* ================= PROPS ================= */
const props = defineProps({
    year: Object,
    summary: Object,
    rows: Array,
})

/* ================= STATE ================= */
const selectedTeacher = ref(null)

/* ================= COMPUTED ================= */
const rowsSafe = computed(() =>
    Array.isArray(props.rows) ? props.rows : []
)

/* ================= METHODS ================= */
const openDetail = (row) => {
    selectedTeacher.value = row
}

const severityStatus = (status) => {
    if (status === 'balanced') return 'success'
    if (status === 'overload') return 'danger'
    return 'warning'
}

const labelStatus = (status) => {
    if (status === 'balanced') return 'Seimbang'
    if (status === 'overload') return 'Overload'
    return 'Underload'
}

const diffColor = (diff) => {
    if (diff > 0) return 'text-red-500'
    if (diff < 0) return 'text-orange-500'
    return 'text-green-600'
}
const expandedRows = ref([])
const formatDays = (obj) => {
    if (!obj) return '-'
    return Object.entries(obj)
        .map(([day, count]) => `${day.toUpperCase()} (${count})`)
        .join(', ')
}

const formatDayNames = (obj) => {
    if (!obj) return '-'
    const map = {
        mon: 'Senin',
        tue: 'Selasa',
        wed: 'Rabu',
        thu: 'Kamis',
        fri: 'Jumat',
        sat: 'Sabtu',
    }

    return Object.keys(obj)
        .map(d => map[d] ?? d)
        .join(', ')
}


</script>
