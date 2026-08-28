<template>
    <SiswaLayout title="Dashboard Ujian CBT">
        <div class="card p-3">
            <div class="p-3 border-round-xl shadow-2 mb-4 text-white flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #0f766e 0%, #064e3b 100%);">
                <div class="flex flex-column gap-1">
                    <span class="text-sm opacity-80">Portal Ujian CBT</span>
                    <span class="text-xl font-bold">Ujian & Evaluasi Belajar</span>
                    <Tag :value="classroom?.name || 'Kelas -'" severity="info" class="bg-white-alpha-20 border-none w-max mt-1" />
                </div>
                <div class="text-right">
                    <i class="pi pi-file-edit text-4xl opacity-50"></i>
                </div>
            </div>

            <div class="mb-4">
                <h3 class="text-900 font-bold mb-3 flex align-items-center gap-2 px-1 text-base">
                    <i class="pi pi-calendar-clock text-primary"></i>
                    Daftar Ujian Anda
                </h3>

                <!-- Active/Available Exams -->
                <div v-if="exams.length > 0" class="flex flex-column gap-3">
                    <div 
                        v-for="exam in exams" 
                        :key="exam.id" 
                        class="surface-card p-3 border-round-xl shadow-1 border-left-3 flex flex-column gap-3"
                        :class="getExamBorderClass(exam)"
                    >
                        <div class="flex justify-content-between align-items-start gap-2">
                            <div class="flex flex-column">
                                <span class="font-bold text-900 text-base line-height-2">{{ exam.title }}</span>
                                <small class="text-600 font-semibold mt-1">Mata Pelajaran: {{ exam.subject_name }}</small>
                            </div>
                            <Tag 
                                :value="formatStatusTag(exam)" 
                                :severity="formatStatusSeverity(exam)" 
                            />
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-nogutter bg-50 p-2 border-round text-xs text-700">
                            <div class="col-6 mb-2">
                                <i class="pi pi-clock text-primary mr-1"></i> Durasi: <b>{{ exam.duration }} Menit</b>
                            </div>
                            <div class="col-6 mb-2 text-right">
                                <i class="pi pi-info-circle text-primary mr-1"></i> Status Sesi: 
                                <b class="capitalize">{{ formatSessionLabel(exam.session_status) }}</b>
                            </div>
                            <div class="col-12 mt-1 pt-1 border-top-1 border-200">
                                <i class="pi pi-calendar-times text-danger mr-1"></i> Batas Waktu: 
                                <b>{{ formatExamDate(exam.end_time) }}</b>
                            </div>
                        </div>

                        <!-- Action Button or Score Display -->
                        <div class="flex justify-content-between align-items-center mt-1">
                            <div v-if="exam.session_status === 'submitted'" class="flex align-items-center gap-2">
                                <span class="text-xs text-600 font-medium">Ujian dikumpulkan pada: {{ exam.submitted_at }}</span>
                            </div>
                            <!-- Pesan menunggu izin guru (setelah di-kick pelanggaran ke-3) -->
                            <div v-else-if="exam.session_status === 'logged_out'" class="flex align-items-center gap-2 text-xs font-semibold text-orange-600">
                                <i class="pi pi-clock"></i>
                                <span>Menunggu izin Guru/Pengajar untuk masuk kembali</span>
                            </div>
                            <div v-else class="text-xs text-500 font-medium">
                                <span v-if="exam.is_upcoming">Dibuka: {{ formatExamDate(exam.start_time) }}</span>
                                <span v-else-if="exam.is_closed" class="text-red-500 font-bold">Sesi Ujian Ditutup</span>
                                <span v-else class="text-green-600 font-bold"><i class="pi pi-spin pi-spinner text-xs mr-1"></i> Ujian Sedang Berjalan</span>
                            </div>

                            <!-- Button Mulai/Lanjutkan -->
                            <Button 
                                v-if="exam.session_status !== 'submitted' && exam.session_status !== 'logged_out' && exam.is_open"
                                :label="exam.session_status === 'started' ? 'Lanjutkan Ujian' : 'Mulai Ujian'" 
                                :icon="exam.session_status === 'started' ? 'pi pi-arrow-right' : 'pi pi-play'" 
                                :severity="exam.session_status === 'started' ? 'warning' : 'primary'"
                                size="small"
                                class="border-round-lg px-4"
                                @click="startExam(exam)"
                            />

                            <!-- Score Badge -->
                            <div v-if="exam.session_status === 'submitted'" class="flex align-items-center gap-2">
                                <span class="text-xs text-500 font-bold">Skor:</span>
                                <Tag 
                                    :value="exam.score !== null ? exam.score.toString() : 'Sedang Dinilai'" 
                                    :severity="exam.score >= 70 ? 'success' : 'danger'"
                                    class="text-sm px-2 py-1 font-bold font-mono"
                                />
                            </div>
                        </div>
                    </div>
                </div>

        <!-- Empty State -->
                <div v-else class="text-center p-5 surface-100 border-round-xl border-dashed border-2 text-500 mt-2">
                    <i class="pi pi-calendar-times text-3xl mb-2"></i>
                    <p class="m-0 text-sm">Tidak ada jadwal ujian untuk kelas Anda saat ini.</p>
                </div>
            </div>
        </div>

        <!-- Token Input Dialog -->
        <Dialog v-model:visible="displayTokenModal" header="Masukkan Token Ujian" :modal="true" :style="{ width: '380px' }">
            <div class="p-fluid">
                <p class="text-sm text-slate-500 mb-3">
                    Mata Pelajaran: <b>{{ activeExam?.subject_name }}</b><br>
                    Minta token ujian aktif kepada Pengawas Ruangan Anda.
                </p>
                <div class="field mb-3">
                    <label for="token" class="font-semibold text-sm block mb-1">Token Pengawas</label>
                    <InputText 
                        id="token" 
                        v-model="tokenInput" 
                        placeholder="Contoh: ABCDEF" 
                        class="w-full text-center font-bold text-xl uppercase"
                        @keyup.enter="verifyToken"
                    />
                    <small class="text-red-500 block mt-1 font-semibold" v-if="tokenError">{{ tokenError }}</small>
                </div>
                <div class="flex justify-content-end gap-2 mt-4">
                    <Button label="Batal" severity="secondary" text @click="displayTokenModal = false" />
                    <Button label="Mulai Ujian" :loading="isVerifyingToken" @click="verifyToken" />
                </div>
            </div>
        </Dialog>
    </SiswaLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';

