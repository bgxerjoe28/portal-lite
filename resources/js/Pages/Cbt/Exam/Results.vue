<template>
    <AppLayout title="Proktoring & Hasil Ujian">
        <div class="card">
            <!-- Header Nav & Title -->
            <div class="flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
                <div class="flex align-items-center gap-2">
                    <Link :href="route('cbt.exams.index')">
                        <Button icon="pi pi-arrow-left" severity="secondary" text rounded />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-900 m-0">Proktoring & Analisis Hasil Ujian: {{ exam.title }}</h2>
                        <span class="text-500 block mt-1">
                            Mapel: <b>{{ exam.bank?.subject?.name || '-' }}</b> | Durasi: <b>{{ exam.duration }} Menit</b>
                            <Tag v-if="exam.is_independent" value="Ujian Mandiri" severity="info" class="ml-2" />
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
                    <Link :href="route('cbt.exams.results.recap', exam.id)">
                        <Button label="Lihat Rekap Jawaban" icon="pi pi-table" severity="primary" />
                    </Link>
                </div>
            </div>

            <!-- Legend untuk logged_out khusus ujian mandiri -->
            <div v-if="exam.is_independent && hasKickedStudents" class="mb-3 p-3 bg-orange-50 border-round border border-orange-300 flex align-items-center gap-2 text-sm text-orange-700">
                <i class="pi pi-info-circle text-lg"></i>
                <span>
                    <b>Ujian Mandiri:</b> Siswa dengan status <b>"Dikeluarkan (Pelanggaran 3x)"</b> tidak dapat masuk kembali tanpa izin Anda.
                    Klik tombol <i class="pi pi-sign-in"></i> untuk memberi izin masuk kembali. Jawaban sebelumnya tetap tersimpan.
                </span>
            </div>

            <!-- Custom Tab Navigation (Bebas Deprecation Warning PrimeVue v4) -->
            <div class="flex bg-slate-100 p-1 border-round mb-4 gap-1 flex-wrap">
                <button 
                    type="button"
                    class="px-4 py-2 border-none border-round font-medium text-sm transition-all cursor-pointer flex align-items-center gap-2"
                    :class="activeTab === 'students' ? 'bg-white text-primary shadow-1 font-bold' : 'text-slate-600 hover:text-slate-900 bg-transparent'"
                    @click="activeTab = 'students'"
                >
                    <i class="pi pi-users"></i>
                    <span>Hasil & Proktoring Siswa ({{ results.length }})</span>
                </button>

                <button 
                    type="button"
                    class="px-4 py-2 border-none border-round font-medium text-sm transition-all cursor-pointer flex align-items-center gap-2"
                    :class="activeTab === 'ctt_summary' ? 'bg-white text-primary shadow-1 font-bold' : 'text-slate-600 hover:text-slate-900 bg-transparent'"
                    @click="activeTab = 'ctt_summary'"
                >
                    <i class="pi pi-chart-bar"></i>
                    <span>Ringkasan CTT & Statistik Ujian</span>
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

            <!-- TAB CONTENT 1: HASIL SISWA & PROKTORING -->
            <div v-show="activeTab === 'students'" class="surface-card p-4 shadow-2 border-round">
                <!-- Action Banner jika ada siswa yang sedang ujian / login -->
                <div v-if="activeStudentsCount > 0" class="mb-4 p-3 bg-amber-50 border-round border border-amber-300 flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="flex align-items-center gap-2 text-amber-900 text-sm">
                        <i class="pi pi-info-circle text-xl text-amber-600"></i>
                        <span>
                            Terdapat <b>{{ activeStudentsCount }}</b> siswa yang masih berstatus aktif/mengerjakan ujian. 
                            Anda dapat menyelesaikan seluruh pengerjaan siswa tersebut secara serentak dalam 1 klik.
                        </span>
                    </div>
                    <Button 
                        :label="`Selesaikan Semua Siswa Aktif (${activeStudentsCount})`" 
                        icon="pi pi-check-circle" 
                        severity="warn" 
                        @click="confirmForceSubmitAll" 
                    />
                </div>

                <DataTable v-bind="$pagination({ label: 'results' })" 
                    :value="results" 
                    stripedRows 
                    tableStyle="min-width: 50rem"
                >
                    <template #empty> Tidak ada siswa terdaftar di rombel kelas ujian ini. </template>

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

                    <Column field="status" header="Status Ujian" sortable style="width: 18%">
                        <template #body="{ data }">
                            <div class="flex flex-column gap-1">
                                <Tag 
                                    :value="formatStatusLabel(data)" 
                                    :severity="formatStatusSeverity(data)" 
                                />
                                <small 
                                    v-if="data.warning_count > 0" 
                                    class="text-xs font-semibold flex align-items-center gap-1"
                                    :class="data.is_blocked ? 'text-red-600' : data.warning_count >= 3 ? 'text-orange-500' : 'text-yellow-600'"
                                >
                                    <i class="pi pi-exclamation-triangle"></i>
                                    {{ data.warning_count }}x Pelanggaran Fokus
                                    <span v-if="data.is_blocked">(🔒 Diblokir)</span>
                                </small>

                                <!-- Progres Soal Indicator -->
                                <div v-if="data.total_questions > 0 && data.status !== 'not_started'" class="mt-1 flex flex-column gap-1" style="max-width: 140px;">
                                    <div class="flex justify-content-between text-xs font-semibold text-slate-700">
                                        <span>Progres:</span>
                                        <b class="text-blue-600 font-mono">{{ data.answered_count }}/{{ data.total_questions }}</b>
                                    </div>
                                    <div class="w-full bg-slate-200 border-round overflow-hidden" style="height: 5px;">
                                        <div 
                                            class="h-full border-round transition-all duration-300"
                                            :class="data.answered_count === data.total_questions ? 'bg-green-500' : 'bg-blue-500'"
                                            :style="{ width: Math.min(100, Math.round((data.answered_count / data.total_questions) * 100)) + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Jejak Waktu" style="width: 18%">
                        <template #body="{ data }">
                            <div class="flex flex-column gap-1 text-xs text-700" v-if="data.started_at">
                                <div><i class="pi pi-sign-in mr-1 text-primary"></i> Mulai: <b>{{ formatDate(data.started_at) }}</b></div>
                                <div v-if="data.submitted_at"><i class="pi pi-sign-out mr-1 text-danger"></i> Kumpul: <b>{{ formatDate(data.submitted_at) }}</b></div>
                            </div>
                            <span v-else class="text-500 italic text-sm">-</span>
                        </template>
                    </Column>

                    <Column field="score" header="Nilai CTT (%)" sortable style="width: 14%">
                        <template #body="{ data }">
                            <div v-if="data.score !== null" class="flex flex-column gap-1">
                                <span 
                                    class="text-xl font-bold p-2 border-round text-center"
                                    :class="data.score >= 70 ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50'"
                                >
                                    {{ data.score }}
                                </span>
                                <small v-if="data.ctt_result?.rank" class="text-xs text-center text-slate-600">
                                    Peringkat: <b>#{{ data.ctt_result.rank }}</b>
                                </small>
                            </div>
                            <span v-else class="text-500 italic text-sm">-</span>
                        </template>
                    </Column>

                    <Column header="IRT (Theta θ)" style="width: 14%">
                        <template #body="{ data }">
                            <div v-if="data.irt_ability" class="flex flex-column gap-1 text-xs">
                                <div class="font-mono bg-purple-50 text-purple-700 p-2 border-round text-center">
                                    <b>θ: {{ data.irt_ability.theta }}</b>
                                    <div class="text-2xs text-purple-600">SE: ±{{ data.irt_ability.standard_error }}</div>
                                </div>
                                <div class="text-center font-semibold text-slate-700">
                                    Skala: <span class="text-purple-700">{{ data.irt_ability.scaled_score }}</span>
                                </div>
                            </div>
                            <span v-else class="text-500 italic text-xs text-center block">-</span>
                        </template>
                    </Column>

                    <Column header="Aksi Guru" style="width: 16%">
                        <template #body="{ data }">
                            <div class="flex gap-1 flex-wrap" v-if="data.student_exam_id">
                                <Button 
                                    v-if="data.status === 'started'"
                                    icon="pi pi-check" 
                                    severity="success" 
                                    text 
                                    rounded 
                                    v-tooltip.top="'Selesaikan Ujian Paksa'" 
                                    @click="confirmForceSubmit(data)" 
                                />
                                <Button 
                                    v-if="data.status === 'logged_out'"
                                    icon="pi pi-sign-in" 
                                    severity="warn" 
                                    text 
                                    rounded 
                                    v-tooltip.top="'Izinkan Masuk Kembali (Pelanggaran ke-3)'"
                                    @click="confirmAllowReenter(data)" 
                                />
                                <Button 
                                    v-if="['submitted', 'logged_out'].includes(data.status) && !data.is_blocked && data.warning_count < 4"
                                    icon="pi pi-undo" 
                                    severity="info" 
                                    text 
                                    rounded 
                                    v-tooltip.top="'Reset Status Selesai (Buka Ujian Kembali, Jawaban Tidak Hilang)'" 
                                    @click="confirmReopen(data)" 
                                />
                                <Button 
                                    icon="pi pi-refresh" 
                                    severity="danger" 
                                    text 
                                    rounded 
                                    v-tooltip.top="(data.is_blocked || data.warning_count >= 4) ? 'Siswa Diblokir (Strike 4+): Reset Total Sesi' : 'Reset Total Sesi Ujian'" 
                                    @click="confirmReset(data)" 
                                />
                            </div>
                            <span v-else class="text-500 italic text-sm">-</span>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- TAB CONTENT 2: RINGKASAN CTT -->
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
                                    <span class="block text-500 font-medium mb-1">Rata-Rata Nilai</span>
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

                    <div class="col-12 md:col-6 lg:col-3">
                        <div class="surface-card shadow-2 p-3 border-round border-left-4 border-purple-500">
                            <div class="flex justify-content-between mb-3">
                                <div>
                                    <span class="block text-500 font-medium mb-1">Total Peserta Submit</span>
                                    <div class="text-900 font-bold text-2xl">{{ ctt_summary.total_participants }} Siswa</div>
                                </div>
                                <div class="flex align-items-center justify-content-center bg-purple-100 border-round" style="width:2.5rem;height:2.5rem">
                                    <i class="pi pi-check-circle text-purple-500 text-xl"></i>
                                </div>
                            </div>
                            <span class="text-purple-600 text-xs font-semibold">Tersimpan di DB ter-decouple</span>
                        </div>
                    </div>
                </div>

                <div v-else class="p-4 bg-slate-50 border-round text-center text-600">
                    <i class="pi pi-info-circle text-2xl block mb-2 text-primary"></i>
                    Belum ada ringkasan analisis CTT. Klik tombol <b>"Kalkulasi Ulang Analisis"</b> untuk memproses.
                </div>
            </div>

            <!-- TAB CONTENT 3: ANALISIS BUTIR SOAL CTT -->
            <div v-show="activeTab === 'ctt_items'" class="surface-card p-4 shadow-2 border-round">
                <DataTable v-bind="$pagination({ label: 'ctt_item_analyses' })" :value="ctt_item_analyses" stripedRows tableStyle="min-width: 50rem">
                    <template #empty> Belum ada data analisis butir soal CTT. </template>

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
                            <h4 class="font-bold m-0 text-yellow-900">Estimasi IRT Nonaktif (Peserta N &lt; 100)</h4>
                            <p class="m-0 text-xs mt-1">{{ irt_job_status.error_message }}</p>
                        </div>
                    </div>

                    <div v-else-if="irt_job_status?.status === 'sent_to_microservice' || irt_job_status?.status === 'processing'" class="p-3 bg-blue-50 border-round border border-blue-300 flex align-items-center gap-3 text-blue-800">
                        <i class="pi pi-spin pi-spinner text-2xl text-blue-600"></i>
                        <div>
                            <h4 class="font-bold m-0 text-blue-900">Estimasi Model IRT Sedang Diproses di Python Microservice</h4>
                            <p class="m-0 text-xs mt-1">Total Peserta: <b>{{ irt_job_status.total_participants }} Siswa</b> | Progress: {{ irt_job_status.progress_percent }}%</p>
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
                        <template #empty> Belum ada data parameter IRT. Pastikan peserta N &ge; 100 dan Microservice Python aktif. </template>

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
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import SplitButton from 'primevue/splitbutton';
import Tag from 'primevue/tag';

