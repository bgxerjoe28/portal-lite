<template>
    <AppLayout title="Agenda Guru">
        <TeacherTabMenu />
        <div class="card">

            <!-- ===== HEADER ===== -->
            <div class="flex flex-column gap-4 mb-5">

                <!-- Judul -->
                <div class="flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h2 class="text-2xl font-bold m-0 text-900 flex align-items-center gap-2">
                            <i class="pi pi-calendar text-primary"></i>
                            Agenda Mengajar
                        </h2>
                        <small class="text-500">
                            Tahun Ajaran {{ academicYear.name }} • Semester {{ academicYear.semester }}
                        </small>
                    </div>

                    <Button
                        label="Tambah Agenda"
                        icon="pi pi-plus"
                        severity="primary"
                        @click="router.get(route('guru.agenda.create'))"
                    />
                </div>

                <!-- Filter Bar -->
                <div class="surface-card p-3 border-round-lg shadow-1 flex flex-wrap gap-3 align-items-center">

                    <div class="flex align-items-center gap-2">
                        <span class="text-sm font-semibold text-700">Buka Tanggal</span>
                        <DatePicker
                            v-model="jumpDate"
                            dateFormat="dd MM yy"
                            showIcon
                            iconDisplay="input"
                            placeholder="Pilih tanggal"
                            class="w-12rem"
                            @date-select="handleJumpDate"
                        />
                    </div>

                    <div class="flex align-items-center gap-2">
                        <span class="text-sm font-semibold text-700">Filter</span>
                        <Select
                            v-model="selectedFilter"
                            :options="filterOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Pilih"
                            class="w-14rem"
                            @change="handleFilter"
                        />
                    </div>

                </div>
            </div>

            <!-- Flash Message -->
            <Message
                v-if="$page.props.flash.success"
                severity="success"
                class="mb-4"
            >
                {{ $page.props.flash.success }}
            </Message>

            <!-- ===== TABLE ===== -->
            <div class="surface-card p-4 shadow-2 border-round-lg">

                <DataTable
                    :value="agendas.data"
                    stripedRows
                    size="small"
                    responsiveLayout="scroll"
                >

                    <!-- HEADER BERTINGKAT -->
                    <ColumnGroup type="header">
                        <Row>
                            <Column header="No" rowspan="2" />
                            <Column header="Tanggal" rowspan="2" />
                            <Column header="Jam" rowspan="2" />
                            <Column header="Kelas" rowspan="2" />
                            <Column header="Mapel" rowspan="2" />
                            <Column header="TP" rowspan="2" />
                            <Column header="Presensi" colspan="3" />
                            <Column header="Aksi" rowspan="2" />
                        </Row>
                        <Row>
                            <Column header="Hadir" />
                            <Column header="Absen" />
                            <Column header="Jumlah" />
                        </Row>
                    </ColumnGroup>

                    <!-- No -->
                    <Column>
                        <template #body="{ index }">
                            <span class="text-600">{{ index + 1 }}</span>
                        </template>
                    </Column>

                    <!-- Tanggal -->
                    <Column>
                        <template #body="{ data }">
                            <div class="flex flex-column">
                                <span class="font-semibold">{{ formatDate(data.date) }}</span>
                            </div>
                        </template>
                    </Column>

                    <!-- Jam -->
                    <Column>
                        <template #body="{ data }">
                            <Tag
                                v-if="data.schedule_detail"
                                severity="info"
                                :value="`${data.schedule_detail.start_slot}-${data.schedule_detail.end_slot}`"
                            />
                            <span v-else class="text-400">-</span>
                        </template>
                    </Column>

                    <!-- Kelas -->
                    <Column field="classroom.name">
                        <template #body="{ data }">
                            <Tag
                                severity="secondary"
                                :value="data.classroom?.name || '-'"
                            />
                        </template>
                    </Column>

                    <!-- Mapel -->
                    <Column field="subject.name">
                        <template #body="{ data }">
                            <span class="font-semibold">{{ data.subject?.name }}</span>
                        </template>
                    </Column>

                    <!-- TP -->
                    <Column>
                        <template #body="{ data }">
                            <Tag
                                v-if="data.tp"
                                severity="warning"
                                :value="data.tp.kode_tp"
                            />
                            <span v-else class="text-400">-</span>
                        </template>
                    </Column>

                    <!-- Hadir -->
                    <Column>
                        <template #body="{ data }">
                            <Tag severity="success" :value="data.hadir_count" />
                        </template>
                    </Column>

                    <!-- Absen -->
                    <Column>
                        <template #body="{ data }">
                            <Tag severity="danger" :value="data.tidak_hadir_count" />
                        </template>
                    </Column>

                    <!-- Jumlah -->
                    <Column>
                        <template #body="{ data }">
                            <Tag severity="info" :value="data.total_siswa" />
                        </template>
                    </Column>

                    <!-- Aksi -->
                    <Column>
                        <template #body="{ data }">
                            <Button
                                icon="pi pi-eye"
                                text
                                rounded
                                severity="info"
                                v-tooltip.top="'Detail Agenda'"
                                @click="router.get(route('guru.agenda.show', data.id))"
                            />
                        </template>
                    </Column>

                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import TeacherTabMenu from '@/Components/TeacherTabMenu.vue'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ColumnGroup from 'primevue/columngroup'
import Row from 'primevue/row'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Message from 'primevue/message'
import dayjs from 'dayjs'
import 'dayjs/locale/id'

dayjs.locale('id')

const props = defineProps({
    agendas: Object,
    academicYear: Object,
    filters: Object
})

const formatDate = (val) => {
    if (!val) return '-'
    return dayjs(val).format('dddd, D MMMM YYYY')
}

const jumpDate = ref(null)

const handleJumpDate = (date) => {
    const formattedDate = date.toLocaleDateString('en-CA')
    router.get(route('guru.agenda.index'), {
        filter: 'custom',
        date: formattedDate
    })
}

const selectedFilter = ref(props.filters.filter || 'semua')

const filterOptions = [
    { label: 'Semua Agenda', value: 'semua' },
    { label: 'Hari Ini', value: 'hari_ini' },
    { label: 'Minggu Ini', value: 'minggu_ini' },
    { label: 'Bulan Ini', value: 'bulan_ini' },
    { label: 'Semester Ini', value: 'semester' },
]

const handleFilter = () => {
    router.get(
        route('guru.agenda.index'),
        { filter: selectedFilter.value },
        { preserveState: true, replace: true }
    )
}
</script>
