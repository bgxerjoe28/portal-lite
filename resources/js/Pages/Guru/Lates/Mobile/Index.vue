<template>
    <MobileLayout title="Siswa Terlambat">
        <div class="p-3 mb-8">
            <div class="flex flex-column gap-1 mb-4">
                <h2 class="text-xl font-bold m-0 text-teal-700">
                    <i class="pi pi-clock mr-2"></i>Input Keterlambatan
                </h2>
                <small class="text-500 font-medium">{{ formatDate(today) }}</small>
            </div>

            <div class="surface-card p-4 shadow-3 border-round-xl mb-5 border-top-4 border-teal-500 bg-teal-50">
                <div class="p-fluid flex flex-column gap-4">
                    <div class="field">
                        <label class="font-semibold text-lg block mb-2 text-700">Cari Siswa / NIS</label>
                        <AutoComplete
                            v-model="selectedStudent"
                            :suggestions="studentOptions"
                            @complete="searchStudents"
                            optionLabel="full_name"
                            placeholder="Ketik nama..."
                            @item-select="onStudentSelect"
                            class="w-full"
                            inputClass="py-3 text-lg border-round-lg"
                        >
                            <template #option="slotProps">
                                <div class="flex flex-column">
                                    <span class="font-bold">{{ slotProps.option.full_name }}</span>
                                    <small class="text-500">NIS: {{ slotProps.option.nis }}</small>
                                </div>
                            </template>
                        </AutoComplete>
                    </div>

                    <div class="field">
                        <label class="font-semibold text-lg block mb-2 text-700">Alasan</label>
                        <Textarea
                            v-model="form.reason"
                            rows="3"
                            placeholder="Contoh: Ban bocor, Bangun kesiangan..."
                            class="w-full text-lg p-3 border-round-lg"
                            autoResize
                        />
                    </div>

                    <Button
                        label="Catat & Simpan"
                        icon="pi pi-save"
                        class="p-button-teal w-full font-bold text-xl py-3 shadow-3 border-round-lg"
                        @click="submit"
                        :loading="form.processing"
                        :disabled="!form.student_id"
                    />
                </div>
            </div>

            <div class="flex justify-content-between align-items-center mb-3">
                <h3 class="text-sm font-bold text-500 uppercase tracking-wider m-0">Daftar Hari Ini</h3>
                <Tag severity="success" :value="lates.length + ' Siswa'" rounded />
            </div>

            <div v-if="lates.length === 0" class="text-center py-6 surface-card border-round-xl shadow-1">
                <i class="pi pi-check-circle text-4xl text-200 mb-3"></i>
                <p class="text-500 m-0 font-medium">Belum ada siswa terlambat.</p>
            </div>

            <div class="flex flex-column gap-3">
                <div 
                    v-for="late in lates" 
                    :key="late.id" 
                    class="flex align-items-center gap-3 p-3 surface-card border-round-xl shadow-2 border-left-3 border-teal-500"
                >
                    <Avatar 
                        :label="late.student.full_name.charAt(0)" 
                        shape="circle" 
                        size="large" 
                        class="bg-teal-50 text-teal-700 font-bold flex-shrink-0" 
                    />

                    <div class="flex-1 overflow-hidden">
                        <div class="flex justify-content-between align-items-start">
                            <span class="text-sm font-bold text-900 line-height-2">{{ late.student.full_name }}</span>
                            <small class="text-teal-600 font-bold ml-2">{{ late.student.current_classroom?.name }}</small>
                        </div>
                        <div class="text-xs text-600 mt-1 italic">
                            "{{ late.reason }}"
                        </div>
                    </div>

                    <div class="flex gap-1">
                        <Button 
                            icon="pi pi-file-pdf" 
                            severity="danger" 
                            rounded 
                            text
                            v-tooltip.top="'Simpan PDF'"
                            @click="downloadPdf(late.id)" 
                        />
                        <Button 
                            icon="pi pi-print" 
                            severity="info" 
                            text 
                            rounded 
                            @click="printLate(late.id)" 
                        />
                        <Button 
                            icon="pi pi-trash" 
                            severity="danger" 
                            text 
                            rounded 
                            @click="confirmDelete(late.id)" 
                        />
                    </div>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import AutoComplete from 'primevue/autocomplete';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    lates: Array,
    today: String
});

const selectedStudent = ref(null);
const studentOptions = ref([]);

const form = useForm({
    student_id: null,
    reason: '',
});

// 🚀 Pencarian Siswa via Service (Autocomplete)
const searchStudents = async (event) => {
    if (event.query.length < 3) return;
    try {
        const response = await axios.get(route('lates.search'), {
            params: { query: event.query }
        });
        studentOptions.value = response.data;
    } catch (error) {
        console.error('Gagal mencari siswa:', error);
    }
};

const onStudentSelect = (event) => {
    form.student_id = event.value.id;
};

// 🚀 Simpan via Slim Controller
const submit = () => {
    form.post(route('lates.store'), {
        onSuccess: () => {
            form.reset();
            selectedStudent.value = null;
        }
    });
};

// 🚀 Cetak Karcis 58mm
const printLate = (id) => {
    window.open(route('lates.print', id), '_blank');
};

const confirmDelete = (id) => {
    if (confirm('Hapus data ini?')) {
        router.delete(route('lates.destroy', id));
    }
};

const downloadPdf = (id) => {
    window.location.href = route('lates.pdf', id);
};
const formatDate = (val) => dayjs(val).format('dddd, DD MMMM YYYY');
</script>

<style scoped>
.p-button-teal {
    background-color: #0d9488 !important;
    border-color: #0d9488 !important;
}

/* Memperbesar area sentuh tombol pada mobile */
.p-button.p-button-rounded {
    width: 3rem;
    height: 3rem;
}

.surface-card:active {
    transform: scale(0.97);
    transition: all 0.2s ease;
}
</style>