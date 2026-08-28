<template>
    <AppLayout title="Rekap Penyelesaian & Penilaian Tugas">
        <div class="card p-4 surface-card border-round-xl shadow-2">
            <!-- Header Bar -->
            <div class="flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center mb-4 gap-3 border-bottom-1 border-200 pb-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-check-square text-primary text-2xl"></i>
                        Rekap Penyelesaian Tugas: {{ assignment.title }}
                    </h2>
                    <div class="flex align-items-center gap-3 text-sm text-600 mt-1 flex-wrap">
                        <span><i class="pi pi-book mr-1 text-primary"></i>{{ assignment.subject?.name }}</span>
                        <span>•</span>
                        <span><i class="pi pi-users mr-1 text-primary"></i>Kelas {{ assignment.classroom?.name }}</span>
                        <span>•</span>
                        <Tag :value="assignment.grading_component?.name || 'Umum'" severity="info" />
                        <span>•</span>
                        <Tag 
                            :value="assignment.is_grades_published ? 'Nilai Dipublikasikan' : 'Nilai Belum Dipublikasikan'" 
                            :severity="assignment.is_grades_published ? 'success' : 'warn'" 
                        />
                    </div>
                </div>
                <div class="flex align-items-center gap-2 flex-wrap">
                    <Button 
                        v-if="draftCount > 0"
                        :label="'Paksa Kumpulkan Semua Draf (' + draftCount + ')'" 
                        icon="pi pi-check-circle" 
                        severity="warning" 
                        outlined 
                        v-tooltip.top="'Paksa pengumpulan seluruh draf jawaban siswa yang belum disubmit'"
                        @click="forceSubmitAllDrafts()" 
                    />
                    <Button 
                        v-if="submittedCount > 0"
                        label="Buka Semua Akses Edit" 
                        icon="pi pi-unlock" 
                        severity="info" 
                        outlined 
                        @click="unlockAllEdit()" 
                    />
                    <Button 
                        :label="assignment.is_grades_published ? 'Tarik Publikasi Nilai' : 'Publish Nilai ke Siswa'" 
                        :icon="assignment.is_grades_published ? 'pi pi-eye-slash' : 'pi pi-send'" 
                        :severity="assignment.is_grades_published ? 'warn' : 'success'" 
                        raised 
                        @click="togglePublishGrades()" 
                    />
                    <Button 
                        label="Kembali" 
                        icon="pi pi-arrow-left" 
                        severity="secondary" 
                        outlined 
                        @click="router.get(route('guru.assignments.index'))" 
                    />
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid mb-4">
                <div class="col-12 md:col-3">
                    <div class="surface-card p-3 border-round-xl shadow-1 border-left-4 border-blue-500 flex justify-content-between align-items-center">
                        <div>
                            <span class="block text-500 text-xs uppercase font-bold">Total Murid</span>
                            <span class="text-2xl font-bold text-900">{{ studentsList.length }}</span>
                        </div>
                        <i class="pi pi-users text-blue-500 text-3xl"></i>
                    </div>
                </div>
                <div class="col-12 md:col-3">
                    <div class="surface-card p-3 border-round-xl shadow-1 border-left-4 border-green-500 flex justify-content-between align-items-center">
                        <div>
                            <span class="block text-500 text-xs uppercase font-bold">Sudah Mengumpulkan</span>
                            <span class="text-2xl font-bold text-green-700">{{ submittedCount }}</span>
                        </div>
                        <i class="pi pi-check-circle text-green-500 text-3xl"></i>
                    </div>
                </div>
                <div class="col-12 md:col-3">
                    <div class="surface-card p-3 border-round-xl shadow-1 border-left-4 border-indigo-500 flex justify-content-between align-items-center">
                        <div>
                            <span class="block text-500 text-xs uppercase font-bold">Draf Tersimpan</span>
                            <span class="text-2xl font-bold text-indigo-700">{{ draftCount }}</span>
                        </div>
                        <i class="pi pi-file-edit text-indigo-500 text-3xl"></i>
                    </div>
                </div>
                <div class="col-12 md:col-3">
                    <div class="surface-card p-3 border-round-xl shadow-1 border-left-4 border-orange-500 flex justify-content-between align-items-center">
                        <div>
                            <span class="block text-500 text-xs uppercase font-bold">Belum Mengerjakan</span>
                            <span class="text-2xl font-bold text-orange-700 font-mono">{{ studentsList.length - submittedCount - draftCount }}</span>
                        </div>
                        <i class="pi pi-clock text-orange-500 text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Table Submissions -->
            <DataTable v-bind="$pagination({ label: 'studentslist' })" 
                :value="studentsList" 
                responsiveLayout="scroll" 
                stripedRows
                class="p-datatable-sm"
            >
                <Column field="full_name" header="Nama Siswa" sortable>
                    <template #body="slotProps">
                        <div class="font-bold text-900">{{ slotProps.data.full_name }}</div>
                        <small class="text-500">NISN: {{ slotProps.data.nisn || '-' }}</small>
                    </template>
                </Column>

                <Column header="Status Pengerjaan">
                    <template #body="slotProps">
                        <Tag 
                            v-if="!slotProps.data.submission" 
                            value="Belum Mengerjakan" 
                            severity="danger" 
                        />
                        <Tag 
                            v-else-if="slotProps.data.submission.status === 'draft'" 
                            value="Draf (Belum Submit)" 
                            severity="info" 
                            icon="pi pi-file-edit"
                        />
                        <Tag 
                            v-else-if="slotProps.data.submission.status === 'submitted'" 
                            value="Perlu Diperiksa Guru" 
                            severity="warning" 
                        />
                        <Tag 
                            v-else 
                            value="Sudah Dinilai" 
                            severity="success" 
                        />
                        <Tag 
                            v-if="slotProps.data.submission?.is_editable && slotProps.data.submission.status !== 'draft'" 
                            :value="slotProps.data.submission.teacher_notes?.toLowerCase().includes('ditolak') ? 'Ajuan Ditolak (Buka Edit)' : 'Akses Edit Dibuka'" 
                            :severity="slotProps.data.submission.teacher_notes?.toLowerCase().includes('ditolak') ? 'danger' : 'info'" 
                            icon="pi pi-unlock"
                            class="ml-1 mt-1 block w-max font-bold text-xs" 
                        />
                        <Tag 
                            v-else-if="slotProps.data.submission && slotProps.data.submission.status !== 'draft'" 
                            value="Terkunci (Tidak Bisa Edit)" 
                            severity="secondary" 
                            icon="pi pi-lock"
                            class="ml-1 mt-1 block w-max text-xs opacity-80" 
                        />
                        <Tag 
                            v-if="slotProps.data.submission?.has_duplicate" 
                            :value="'Indikasi Duplikat (Grup ' + (slotProps.data.submission.duplicate_groups?.join(', ') || '-') + ')'" 
                            severity="danger" 
                            icon="pi pi-exclamation-triangle"
                            class="ml-1 mt-2 block w-max font-bold text-xs bg-red-900 text-white border-1 border-red-500" 
                        />
                    </template>
                </Column>

                <Column header="Waktu Submit">
                    <template #body="slotProps">
                        <span v-if="slotProps.data.submission?.submitted_at" class="text-xs font-semibold text-600">
                            {{ slotProps.data.submission.submitted_at }}
                        </span>
                        <span v-else-if="slotProps.data.submission?.status === 'draft'" class="text-xs text-blue-600 font-semibold">
                            (Draf Tersimpan)
                        </span>
                        <span v-else class="text-xs text-400 font-italic">-</span>
                    </template>
                </Column>

                <Column header="Nilai Akhir (Max 100)" class="text-center">
                    <template #body="slotProps">
                        <div v-if="slotProps.data.submission && slotProps.data.submission.status !== 'draft'" class="flex flex-column align-items-center">
                            <span class="text-xl font-bold" :class="slotProps.data.submission.total_score >= 75 ? 'text-green-700' : 'text-orange-700'">
                                {{ slotProps.data.submission.total_score }}
                            </span>
                            <small class="text-500 text-xs" v-if="slotProps.data.submission.status === 'submitted'">
                                (Auto Similarity)
                            </small>
                        </div>
                        <span v-else-if="slotProps.data.submission?.status === 'draft'" class="text-xs text-blue-600 font-semibold">
                            (Draf)
                        </span>
                        <span v-else class="text-400 font-italic text-sm">-</span>
                    </template>
                </Column>

                <Column header="Intervensi & Detail" class="text-right">
                    <template #body="slotProps">
                        <div class="flex align-items-center justify-content-end gap-2 flex-wrap" v-if="slotProps.data.submission">
                            <!-- AKSI JIKA STATUS MASIH DRAF -->
                            <template v-if="slotProps.data.submission.status === 'draft'">
                                <Button 
                                    label="Paksa Kumpulkan" 
                                    icon="pi pi-check" 
                                    severity="warning" 
                                    size="small"
                                    raised
                                    v-tooltip.top="'Paksa pengumpulan draf jawaban siswa sekarang dan hitung nilai otomatis'"
                                    @click="forceSubmitSingle(slotProps.data)" 
                                />
                                <Button 
                                    label="Lihat Draf" 
                                    icon="pi pi-eye" 
                                    severity="secondary" 
                                    size="small"
                                    outlined
                                    @click="openGradingModal(slotProps.data)" 
                                />
                            </template>

                            <!-- AKSI JIKA SUDAH DISUBMIT / DINILAI -->
                            <template v-else>
                                <Button 
                                    label="Tolak & Buka Edit" 
                                    icon="pi pi-times-circle" 
                                    severity="danger" 
                                    size="small"
                                    outlined
                                    v-tooltip.top="'Tolak ajuan jawaban ini dan buka akses edit untuk perbaikan'"
                                    @click="openRejectModal(slotProps.data)" 
                                />
                                <Button 
                                    :label="slotProps.data.submission.is_editable ? 'Tutup Edit' : 'Buka Edit'" 
                                    :icon="slotProps.data.submission.is_editable ? 'pi pi-lock' : 'pi pi-unlock'" 
                                    :severity="slotProps.data.submission.is_editable ? 'secondary' : 'warning'" 
                                    size="small"
                                    outlined
                                    v-tooltip.top="slotProps.data.submission.is_editable ? 'Tutup akses edit agar siswa tidak dapat mengubah jawaban' : 'Izinkan siswa mengedit dan mengumpulkan ulang jawaban'"
                                    @click="toggleEditAccess(slotProps.data)" 
                                />
                                <Button 
                                    label="Periksa & Nilai" 
                                    icon="pi pi-pencil" 
                                    severity="primary" 
                                    size="small"
                                    raised
                                    @click="openGradingModal(slotProps.data)" 
                                />
                            </template>
                        </div>
                        <small v-else class="text-400">Belum ada submit</small>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- MODAL INTERVENSI PENILAIAN GURU -->
        <Dialog 
            v-model:visible="showModal" 
            header="Intervensi Penilaian Guru" 
            :modal="true" 
            :breakpoints="{ '960px': '90vw', '640px': '95vw' }"
            :style="{ width: '850px' }"
            class="p-fluid"
        >
            <div v-if="selectedStudent" class="p-2">
                <div class="p-3 bg-blue-50 border-round-xl border border-blue-200 mb-4 flex justify-content-between align-items-center">
                    <div>
                        <span class="block text-xs font-bold uppercase text-blue-800">Nama Siswa</span>
                        <span class="text-xl font-bold text-blue-900">{{ selectedStudent.full_name }}</span>
                    </div>
                    <div class="text-right">
                        <span class="block text-xs font-bold uppercase text-blue-800">Total Skor Akhir</span>
                        <span class="text-2xl font-bold text-green-700">{{ calculatedTotalScore }} / 100</span>
                    </div>
                </div>

                <!-- DAFTAR JAWABAN PER SOAL -->
                <div v-if="gradingForm.answers.length === 0" class="p-4 border-round-xl bg-yellow-50 border border-yellow-300 text-yellow-900 mb-4 text-center">
                    <i class="pi pi-exclamation-triangle text-3xl mb-2 block text-yellow-600"></i>
                    <p class="m-0 font-bold">Jawaban siswa tidak ditemukan.</p>
                    <small class="text-700 block mt-1">
                        Tugas ini kemungkinan pernah diubah/disimpan ulang sebelum pembaruan sistem, sehingga soal lama terhapus dan jawaban siswa terhapus secara otomatis dari database.
                    </small>
                </div>

                <div v-else class="flex flex-column gap-4 mb-4">
                    <div 
                        v-for="(ans, idx) in gradingForm.answers" 
                        :key="ans.id" 
                        class="p-4 border-round-xl surface-100 border border-300 shadow-1"
                    >
                        <div class="flex justify-content-between align-items-center mb-2">
                            <span class="font-bold text-900 text-base">Soal #{{ idx + 1 }}</span>
                            <Tag 
                                :value="ans.question.type === 'essay' ? 'Uraian' : 'Pilihan Ganda'" 
                                :severity="ans.question.type === 'essay' ? 'info' : 'success'" 
                            />
                        </div>

                        <div class="mb-3 text-sm text-800 font-semibold bg-white p-3 border-round border border-200 rich-content" v-html="ans.question.question_text"></div>

                        <!-- DISPLAY JAWABAN SISWA -->
                        <div class="p-3 border-round-xl mb-3" :class="ans.is_duplicate_text || ans.is_duplicate_url ? 'bg-red-900 border-2 border-red-500 shadow-2' : 'surface-800 text-white'">
                            <div class="flex justify-content-between align-items-center mb-2">
                                <span class="block text-xs font-bold uppercase" :class="ans.is_duplicate_text || ans.is_duplicate_url ? 'text-red-300' : 'text-blue-300'">Jawaban Siswa:</span>
                                <Tag v-if="ans.is_duplicate_text || ans.is_duplicate_url" :value="'Indikasi Duplikat (Menyontek Versi ' + (ans.duplicate_group_id || '-') + ')'" severity="danger" icon="pi pi-exclamation-triangle" class="text-[10px] py-1 px-2 font-bold" />
                            </div>
                            <p class="m-0 text-sm whitespace-pre-wrap font-sans text-white">{{ ans.answer_text || '(Tidak diisi)' }}</p>

                            <!-- TAMPILAN URL GOOGLE DRIVE -->
                            <div v-if="ans.url_upload" class="mt-3 p-2 border-round flex align-items-center gap-2" :class="ans.is_duplicate_url ? 'bg-red-800 border border-red-400' : 'bg-white-alpha-10'">
                                <i class="pi pi-link text-lg" :class="ans.is_duplicate_url ? 'text-red-200' : 'text-yellow-400'"></i>
                                <span class="text-xs font-bold text-white">Link Tautan Terlampir:</span>
                                <a 
                                    :href="ans.url_upload" 
                                    target="_blank" 
                                    class="font-bold underline hover:text-white text-xs truncate max-w-20rem"
                                    :class="ans.is_duplicate_url ? 'text-red-200' : 'text-blue-300'"
                                >
                                    {{ ans.url_upload }}
                                </a>
                                <i class="pi pi-external-link text-xs" :class="ans.is_duplicate_url ? 'text-red-300' : 'text-yellow-300'"></i>
                            </div>
                        </div>

                        <!-- DISPLAY AUTO KEMIRIPAN & SKOR -->
                        <div class="grid">
                            <div class="col-12 md:col-6" v-if="ans.question.type === 'essay'">
                                <div class="p-2 bg-amber-50 border-round border border-amber-200">
                                    <span class="text-xs font-bold text-amber-900 block">Auto % Kemiripan Kata Kunci:</span>
                                    <span class="text-lg font-bold text-amber-800">{{ ans.similarity_percentage ?? 0 }}%</span>
                                    <small class="block text-amber-700 text-xs">Kata kunci: {{ formatKeywords(ans.question.keywords) }}</small>
                                </div>
                            </div>

                            <div class="col-12 md:col-6" v-if="ans.question.type === 'mcq'">
                                <div class="p-2 border-round border" :class="ans.is_correct ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
                                    <span class="text-xs font-bold block" :class="ans.is_correct ? 'text-green-900' : 'text-red-900'">
                                        Status Jawaban PG: {{ ans.is_correct ? 'BENAR ✅' : 'SALAH ❌' }}
                                    </span>
                                    <small class="text-xs font-semibold">Kunci Benar: {{ ans.question.correct_answer }}</small>
                                </div>
                            </div>

                            <!-- INTERVENSI NILAI SOAL -->
                            <div class="col-12 md:col-6 field mb-0">
                                <label class="block font-bold text-xs uppercase mb-1">Skor Soal (Max: {{ ans.question.max_score }})</label>
                                <InputNumber 
                                    v-model="ans.score" 
                                    :min="0" 
                                    :max="ans.question.max_score" 
                                    class="w-full" 
                                />
                            </div>

                            <div class="col-12 field mb-0 mt-2">
                                <label class="block font-semibold text-xs mb-1">Catatan Feedback Per Soal (Opsional)</label>
                                <InputText v-model="ans.feedback" placeholder="Masukan / koreksi untuk siswa..." class="w-full text-xs" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="field mb-4">
                    <label class="block font-bold mb-1">Catatan Penilaian Guru (Keseluruhan)</label>
                    <Textarea v-model="gradingForm.teacher_notes" rows="2" placeholder="Catatan evaluasi untuk siswa..." class="w-full" />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-content-between w-full align-items-center flex-wrap gap-2">
                    <div>
                        <Button 
                            label="Tolak Ajuan & Buka Edit" 
                            icon="pi pi-times-circle" 
                            severity="danger" 
                            outlined 
                            @click="openRejectModal(selectedStudent)" 
                        />
                    </div>
                    <div class="flex gap-2">
                        <Button label="Batal" icon="pi pi-times" severity="secondary" outlined @click="showModal = false" />
                        <Button 
                            label="Simpan & Sync Ke Tabel Penilaian" 
                            icon="pi pi-check" 
                            severity="success" 
                            :loading="gradingForm.processing"
                            class="font-bold px-4" 
                            @click="saveGrading" 
                        />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- MODAL TOLAK AJUAN JAWABAN & BUKA EDIT -->
        <Dialog 
            v-model:visible="showRejectModal" 
            header="Tolak Ajuan Jawaban & Buka Akses Edit" 
            :modal="true" 
            :style="{ width: '500px' }"
            class="p-fluid"
        >
            <div v-if="studentToReject" class="p-2">
                <div class="p-3 bg-red-50 border-round-xl border border-red-200 mb-3">
                    <span class="block text-xs font-bold uppercase text-red-800">Siswa Yang Ditolak Ajuannya</span>
                    <span class="text-lg font-bold text-red-900">{{ studentToReject.full_name }}</span>
                </div>

                <p class="text-sm text-700 mb-3">
                    Dengan menolak ajuan ini, status tugas siswa akan diatur ulang agar dapat dikumpulkan kembali, nilai direset ke 0, dan akses edit otomatis dibuka.
                </p>

                <div class="field mb-0">
                    <label class="block font-bold mb-1 text-sm">Catatan Alasan Penolakan / Instruksi Perbaikan <span class="text-red-500">*</span></label>
                    <Textarea 
                        v-model="rejectNote" 
                        rows="4" 
                        placeholder="Contoh: File lampiran tidak dapat dibuka / jawaban kurang lengkap. Silakan periksa kembali dan kumpulkan ulang." 
                        class="w-full" 
                    />
                </div>
            </div>

            <template #footer>
                <Button label="Batal" icon="pi pi-times" severity="secondary" outlined @click="showRejectModal = false" />
                <Button 
                    label="Tolak & Buka Akses Edit Sekarang" 
                    icon="pi pi-exclamation-circle" 
                    severity="danger" 
                    class="font-bold px-3" 
                    @click="confirmReject" 
                />
            </template>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

