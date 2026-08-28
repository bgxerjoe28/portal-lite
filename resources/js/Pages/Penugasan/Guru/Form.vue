<template>
    <AppLayout :title="isEdit ? 'Edit Tugas Mata Pelajaran' : 'Buat Tugas Mata Pelajaran Baru'">
        <div class="card p-4 surface-card border-round-xl shadow-2">
            <div class="flex align-items-center justify-content-between mb-4 border-bottom-1 border-200 pb-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">
                        {{ isEdit ? 'Edit Penugasan' : 'Buat Penugasan Baru (PR / Homework)' }}
                    </h2>
                    <small class="text-600">Isi detail tugas, jadwal buka & deadline, serta buat daftar soal Uraian / PG.</small>
                </div>
                <Button 
                    label="Kembali" 
                    icon="pi pi-arrow-left" 
                    severity="secondary" 
                    outlined 
                    @click="router.get(route('guru.assignments.index'))" 
                />
            </div>

            <div v-if="Object.keys(form.errors).length > 0" class="p-4 mb-4 bg-red-50 border-round-xl border border-red-200 text-red-700">
                <div class="font-bold flex align-items-center gap-2 mb-2"><i class="pi pi-exclamation-triangle"></i> Terjadi Kesalahan Validasi</div>
                <ul class="m-0 pl-3">
                    <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                </ul>
            </div>

            <form @submit.prevent="submitForm">
                <!-- METADATA TUGAS -->
                <div class="p-3 surface-100 border-round-xl mb-4">
                    <h3 class="text-lg font-bold text-primary mb-3">1. Informasi & Pengaturan Tugas</h3>
                    <div class="grid p-fluid">
                        <div class="col-12 field mb-3">
                            <label class="block font-semibold mb-1">Tipe Penugasan <span class="text-red-500">*</span></label>
                            <SelectButton 
                                v-model="form.type" 
                                :options="[{label: 'Tugas Reguler (Siswa Kumpul Jawaban)', value: 'standard'}, {label: 'Penilaian Kinerja / Praktik (Guru Rekap Nilai)', value: 'performance'}]" 
                                optionLabel="label" 
                                optionValue="value" 
                                class="w-full" 
                                aria-labelledby="tipe-penugasan"
                            />
                        </div>

                        <div class="col-12 field mb-3">
                            <label class="block font-semibold mb-1">Judul Tugas <span class="text-red-500">*</span></label>
                            <InputText v-model="form.title" placeholder="Contoh: Tugas 1 - Persamaan Kuadrat & Garis" class="w-full" :class="{'p-invalid': form.errors.title}" />
                            <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small>
                        </div>

                        <div class="col-12 field mb-3">
                            <label class="block font-semibold mb-1">Petunjuk / Deskripsi Singkat</label>
                            <Textarea v-model="form.description" rows="3" placeholder="Tuliskan petunjuk pengerjaan bagi siswa..." class="w-full" />
                        </div>

                        <div class="col-12 md:col-4 field mb-3" v-if="!isEdit || isDuplicate">
                            <label class="block font-semibold mb-1">Kelas Target <span class="text-red-500">* (Bisa Pilih Beberapa Kelas)</span></label>
                            <MultiSelect 
                                v-model="form.classroom_ids" 
                                :options="filteredClassrooms" 
                                optionLabel="name" 
                                optionValue="id" 
                                placeholder="Pilih Satu atau Beberapa Kelas" 
                                class="w-full" 
                                :class="{'p-invalid': form.errors.classroom_ids}" 
                                filter 
                                display="chip"
                                @change="onClassroomChange"
                            />
                            <small v-if="form.errors.classroom_ids" class="p-error">{{ form.errors.classroom_ids }}</small>
                        </div>
                        <div class="col-12 md:col-4 field mb-3" v-else>
                            <label class="block font-semibold mb-1">Kelas Target <span class="text-red-500">*</span></label>
                            <Select 
                                v-model="form.classroom_id" 
                                :options="filteredClassrooms" 
                                optionLabel="name" 
                                optionValue="id" 
                                placeholder="Pilih Kelas" 
                                class="w-full" 
                                :class="{'p-invalid': form.errors.classroom_id}" 
                                @change="onClassroomChange"
                            />
                            <small v-if="form.errors.classroom_id" class="p-error">{{ form.errors.classroom_id }}</small>
                        </div>

                        <div class="col-12 md:col-4 field mb-3">
                            <label class="block font-semibold mb-1">Mata Pelajaran <span class="text-red-500">*</span></label>
                            <Select 
                                v-model="form.subject_id" 
                                :options="filteredSubjects" 
                                optionLabel="name" 
                                optionValue="id" 
                                placeholder="Pilih Mata Pelajaran" 
                                class="w-full" 
                                :class="{'p-invalid': form.errors.subject_id}" 
                                @change="onSubjectChange"
                            />
                            <small v-if="form.errors.subject_id" class="p-error">{{ form.errors.subject_id }}</small>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-1">
                                Kategori Penilaian 
                                <a :href="route('penilaian.components.index')" target="_blank" class="text-primary text-xs ml-1 hover:underline">
                                    (Kelola Kategori <i class="pi pi-external-link" style="font-size: 0.7rem"></i>)
                                </a>
                            </label>
                            <Select 
                                v-model="form.grading_component_id" 
                                :options="filteredGradingComponents" 
                                optionLabel="name" 
                                optionValue="id" 
                                placeholder="Pilih Komponen (Contoh: Tugas/PR)" 
                                class="w-full" 
                            />
                            <small class="text-500 text-xs">Nilai siswa akan otomatis masuk ke tabel penilaian komponen ini.</small>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-1">
                                Prasyarat Tugas <span class="text-xs text-500 font-normal">(Siswa Harus Selesaikan Tugas Ini Terlebih Dahulu)</span>
                            </label>
                            <Select 
                                v-model="form.prerequisite_assignment_id" 
                                :options="filteredAvailablePrerequisites" 
                                optionLabel="title" 
                                optionValue="id" 
                                placeholder="Tanpa Prasyarat (Dapat Langsung Dikerjakan)" 
                                showClear
                                filter
                                class="w-full" 
                            />
                            <small class="text-500 text-xs">Tugas ini akan terkunci bagi siswa sampai tugas prasyarat yang dipilih diselesaikan.</small>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-1">Waktu Dibuka <span class="text-red-500">*</span></label>
                            <input 
                                type="datetime-local" 
                                v-model="form.start_at" 
                                class="p-inputtext w-full" 
                                :class="{'p-invalid': form.errors.start_at}" 
                            />
                            <small v-if="form.errors.start_at" class="p-error">{{ form.errors.start_at }}</small>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-1">Waktu Ditutup (Due Date) <span class="text-red-500">*</span></label>
                            <input 
                                type="datetime-local" 
                                v-model="form.due_at" 
                                class="p-inputtext w-full" 
                                :class="{'p-invalid': form.errors.due_at}" 
                            />
                            <small v-if="form.errors.due_at" class="p-error">{{ form.errors.due_at }}</small>
                        </div>

                        <div class="col-12 field mb-0">
                            <div class="flex align-items-center gap-2 mt-2">
                                <ToggleSwitch v-model="form.is_published" id="switch-publish" />
                                <label for="switch-publish" class="font-bold cursor-pointer">
                                    Posting / Aktifkan Tugas Sekarang (Siswa dapat langsung melihat tugas)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BUILDER SOAL -->
                <div class="p-3 surface-card border-round-xl border border-300 mb-4">
                    <div class="flex justify-content-between align-items-center mb-3">
                        <h3 class="text-lg font-bold text-primary m-0">
                            {{ form.type === 'performance' ? '2. Daftar Indikator Penilaian' : '2. Daftar Soal Tugas' }}
                        </h3>
                        <div class="flex gap-2">
                            <template v-if="form.type === 'standard'">
                                <Button 
                                    label="+ Tambah Uraian" 
                                    icon="pi pi-file-edit" 
                                    severity="info" 
                                    size="small"
                                    type="button"
                                    @click="addQuestion('essay')" 
                                />
                                <Button 
                                    label="+ Tambah Pilihan Ganda" 
                                    icon="pi pi-list" 
                                    severity="success" 
                                    size="small"
                                    type="button"
                                    @click="addQuestion('mcq')" 
                                />
                            </template>
                            <template v-else>
                                <Button 
                                    label="+ Tambah Indikator (Skor)" 
                                    icon="pi pi-check-square" 
                                    severity="help" 
                                    size="small"
                                    type="button"
                                    @click="addQuestion('performance_indicator')" 
                                />
                                <Button 
                                    label="+ Tambah Indikator (Ceklist)" 
                                    icon="pi pi-check-circle" 
                                    severity="warning" 
                                    size="small"
                                    type="button"
                                    @click="addQuestion('performance_checklist')" 
                                />
                            </template>
                        </div>
                    </div>

                    <div v-if="form.questions.length === 0" class="text-center p-5 surface-100 border-round-xl border-dashed border-2 text-500">
                        <i class="pi pi-question-circle text-4xl block mb-2 opacity-50"></i>
                        {{ form.type === 'performance' ? 'Belum ada indikator. Klik tombol di atas untuk menambah Indikator Penilaian.' : 'Belum ada soal. Klik tombol di atas untuk menambah Soal Uraian atau Pilihan Ganda.' }}
                    </div>

                    <div v-else class="flex flex-column gap-4">
                        <div 
                            v-for="(q, idx) in form.questions" 
                            :key="idx" 
                            class="p-4 border-round-xl surface-50 border border-300 relative shadow-1"
                        >
                            <div class="flex justify-content-between align-items-center mb-3 border-bottom-1 border-200 pb-2">
                                <div class="flex align-items-center gap-2">
                                    <span class="font-bold text-lg text-900">{{ form.type === 'performance' ? 'Indikator' : 'Soal' }} #{{ idx + 1 }}</span>
                                    <Tag 
                                        :value="q.type === 'essay' ? 'Uraian (Essay)' : (q.type === 'mcq' ? 'Pilihan Ganda (PG)' : (q.type === 'performance_checklist' ? 'Indikator (Ceklist)' : 'Indikator (Skor)'))" 
                                        :severity="q.type === 'essay' ? 'info' : (q.type === 'mcq' ? 'success' : (q.type === 'performance_checklist' ? 'warning' : 'help'))" 
                                    />
                                </div>
                                <Button 
                                    icon="pi pi-trash" 
                                    severity="danger" 
                                    text 
                                    rounded
                                    type="button"
                                    @click="removeQuestion(idx)" 
                                />
                            </div>

                            <div class="grid p-fluid">
                                <div class="col-12 field mb-3">
                                    <label class="block font-semibold mb-2">
                                        {{ form.type === 'performance' ? 'Nama Indikator Penilaian' : 'Isi Pertanyaan / Soal' }} <span class="text-red-500">*</span>
                                        <span class="text-xs text-500 font-normal ml-2">— Gunakan toolbar untuk memformat teks, insert gambar, tabel, dll.</span>
                                    </label>
                                    <RichTextEditor 
                                        v-model="q.question_text" 
                                        :placeholder="(form.type === 'performance' ? 'Tuliskan indikator' : 'Tuliskan teks soal') + ' #' + (idx + 1) + ' di sini...'" 
                                        :hasError="!q.question_text || q.question_text === '<p></p>'"
                                    />
                                    <small v-if="!q.question_text || q.question_text === '<p></p>'" class="p-error">{{ form.type === 'performance' ? 'Indikator' : 'Soal' }} tidak boleh kosong</small>
                                </div>

                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">Bobot Maksimal {{ form.type === 'performance' ? 'Indikator' : 'Soal' }}</label>
                                    <InputNumber v-model="q.max_score" :min="1" :max="100" class="w-full" />
                                    <small class="text-500">Nilai total siswa akan otomatis dikalkulasi ke skala max 100.</small>
                                </div>

                                <!-- SPECIFIC UNTUK URAIAN (ESSAY) -->
                                <template v-if="q.type === 'essay'">
                                    <div class="col-12 field mb-3">
                                        <div class="p-3 bg-blue-50 border-round-lg border border-blue-200">
                                            <div class="flex align-items-center gap-2 mb-2">
                                                <Checkbox v-model="q.allow_url_upload" :binary="true" :inputId="`check-url-${idx}`" />
                                                <label :for="`check-url-${idx}`" class="font-bold text-blue-900 cursor-pointer">
                                                    Aktifkan Pilihan Upload URL (Siswa dapat mengumpulkan link Google Drive / External URL)
                                                </label>
                                            </div>
                                            <small class="text-blue-700 block ml-6">Jika diaktifkan, siswa dapat mengisikan tautan Google Drive / link dokumen pada jawaban soal ini.</small>
                                        </div>
                                    </div>

                                    <div class="col-12 field mb-3">
                                        <label class="block font-semibold mb-1">Kata Kunci Penilaian (Keywords Similarity) <span class="text-primary text-xs">(Dipisahkan Koma)</span></label>
                                        <InputText 
                                            v-model="q.keywords" 
                                            placeholder="Contoh: fotosintesis, klorofil, sinar matahari, karbon dioksida" 
                                            class="w-full" 
                                        />
                                        <small class="text-500 text-xs">Kata kunci ini akan digunakan untuk menghitung persentase kemiripan jawaban siswa secara otomatis. Anda tetap dapat mengintervensi nilainya nanti.</small>
                                    </div>
                                </template>

                                <!-- SPECIFIC UNTUK PILIHAN GANDA (MCQ) -->
                                <template v-if="q.type === 'mcq'">
                                    <div class="col-12 field mb-3">
                                        <label class="block font-semibold mb-2">Pilihan Opsi Jawaban (PG)</label>
                                        <div 
                                            v-for="(opt, oIdx) in q.options" 
                                            :key="oIdx" 
                                            class="flex align-items-center gap-2 mb-2"
                                        >
                                            <Tag :value="opt.key" severity="secondary" rounded class="px-3" />
                                            <InputText v-model="opt.text" placeholder="Teks pilihan jawaban..." class="flex-1" />
                                            <Button 
                                                icon="pi pi-times" 
                                                severity="danger" 
                                                text 
                                                type="button"
                                                @click="removeOption(q, oIdx)" 
                                                v-if="q.options.length > 2"
                                            />
                                        </div>
                                        <Button 
                                            label="+ Tambah Opsi" 
                                            icon="pi pi-plus" 
                                            severity="secondary" 
                                            size="small" 
                                            outlined 
                                            type="button"
                                            class="mt-2"
                                            @click="addOption(q)" 
                                        />
                                    </div>

                                    <div class="col-12 md:col-6 field mb-3">
                                        <label class="block font-semibold mb-1">Kunci Jawaban Benar <span class="text-red-500">*</span></label>
                                        <Select 
                                            v-model="q.correct_answer" 
                                            :options="q.options" 
                                            optionLabel="key" 
                                            optionValue="key" 
                                            placeholder="Pilih Kunci (A, B, C...)" 
                                            class="w-full" 
                                        />
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="flex justify-content-end gap-3">
                    <Button 
                        label="Batal" 
                        icon="pi pi-times" 
                        severity="secondary" 
                        outlined 
                        type="button"
                        @click="router.get(route('guru.assignments.index'))" 
                    />
                    <Button 
                        :label="isEdit ? 'Simpan Perubahan' : 'Simpan & Terbitkan'" 
                        icon="pi pi-check" 
                        severity="primary" 
                        type="submit" 
                        :loading="form.processing"
                        raised 
                        class="font-bold px-4" 
                    />
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import InputNumber from 'primevue/inputnumber';
import ToggleSwitch from 'primevue/toggleswitch';
import Checkbox from 'primevue/checkbox';
import SelectButton from 'primevue/selectbutton';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const props = defineProps({
    assignment: Object,
    classrooms: Array,
    subjects: Array,
    schedules: Array,
    gradingComponents: Array,
    availablePrerequisites: Array,
    isDuplicate: Boolean,
});

