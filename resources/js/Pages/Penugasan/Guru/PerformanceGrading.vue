<template>
    <AppLayout :title="`Rekap Penilaian Kinerja: ${assignment.title}`">
        <div class="card p-4 surface-card border-round-xl shadow-2">
            <!-- Header -->
            <div class="flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center mb-4 border-bottom-1 border-200 pb-3 gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0 flex align-items-center gap-2">
                        <i class="pi pi-chart-bar text-help text-2xl"></i>
                        Penilaian Kinerja / Praktik
                    </h2>
                    <p class="text-600 text-sm mt-1 mb-0">{{ assignment.subject?.name }} | {{ assignment.title }} — {{ assignment.classroom?.name }}</p>
                </div>
                <div class="flex gap-2">
                    <Button 
                        label="Kembali" 
                        icon="pi pi-arrow-left" 
                        severity="secondary" 
                        outlined 
                        @click="router.get(route('guru.assignments.index'))" 
                    />
                    <Button 
                        v-if="!assignment.is_grades_published"
                        label="Publikasikan Nilai" 
                        icon="pi pi-globe" 
                        severity="success" 
                        raised 
                        @click="publishGrades" 
                    />
                    <Button 
                        v-else
                        label="Tarik Publikasi" 
                        icon="pi pi-undo" 
                        severity="warning" 
                        outlined 
                        @click="publishGrades" 
                    />
                    <Button 
                        label="Simpan Semua Penilaian" 
                        icon="pi pi-save" 
                        severity="primary" 
                        raised 
                        :loading="form.processing"
                        @click="submitAll" 
                    />
                </div>
            </div>

            <!-- Warning if not published -->
            <div v-if="!assignment.is_grades_published && showWarning" class="mb-4">
                <div class="p-3 bg-yellow-50 text-yellow-900 border-round-xl border border-yellow-200 flex align-items-start justify-content-between">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-exclamation-triangle text-xl"></i>
                        <div>
                            <span class="font-bold block">Nilai Belum Dipublikasi!</span>
                            <small>Nilai yang disimpan di sini belum masuk ke menu Penilaian dan belum bisa dilihat siswa. Silakan klik tombol "Publish Nilai" di halaman daftar tugas jika sudah selesai merekap.</small>
                        </div>
                    </div>
                    <Button 
                        icon="pi pi-times" 
                        severity="secondary" 
                        text 
                        rounded 
                        aria-label="Tutup" 
                        @click="showWarning = false" 
                        class="text-yellow-900 hover:bg-yellow-100"
                    />
                </div>
            </div>

            <!-- Table Matrix -->
            <div class="overflow-auto" style="max-height: 70vh;">
                <table class="w-full border-collapse surface-border border" style="min-width: 1000px">
                    <thead>
                        <tr class="surface-100">
                            <th class="p-3 border surface-border text-left surface-100" style="width: 250px; position: sticky; left: 0; top: 0; z-index: 20;">Nama Siswa</th>
                            <th class="p-3 border surface-border text-center surface-100" v-for="(q, idx) in assignment.questions" :key="q.id" style="min-width: 200px; position: sticky; top: 0; z-index: 10;">
                                <div class="font-bold text-sm mb-1" v-html="stripTags(q.question_text)"></div>
                                <Tag severity="help" :value="`Max: ${q.max_score}`" />
                            </th>
                            <th class="p-3 border surface-border text-center surface-100" style="width: 150px; position: sticky; top: 0; z-index: 10;">Total Skor (100)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(student, sIdx) in form.gradings" :key="student.student_id" class="hover:surface-50 transition-colors transition-duration-150">
                            <td class="p-3 border surface-border bg-white" style="position: sticky; left: 0; z-index: 5;">
                                <div class="font-bold text-900">{{ getStudentName(student.student_id) }}</div>
                                <small class="text-500">{{ getStudentNisn(student.student_id) }}</small>
                            </td>
                            <td class="p-3 border surface-border" v-for="(ans, aIdx) in student.answers" :key="ans.question_id">
                                <div class="flex flex-column gap-2">
                                    <template v-if="getQuestionType(ans.question_id) === 'performance_checklist'">
                                        <div class="flex flex-column align-items-center justify-content-center gap-2 mt-2">
                                            <ToggleSwitch 
                                                :modelValue="ans.score > 0" 
                                                @update:modelValue="val => updateChecklistScore(ans, val, student)" 
                                            />
                                            <span class="font-bold text-sm" :class="ans.score > 0 ? 'text-green-600' : 'text-red-500'">
                                                {{ ans.score > 0 ? 'Tercapai' : 'Belum' }}
                                            </span>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="flex align-items-center gap-2">
                                            <Slider 
                                                v-model="ans.score" 
                                                :max="getMaxScore(ans.question_id)" 
                                                :step="1" 
                                                class="w-full"
                                                @change="calculateTotal(student)"
                                            />
                                            <InputNumber 
                                                v-model="ans.score" 
                                                :min="0" 
                                                :max="getMaxScore(ans.question_id)" 
                                                :step="1" 
                                                inputClass="w-3rem text-center p-1 text-sm font-bold" 
                                                @input="calculateTotal(student)"
                                            />
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="p-3 border surface-border text-center font-bold text-lg text-primary bg-primary-reverse">
                                {{ student._calculatedTotal }}
                            </td>
                        </tr>
                        <tr v-if="form.gradings.length === 0">
                            <td :colspan="assignment.questions.length + 2" class="text-center p-5 text-500">
                                Tidak ada data siswa di kelas ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Slider from 'primevue/slider';