const props = defineProps({
    assignment: Object,
    studentsList: Array,
});

const submittedCount = computed(() => {
    return props.studentsList.filter(s => !!s.submission && s.submission.status !== 'draft').length;
});

const draftCount = computed(() => {
    return props.studentsList.filter(s => !!s.submission && s.submission.status === 'draft').length;
});

const togglePublishGrades = () => {
    router.post(route('guru.assignments.publish_grades', props.assignment.id), {}, { preserveScroll: true });
};

const forceSubmitSingle = (studentData) => {
    if (!studentData.submission) return;
    if (!confirm(`Apakah Anda yakin ingin memaksa pengumpulan draf jawaban untuk siswa ${studentData.full_name}? Draf akan dinilai otomatis dan dikunci.`)) {
        return;
    }
    router.post(
        route('guru.assignments.submissions.force_submit', [props.assignment.id, studentData.submission.id]),
        {},
        { preserveScroll: true }
    );
};

const forceSubmitAllDrafts = () => {
    if (!confirm(`Apakah Anda yakin ingin memaksa pengumpulan SEMUA (${draftCount.value}) draf jawaban siswa? Seluruh draf jawaban akan disubmit, dinilai otomatis, dan dikunci.`)) {
        return;
    }
    router.post(
        route('guru.assignments.submissions.force_submit_all', props.assignment.id),
        {},
        { preserveScroll: true }
    );
};

