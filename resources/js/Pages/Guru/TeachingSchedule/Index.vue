<template>
    <MobileLayout title="Atur Jadwal">
        <div class="max-w-7xl mx-auto space-y-5 px-3 py-2">
            <TeacherTabMenu />

            <!-- HEADER CARD -->
            <div class="surface-card shadow-2 border-round p-5 flex align-items-center no-print">
                <div>
                    <h2 class="text-2xl font-bold text-primary m-0">Jadwal Mengajar</h2>
                    <div class="text-600 mt-1">
                        <i class="pi pi-calendar mr-2"></i>
                        Tahun Ajaran: <b>{{ activeYear.name }}</b>
                    </div>
                </div>

                <!-- ⬇️ INI KUNCINYA -->
                <div class="ml-auto">
                    <Button
                        label="Cetak Jadwal"
                        icon="pi pi-print"
                        severity="secondary"
                        @click="printSchedule"
                    />
                </div>
            </div>

            <!-- SUMMARY CARDS -->
            <div class="grid no-print">
                <div class="col-12 md:col-4">
                    <div class="surface-card border-round shadow-1 p-4">
                        <div class="text-600 text-sm">Total Kelas</div>
                        <div class="text-2xl font-bold text-primary">
                            {{ schedules.length }}
                        </div>
                    </div>
                </div>

                <div class="col-12 md:col-4">
                    <div class="surface-card border-round shadow-1 p-4">
                        <div class="text-600 text-sm">Total Mapel</div>
                        <div class="text-2xl font-bold text-green-600">
                            {{ totalSubjects }}
                        </div>
                    </div>
                </div>

                <div class="col-12 md:col-4">
                    <div class="surface-card border-round shadow-1 p-4">
                        <div class="text-600 text-sm">Total JP Terjadwal</div>
                        <div class="text-2xl font-bold text-orange-500">
                            {{ totalJP }} JP
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="surface-card shadow-2 border-round p-4 no-print">
                <h3 class="text-lg font-bold mb-3">Daftar Jadwal Mengajar</h3>

                <DataTable
                    :value="schedules"
                    size="small"
                    stripedRows
                    showGridlines
                >
                    <Column field="classroom" header="Kelas" style="width: 15%" />

                    <Column field="subject" header="Mata Pelajaran" style="width: 35%" />

                    <Column header="Status JP" style="width: 30%">
                        <template #body="{ data }">
                            <ProgressBar
                                :value="(data.scheduled_jp / data.quota) * 100"
                                :showValue="false"
                                style="height: 6px"
                                :color="data.scheduled_jp === data.quota ? '#22C55E' : '#F59E0B'"
                            />
                            <small class="block text-right text-600 mt-1">
                                {{ data.scheduled_jp }} / {{ data.quota }} JP
                            </small>
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 20%" class="text-center">
                        <template #body="{ data }">
                            <Link :href="route('guru.teaching-schedules.edit', data.id)">
                                <Button
                                    label="Atur Jadwal"
                                    icon="pi pi-calendar-edit"
                                    size="small"
                                    text
                                />
                            </Link>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- PRINTABLE AREA (TIDAK DIUBAH LOGIKA) -->
            <div id="printable-area">
                <div class="hidden print-only mb-4 text-center">
                    <h2 class="m-0">JADWAL MENGAJAR MINGGUAN</h2>
                    <h3 class="m-0 text-primary">{{ $page.props.auth.user.name }}</h3>
                    <p class="m-0 text-600">Tahun Ajaran: {{ activeYear.name }}</p>
                    <hr class="my-4">
                </div>

                <div class="overflow-auto border-round border border-300">
                    <table class="w-full border-collapse border border-300">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3 border border-300 text-center" style="width: 100px;">HARI</th>
                                <th v-for="i in 10" :key="i" class="p-2 border border-300 text-center w-3rem">
                                    {{ i }}
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="day in days" :key="day.value">
                                <td class="font-bold p-3 border border-300 bg-gray-50 capitalize text-center">
                                    {{ day.label }}
                                </td>

                                <td
                                    v-for="slot in 10"
                                    :key="slot"
                                    class="border border-300 p-1 text-center h-4rem"
                                >
                                    <template v-for="sched in schedules" :key="sched.id">
                                        <template v-for="det in sched.details" :key="det.id">
                                            <div
                                                v-if="det.day === day.value && slot >= det.start_slot && slot <= det.end_slot"
                                                class="text-xs p-1 border-round h-full flex flex-column justify-content-center
                                                       bg-blue-50 text-blue-900 border-left-3 border-blue-500 shadow-1"
                                            >
                                                <span class="font-bold text-blue-700">
                                                    {{ sched.classroom }}
                                                </span>
                                                <span style="font-size: 8px">
                                                    {{ sched.subject }}
                                                </span>
                                            </div>
                                        </template>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </MobileLayout>
</template>


<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import TeacherTabMenu from '@/Components/TeacherTabMenu.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';

const props = defineProps({ schedules: Array, activeYear: Object });
const days = [{ label: 'Senin', value: 'senin' }, { label: 'Selasa', value: 'selasa' }, { label: 'Rabu', value: 'rabu' }, { label: 'Kamis', value: 'kamis' }, { label: 'Jumat', value: 'jumat' }, { label: 'Sabtu', value: 'sabtu' }];
const printSchedule = () => { window.print(); };
// SUMMARY
const totalSubjects = computed(() =>
    new Set(props.schedules.map(s => s.subject)).size
)

const totalJP = computed(() =>
    props.schedules.reduce((sum, s) => sum + s.scheduled_jp, 0)
)
</script>

<style scoped>
/* Gunakan CSS fixed yang berhasil Anda buat tadi */
@media print {
    body * { visibility: hidden; }
    .no-print, .layout-sidebar, .layout-topbar, .layout-menu { display: none !important; }
    #printable-area, #printable-area * { visibility: visible; }
    #printable-area { 
        position: fixed !important; left: 0 !important; top: 0 !important; 
        width: 100vw !important; height: 100vh !important; 
        margin: 0 !important; padding: 20px !important; 
        background-color: white !important; z-index: 999999 !important; 
    }
    .print-only { display: block !important; }
}
.print-only { display: none; }
</style>