const props = defineProps({
    exam: Object,
    results: {
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
const toast = useToast();
const isRecalculating = ref(false);
const activeTab = ref('students');

const hasKickedStudents = computed(() => 
    props.results.some(r => r.status === 'logged_out')
);

const activeStudentsCount = computed(() => 
    props.results.filter(r => r.status === 'started' || r.status === 'login').length
);

const confirmForceSubmitAll = () => {
    confirm.require({
        message: `Selesaikan ujian secara paksa untuk seluruh siswa yang masih aktif (${activeStudentsCount.value} siswa)? Jawaban yang telah tersimpan masing-masing siswa akan langsung dinilai dan analisis CTT diperbarui serentak.`,
        header: 'Selesaikan Semua Siswa Aktif (Massal)',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-warning',
        acceptLabel: 'Ya, Selesaikan Semua',
        rejectLabel: 'Batal',
        accept: () => {
            router.post(route('cbt.exams.results.force-submit-all', props.exam.id));
        }
    });
};

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
            window.open(route('cbt.exams.export_answers_pdf', props.exam.id), '_blank');
        }
    },
    {
        label: 'Cetak Matriks Dikotomi',
        icon: 'pi pi-table',
        command: () => {
            window.open(route('cbt.exams.export_dichotomous_pdf', props.exam.id), '_blank');
        }
    },
    {
        label: 'Cetak Daftar Nilai Siswa',
        icon: 'pi pi-list',
        command: () => {
            window.open(route('cbt.exams.export_daftar_nilai_pdf', props.exam.id), '_blank');
        }
    }
];