const toggleEditAccess = (studentData) => {
    if (!studentData.submission) return;
    const isCurrentlyEditable = studentData.submission.is_editable;
    const actionText = isCurrentlyEditable ? 'menutup' : 'membuka';
    if (!confirm(`Apakah Anda yakin ingin ${actionText} akses edit jawaban untuk siswa ${studentData.full_name}?`)) {
        return;
    }
    router.post(route('guru.assignments.submissions.toggle_edit', [props.assignment.id, studentData.submission.id]), {}, {
        preserveScroll: true,
    });
};

const unlockAllEdit = () => {
    if (!confirm('Apakah Anda yakin ingin membuka akses edit untuk semua siswa yang sudah mengumpulkan tugas ini?')) {
        return;
    }
    router.post(route('guru.assignments.submissions.unlock_all_edit', props.assignment.id), {}, {
        preserveScroll: true,
    });
};

const showRejectModal = ref(false);
const studentToReject = ref(null);
const rejectNote = ref('');

const openRejectModal = (studentData) => {
    studentToReject.value = studentData;
    rejectNote.value = studentData.submission?.teacher_notes || 'Ajuan jawaban Anda ditolak oleh guru (perlu perbaikan). Silakan periksa kembali dan kumpulkan ulang.';
    showRejectModal.value = true;
};

