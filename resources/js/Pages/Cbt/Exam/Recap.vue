<template>
    <AppLayout title="Rekap Jawaban Siswa">
        <div class="card">
            <!-- Header section -->
            <div class="flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
                <div class="flex align-items-center gap-2">
                    <Link :href="route('cbt.exams.results', exam.id)">
                        <Button icon="pi pi-arrow-left" severity="secondary" text rounded />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-900 m-0">Rekap Jawaban Siswa: {{ exam.title }}</h2>
                        <span class="text-500 block mt-1">
                            Mapel: <b>{{ exam.bank?.subject?.name || '-' }}</b> | Durasi: <b>{{ exam.duration }} Menit</b>
                        </span>
                    </div>
                </div>

                <div>
                    <a :href="route('cbt.exams.results.export', exam.id)" target="_blank">
                        <Button label="Unduh Excel (xlsx)" icon="pi pi-file-excel" severity="success" />
                    </a>
                </div>
            </div>

            <!-- Legend Info -->
            <div class="flex gap-4 align-items-center mb-4 p-3 bg-gray-50 border-round border border-200 text-xs">
                <span class="font-bold text-700">Keterangan Warna:</span>
                <div class="flex align-items-center gap-1">
                    <span class="w-1rem h-1rem border-round bg-green-50 border border-green-300"></span>
                    <span class="text-green-700 font-semibold">Jawaban Benar</span>
                </div>
                <div class="flex align-items-center gap-1">
                    <span class="w-1rem h-1rem border-round bg-red-50 border border-red-300"></span>
                    <span class="text-red-700 font-semibold">Jawaban Salah</span>
                </div>
                <div class="flex align-items-center gap-1">
                    <span class="w-1rem h-1rem border-round bg-gray-50 border border-200"></span>
                    <span class="text-500">Tidak Menjawab / Kosong (-)</span>
                </div>
            </div>

            <!-- Matrix Recap Table -->
            <div class="overflow-x-auto border-round border border-300 shadow-1 bg-white">
                <table class="w-full text-left border-collapse" style="font-family: inherit;">
                    <thead>
                        <!-- Row 1: Header Soal & Kunci Jawaban -->
                        <tr class="bg-gray-800 text-white font-bold border-bottom-2 border-gray-700">
                            <th class="p-3 text-center border-right-1 border-gray-700 sticky left-0 z-3 bg-gray-800" style="min-width: 200px;">Nama Siswa</th>
                            <th class="p-3 text-center border-right-1 border-gray-700 sticky left-200 z-3 bg-gray-800" style="min-width: 130px; left: 200px;">NISN</th>
                            <th class="p-3 text-center border-right-1 border-gray-700 sticky left-330 z-3 bg-gray-800" style="min-width: 100px; left: 330px;">Kelas</th>
                            <th class="p-3 text-center border-right-1 border-gray-700 sticky z-3 bg-gray-800" style="min-width: 80px; left: 430px;">Nilai</th>
                            <th v-for="(h, idx) in headers" :key="idx" class="p-3 text-center border-right-1 border-gray-700" style="min-width: 140px;">
                                <div class="text-xs text-gray-300 font-medium mb-1">{{ h.label }}</div>
                                <div class="text-sm font-bold text-white bg-gray-900 px-2 py-1 border-round truncate" v-tooltip.bottom="'Kunci Jawaban: ' + h.correct">
                                    Kunci: {{ h.correct }}
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(student, sIdx) in matrix" :key="sIdx" class="hover:bg-gray-50 transition-all border-bottom-1 border-200">
                            <!-- Locked Student Info Columns -->
                            <td class="p-3 font-semibold text-900 border-right-1 border-200 sticky left-0 bg-white" style="z-index: 1;">{{ student.name }}</td>
                            <td class="p-3 text-600 border-right-1 border-200 sticky left-200 bg-white text-center" style="left: 200px; z-index: 1;">{{ student.nisn }}</td>
                            <td class="p-3 border-right-1 border-200 sticky left-330 bg-white text-center" style="left: 330px; z-index: 1;">
                                <span class="bg-blue-50 text-blue-700 font-semibold px-2 py-1 border-round text-xs">{{ student.classroom_name }}</span>
                            </td>
                            <td class="p-3 border-right-1 border-200 sticky bg-white text-center" style="left: 430px; z-index: 1;">
                                <span 
                                    v-if="student.score !== null" 
                                    class="font-bold px-2 py-1 border-round text-xs animate-duration-150"
                                    :class="student.score >= 70 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
                                >
                                    {{ student.score }}
                                </span>
                                <span v-else class="text-500 italic text-xs">-</span>
                            </td>
                            <!-- Dynamic Answers Columns -->
                            <td 
                                v-for="(ans, aIdx) in student.answers" 
                                :key="aIdx"
                                class="p-3 text-center font-bold border-right-1 border-200 text-sm"
                                :class="getAnswerCellClass(ans)"
                            >
                                {{ ans.val }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';

defineProps({
    exam: Object,
    headers: Array,
    matrix: Array,
});

const getAnswerCellClass = (ans) => {
    if (!ans.attempted) {
        return 'bg-gray-50 text-500 font-normal border-200';
    }
    return ans.is_correct 
        ? 'bg-green-50 text-green-700 border-green-200' 
        : 'bg-red-50 text-red-700 border-red-200';
};
</script>

<style scoped>
/* Scoped styles to ensure header colors are visible and correct under all conditions */
th {
    background-color: #1f2937 !important; /* bg-gray-800 */
    color: #ffffff !important;
    border-color: #374151 !important; /* border-gray-700 */
}

/* Sticky columns formatting support */
.sticky {
    position: sticky;
}
.z-3 {
    z-index: 3;
}
</style>