const isEdit = computed(() => !!props.assignment?.id);

const formatLocalDatetime = (dateInput) => {
    if (!dateInput) return '';
    const d = new Date(dateInput);
    if (isNaN(d.getTime())) return '';
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

// Form State
const form = useForm({
    title: props.assignment?.title || '',
    type: props.assignment?.type || 'standard',
    description: props.assignment?.description || '',
    classroom_id: props.assignment?.classroom_id || null,
    classroom_ids: props.assignment?.classroom_id ? [props.assignment.classroom_id] : [],
    subject_id: props.assignment?.subject_id || null,
    grading_component_id: props.assignment?.grading_component_id || null,
    prerequisite_assignment_id: props.assignment?.prerequisite_assignment_id || null,
    start_at: props.assignment?.start_at ? formatLocalDatetime(props.assignment.start_at) : formatLocalDatetime(new Date()),
    due_at: props.assignment?.due_at ? formatLocalDatetime(props.assignment.due_at) : formatLocalDatetime(new Date(Date.now() + 86400000)),
    is_published: props.assignment?.is_published ?? true,
    questions: props.assignment?.questions ? props.assignment.questions.map(q => ({
        id: props.isDuplicate ? undefined : q.id,
        type: q.type,
        question_text: q.question_text,
        options: q.options || [
            { key: 'A', text: '' },
            { key: 'B', text: '' },
            { key: 'C', text: '' },
            { key: 'D', text: '' },
        ],
        correct_answer: q.correct_answer || null,
        keywords: Array.isArray(q.keywords) ? q.keywords.join(', ') : (q.keywords || ''),
        allow_url_upload: q.allow_url_upload ?? false,
        max_score: q.max_score || 10,
    })) : [],
});

// Dependent Classrooms
const filteredClassrooms = computed(() => {
    if (!props.schedules || props.schedules.length === 0) return props.classrooms || [];
    if (!form.subject_id) {
        const classMap = new Map();
        props.schedules.forEach(s => {
            if (s.classroom) classMap.set(s.classroom.id, s.classroom);
        });
        return Array.from(classMap.values());
    }
    const classMap = new Map();
    props.schedules.filter(s => s.subject_id === form.subject_id).forEach(s => {
        if (s.classroom) classMap.set(s.classroom.id, s.classroom);
    });
    return Array.from(classMap.values());
});

// Dependent Subjects
const filteredSubjects = computed(() => {
    if (!props.schedules || props.schedules.length === 0) return props.subjects || [];
    if (!form.classroom_id) {
        const subMap = new Map();
        props.schedules.forEach(s => {
            if (s.subject) subMap.set(s.subject.id, s.subject);
        });
        return Array.from(subMap.values());
    }
    const subMap = new Map();
    props.schedules.filter(s => s.classroom_id === form.classroom_id).forEach(s => {
        if (s.subject) subMap.set(s.subject.id, s.subject);
    });
    return Array.from(subMap.values());
});

// Dependent Grading Components
const filteredGradingComponents = computed(() => {
    if (!props.gradingComponents) return [];
    if (!form.subject_id) return props.gradingComponents;
    return props.gradingComponents.filter(c => !c.subject_id || c.subject_id === form.subject_id);
});

// Dependent Available Prerequisites
const filteredAvailablePrerequisites = computed(() => {
    if (!props.availablePrerequisites) return [];
    if (!form.subject_id) return props.availablePrerequisites;
    return props.availablePrerequisites.filter(p => !p.subject_id || p.subject_id === form.subject_id);
});

const onClassroomChange = () => {
    if (form.subject_id) {
        const isValid = filteredSubjects.value.some(s => s.id === form.subject_id);
        if (!isValid) {
            form.subject_id = null;
            form.grading_component_id = null;
        }
    }
    if (!form.subject_id && filteredSubjects.value.length === 1) {
        form.subject_id = filteredSubjects.value[0].id;
        onSubjectChange();
    }
};

const onSubjectChange = () => {
    if (form.classroom_id) {
        const isValid = filteredClassrooms.value.some(c => c.id === form.classroom_id);
        if (!isValid) {
            form.classroom_id = null;
        }
    }
    if (!form.classroom_id && filteredClassrooms.value.length === 1) {
        form.classroom_id = filteredClassrooms.value[0].id;
    }

    if (form.grading_component_id) {
        const isValidComponent = filteredGradingComponents.value.some(c => c.id === form.grading_component_id);
        if (!isValidComponent) {
            form.grading_component_id = null;
        }
    }
    if (!form.grading_component_id && filteredGradingComponents.value.length === 1) {
        form.grading_component_id = filteredGradingComponents.value[0].id;
    }
};

const addQuestion = (type) => {
    if (type === 'essay') {
        form.questions.push({
            type: 'essay',
            question_text: '',
            keywords: '',
            allow_url_upload: true,
            max_score: 10,
        });
    } else if (type === 'mcq') {
        form.questions.push({
            type: 'mcq',
            question_text: '',
            options: [
                { key: 'A', text: '' },
                { key: 'B', text: '' },
                { key: 'C', text: '' },
                { key: 'D', text: '' },
            ],
            correct_answer: 'A',
            max_score: 10,
        });
    } else if (type === 'performance_indicator') {
        form.questions.push({
            type: 'performance_indicator',
            question_text: '',
            max_score: 10,
        });
    } else if (type === 'performance_checklist') {
        form.questions.push({
            type: 'performance_checklist',
            question_text: '',
            max_score: 10,
        });
    }
};

const removeQuestion = (index) => {
    form.questions.splice(index, 1);
};

const addOption = (question) => {
    const keys = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
    const currentCount = question.options.length;
    if (currentCount < keys.length) {
        question.options.push({ key: keys[currentCount], text: '' });
    }
};

const removeOption = (question, oIdx) => {
    question.options.splice(oIdx, 1);
};

const submitForm = () => {
    if (isEdit.value && !props.isDuplicate) {
        form.put(route('guru.assignments.update', props.assignment.id), {
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Gagal Menyimpan',
                    detail: 'Mohon periksa kembali form dan lengkapi data/soal yang diperlukan.',
                    life: 5000
                });
            }
        });
    } else {
        form.post(route('guru.assignments.store'), {
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Gagal Menyimpan',
                    detail: 'Mohon periksa kembali form dan lengkapi data/soal yang diperlukan.',
                    life: 5000
                });
            }
        });
    }
};

onMounted(() => {
    if (!isEdit.value && form.questions.length === 0) {
        if (form.type === 'performance') {
            addQuestion('performance_indicator');
        } else {
            addQuestion('essay');
        }
    }
});
</script>
