<template>
    <AppLayout title="Detail Soal Bank Soal">
        <div class="card">
            <!-- Header Nav & Actions -->
            <div class="flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
                <div class="flex align-items-center gap-2">
                    <Link :href="route('cbt.bank.index')">
                        <Button icon="pi pi-arrow-left" severity="secondary" text rounded />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-900 m-0">Detail Soal: {{ bank.name }}</h2>
                        <span class="text-500 block mt-1">
                            Mata Pelajaran: <b>{{ bank.subject?.name || '-' }}</b> | Total: <b>{{ questions.length }} Soal</b>
                        </span>
                    </div>
                </div>

                <div class="flex gap-2 flex-wrap align-items-center">
                    <Button 
                        label="Import Revisi Kunci (Excel)" 
                        icon="pi pi-file-excel" 
                        severity="success" 
                        @click="openImportModal" 
                    />
                </div>
            </div>

            <!-- Warning: Ujian Aktif -->
            <div v-if="props.activeExams && props.activeExams.length > 0" class="mb-4 p-3 bg-orange-50 border-round border border-orange-300 flex align-items-start gap-3 text-sm text-orange-900">
                <i class="pi pi-exclamation-triangle text-2xl text-orange-500 flex-shrink-0 mt-1"></i>
                <div class="line-height-3">
                    <b>⚠️ Ujian Sedang Berlangsung!</b> Bank soal ini sedang digunakan oleh ujian aktif berikut:
                    <ul class="m-0 mt-1 pl-4">
                        <li v-for="exam in props.activeExams" :key="exam.title"><b>{{ exam.title }}</b></li>
                    </ul>
                    <span class="text-orange-700 mt-1 block">Perubahan teks/pilihan soal akan <b>langsung berlaku</b> bagi siswa yang sedang mengerjakan. Hanya perbaiki soal yang benar-benar bermasalah.</span>
                </div>
            </div>

            <!-- Edukasi / Info Banner Sinkronisasi Nilai -->
            <div class="mb-4 p-3 bg-blue-50 border-round border border-blue-200 flex align-items-center gap-3 text-sm text-blue-900">
                <i class="pi pi-info-circle text-2xl text-blue-600 flex-shrink-0"></i>
                <div class="line-height-3">
                    <b>Tips Pembaruan Kunci:</b> Jika ujian sudah dikerjakan siswa dan Anda mengoreksi kunci jawaban (baik via edit manual maupun import Excel revisi), 
                    silakan buka menu <b>Jadwal Ujian &gt; Hasil Ujian</b> lalu klik tombol <b>"Hitung Ulang Nilai &amp; Analisis"</b> agar seluruh nilai peserta langsung diperbarui.
                </div>
            </div>

            <!-- List of Questions -->
            <div v-if="questions.length > 0" class="flex flex-column gap-4">
                <div 
                    v-for="(q, idx) in questions" 
                    :key="q.id" 
                    class="surface-card p-4 shadow-1 border-round border-left-3 border-blue-500"
                >
                    <div class="flex justify-content-between align-items-start mb-3 gap-2 border-bottom-1 border-100 pb-2 flex-wrap">
                        <div class="flex align-items-center gap-2">
                            <span class="font-bold text-lg text-primary">Soal #{{ idx + 1 }}</span>
                            <Tag :value="formatTypeLabel(q.question_type)" severity="info" />
                            <Tag :value="'Bobot: ' + q.score + ' Pts'" severity="secondary" />
                            <Tag v-if="q.lock_n" value="Posisi Terkunci (Lock N)" severity="warn" />
                            <Tag v-if="q.grouping" :value="'Grup ' + q.grouping" severity="help" />
                        </div>
                        
                        <div class="flex gap-2">
                            <Button 
                                label="Edit Soal" 
                                icon="pi pi-file-edit" 
                                size="small" 
                                severity="info" 
                                outlined 
                                @click="openPatchModal(q, idx)" 
                            />
                            <Button 
                                label="Edit Kunci & Bobot" 
                                icon="pi pi-pencil" 
                                size="small" 
                                severity="warning" 
                                outlined 
                                @click="openEditModal(q, idx)" 
                            />
                        </div>
                    </div>

                    <!-- Question Text -->
                    <div class="text-800 text-lg mb-4 line-height-3" v-html="q.question_text"></div>

                    <!-- Options / Rendering based on Type -->
                    <div class="surface-ground p-3 border-round border border-200">
                        <h4 class="font-bold text-900 m-0 mb-3 text-sm uppercase tracking-wider text-600">Struktur Pilihan & Kunci Aktif</h4>
                        
                        <!-- 1. PILIHAN GANDA & 8. SURVEY & 9. SKOR BERBEDA -->
                        <div v-if="['pilihan_ganda', 'survey', 'skor_berbeda', 'list', 'checklist'].includes(q.question_type)">
                            <div class="flex flex-column gap-2">
                                <div 
                                    v-for="(val, optKey) in q.options" 
                                    :key="optKey"
                                    class="flex align-items-center justify-content-between p-2 border-round"
                                    :class="isKeyMatch(q, optKey) ? 'bg-green-50 border-left-3 border-green-500 font-semibold text-green-800' : 'bg-white'"
                                >
                                    <div class="flex align-items-center gap-2">
                                        <span class="border-circle flex align-items-center justify-content-center text-xs font-bold bg-200" style="width:20px; height:20px;">
                                            {{ optKey }}
                                        </span>
                                        <span v-html="val"></span>
                                    </div>
                                    <div v-if="q.question_type === 'skor_berbeda'" class="text-xs font-bold text-blue-600">
                                        Skor: {{ q.correct_answer?.[optKey] || 0 }}
                                    </div>
                                    <div v-else-if="isKeyMatch(q, optKey)" class="text-xs font-bold text-green-600">
                                        <i class="pi pi-check-circle"></i> KUNCI BENAR
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. ISIAN SINGKAT -->
                        <div v-else-if="q.question_type === 'isian_singkat'">
                            <div class="p-3 bg-white border-round border-left-3 border-green-500">
                                <span class="font-semibold text-green-800">Kunci Jawaban yang Diterima:</span>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <Tag 
                                        v-for="(ans, aIdx) in (Array.isArray(q.correct_answer) ? q.correct_answer : [q.correct_answer])" 
                                        :key="aIdx" 
                                        :value="ans" 
                                        severity="success" 
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- 3. PERNYATAAN BENAR / SALAH -->
                        <div v-else-if="q.question_type === 'benar_salah'">
                            <div class="p-3 bg-white border-round border-left-3 border-green-500 flex flex-column gap-2">
                                <span class="font-semibold text-green-800 mb-1">Kunci Pernyataan Benar / Salah:</span>
                                <div v-for="(stmt, stmtKey) in (q.options?.statements || {})" :key="stmtKey" class="flex align-items-center justify-content-between p-2 border-round bg-slate-50 border border-slate-200">
                                    <span>{{ stmtKey }}. <span v-html="stmt"></span></span>
                                    <Tag :value="q.correct_answer?.[stmtKey] === 'B' ? 'BENAR (B)' : 'SALAH (S)'" :severity="q.correct_answer?.[stmtKey] === 'B' ? 'success' : 'danger'" />
                                </div>
                            </div>
                        </div>

                        <!-- 4. URAIAN / DINAMIS (MAJEMUK) -->
                        <div v-else-if="q.question_type === 'uraian'">
                            <div class="p-3 bg-white border-round border-left-3 border-yellow-500">
                                <div v-if="typeof q.correct_answer === 'object' && !Array.isArray(q.correct_answer) && Object.keys(q.correct_answer || {}).length > 0">
                                    <span class="font-semibold text-yellow-800 block mb-2">Kunci Jawaban Soal Dinamis / Majemuk:</span>
                                    <div class="flex flex-wrap gap-2">
                                        <div v-for="(val, subId) in q.correct_answer" :key="subId" class="px-2 py-1 bg-yellow-50 text-yellow-800 text-xs border-round border border-yellow-300">
                                            ID: <b>{{ subId }}</b> ➜ Kunci: <b>{{ Array.isArray(val) ? val.join(', ') : val }}</b>
                                        </div>
                                    </div>
                                </div>
                                <div v-else>
                                    <span class="font-semibold text-yellow-800">Catatan/Referensi Uraian:</span>
                                    <p class="m-0 mt-2 text-600 text-sm font-medium">
                                        {{ q.correct_answer?.[0] || 'Tidak ada catatan referensi. Soal ini memerlukan penilaian manual dari Guru.' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 5. PENJODOHAN (MATCHING) -->
                        <div v-else-if="q.question_type === 'penjodohan'">
                            <div class="p-3 bg-white border-round border-left-3 border-green-500 flex flex-column gap-2">
                                <span class="font-semibold text-green-800 mb-1">Kunci Pasangan Penjodohan:</span>
                                <div v-for="(prem, pKey) in (q.options?.premises || {})" :key="pKey" class="flex align-items-center justify-content-between p-2 border-round bg-slate-50 border border-slate-200 text-sm">
                                    <span><b>{{ pKey }}.</b> {{ prem }}</span>
                                    <span class="font-bold text-primary">➜ {{ q.correct_answer?.[pKey] }} ({{ q.options?.targets?.[q.correct_answer?.[pKey]] || '-' }})</span>
                                </div>
                            </div>
                        </div>

                        <!-- 6. MENGURUTKAN (SORTING) -->
                        <div v-else-if="q.question_type === 'sorting'">
                            <div class="flex flex-column gap-2">
                                <span class="font-semibold text-green-800 text-sm mb-1 block">Urutan Item yang Benar:</span>
                                <div 
                                    v-for="(itemKey, itemIdx) in q.correct_answer" 
                                    :key="itemIdx" 
                                    class="p-2 bg-white border-round flex align-items-center gap-2 border-left-3 border-green-500"
                                >
                                    <span class="font-bold text-green-600">#{{ itemIdx + 1 }}</span>
                                    <span class="text-700" v-html="q.options?.items?.[itemKey] || '-'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center p-8 surface-card border-round shadow-1">
                <i class="pi pi-file-excel text-500 text-6xl mb-3"></i>
                <h3 class="text-900 font-bold m-0 mb-2">Bank Soal Kosong</h3>
                <p class="text-600 m-0 mb-4">Silakan import soal menggunakan file Word & Excel template di menu utama Bank Soal.</p>
                <Link :href="route('cbt.bank.index')">
                    <Button label="Kembali ke Bank Soal" icon="pi pi-arrow-left" severity="secondary" outlined />
                </Link>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL 1: EDIT KUNCI MANUAL PER BUTIR SOAL -->
        <!-- ========================================================================= -->
        <Dialog 
            v-model:visible="editModalVisible" 
            :header="`Edit Kunci Soal #${editingIndex + 1} (${formatTypeLabel(editingQuestion?.question_type)})`" 
            :modal="true" 
            :style="{ width: '600px', maxWidth: '95vw' }"
        >
            <form @submit.prevent="submitEditKey" class="p-fluid">
                <!-- Bobot Nilai -->
                <div class="field mb-4">
                    <label class="font-bold block mb-1">Bobot Nilai / Skor Soal <span class="text-red-500">*</span></label>
                    <InputNumber v-model="editForm.score" :min="0" :maxFractionDigits="2" class="w-full" />
                </div>

                <!-- Input Kunci Berdasarkan Tipe Soal -->
                <div class="field mb-4">
                    <label class="font-bold block mb-2">Pilih / Ubah Kunci Jawaban <span class="text-red-500">*</span></label>

                    <!-- Pilihan Ganda / Survey -->
                    <div v-if="['pilihan_ganda', 'survey'].includes(editingQuestion?.question_type)" class="flex flex-column gap-2">
                        <div 
                            v-for="(val, optKey) in editingQuestion?.options" 
                            :key="optKey"
                            class="p-3 border-round border cursor-pointer transition-all flex align-items-center gap-3"
                            :class="editForm.correct_answer === optKey ? 'bg-green-50 border-green-500 shadow-1' : 'bg-white border-slate-300 hover:surface-100'"
                            @click="editForm.correct_answer = optKey"
                        >
                            <RadioButton 
                                v-model="editForm.correct_answer" 
                                :inputId="'opt_' + optKey" 
                                :value="optKey" 
                            />
                            <label :for="'opt_' + optKey" class="cursor-pointer font-bold flex-1">
                                Opsi {{ optKey }}: <span class="font-normal" v-html="val"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Isian Singkat -->
                    <div v-else-if="editingQuestion?.question_type === 'isian_singkat'">
                        <small class="text-600 block mb-2">Pisahkan dengan koma jika ada beberapa kemungkinan jawaban yang sama-sama benar (contoh: <code>Jakarta, DKI Jakarta</code>).</small>
                        <InputText v-model="editFormIsianText" placeholder="Ketik kunci jawaban yang diterima..." class="w-full" />
                    </div>

                    <!-- Benar / Salah -->
                    <div v-else-if="editingQuestion?.question_type === 'benar_salah'" class="flex flex-column gap-3">
                        <div v-for="(stmt, stmtKey) in (editingQuestion?.options?.statements || {})" :key="stmtKey" class="p-2 border-round bg-slate-50 border border-slate-200">
                            <div class="text-sm font-semibold mb-2">{{ stmtKey }}. <span v-html="stmt"></span></div>
                            <div class="flex gap-4">
                                <div class="flex align-items-center gap-2">
                                    <RadioButton v-model="editForm.correct_answer[stmtKey]" :inputId="`bs_b_${stmtKey}`" value="B" />
                                    <label :for="`bs_b_${stmtKey}`" class="font-bold text-green-700">BENAR (B)</label>
                                </div>
                                <div class="flex align-items-center gap-2">
                                    <RadioButton v-model="editForm.correct_answer[stmtKey]" :inputId="`bs_s_${stmtKey}`" value="S" />
                                    <label :for="`bs_s_${stmtKey}`" class="font-bold text-red-700">SALAH (S)</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Uraian / Lainnya -->
                    <div v-else>
                        <Textarea v-model="editFormRawJson" rows="4" class="w-full font-mono text-sm" placeholder="Ketik kunci jawaban atau format JSON..." />
                    </div>
                </div>

                <div class="flex justify-content-end gap-2 mt-4 pt-3 border-top-1 border-200">
                    <Button label="Batal" severity="secondary" text @click="editModalVisible = false" />
                    <Button label="Simpan Kunci" icon="pi pi-check" severity="success" type="submit" :loading="isSubmitting" />
                </div>
            </form>
        </Dialog>

        <!-- ========================================================================= -->
        <!-- MODAL PATCH: EDIT TEKS SOAL TUNGGAL -->
        <!-- ========================================================================= -->
        <Dialog 
            v-model:visible="patchModalVisible" 
            :header="`Edit Teks Soal #${patchingIndex + 1}`" 
            :modal="true" 
            :style="{ width: '700px', maxWidth: '95vw' }"
        >
            <div v-if="patchingQuestion" class="p-fluid flex flex-column gap-4">
                <!-- Warning jika ujian aktif -->
                <div v-if="props.activeExams && props.activeExams.length > 0" class="p-3 bg-orange-50 border-round border border-orange-300 text-sm text-orange-900">
                    <i class="pi pi-exclamation-triangle mr-1 text-orange-600"></i>
                    <b>Perhatian:</b> Ada ujian yang sedang berjalan. Perubahan ini akan <b>langsung terlihat</b> oleh siswa saat halaman ujian di-refresh.
                </div>

                <!-- Teks Soal -->
                <div class="field">
                    <label class="font-bold block mb-2">Teks Soal <span class="text-red-500">*</span></label>
                    <RichTextEditor
                        v-model="patchForm.question_text"
                        placeholder="Ketik teks soal di sini..."
                        :uploadUrl="route('cbt.questions.upload_image')"
                        :extraUploadData="{ bank_id: props.bank?.id }"
                    />
                    <small class="text-500 mt-1 block">Gunakan toolbar di atas untuk format teks dan upload gambar. Perubahan langsung tersimpan saat klik "Simpan Perubahan".</small>
                </div>

                <!-- Pilihan Jawaban (hanya untuk tipe pilihan_ganda, list, dll.) -->
                <div class="field" v-if="['pilihan_ganda', 'survey', 'list', 'checklist', 'skor_berbeda'].includes(patchingQuestion.question_type)">
                    <label class="font-bold block mb-2">Pilihan Jawaban</label>
                    <div class="flex flex-column gap-2">
                        <div v-for="(val, optKey) in patchForm.options" :key="optKey" class="flex align-items-center gap-2">
                            <span class="font-bold text-primary flex align-items-center justify-content-center border-circle" style="width:28px;height:28px;min-width:28px;background:#e8f4fd;">{{ optKey }}</span>
                            <InputText v-model="patchForm.options[optKey]" class="flex-1" :placeholder="'Teks pilihan ' + optKey" />
                        </div>
                    </div>
                    <small class="text-500 mt-1 block">
                        <i class="pi pi-info-circle mr-1"></i>
                        Teks ditampilkan bersih (HTML otomatis di-strip). Ketik teks saja — format akan disimpan secara otomatis dan tampilan di halaman ujian siswa tidak akan terganggu.
                    </small>
                </div>

                <div class="flex justify-content-end gap-2 pt-3 border-top-1 border-200">
                    <Button label="Batal" severity="secondary" text @click="patchModalVisible = false" />
                    <Button label="Simpan Perubahan" icon="pi pi-check" severity="info" :loading="isPatchSubmitting" @click="submitPatchQuestion" />
                </div>
            </div>
        </Dialog>

        <!-- ========================================================================= -->
        <!-- MODAL 2: IMPORT REVISI KUNCI EXCEL (AMAN TANPA MERUSAK ID SOAL) -->
        <!-- ========================================================================= -->
        <Dialog 
            v-model:visible="importModalVisible" 
            header="Import Revisi Kunci Jawaban (Excel)" 
            :modal="true" 
            :style="{ width: '500px', maxWidth: '95vw' }"
        >
            <form @submit.prevent="submitImportKeys" class="p-fluid">
                <div class="mb-3 p-3 bg-amber-50 border-round border border-amber-300 text-sm text-amber-900 line-height-3">
                    <i class="pi pi-shield mr-1 font-bold text-amber-700"></i>
                    <b>Aman untuk Ujian yang Selesai:</b> Fitur ini hanya akan memperbarui kunci dan bobot pada butir soal yang ada berdasarkan nomor urut di file Excel. 
                    <b>Teks soal dan ID soal tidak akan dihapus</b>, sehingga riwayat jawaban peserta tetap utuh.
                </div>

                <!-- Download Template Kunci -->
                <div class="mb-4 p-3 surface-ground border-round border border-200 flex justify-content-between align-items-center gap-3">
                    <div>
                        <span class="text-sm font-bold text-800 block">Belum punya format file kunci?</span>
                        <small class="text-500">Unduh template resmi Excel kunci CBT</small>
                    </div>
                    <a :href="route('cbt.bank.template', { format: 'excel' })" class="no-underline">
                        <Button 
                            label="Unduh Template Kunci" 
                            icon="pi pi-download" 
                            severity="info" 
                            outlined 
                            size="small" 
                        />
                    </a>
                </div>

                <div class="field mb-4">
                    <label class="font-bold block mb-2">Pilih File Excel Kunci (.xlsx / .xls) <span class="text-red-500">*</span></label>
                    <input 
                        type="file" 
                        ref="keyFileInput" 
                        accept=".xlsx, .xls" 
                        class="p-inputtext w-full" 
                        required 
                        @change="handleFileSelect"
                    />
                </div>

                <div class="flex justify-content-end gap-2 mt-4 pt-3 border-top-1 border-200">
                    <Button label="Batal" severity="secondary" text @click="importModalVisible = false" />
                    <Button label="Upload & Update Kunci" icon="pi pi-upload" severity="success" type="submit" :loading="isImporting" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import RadioButton from 'primevue/radiobutton';
import RichTextEditor from '@/Components/RichTextEditor.vue';

const props = defineProps({
    bank: Object,
    questions: Array,
    activeExams: { type: Array, default: () => [] },
});

// Edit Key Modal State
const editModalVisible = ref(false);
const editingQuestion = ref(null);
const editingIndex = ref(0);
const isSubmitting = ref(false);

const editForm = reactive({
    score: 1.00,
    correct_answer: null,
});
const editFormIsianText = ref('');
const editFormRawJson = ref('');

const openEditModal = (q, idx) => {
    editingQuestion.value = q;
    editingIndex.value = idx;
    editForm.score = q.score || 1.00;

    if (q.question_type === 'pilihan_ganda' || q.question_type === 'survey') {
        editForm.correct_answer = isKeyMatchString(q.correct_answer);
    } else if (q.question_type === 'isian_singkat') {
        const arr = Array.isArray(q.correct_answer) ? q.correct_answer : [q.correct_answer];
        editFormIsianText.value = arr.filter(Boolean).join(', ');
        editForm.correct_answer = arr;
    } else if (q.question_type === 'benar_salah') {
        editForm.correct_answer = { ...(q.correct_answer || {}) };
    } else {
        editFormRawJson.value = typeof q.correct_answer === 'object' ? JSON.stringify(q.correct_answer, null, 2) : String(q.correct_answer || '');
        editForm.correct_answer = q.correct_answer;
    }

    editModalVisible.value = true;
};

const isKeyMatchString = (correct) => {
    if (!correct) return 'A';
    if (Array.isArray(correct)) return String(correct[0] || 'A').toUpperCase();
    return String(correct).toUpperCase();
};

const submitEditKey = () => {
    if (!editingQuestion.value) return;

    let payloadCorrectAnswer = editForm.correct_answer;

    if (editingQuestion.value.question_type === 'isian_singkat') {
        payloadCorrectAnswer = editFormIsianText.value.split(',').map(s => s.trim()).filter(Boolean);
    } else if (!['pilihan_ganda', 'survey', 'benar_salah'].includes(editingQuestion.value.question_type)) {
        try {
            payloadCorrectAnswer = JSON.parse(editFormRawJson.value);
        } catch {
            payloadCorrectAnswer = editFormRawJson.value;
        }
    }

    isSubmitting.value = true;
    router.put(route('cbt.bank.questions.update_key', [props.bank.id, editingQuestion.value.id]), {
        correct_answer: payloadCorrectAnswer,
        score: editForm.score,
    }, {
        onSuccess: () => {
            editModalVisible.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

// Import Key Modal State
const importModalVisible = ref(false);
const keyFileInput = ref(null);
const selectedKeyFile = ref(null);
const isImporting = ref(false);

// ============================
// Patch Question Modal State
// ============================
const patchModalVisible = ref(false);
const patchingQuestion = ref(null);
const patchingIndex = ref(0);
const isPatchSubmitting = ref(false);
const patchForm = reactive({
    question_text: '',
    options: {},
});

/**
 * Strip semua tag HTML dan kembalikan teks bersih.
 * Digunakan agar guru tidak perlu melihat/mengedit raw HTML di field pilihan jawaban.
 */
const stripHtml = (html) => {
    if (!html || typeof html !== 'string') return '';
    return html.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').trim();
};

const openPatchModal = (q, idx) => {
    patchingQuestion.value = q;
    patchingIndex.value = idx;
    patchForm.question_text = q.question_text || '';
    // Strip HTML dari setiap opsi agar tampil bersih di InputText
    const rawOptions = q.options ? JSON.parse(JSON.stringify(q.options)) : {};
    const cleanOptions = {};
    for (const key in rawOptions) {
        const val = rawOptions[key];
        // Hanya strip jika nilainya string HTML, biarkan jika number/object (skor_berbeda)
        cleanOptions[key] = typeof val === 'string' ? stripHtml(val) : val;
    }
    patchForm.options = cleanOptions;
    patchModalVisible.value = true;
};

const submitPatchQuestion = () => {
    if (!patchingQuestion.value) return;
    isPatchSubmitting.value = true;

    const payload = {
        question_text: patchForm.question_text,
    };

    if (['pilihan_ganda', 'survey', 'list', 'checklist', 'skor_berbeda'].includes(patchingQuestion.value.question_type)) {
        // Re-wrap teks pilihan ke dalam <p class="mb-2"> agar konsisten dengan format parser Word
        const wrappedOptions = {};
        for (const key in patchForm.options) {
            const val = patchForm.options[key];
            if (typeof val === 'string' && val.trim() !== '') {
                // Sudah ada tag HTML? langsung pakai. Plain text? bungkus.
                wrappedOptions[key] = val.trimStart().startsWith('<') ? val : `<p class="mb-2">${val}</p>`;
            } else {
                wrappedOptions[key] = val;
            }
        }
        payload.options = wrappedOptions;
    }

    router.put(
        route('cbt.bank.questions.patch', [props.bank.id, patchingQuestion.value.id]),
        payload,
        {
            onSuccess: () => {
                patchModalVisible.value = false;
            },
            onFinish: () => {
                isPatchSubmitting.value = false;
            }
        }
    );
};

const openImportModal = () => {
    selectedKeyFile.value = null;
    if (keyFileInput.value) keyFileInput.value.value = '';
    importModalVisible.value = true;
};

const handleFileSelect = (event) => {
    selectedKeyFile.value = event.target.files[0] || null;
};

const submitImportKeys = () => {
    if (!selectedKeyFile.value) return;

    const formData = new FormData();
    formData.append('file_kunci', selectedKeyFile.value);

    isImporting.value = true;
    router.post(route('cbt.bank.import_keys', props.bank.id), formData, {
        onSuccess: () => {
            importModalVisible.value = false;
        },
        onFinish: () => {
            isImporting.value = false;
        }
    });
};

onMounted(() => {
    nextTick(() => {
        props.questions.forEach(q => {
            if (q.correct_answer) {
                const correct = q.correct_answer;
                if (typeof correct === 'object' && !Array.isArray(correct)) {
                    Object.entries(correct).forEach(([subId, val]) => {
                        const elList = document.querySelectorAll(`[data-id="${subId}"]`);
                        elList.forEach(el => {
                            if (el.type === 'radio') {
                                if (el.value === val) {
                                    el.checked = true;
                                }
                                el.disabled = true;
                            } else if (el.type === 'checkbox') {
                                if (val === 'CHECK') {
                                    el.checked = true;
                                }
                                el.disabled = true;
                            } else {
                                el.value = Array.isArray(val) ? val.join(', ') : val;
                                el.readOnly = true;
                                el.style.backgroundColor = '#f0fdf4';
                                el.style.color = '#166534';
                                el.style.borderColor = '#bbf7d0';
                                el.style.fontWeight = '600';
                            }
                        });
                    });
                } else if (Array.isArray(correct)) {
                    correct.forEach(val => {
                        const cb = document.querySelector(`.cbt-dynamic-checkbox[value="${val}"]`);
                        if (cb) {
                            cb.checked = true;
                        }
                    });
                }
            }
        });

        // Disable all dynamic elements
        const allCBs = document.querySelectorAll('.cbt-dynamic-checkbox');
        allCBs.forEach(cb => cb.disabled = true);
        const allRadios = document.querySelectorAll('.cbt-dynamic-radio');
        allRadios.forEach(r => r.disabled = true);
    });
});

const formatTypeLabel = (type) => {
    const labels = {
        'pilihan_ganda': 'Pilihan Ganda',
        'isian_singkat': 'Isian Singkat',
        'uraian': 'Uraian / Soal Dinamis',
        'list': 'Dropdown List',
        'checklist': 'Kotak Centang (Checklist)',
        'benar_salah': 'Pernyataan Benar/Salah',
        'penjodohan': 'Penjodohan (Matching)',
        'survey': 'Survey / Kuisioner',
        'skor_berbeda': 'Pilihan (Skor Berbeda)',
        'sorting': 'Mengurutkan (Sorting)',
    };
    return labels[type] || type;
};

const isKeyMatch = (question, optionKey) => {
    const type = question.question_type;
    const correct = question.correct_answer;

    if (type === 'pilihan_ganda' || type === 'survey') {
        const cStr = Array.isArray(correct) ? correct[0] : correct;
        return String(cStr || '').toUpperCase() === String(optionKey || '').toUpperCase();
    }
    if (['list', 'checklist'].includes(type)) {
        return Array.isArray(correct) && correct.includes(optionKey);
    }
    return false;
};
</script>

<style scoped>
:deep(.cbt-dynamic-input) {
    background-color: #f8fafc !important;
    color: #0f172a !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;
    padding: 6px 10px !important;
    font-size: 0.95rem !important;
    outline: none !important;
}
:deep(input.cbt-dynamic-input) {
    display: inline-block !important;
    max-width: 200px !important;
    text-align: center !important;
}
:deep(textarea.cbt-dynamic-input) {
    width: 100% !important;
    display: block !important;
}
</style>