const printFullReport = () => {
    window.open(route('cbt.exams.export_full_report_pdf', props.exam.id), '_blank');
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
    router.post(route('cbt.exams.recalculate_analytics', props.exam.id), {}, {
        onFinish: () => {
            isRecalculating.value = false;
        }
    });
};

const formatStatusLabel = (data) => {
    if (!data) return '-';
    const status = typeof data === 'string' ? data : data.status;
    const submitType = typeof data === 'object' ? data.submit_type : null;
    const warningCount = typeof data === 'object' ? (data.warning_count || 0) : 0;
    const isBlocked = typeof data === 'object' ? !!data.is_blocked : false;

    if (status === 'submitted') {
        if (submitType === 'student' || (!submitType && warningCount < 4 && !isBlocked)) {
            return 'Selesai Mandiri';
        }
        if (submitType === 'system_timeout') {
            return 'Selesai Sistem (Waktu Habis)';
        }
        if (submitType === 'system_proctor') {
            return 'Selesai Sistem (Pengawas)';
        }
        if (submitType === 'system_teacher') {
            return 'Selesai Sistem (Dipaksa Guru)';
        }
        if (submitType === 'system_cheat' || warningCount >= 4 || isBlocked) {
            return 'Selesai Sistem (Pelanggaran / Ban)';
        }
        return 'Selesai Sistem';
    }

    const labels = {
        'not_started': 'Belum Mulai',
        'login':       'Menunggu Masuk',
        'started':     'Sedang Ujian',
        'logged_out':  'Dikeluarkan (Pelanggaran 3x)',
        'blocked':     'Diblokir Permanen',
    };
    return labels[status] || status;
};