const confirmReject = () => {
    if (!studentToReject.value?.submission) return;
    router.post(
        route('guru.assignments.submissions.reject', [props.assignment.id, studentToReject.value.submission.id]),
        { teacher_notes: rejectNote.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showRejectModal.value = false;
                showModal.value = false;
            }
        }
    );
};

const showModal = ref(false);
const selectedStudent = ref(null);

const gradingForm = useForm({
    answers: [],
    teacher_notes: '',
});

const openGradingModal = (studentData) => {
    selectedStudent.value = studentData;
    if (studentData.submission) {
        gradingForm.teacher_notes = studentData.submission.teacher_notes || '';
        gradingForm.answers = studentData.submission.answers.map(ans => {
            const question = ans.question || props.assignment.questions?.find(q => q.id === ans.question_id) || {};
            return {
                id: ans.id,
                question_id: ans.question_id,
                answer_text: ans.answer_text,
                url_upload: ans.url_upload,
                is_duplicate_text: ans.is_duplicate_text,
                is_duplicate_url: ans.is_duplicate_url,
                duplicate_group_id: ans.duplicate_group_id,
                similarity_percentage: ans.similarity_percentage,
                score: ans.score ?? 0,
                is_correct: ans.is_correct,
                feedback: ans.feedback || '',
                question: question,
            };
        });
    }
    showModal.value = true;
};

const calculatedTotalScore = computed(() => {
    if (!gradingForm.answers || gradingForm.answers.length === 0) return 0;
    const totalMaxRaw = props.assignment.questions.reduce((acc, q) => acc + (q.max_score || 0), 0);
    const studentRaw = gradingForm.answers.reduce((acc, a) => acc + (parseFloat(a.score) || 0), 0);
    if (totalMaxRaw <= 0) return 0;
    const scaled = Math.round((studentRaw / totalMaxRaw) * 100);
    return Math.min(100, Math.max(0, scaled));
});

const saveGrading = () => {
    if (!selectedStudent.value?.submission) return;
    gradingForm.post(
        route('guru.assignments.submissions.grade', {
            id: props.assignment.id,
            submissionId: selectedStudent.value.submission.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            }
        }
    );
};

const formatKeywords = (keywords) => {
    if (!keywords) return '-';
    if (Array.isArray(keywords)) return keywords.join(', ');
    return keywords;
};
</script>
