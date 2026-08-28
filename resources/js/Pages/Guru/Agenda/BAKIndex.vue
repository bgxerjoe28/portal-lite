<template>
    <AppLayout title="Agenda Guru">
        <div class="card">
            <div class="flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-2xl font-bold">Agenda Mengajar</h2>
                    <span class="text-500 text-sm">
                        Tahun Ajaran {{ academicYear.name }} ({{ academicYear.semester }})
                    </span>
                </div>
                <div class="flex align-items-center gap-2 surface-card p-2 border-round shadow-1">
                    <span class="text-sm font-bold text-700 px-2">Buka Tanggal:</span>
                        <DatePicker 
                            v-model="jumpDate" 
                            dateFormat="dd/mm/yy" 
                            showIcon 
                            iconDisplay="input"
                            placeholder="Pilih Tanggal"
                            @date-select="handleJumpDate"
                            class="w-full md:w-12rem"
                        />
                </div>

                <div class="flex gap-2">

                    <Select 
                        v-model="selectedFilter" 
                        :options="filterOptions" 
                        optionLabel="label" 
                        optionValue="value" 
                        placeholder="Filter Waktu"
                        class="w-12rem"
                        @change="handleFilter"
                    />
                    <Button
                        label="Tambah Agenda"
                        icon="pi pi-plus"
                        severity="primary"
                        @click="router.get(route('guru.agenda.create'))"
                    />
                </div>
            </div>
            <div>
                <Message v-if="$page.props.flash.success" severity="success">
                    {{ $page.props.flash.success }}
                </Message>
            </div>

            <div class="surface-card p-4 shadow-2 border-round">
            <DataTable :value="agendas.data" stripedRows>

                <!-- 🔷 HEADER BERTINGKAT -->
                <ColumnGroup type="header">
                    <Row>
                        <Column header="No." rowspan="2" />
                        <Column header="Tanggal" rowspan="2" />
                        <Column header="Jam ke" rowspan="2" />
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

                <!-- 🔷 BODY -->
                <Column>
                    <template #body="slotProps">
                        {{ slotProps.index + 1 }}
                    </template>
                </Column>
                <Column>
                    <template #body="{ data }">
                        {{ formatDate(data.date) }}
                    </template>
                </Column>
                <Column>
                    <template #body="{ data }">
                            <Tag 
                                v-if="data.schedule_detail" 
                                severity="info" 
                                :value="`Jam: ${data.schedule_detail.start_slot}-${data.schedule_detail.end_slot}`" 
                            />
                            <span v-else class="text-400">-</span>
                        </template>
                </Column>

                <Column field="classroom.name" />

                <Column field="subject.name" />

                <Column>
                    <template #body="{ data }">
                        {{ data.tp?.kode_tp || '-' }}
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
                        <Tag severity="secondary" :value="data.total_siswa" />
                    </template>
                </Column>

                <!-- Aksi -->
                <Column>
                    <template #body="{ data }">
                        <Button
                            icon="pi pi-eye"
                            text
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
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import dayjs from 'dayjs'
import 'dayjs/locale/id'
import ColumnGroup from 'primevue/columngroup'
import Row from 'primevue/row'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Message from 'primevue/message'


dayjs.locale('id')
const formatDate = (val) => {
    if(!val) return '-'
    return dayjs(val).format('dddd, D MMMM YYYY')
}
const jumpDate = ref(null);
const handleJumpDate = (date) => {
    // Format tanggal ke YYYY-MM-DD agar aman di URL
    const formattedDate = date.toLocaleDateString('en-CA');
    
    // Lompat ke Index dengan filter tanggal
    router.get(route('guru.agenda.index'), { 
        filter: 'custom', 
        date: formattedDate 
    });
};
const props = defineProps({
    agendas: Object,
    academicYear: Object,
    filters: Object
})
const selectedFilter = ref(props.filters.filter || 'semua')

const filterOptions = [
    { label: 'Semua Agenda', value: 'semua' },
    { label: 'Hari Ini', value: 'hari_ini' },
    { label: 'Minggu Ini', value: 'minggu_ini' },
    { label: 'Bulan Ini', value: 'bulan_ini' },
    { label: 'Semester Ini', value: 'semester' },
]
const handleFilter = () => {
    router.get(route('guru.agenda.index'), 
        { filter: selectedFilter.value }, 
        { preserveState: true, replace: true }
    )
}

const getJamKe = (agenda) => {
    const d = agenda.schedule?.details?.[0]
    return d ? `${d.start_slot}-${d.end_slot}` : '-'
}
</script>