import InputNumber from 'primevue/inputnumber';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    assignment: Object,
    studentsList: Array,
});

const toast = useToast();
const showWarning = ref(true);

const stripTags = (html) => {
    if (!html) return '';
    const tmp = document.createElement("DIV");
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || "";
};

const getMaxScore = (questionId) => {
    const q = props.assignment.questions.find(x => x.id === questionId);
    return q ? Number(q.max_score) : 100;
};

const getQuestionType = (questionId) => {
    const q = props.assignment.questions.find(x => x.id === questionId);
    return q ? q.type : 'performance_indicator';
};

const updateChecklistScore = (ans, val, student) => {
    ans.score = val ? getMaxScore(ans.question_id) : 0;
    calculateTotal(student);
};

const totalMaxRaw = computed(() => {
    return props.assignment.questions.reduce((sum, q) => sum + Number(q.max_score), 0);
});

const form = useForm({
    gradings: props.studentsList.map(s => ({
        student_id: s.id,
        answers: s.answers.map(a => ({
            question_id: a.question_id,
            score: Number(a.score)
        })),
        _calculatedTotal: s.total_score || 0
    }))
});

const getStudentName = (id) => {
    return props.studentsList.find(s => s.id === id)?.full_name || '-';
};
const getStudentNisn = (id) => {
    return props.studentsList.find(s => s.id === id)?.nisn || '-';
};

const calculateTotal = (studentObj) => {
    const rawTotal = studentObj.answers.reduce((sum, ans) => sum + Number(ans.score || 0), 0);
    if (totalMaxRaw.value > 0) {
        studentObj._calculatedTotal = Math.round((rawTotal / totalMaxRaw.value) * 100);
    } else {
        studentObj._calculatedTotal = 0;
    }
};

const submitAll = () => {
    form.post(route('guru.assignments.store_performance_grading', props.assignment.id), {
        preserveScroll: true,
    });
};

const publishGrades = () => {
    router.post(route('guru.assignments.publish_grades', props.assignment.id), {}, {
        preserveScroll: true,
    });
};
</script>

<style scoped>
.bg-primary-reverse {
    background-color: var(--primary-50);
}
</style>
