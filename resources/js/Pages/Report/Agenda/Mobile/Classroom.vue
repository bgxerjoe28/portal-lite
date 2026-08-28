<template>
    <MobileLayout>
        <div class="p-3">
            <div class="flex justify-content-between align-items-center mb-3">
                <h2 class="text-xl font-bold m-0">Jurnal Kelas</h2>
                <Button icon="pi pi-print" rounded severity="info" aria-label="Cetak" @click="printPdf" />
            </div>

            <div class="mb-3">
                <small class="text-600">Kelas: <b class="text-900">{{ classroom?.name }}</b></small>
            </div>

            <!-- Filters -->
            <div class="flex gap-2 mb-4">
                <Select 
                    v-model="selectedMonth" 
                    :options="months" 
                    optionLabel="label" 
                    optionValue="val" 
                    @change="handleFilter"
                    class="flex-1"
                />
                <Select 
                    v-model="selectedYear" 
                    :options="yearOptions" 
                    optionLabel="label" 
                    optionValue="val" 
                    @change="handleFilter"
                    class="w-7rem"
                />
            </div>
            
            <div v-for="(dayAgendas, date) in reports" :key="date" class="mb-4">
                <div class="text-xs font-bold text-500 uppercase mb-2 border-bottom-1 border-200 pb-1">
                    {{ formatDateIndo(date) }}
                </div>
                
                <div class="flex flex-column gap-3">
                    <div v-for="agenda in dayAgendas" :key="agenda.id" 
                         class="surface-card p-3 border-round shadow-1 border-left-3 border-primary">
                        <div class="flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="font-bold text-900">{{ agenda.teacher?.full_name }}</div>
                                <small class="text-600">{{ agenda.subject?.name }}</small>
                            </div>
                            <Tag :value="'Jam Ke-' + (agenda.start_slot ?? agenda.schedule_detail?.start_slot ?? '-')" severity="secondary" />
                        </div>
                        <div class="text-sm line-height-3 mb-2">
                            {{ agenda.materi_pembelajaran }}
                        </div>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <span class="text-xs font-bold text-green-700 bg-green-50 px-2 py-1 border-round">
                                {{ agenda.hadir_count ?? 0 }} Hadir
                            </span>
                            <span v-if="(agenda.sakit_count ?? 0) > 0" class="text-xs font-bold text-blue-700 bg-blue-50 px-2 py-1 border-round">
                                {{ agenda.sakit_count }} Sakit
                            </span>
                            <span v-if="(agenda.izin_count ?? 0) > 0" class="text-xs font-bold text-orange-700 bg-orange-50 px-2 py-1 border-round">
                                {{ agenda.izin_count }} Izin
                            </span>
                            <span v-if="(agenda.dispen_count ?? 0) > 0" class="text-xs font-bold text-purple-700 bg-purple-50 px-2 py-1 border-round">
                                {{ agenda.dispen_count }} Dispen
                            </span>
                            <span v-if="(agenda.alpa_count ?? 0) > 0" class="text-xs font-bold text-red-700 bg-red-50 px-2 py-1 border-round">
                                {{ agenda.alpa_count }} Alpa
                            </span>
                        </div>
                        <div v-if="agenda.absent_students && agenda.absent_students.length > 0" class="text-xs text-700 bg-surface-50 p-2 border-round border-1 surface-border">
                            <div class="font-bold text-600 mb-1">Siswa Tidak Hadir:</div>
                            <div v-for="(st, sIdx) in agenda.absent_students" :key="sIdx" class="text-2xs text-800">
                                • {{ st }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="Object.keys(reports).length === 0" class="text-center py-8">
                <i class="pi pi-inbox text-400 text-5xl mb-3"></i>
                <p class="text-500">Belum ada data di bulan ini.</p>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Select from 'primevue/select'

const props = defineProps({ 
    reports: Object, 
    filters: Object, 
    classroom: Object,
    yearOptions: Array 
});

const selectedMonth = ref(props.filters.month);
const selectedYear = ref(props.filters.year);

const months = [
    { val: 1, label: 'Januari' }, { val: 2, label: 'Februari' }, { val: 3, label: 'Maret' },
    { val: 4, label: 'April' }, { val: 5, label: 'Mei' }, { val: 6, label: 'Juni' },
    { val: 7, label: 'Juli' }, { val: 8, label: 'Agustus' }, { val: 9, label: 'September' },
    { val: 10, label: 'Oktober' }, { val: 11, label: 'November' }, { val: 12, label: 'Desember' }
];

const handleFilter = () => {
    router.get(route('guru.reports.classroom'), {
        month: selectedMonth.value,
        year: selectedYear.value
    }, { preserveState: true });
};

const printPdf = () => {
    const url = route('guru.reports.classroom.pdf', {
        month: selectedMonth.value,
        year: selectedYear.value
    });
    window.open(url, '_blank');
};

const formatDateIndo = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
};
const countHadir = (agenda) => {
    if (!agenda.attendances) return 0;
    return agenda.attendances.filter(a => a.is_present === true || a.is_present === 't').length;
};
const countAbsen = (agenda) => {
    if (!agenda.attendances) return 0;
    return agenda.attendances.filter(a => a.is_present === false || a.is_present === 'f' || !a.is_present).length;
};
</script>
