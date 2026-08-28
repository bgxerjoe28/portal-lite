<template>
    <AppLayout title="Analisis Butir Soal Bank Soal">
        <div class="card">
            <!-- Header Nav & Title -->
            <div class="flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
                <div class="flex align-items-center gap-2">
                    <Link :href="route('cbt.bank.index')">
                        <Button icon="pi pi-arrow-left" severity="secondary" text rounded />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-900 m-0">Analisis Butir Soal & IRT: {{ bank.name }}</h2>
                        <span class="text-500 block mt-1">
                            Mata Pelajaran: <b>{{ bank.subject?.name || '-' }}</b> | Pengajar: <b>{{ bank.teacher?.full_name || 'Admin' }}</b> | Total Soal: <b>{{ bank.questions?.length || 0 }} Soal</b>
                        </span>
                    </div>
                </div>

                <div class="flex gap-2 flex-wrap align-items-center">
                    <SplitButton 
                        label="Cetak Laporan Lengkap (PDF)" 
                        icon="pi pi-print" 
                        severity="info" 
                        :model="exportMenuItems" 
                        @click="printFullReport" 
                    />
                    <Button 
                        label="Hitung Ulang Nilai & Analisis" 
                        icon="pi pi-sync" 
                        severity="help" 
                        outlined
                        :loading="isRecalculating"
                        @click="confirmRecalculate" 
                    />
                    <Link :href="route('cbt.bank.questions', bank.id)">
                        <Button label="Kelola Soal" icon="pi pi-list" severity="secondary" />
                    </Link>
                </div>
            </div>

            <!-- Info Accumulative Banner -->
            <div class="mb-4 p-3 bg-blue-50 border-round border border-blue-200 flex align-items-center gap-3 text-sm text-blue-800">
                <i class="pi pi-info-circle text-2xl text-blue-600"></i>
                <div>
                    <b>Analisis Butir Soal Akumulatif (Bank Soal Wide):</b>
                    <p class="m-0 text-xs mt-1">
                        Data statistik CTT & IRT di halaman ini merupakan hasil komputasi dari <b>SELURUH PESERTA & KELAS</b> yang pernah mengerjakan ujian berbasis Bank Soal ini.
                    </p>
                </div>
            </div>

            <!-- Custom Tab Navigation -->
            <div class="flex bg-slate-100 p-1 border-round mb-4 gap-1 flex-wrap">
                <button 
                    type="button"
                    class="px-4 py-2 border-none border-round font-medium text-sm transition-all cursor-pointer flex align-items-center gap-2"
                    :class="activeTab === 'ctt_summary' ? 'bg-white text-primary shadow-1 font-bold' : 'text-slate-600 hover:text-slate-900 bg-transparent'"
                    @click="activeTab = 'ctt_summary'"
                >
                    <i class="pi pi-chart-bar"></i>
                    <span>Ringkasan CTT & Reliabilitas Bank</span>
                </button>

                <button 
                    type="button"
                    class="px-4 py-2 border-none border-round font-medium text-sm transition-all cursor-pointer flex align-items-center gap-2"
                    :class="activeTab === 'all_participants' ? 'bg-white text-primary shadow-1 font-bold' : 'text-slate-600 hover:text-slate-900 bg-transparent'"
                    @click="activeTab = 'all_participants'"
                >
                    <i class="pi pi-users"></i>
                    <span>Daftar Peserta & Model ({{ participants.length }})</span>
                </button>

                <button 
                    type="button"
                    class="px-4 py-2 border-none border-round font-medium text-sm transition-all cursor-pointer flex align-items-center gap-2"
                    :class="activeTab === 'ctt_items' ? 'bg-white text-primary shadow-1 font-bold' : 'text-slate-600 hover:text-slate-900 bg-transparent'"
                    @click="activeTab = 'ctt_items'"
                >
                    <i class="pi pi-list"></i>
                    <span>Analisis Butir Soal & Pengecoh ({{ ctt_item_analyses.length }})</span>
                </button>

                <button 
                    type="button"
                    class="px-4 py-2 border-none border-round font-medium text-sm transition-all cursor-pointer flex align-items-center gap-2"
                    :class="activeTab === 'irt_params' ? 'bg-white text-purple-700 shadow-1 font-bold' : 'text-slate-600 hover:text-slate-900 bg-transparent'"
                    @click="activeTab = 'irt_params'"
                >
                    <i class="pi pi-sliders-h"></i>
                    <span>Parameter Model IRT ({{ irt_parameters.length }})</span>
                </button>
            </div>

            <!-- TAB CONTENT 1: RINGKASAN CTT -->
            <div v-show="activeTab === 'ctt_summary'">
                <div v-if="ctt_summary" class="grid mb-4">
                    <div class="col-12 md:col-6 lg:col-3">
                        <div class="surface-card shadow-2 p-3 border-round border-left-4 border-blue-500">
                            <div class="flex justify-content-between mb-3">
                                <div>
                                    <span class="block text-500 font-medium mb-1">Reliabilitas Cronbach's α</span>
                                    <div class="text-900 font-bold text-2xl">{{ ctt_summary.cronbach_alpha ?? '-' }}</div>
                                </div>
                                <div class="flex align-items-center justify-content-center bg-blue-100 border-round" style="width:2.5rem;height:2.5rem">
                                    <i class="pi font-bold text-blue-500 text-xl">α</i>
                                </div>
                            </div>
                            <span class="text-xs font-semibold" :class="getAlphaClass(ctt_summary.cronbach_alpha)">
                                {{ getAlphaLabel(ctt_summary.cronbach_alpha) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-12 md:col-6 lg:col-3">
                        <div class="surface-card shadow-2 p-3 border-round border-left-4 border-green-500">
                            <div class="flex justify-content-between mb-3">
                                <div>
                                    <span class="block text-500 font-medium mb-1">Rata-Rata Nilai Bank</span>
                                    <div class="text-900 font-bold text-2xl">{{ ctt_summary.mean_score }}</div>
                                </div>
                                <div class="flex align-items-center justify-content-center bg-green-100 border-round" style="width:2.5rem;height:2.5rem">
                                    <i class="pi pi-calculator text-green-500 text-xl"></i>
                                </div>
                            </div>
                            <span class="text-500 text-xs">Median: <b>{{ ctt_summary.median_score }}</b></span>
                        </div>
                    </div>

                    <div class="col-12 md:col-6 lg:col-3">
                        <div class="surface-card shadow-2 p-3 border-round border-left-4 border-orange-500">
                            <div class="flex justify-content-between mb-3">
                                <div>
                                    <span class="block text-500 font-medium mb-1">Standar Deviasi</span>
                                    <div class="text-900 font-bold text-2xl">{{ ctt_summary.std_deviation }}</div>
                                </div>
                                <div class="flex align-items-center justify-content-center bg-orange-100 border-round" style="width:2.5rem;height:2.5rem">
                                    <i class="pi pi-sliders-v text-orange-500 text-xl"></i>
                                </div>
                            </div>
                            <span class="text-500 text-xs">Min: <b>{{ ctt_summary.min_score }}</b> | Max: <b>{{ ctt_summary.max_score }}</b></span>
                        </div>
                    </div>

                    <!-- CARD CLICKABLE: TOTAL RESPON SISWA -->
                    <div class="col-12 md:col-6 lg:col-3">
                        <div 
                            class="surface-card shadow-2 p-3 border-round border-left-4 border-purple-500 cursor-pointer hover:shadow-4 transition-all"
                            v-tooltip.top="'Klik untuk melihat daftar seluruh peserta & skor model CTT/IRT'"
                            @click="activeTab = 'all_participants'"
                        >
                            <div class="flex justify-content-between mb-3">
                                <div>
                                    <span class="block text-500 font-medium mb-1">Total Respon Siswa</span>
                                    <div class="text-900 font-bold text-2xl">{{ ctt_summary.total_participants }} Siswa</div>
                                </div>
                                <div class="flex align-items-center justify-content-center bg-purple-100 border-round" style="width:2.5rem;height:2.5rem">
                                    <i class="pi pi-users text-purple-500 text-xl"></i>
                                </div>
                            </div>
                            <span class="text-purple-600 text-xs font-semibold flex align-items-center gap-1">
                                <i class="pi pi-arrow-right"></i> Klik untuk lihat daftar peserta
                            </span>
                        </div>
                    </div>
                </div>

                <div v-else class="p-4 bg-slate-50 border-round text-center text-600">
                    <i class="pi pi-info-circle text-2xl block mb-2 text-primary"></i>
                    Belum ada ringkasan analisis CTT bank soal. Klik tombol <b>"Kalkulasi Ulang Analisis Bank"</b> untuk memproses.
                </div>
            </div>

            <!-- TAB CONTENT 2: DAFTAR SELURUH PESERTA & MODEL (CTT & IRT) -->
            <div v-show="activeTab === 'all_participants'" class="surface-card p-4 shadow-2 border-round">
                <div class="flex justify-content-between align-items-center mb-3">
                    <h3 class="text-lg font-bold text-900 m-0">Daftar Seluruh Peserta & Skor Model (CTT & IRT)</h3>
                    <Tag :value="participants.length + ' Peserta Akumulatif'" severity="purple" />
                </div>

                <DataTable v-bind="$pagination({ label: 'participants' })" :value="participants" stripedRows tableStyle="min-width: 50rem">
                    <template #empty> Belum ada peserta yang memicu pengerjaan bank soal ini. </template>

                    <Column field="name" header="Nama Siswa" sortable>
                        <template #body="{ data }">
                            <span class="font-bold text-900">{{ data.name }}</span>
                            <small class="text-600 block mt-1">NISN: {{ data.nisn }}</small>
                        </template>
                    </Column>

                    <Column field="classroom_name" header="Kelas" sortable style="width: 10%">
                        <template #body="{ data }">
                            <Tag :value="data.classroom_name" severity="info" />
                        </template>
                    </Column>

                    <Column field="exam_title" header="Sesi Ujian" sortable style="width: 22%">
                        <template #body="{ data }">
                            <span class="text-sm text-800 font-semibold">{{ data.exam_title }}</span>
                        </template>
                    </Column>

                    <Column field="ctt_score" header="Nilai CTT (%)" sortable style="width: 15%">
                        <template #body="{ data }">
                            <div v-if="data.ctt_score !== null" class="flex flex-column gap-1">
                                <span 
                                    class="text-lg font-bold p-1 border-round text-center"
                                    :class="data.ctt_score >= 70 ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50'"
                                >
                                    {{ data.ctt_score }}%
                                </span>
                                <small v-if="data.ctt_raw !== null" class="text-2xs text-center text-slate-500">
                                    Raw: {{ data.ctt_raw }} pts
                                </small>
                            </div>
                            <span v-else class="text-500 italic text-sm">-</span>
                        </template>
                    </Column>

                    <Column header="IRT (Ability θ)" sortable style="width: 18%">
                        <template #body="{ data }">
                            <div v-if="data.irt_theta !== null" class="flex flex-column gap-1 text-xs">
                                <div class="font-mono bg-purple-50 text-purple-700 p-2 border-round text-center">
                                    <b>θ: {{ data.irt_theta }}</b>
                                    <div class="text-2xs text-purple-600">SE: ±{{ data.irt_se }}</div>
                                </div>
                                <div class="text-center font-semibold text-slate-700">
                                    Skala: <span class="text-purple-700 font-mono">{{ data.irt_scaled }}</span>
                                    <span v-if="data.irt_percentile" class="text-2xs text-slate-500 block">({{ data.irt_percentile }}%)</span>
                                </div>
                            </div>
                            <span v-else class="text-500 italic text-xs text-center block">-</span>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- TAB CONTENT 3: ANALISIS BUTIR SOAL CTT -->
            <div v-show="activeTab === 'ctt_items'" class="surface-card p-4 shadow-2 border-round">
                <DataTable v-bind="$pagination({ label: 'ctt_item_analyses' })" :value="ctt_item_analyses" stripedRows tableStyle="min-width: 50rem">
                    <template #empty> Belum ada data analisis butir soal CTT untuk bank ini. </template>

                    <Column header="Soal #" sortable field="item_number" style="width: 8%">
                        <template #body="{ data, index }">
                            <span class="font-bold text-primary">#{{ data.item_number || (index + 1) }}</span>
                        </template>
                    </Column>

                    <Column header="Pertanyaan" style="width: 35%">
                        <template #body="{ data }">
                            <div class="text-sm line-clamp-2" v-html="data.question?.question_text || '-'"></div>
                            <small class="text-500 mt-1 block">Tipe: <b>{{ data.question?.question_type }}</b></small>
                        </template>
                    </Column>

                    <Column field="difficulty_index" header="Kesukaran (p)" sortable style="width: 14%">
                        <template #body="{ data }">
                            <div class="flex flex-column gap-1">
                                <span class="font-bold font-mono">{{ data.difficulty_index }}</span>
                                <Tag :value="getDifficultyLabel(data.difficulty_index)" :severity="getDifficultySeverity(data.difficulty_index)" class="text-xs" />
                            </div>
                        </template>
                    </Column>

                    <Column field="discrimination_index" header="Daya Beda (D)" sortable style="width: 14%">
                        <template #body="{ data }">
                            <div class="flex flex-column gap-1">
                                <span class="font-bold font-mono">{{ data.discrimination_index }}</span>
                                <Tag :value="getDiscriminationLabel(data.discrimination_index)" :severity="getDiscriminationSeverity(data.discrimination_index)" class="text-xs" />
                            </div>
                        </template>
                    </Column>

                    <Column field="point_biserial" header="r_pbis" sortable style="width: 10%">
                        <template #body="{ data }">
                            <span class="font-mono text-sm font-semibold" :class="(data.point_biserial ?? 0) >= 0.2 ? 'text-green-700' : 'text-red-600'">
                                {{ data.point_biserial ?? '-' }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Analisis Pengecoh (Distractors)" style="width: 20%">
                        <template #body="{ data }">
                            <div v-if="data.distractor_stats && Object.keys(data.distractor_stats).length > 0" class="flex flex-column gap-1 text-xs">
                                <div 
                                    v-for="(optInfo, optKey) in data.distractor_stats" 
                                    :key="optKey"
                                    class="p-1 border-round flex justify-content-between align-items-center"
                                    :class="optInfo.is_key ? 'bg-green-100 text-green-800 font-bold' : optInfo.is_effective ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600'"
                                >
                                    <span>
                                        <b>Opsi {{ optKey }}:</b> {{ optInfo.percentage }}% ({{ optInfo.count }})
                                    </span>
                                    <span class="text-2xs font-semibold" v-if="optInfo.mean_theta !== null">
                                        θ: {{ optInfo.mean_theta }}
                                    </span>
                                </div>
                            </div>
                            <span v-else class="text-500 italic text-xs">-</span>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- TAB CONTENT 4: PARAMETER MODEL IRT -->
            <div v-show="activeTab === 'irt_params'">
                <!-- Status Banner Job IRT Microservice -->
                <div class="mb-4">
                    <div v-if="irt_job_status?.status === 'skipped'" class="p-3 bg-yellow-50 border-round border border-yellow-300 flex align-items-center gap-3 text-yellow-800">
                        <i class="pi pi-exclamation-triangle text-2xl text-yellow-600"></i>
                        <div>
                            <h4 class="font-bold m-0 text-yellow-900">Estimasi IRT Nonaktif (Peserta Total Akumulatif N &lt; 100)</h4>
                            <p class="m-0 text-xs mt-1">{{ irt_job_status.error_message }}</p>
                        </div>
                    </div>

                    <div v-else-if="irt_job_status?.status === 'sent_to_microservice' || irt_job_status?.status === 'processing'" class="p-3 bg-blue-50 border-round border border-blue-300 flex align-items-center gap-3 text-blue-800">
                        <i class="pi pi-spin pi-spinner text-2xl text-blue-600"></i>
                        <div>
                            <h4 class="font-bold m-0 text-blue-900">Estimasi Model IRT Sedang Diproses di Python Microservice</h4>
                            <p class="m-0 text-xs mt-1">Total Peserta Akumulatif: <b>{{ irt_job_status.total_participants }} Siswa</b> | Progress: {{ irt_job_status.progress_percent }}%</p>
                        </div>
                    </div>

                    <div v-else-if="irt_job_status?.status === 'completed'" class="p-3 bg-green-50 border-round border border-green-300 flex align-items-center gap-3 text-green-800">
                        <i class="pi pi-check-circle text-2xl text-green-600"></i>
                        <div>
                            <h4 class="font-bold m-0 text-green-900">Estimasi Parameter IRT Berhasil Dikalkulasi!</h4>
                            <p class="m-0 text-xs mt-1">Model: <b>{{ irt_job_status.job_type }}</b> | Selesai pada: {{ formatDate(irt_job_status.completed_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tabel Parameter IRT (a, b, c) -->
                <div class="surface-card p-4 shadow-2 border-round">
                    <DataTable v-bind="$pagination({ label: 'irt_parameters' })" :value="irt_parameters" stripedRows tableStyle="min-width: 50rem">
                        <template #empty> Belum ada data parameter IRT. Pastikan total peserta N &ge; 100 dan Microservice Python aktif. </template>

                        <Column header="Soal #" sortable field="item_number" style="width: 10%">
                            <template #body="{ data, index }">
                                <span class="font-bold text-purple-700">#{{ data.item_number || (index + 1) }}</span>
                            </template>
                        </Column>

                        <Column field="model_type" header="Model" style="width: 12%">
                            <template #body="{ data }">
                                <Tag :value="data.model_type || '2PL'" severity="purple" />
                            </template>
                        </Column>

                        <Column field="difficulty_b" header="Kesukaran (b)" sortable style="width: 22%">
                            <template #body="{ data }">
                                <div class="flex flex-column gap-1">
                                    <span class="font-bold font-mono text-base text-purple-800">{{ data.difficulty_b }}</span>
                                    <small class="text-xs text-slate-500">Batas [-4.0 s.d +4.0]</small>
                                </div>
                            </template>
                        </Column>

                        <Column field="discrimination_a" header="Daya Beda (a)" sortable style="width: 22%">
                            <template #body="{ data }">
                                <div class="flex flex-column gap-1">
                                    <span class="font-bold font-mono text-base text-purple-800">{{ data.discrimination_a }}</span>
                                    <small class="text-xs text-slate-500">Kemiringan Kurva TR</small>
                                </div>
                            </template>
                        </Column>

                        <Column field="guessing_c" header="Tebakan Pseudo (c)" sortable style="width: 22%">
                            <template #body="{ data }">
                                <div class="flex flex-column gap-1">
                                    <span class="font-bold font-mono text-base text-purple-800">{{ data.guessing_c }}</span>
                                    <small class="text-xs text-slate-500">Tebakan Asimtot Bawah</small>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import SplitButton from 'primevue/splitbutton';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    bank: Object,
    participants: {
        type: Array,
        default: () => []
    },
    ctt_summary: {
        type: Object,
        default: null
    },
    ctt_item_analyses: {
        type: Array,
        default: () => []
    },
    irt_parameters: {
        type: Array,
        default: () => []
    },
    irt_job_status: {
        type: Object,
        default: null
    },
});

const confirm = useConfirm();
const isRecalculating = ref(false);
const activeTab = ref('ctt_summary');

const exportMenuItems = [
    {
        label: 'Cetak Laporan Lengkap (Semua)',
        icon: 'pi pi-print',
        command: () => {
            printFullReport();
        }
    },
    {
        separator: true
    },
    {
        label: 'Cetak Laporan Jawaban Siswa',
        icon: 'pi pi-file-pdf',
        command: () => {
            window.open(route('cbt.bank.export_answers_pdf', props.bank.id), '_blank');
        }
    },
    {
        label: 'Cetak Matriks Dikotomi',
        icon: 'pi pi-table',
        command: () => {
            window.open(route('cbt.bank.export_dichotomous_pdf', props.bank.id), '_blank');
        }
    },
    {
        label: 'Cetak Daftar Nilai Siswa',
        icon: 'pi pi-list',
        command: () => {
            window.open(route('cbt.bank.export_daftar_nilai_pdf', props.bank.id), '_blank');
        }
    }
];

const printFullReport = () => {
    window.open(route('cbt.bank.export_full_report_pdf', props.bank.id), '_blank');
};

const confirmRecalculate = () => {
    confirm.require({
        message: 'Perbarui Nilai & analisis CTT & IRT. Lanjutkan?',
        header: 'Hitung Ulang Nilai & Analisis',
        icon: 'pi pi-sync',
        acceptClass: 'p-button-help',
        acceptLabel: 'Ya, Hitung Ulang',
        rejectLabel: 'Batal',
        accept: () => {
            triggerRecalculate();
        }
    });
};

const triggerRecalculate = () => {
    isRecalculating.value = true;
    router.post(route('cbt.bank.recalculate_analytics', props.bank.id), {}, {
        onFinish: () => {
            isRecalculating.value = false;
        }
    });
};

const getAlphaLabel = (alpha) => {
    if (alpha === null || alpha === undefined) return 'Belum Dihitung';
    if (alpha >= 0.8) return 'Reliabilitas Sangat Tinggi';
    if (alpha >= 0.7) return 'Reliabilitas Tinggi';
    if (alpha >= 0.6) return 'Reliabilitas Cukup';
    return 'Reliabilitas Rendah';
};

const getAlphaClass = (alpha) => {
    if (alpha >= 0.7) return 'text-green-600';
    if (alpha >= 0.6) return 'text-orange-500';
    return 'text-red-600';
};

const getDifficultyLabel = (p) => {
    if (p > 0.7) return 'Mudah';
    if (p >= 0.3) return 'Sedang';
    return 'Sukar';
};

const getDifficultySeverity = (p) => {
    if (p > 0.7) return 'success';
    if (p >= 0.3) return 'info';
    return 'danger';
};

const getDiscriminationLabel = (D) => {
    if (D >= 0.4) return 'Sangat Baik';
    if (D >= 0.3) return 'Baik';
    if (D >= 0.2) return 'Cukup';
    return 'Jelek / Buang';
};

const getDiscriminationSeverity = (D) => {
    if (D >= 0.3) return 'success';
    if (D >= 0.2) return 'warn';
    return 'danger';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }) + ' WIB';
};
</script>
