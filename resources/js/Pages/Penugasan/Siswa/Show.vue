<template>
    <SiswaLayout :title="assignment.title">
        <div class="card p-4 surface-card border-round-xl shadow-2">
            <!-- Header Bar -->
            <div class="flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center mb-4 gap-3 border-bottom-1 border-200 pb-3">
                <div>
                    <span class="text-xs uppercase font-bold text-primary tracking-wider">Tugas Mata Pelajaran</span>
                    <h2 class="text-2xl font-bold text-900 m-0 mt-1">{{ assignment.title }}</h2>
                    <div class="flex flex-wrap align-items-center gap-3 text-sm text-600 mt-2">
                        <span><i class="pi pi-book mr-1 text-primary"></i>{{ assignment.subject?.name }}</span>
                        <span>•</span>
                        <span><i class="pi pi-user mr-1 text-primary"></i>Guru: {{ assignment.teacher?.name }}</span>
                        <span>•</span>
                        <span><i class="pi pi-clock mr-1 text-red-500"></i>Deadline: {{ formatDate(assignment.due_at) }}</span>
                    </div>
                </div>
                <Button 
                    label="Kembali ke Dashboard" 
                    icon="pi pi-arrow-left" 
                    severity="secondary" 
                    outlined 
                    @click="router.get(route('student.dashboard'))" 
                />
            </div>

            <!-- Petunjuk / Deskripsi -->
            <!-- Petunjuk / Deskripsi -->
            <div v-if="assignment.description" class="p-3 bg-blue-50 border-round-xl border border-blue-200 mb-4">
                <span class="block font-bold text-blue-900 text-sm mb-1"><i class="pi pi-info-circle mr-1"></i>Petunjuk Tugas:</span>
                <p class="m-0 text-blue-800 text-sm whitespace-pre-wrap">{{ assignment.description }}</p>
            </div>

            <!-- INFORMASI PENILAIAN KINERJA / PRAKTIK -->
            <div v-if="assignment.type === 'performance'" class="p-3 surface-100 border-round-xl border border-300 mb-4 flex align-items-center gap-3">
                <i class="pi pi-check-square text-purple-600 text-3xl"></i>
                <div>
                    <span class="font-bold text-900 block">Penugasan Penilaian Kinerja / Praktik</span>
                    <small class="text-600">Penilaian tugas ini dilakukan langsung oleh Guru Mata Pelajaran secara berkala. Siswa tidak perlu mengumpulkan jawaban secara online.</small>
                </div>
            </div>

            <!-- HASIL / REVIEW JIKA SUDAH DISUBMIT / DINILAI GURU DAN TIDAK SEDANG DIBUKA AKSES EDIT DAN BUKAN DRAF -->
            <div v-if="submission && submission.status !== 'draft' && !submission.is_editable" class="mb-4">
                <div class="p-4 surface-card border-round-xl shadow-2 border-top-4 border-green-500 mb-4">
                    <div class="flex flex-column md:flex-row justify-content-between align-items-center gap-3">
                        <div>
                            <div class="flex align-items-center gap-2">
                                <i class="pi pi-check-circle text-green-600 text-2xl"></i>
                                <span class="text-xl font-bold text-green-900">{{ assignment.type === 'performance' ? 'Penilaian Telah Diinput Guru' : 'Tugas Telah Dikumpulkan' }}</span>
                            </div>
                            <small class="text-500 block mt-1" v-if="submission.submitted_at">Tercatat pada: {{ formatDate(submission.submitted_at) }}</small>
                            <div class="mt-2 p-2 bg-gray-50 border-round border border-gray-300 text-gray-700 text-xs flex align-items-center gap-2">
                                <i class="pi pi-lock"></i>
                                <span>Jawaban terkunci dan tidak dapat diubah kecuali akses edit dibuka kembali oleh guru.</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="block text-xs font-bold uppercase text-500">Nilai Akhir Tugas</span>
                            <div v-if="assignment.is_grades_published">
                                <span class="text-3xl font-mono font-bold" :class="submission.total_score >= 75 ? 'text-green-700' : 'text-orange-700'">
                                    {{ submission.total_score }} / 100
                                </span>
                            </div>
                            <div v-else>
                                <Tag value="Menunggu Publikasi Guru" severity="warn" class="px-3 py-1 font-bold text-xs" />
                            </div>
                        </div>
                    </div>

                    <div v-if="assignment.is_grades_published && submission.teacher_notes" class="mt-3 p-3 bg-amber-50 border-round-lg border border-amber-200">
                        <span class="font-bold text-amber-900 text-xs block uppercase mb-1">Catatan Evaluasi Guru:</span>
                        <p class="m-0 text-amber-900 text-sm">{{ submission.teacher_notes }}</p>
                    </div>
                    <div v-else-if="!assignment.is_grades_published" class="mt-3 p-3 bg-blue-50 border-round-lg border border-blue-200 text-blue-900 text-xs font-semibold">
                        <i class="pi pi-info-circle mr-1"></i>Nilai dan catatan evaluasi akan ditampilkan setelah guru mempublikasikan nilai.
                    </div>
                </div>

                <!-- LIST JAWABAN TERSIMPAN / INDIKATOR DINILAI -->
                <div class="flex justify-content-between align-items-center mb-3">
                    <h3 class="text-lg font-bold text-900 m-0">{{ assignment.type === 'performance' ? 'Hasil Penilaian Indikator Kinerja:' : 'Lembar Jawaban Anda:' }}</h3>
                    <Button 
                        :icon="showAnswers ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" 
                        :label="showAnswers ? 'Sembunyikan' : 'Lihat Jawaban'" 
                        size="small" 
                        severity="secondary"
                        text 
                        @click="showAnswers = !showAnswers" 
                    />
                </div>
                
                <div v-show="showAnswers" class="flex flex-column gap-3 transition-all">
                    <div 
                        v-for="(ans, idx) in submission.answers" 
                        :key="ans.id" 
                        class="p-4 surface-100 border-round-xl border border-200"
                    >
                        <div class="flex justify-content-between align-items-center mb-2">
                            <span class="font-bold text-900 text-sm">{{ assignment.type === 'performance' ? 'Indikator' : 'Soal' }} #{{ idx + 1 }}</span>
                            <div class="flex align-items-center gap-2">
                                <Tag 
                                    v-if="assignment.is_grades_published && ans.score !== null && ans.score !== undefined" 
                                    :value="`Nilai: ${ans.score} / ${getQuestion(ans).max_score || 10} Poin`" 
                                    severity="success" 
                                    class="font-bold"
                                />
                                <Tag 
                                    v-else
                                    :value="`Bobot Max: ${getQuestion(ans).max_score || 10} Poin`" 
                                    severity="secondary" 
                                />
                                <Tag 
                                    :value="getQuestionTypeLabel(getQuestion(ans).type)" 
                                    :severity="getQuestionTypeSeverity(getQuestion(ans).type)" 
                                />
                            </div>
                        </div>
                        <div class="font-semibold text-800 text-sm mb-3 bg-white p-3 border-round rich-content" v-html="getQuestion(ans).question_text || '(Teks Soal)'"></div>

                        <div class="p-3 bg-gray-100 text-gray-900 border-round-lg border border-gray-300 mb-2" v-if="assignment.type !== 'performance'">
                            <span class="block text-xs text-blue-700 font-bold uppercase mb-1"><i class="pi pi-check-square mr-1"></i>Jawaban Anda:</span>
                            <p class="m-0 text-sm font-bold font-sans text-900 whitespace-pre-wrap">{{ ans.answer_text || '(Tidak dijawab)' }}</p>

                            <div v-if="ans.url_upload" class="mt-2 text-xs flex align-items-center gap-2 pt-2 border-top-1 border-gray-300">
                                <i class="pi pi-link text-primary"></i>
                                <span class="font-semibold text-700">URL Terlampir:</span>
                                <a :href="ans.url_upload" target="_blank" class="text-primary underline font-bold truncate max-w-20rem">
                                    {{ ans.url_upload }}
                                </a>
                            </div>
                        </div>

                        <div v-if="ans.feedback" class="mt-2 text-xs text-green-700 font-semibold">
                            Feedback Guru: {{ ans.feedback }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- DAFTAR INDIKATOR UNTUK PENILAIAN KINERJA (MODE VIEW ONLY JIKA BELUM DINILAI) -->
            <div v-else-if="assignment.type === 'performance'" class="flex flex-column gap-3 mb-4">
                <h3 class="text-lg font-bold text-900 mb-2">Daftar Indikator Penilaian Kinerja:</h3>
                <div 
                    v-for="(q, idx) in assignment.questions" 
                    :key="q.id" 
                    class="p-4 surface-50 border-round-xl border border-300 shadow-1"
                >
                    <div class="flex justify-content-between align-items-center mb-2">
                        <span class="font-bold text-purple-700 text-base">Indikator #{{ idx + 1 }}</span>
                        <div class="flex align-items-center gap-2">
                            <Tag :value="`Bobot Max: ${q.max_score} Poin`" severity="help" class="font-bold" />
                            <Tag :value="getQuestionTypeLabel(q.type)" :severity="getQuestionTypeSeverity(q.type)" />
                        </div>
                    </div>

                    <div class="font-bold text-900 text-base leading-relaxed rich-content" v-html="q.question_text"></div>
                </div>
            </div>

            <!-- FORM PENGERJAAN SISWA (JIKA BELUM DISUBMIT / DRAF / AKSES EDIT DIBUKA GURU & BUKAN KINERJA) -->
            <div v-else-if="(isOpen || (submission && submission.is_editable) || (submission && submission.status === 'draft')) && assignment.type !== 'performance'">
                
                <!-- BANNER DRAF TERSIMPAN -->
                <div v-if="submission && submission.status === 'draft'" class="p-4 bg-blue-50 border-round-xl border border-blue-300 mb-4 shadow-1">
                    <div class="flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between gap-3">
                        <div class="flex align-items-center gap-3">
                            <i class="pi pi-file-edit text-blue-600 text-3xl"></i>
                            <div>
                                <span class="font-bold text-blue-900 block text-lg">Draf Jawaban Tersimpan</span>
                                <p class="text-blue-800 text-sm m-0 mt-1">
                                    Jawaban Anda tersimpan sebagai draf dan <strong>belum diserahkan/dikumpulkan</strong> ke guru.
                                    <span v-if="submission.updated_at" class="font-semibold text-blue-950"> (Terakhir disimpan: {{ formatDate(submission.updated_at) }})</span>.
                                    Anda dapat terus memperbarui draf atau menekan tombol <strong>Kumpulkan Tugas Sekarang</strong> jika sudah selesai.
                                </p>
                            </div>
                        </div>
                        <Tag value="Status: Draf" severity="info" class="px-3 py-1 font-bold text-sm" />
                    </div>
                </div>

                <!-- BANNER AKSES EDIT DIBUKA OLEH GURU (SETELAH PERNAH SUBMIT) -->
                <div v-else-if="submission && submission.status !== 'draft' && submission.is_editable" class="p-4 bg-amber-50 border-round-xl border-2 border-amber-400 mb-4 shadow-1">
                    <div class="flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between gap-3">
                        <div class="flex align-items-center gap-3">
                            <i class="pi pi-unlock text-amber-600 text-3xl"></i>
                            <div>
                                <span class="font-bold text-amber-900 block text-lg">
                                    {{ submission.teacher_notes?.toLowerCase().includes('ditolak') ? 'Ajuan Jawaban Ditolak / Akses Edit Dibuka' : 'Akses Edit Jawaban Telah Dibuka Oleh Guru' }}
                                </span>
                                <p class="text-amber-900 text-sm m-0 mt-1">Anda diizinkan memperbarui kembali jawaban tugas ini. Jawaban baru yang disubmit akan otomatis menggantikan jawaban sebelumnya.</p>
                            </div>
                        </div>
                        <Tag 
                            :value="submission.teacher_notes?.toLowerCase().includes('ditolak') ? 'Ajuan Ditolak (Perlu Perbaikan)' : 'Mode Edit / Resubmit'" 
                            :severity="submission.teacher_notes?.toLowerCase().includes('ditolak') ? 'danger' : 'warn'" 
                            class="px-3 py-1 font-bold text-sm" 
                        />
                    </div>
                    <div v-if="submission.teacher_notes" class="mt-3 p-3 bg-white border-round-lg border border-red-300">
                        <span class="block text-xs font-bold text-red-700 uppercase mb-1">Catatan / Alasan dari Guru:</span>
                        <p class="m-0 text-sm font-semibold text-900">{{ submission.teacher_notes }}</p>
                    </div>
                </div>

                <form @submit.prevent="confirmSubmit">
                    <div class="flex flex-column gap-4 mb-4">
                        <div 
                            v-for="(q, idx) in assignment.questions" 
                            :key="q.id" 
                            class="p-4 surface-50 border-round-xl border border-300 shadow-1"
                        >
                            <div class="flex justify-content-between align-items-center mb-2">
                                <span class="font-bold text-base text-primary">Soal No. {{ idx + 1 }}</span>
                                <div class="flex align-items-center gap-2">
                                    <Tag :value="`Bobot Max: ${q.max_score} Poin`" severity="warn" class="font-bold" />
                                    <Tag :value="getQuestionTypeLabel(q.type)" :severity="getQuestionTypeSeverity(q.type)" />
                                </div>
                            </div>

                            <div class="font-bold text-900 text-base mb-3 leading-relaxed rich-content" v-html="q.question_text"></div>

                            <!-- SOAL PILIHAN GANDA -->
                            <div v-if="q.type === 'mcq'" class="flex flex-column gap-2 pl-2">
                                <div 
                                    v-for="opt in q.options" 
                                    :key="opt.key" 
                                    class="flex align-items-center gap-3 p-3 surface-card border-round-lg border border-200 cursor-pointer hover:surface-100"
                                    @click="form.answers[idx].answer_text = opt.key"
                                >
                                    <RadioButton 
                                        v-model="form.answers[idx].answer_text" 
                                        :value="opt.key" 
                                        :inputId="`q-${q.id}-opt-${opt.key}`" 
                                    />
                                    <label :for="`q-${q.id}-opt-${opt.key}`" class="font-semibold text-900 cursor-pointer flex-1 text-sm">
                                        <strong class="text-primary mr-2">{{ opt.key }}.</strong> {{ opt.text }}
                                    </label>
                                </div>
                            </div>

                            <!-- SOAL URAIAN (ESSAY) -->
                            <div v-if="q.type === 'essay'" class="flex flex-column gap-3">
                                <div>
                                    <label class="block font-semibold mb-1 text-sm">Tuliskan Jawaban Uraian Anda <span class="text-red-500">*</span></label>
                                    <Textarea 
                                        v-model="form.answers[idx].answer_text" 
                                        rows="4" 
                                        placeholder="Ketikkan uraian jawaban secara lengkap di sini..." 
                                        class="w-full"
                                        style="user-select: auto; -webkit-user-select: auto;"
                                        @paste.stop
                                        @copy.stop
                                        @contextmenu.stop
                                    />
                                </div>

                                <!-- INPUT UPLOAD URL (GOOGLE DRIVE) -->
                                <div v-if="q.allow_url_upload" class="p-3 bg-yellow-50 border-round-lg border border-yellow-200">
                                    <label class="block font-bold text-yellow-900 text-xs uppercase mb-1">
                                        <i class="pi pi-google mr-1"></i>Tautan URL Google Drive / Dokumen (Opsional)
                                    </label>
                                    <InputText 
                                        v-model="form.answers[idx].url_upload" 
                                        placeholder="https://drive.google.com/file/d/... atau URL pengumpulan" 
                                        class="w-full bg-white text-sm"
                                        style="user-select: auto; -webkit-user-select: auto;"
                                        @paste.stop
                                        @copy.stop
                                        @contextmenu.stop
                                    />
                                    <small class="text-yellow-800 text-xs block mt-1">Pastikan hak akses tautan Google Drive diset ke 'Siapa saja yang memiliki link'.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 surface-100 border-round-xl flex flex-column md:flex-row justify-content-between align-items-center gap-3">
                        <div class="flex align-items-center gap-2 text-600 font-semibold text-xs">
                            <i class="pi pi-info-circle text-primary"></i>
                            <span>Simpan draf untuk melanjutkan nanti, atau kumpulkan jika seluruh jawaban sudah lengkap.</span>
                        </div>
                        <div class="flex align-items-center gap-2 w-full md:w-auto justify-content-end">
                            <Button 
                                label="Simpan sebagai Draf" 
                                icon="pi pi-save" 
                                severity="secondary" 
                                outlined
                                size="large"
                                :loading="isSavingDraft"
                                :disabled="form.processing"
                                class="font-bold px-4 py-3 border-round-xl" 
                                type="button" 
                                @click="saveAsDraft"
                            />
                            <Button 
                                :label="submission && submission.status !== 'draft' ? 'Update & Kumpulkan Kembali' : 'Kumpulkan Tugas Sekarang'" 
                                icon="pi pi-send" 
                                severity="success" 
                                size="large" 
                                :loading="form.processing"
                                :disabled="isSavingDraft"
                                raised 
                                class="font-bold px-5 py-3 border-round-xl" 
                                type="submit" 
                            />
                        </div>
                    </div>
                </form>
            </div>

            <!-- JIKA WAKTU SUDAH DITUTUP (CLOSED) -->
            <div v-else class="text-center p-5 surface-100 border-round-xl border-dashed border-2 text-500">
                <i class="pi pi-lock text-500 text-4xl block mb-2 opacity-60"></i>
                <h3 class="text-900 font-bold m-0">Waktu Pengumpulan Tugas Telah Ditutup</h3>
                <p class="text-600 text-sm mt-1">Tugas ini tidak lagi menerima pengumpulan jawaban baru.</p>
            </div>
        </div>
    </SiswaLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
    assignment: Object,
    submission: Object,
    isOpen: Boolean,
    isClosed: Boolean,
    isDraft: Boolean,
    isEditable: Boolean,
    student: Object,
});