const formatStatusSeverity = (data) => {
    if (!data) return 'info';
    const status = typeof data === 'string' ? data : data.status;
    const submitType = typeof data === 'object' ? data.submit_type : null;
    const warningCount = typeof data === 'object' ? (data.warning_count || 0) : 0;
    const isBlocked = typeof data === 'object' ? !!data.is_blocked : false;

    if (status === 'submitted') {
        if (submitType === 'student' || (!submitType && warningCount < 4 && !isBlocked)) {
            return 'success';
        }
        if (submitType === 'system_timeout') {
            return 'info';
        }
        if (submitType === 'system_proctor') {
            return 'help';
        }
        if (submitType === 'system_teacher') {
            return 'warn';
        }
        if (submitType === 'system_cheat' || warningCount >= 4 || isBlocked) {
            return 'danger';
        }
        return 'contrast';
    }

    const severities = {
        'not_started': 'secondary',
        'login':       'info',
        'started':     'warn',
        'logged_out':  'danger',
        'blocked':     'danger',
    };
    return severities[status] || 'info';
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

const confirmForceSubmit = (data) => {
    confirm.require({
        message: `Selesaikan ujian secara paksa untuk siswa "${data.name}"? Jawaban yang tersimpan saat ini akan langsung dihitung nilainya.`,
        header: 'Selesaikan Ujian Paksa',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-success',
        accept: () => {
            router.post(route('cbt.exams.results.force-submit', { id: props.exam.id, student_exam_id: data.student_exam_id }));
        }
    });
};

const confirmAllowReenter = (data) => {
    confirm.require({
        message: `Izinkan siswa "${data.name}" untuk masuk kembali ke ujian?`,
        header: 'Izinkan Masuk Kembali',
        icon: 'pi pi-sign-in',
        acceptClass: 'p-button-warning',
        accept: () => {
            router.post(route('cbt.exams.results.allow-reenter', { id: props.exam.id, student_exam_id: data.student_exam_id }));
        }
    });
};

const confirmReopen = (data) => {
    confirm.require({
        message: `Buka kembali ujian untuk siswa "${data.name}"?`,
        header: 'Reset Status Selesai',
        icon: 'pi pi-undo',
        acceptClass: 'p-button-info',
        accept: () => {
            router.post(route('cbt.exams.results.reopen', { id: props.exam.id, student_exam_id: data.student_exam_id }));
        }
    });
};

const confirmReset = (data) => {
    confirm.require({
        message: `Hapus sesi pengerjaan ujian untuk siswa "${data.name}"? Semua jawaban tersimpan akan dihapus.`,
        header: 'Reset Ujian Siswa',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('cbt.exams.results.reset', { id: props.exam.id, student_exam_id: data.student_exam_id }));
        }
    });
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