const props = defineProps({
    exams: Array,
    classroom: Object,
});

const displayTokenModal = ref(false);
const tokenInput = ref('');
const tokenError = ref('');
const activeExam = ref(null);
const isVerifyingToken = ref(false);

const startExam = (exam) => {
    if (exam.session_status === 'started' || exam.session_status === 'login') {
        router.get(route('student.cbt.exam', exam.id));
    } else if (exam.is_independent && (!exam.session_status || exam.session_status === 'not_started')) {
        router.get(route('student.cbt.exam', exam.id));
    } else {
        activeExam.value = exam;
        tokenInput.value = '';
        tokenError.value = '';
        displayTokenModal.value = true;
    }
};

const verifyToken = async () => {
    if (!tokenInput.value) {
        tokenError.value = 'Token wajib diisi.';
        return;
    }

    isVerifyingToken.value = true;
    tokenError.value = '';

    try {
        const response = await axios.post(route('student.cbt.verify-token', activeExam.value.id), {
            token: tokenInput.value,
        });

        if (response.data.success) {
            displayTokenModal.value = false;
            router.get(route('student.cbt.exam', activeExam.value.id));
        }
    } catch (e) {
        if (e.response && e.response.data && e.response.data.error) {
            tokenError.value = e.response.data.error;
        } else {
            tokenError.value = 'Terjadi kesalahan sistem. Coba lagi.';
        }
    } finally {
        isVerifyingToken.value = false;
    }
};

const getExamBorderClass = (exam) => {
    if (exam.session_status === 'submitted') return 'border-success border-green-500';
    if (exam.session_status === 'logged_out') return 'border-warning border-orange-500';
    if (exam.is_upcoming) return 'border-secondary border-500';
    if (exam.is_closed) return 'border-danger border-red-500';
    return 'border-primary border-blue-500';
};

const formatStatusTag = (exam) => {
    if (exam.session_status === 'submitted') return 'Selesai';
    if (exam.session_status === 'logged_out') return 'Menunggu Izin Guru';
    if (exam.is_upcoming) return 'Mendatang';
    if (exam.is_closed) return 'Ditutup';
    return 'Aktif / Buka';
};

const formatStatusSeverity = (exam) => {
    if (exam.session_status === 'submitted') return 'success';
    if (exam.session_status === 'logged_out') return 'warn';
    if (exam.is_upcoming) return 'secondary';
    if (exam.is_closed) return 'danger';
    return 'warn';
};

const formatSessionLabel = (status) => {
    const labels = {
        'not_started': 'Belum Mulai',
        'login':       'Menunggu Masuk',
        'started':     'Sedang Dikerjakan',
        'submitted':   'Telah Dikirim',
        'logged_out':  'Dikeluarkan (Izin Guru)',
    };
    return labels[status] || status;
};

const formatExamDate = (dateStr) => {
    if (!dateStr) return '-';
    // Backend sends ISO 8601 with +07:00 offset, new Date() parses this correctly.
    // toLocaleString will display in the user's local timezone.
    // We explicitly format in Asia/Jakarta to ensure WIB is always shown.
    return new Date(dateStr).toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }) + ' WIB';
};
</script>