const confirm = useConfirm();
const showAnswers = ref(false);
const isSavingDraft = ref(false);

const form = useForm({
    answers: props.assignment.questions ? props.assignment.questions.map(q => {
        const existingAns = props.submission?.answers?.find(a => a.question_id === q.id);
        return {
            question_id: q.id,
            answer_text: existingAns ? (existingAns.answer_text || '') : '',
            url_upload: existingAns ? (existingAns.url_upload || '') : '',
        };
    }) : [],
});

const saveAsDraft = () => {
    isSavingDraft.value = true;
    form.post(route('student.assignments.draft', props.assignment.id), {
        preserveScroll: true,
        onFinish: () => {
            isSavingDraft.value = false;
        }
    });
};

const confirmSubmit = () => {
    const isUpdatingFinal = props.submission && props.submission.status !== 'draft';
    const msg = isUpdatingFinal
        ? 'Apakah Anda yakin ingin memperbarui dan mengumpulkan kembali jawaban tugas ini? Jawaban baru akan menggantikan jawaban sebelumnya.'
        : 'Apakah Anda yakin ingin mengumpulkan tugas ini? Jawaban tidak dapat diubah kembali setelah disubmit.';
    const header = isUpdatingFinal ? 'Konfirmasi Edit Jawaban Tugas' : 'Konfirmasi Pengumpulan Tugas';
    confirm.require({
        message: msg,
        header: header,
        icon: 'pi pi-send',
        acceptClass: 'p-button-success',
        acceptLabel: isUpdatingFinal ? 'Ya, Update Jawaban' : 'Ya, Kumpulkan',
        rejectLabel: 'Batal',
        accept: () => {
            form.post(route('student.assignments.submit', props.assignment.id));
        }
    });
};

const getQuestion = (ans) => {
    if (ans?.question && ans.question.question_text) return ans.question;
    return props.assignment?.questions?.find(q => q.id === ans?.question_id) || {};
};

const getQuestionTypeLabel = (type) => {
    if (type === 'essay') return 'Uraian';
    if (type === 'mcq') return 'Pilihan Ganda';
    if (type === 'performance_indicator') return 'Indikator Kinerja';
    return 'Soal';
};

const getQuestionTypeSeverity = (type) => {
    if (type === 'essay') return 'info';
    if (type === 'mcq') return 'success';
    if (type === 'performance_indicator') return 'help';
    return 'secondary';
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
