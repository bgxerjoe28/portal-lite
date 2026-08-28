<template>
    <AppLayout>
        <Head title="Proses Kenaikan Kelas & Kelulusan" />
        <div class="card">
            <div class="flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Kenaikan Kelas & Kelulusan</h2>
                    <span class="text-500 block mt-1">
                        Pindahkan siswa secara massal antar tahun ajaran, proses kelulusan, atau tinggal kelas.
                    </span>
                </div>
                <Link :href="route('admin.classrooms.index')">
                    <Button label="Kembali ke Kelas" icon="pi pi-arrow-left" severity="secondary" outlined />
                </Link>
            </div>

            <div class="grid mb-4">
                <div class="col-12 md:col-6">
                    <div class="surface-card p-4 shadow-1 border-round h-full border-top-3 border-orange-500">
                        <h3 class="text-xl font-semibold text-orange-700 mt-0 mb-3 flex align-items-center gap-2">
                            <i class="pi pi-sign-out"></i> Kelas Asal (Sumber)
                        </h3>
                        <div class="field mb-3">
                            <label class="font-medium mb-2 block">Tahun Ajaran Asal</label>
                            <Select 
                                v-model="sourceYear" 
                                :options="academicYears" 
                                optionLabel="name" 
                                optionValue="id" 
                                placeholder="Pilih Tahun Ajaran Asal" 
                                class="w-full"
                                @change="handleSourceYearChange"
                            >
                                <template #option="slotProps">
                                    {{ slotProps.option.name }} ({{ slotProps.option.semester }})
                                    <span v-if="slotProps.option.is_active" class="ml-2 font-bold text-green-500">(Aktif)</span>
                                </template>
                            </Select>
                        </div>
                        <div class="field">
                            <label class="font-medium mb-2 block">Kelas Asal</label>
                            <Select 
                                v-model="sourceClassroom" 
                                :options="sourceClassrooms" 
                                optionLabel="name" 
                                optionValue="id" 
                                placeholder="Pilih Kelas Asal" 
                                class="w-full"
                                :disabled="!sourceYear"
                                @change="loadStudents"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="surface-card p-4 shadow-2 border-round" v-if="students.length > 0">
                <div class="flex justify-content-between align-items-center mb-3">
                    <h3 class="text-lg font-bold m-0 flex align-items-center gap-2">
                        <i class="pi pi-users text-primary"></i> Daftar Siswa Kelas Asal (Total: {{ students.length }})
                    </h3>
                    <div class="flex gap-2">
                        <Button v-if="Number(selectedSourceLevel) !== 12" label="Set Semua Naik" severity="info" size="small" outlined @click="setAllStatus('naik')" />
                        <Button v-if="Number(selectedSourceLevel) === 12" label="Set Semua Lulus" severity="success" size="small" outlined @click="setAllStatus('lulus')" />
                    </div>
                </div>

                <DataTable :value="students" stripedRows dataKey="id" class="p-datatable-sm">
                    <Column header="No" style="width: 5rem">
                        <template #body="{ index }"> {{ index + 1 }} </template>
                    </Column>
                    <Column field="nisn" header="NISN" style="width: 10rem" />
                    <Column field="full_name" header="Nama Siswa">
                        <template #body="{ data }">
                            <span class="font-semibold">{{ data.full_name }}</span>
                        </template>
                    </Column>
                    <Column header="Status Kenaikan" style="width: 20rem">
                        <template #body="{ data }">
                            <Select 
                                v-model="promotions[data.id].status" 
                                :options="statusOptions" 
                                optionLabel="label" 
                                optionValue="value" 
                                class="w-full"
                            />
                        </template>
                    </Column>
                </DataTable>

                <div class="flex justify-content-end gap-2 mt-4 pt-3 border-top-1 border-200">
                    <Button label="Batal" severity="secondary" text @click="resetForm" />
                    <Button :label="submitButtonLabel" icon="pi pi-check" severity="primary" :loading="processing" @click="submitPromotion" />
                </div>
            </div>

            <div class="surface-hover p-5 border-round text-center text-500" v-else>
                <i class="pi pi-info-circle text-4xl mb-3 block text-primary"></i>
                Pilih <b>Tahun Ajaran Asal</b> dan <b>Kelas Asal</b> terlebih dahulu untuk menampilkan daftar siswa.
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({ academicYears: Array, activeYear: Object });
const confirm = useConfirm();
const toast = useToast();

const sourceYear = ref(null);
const sourceClassroom = ref(null);
const sourceClassrooms = ref([]);
const students = ref([]);
const promotions = reactive({});
const processing = ref(false);

const selectedSourceLevel = computed(() => {
    const found = sourceClassrooms.value.find(c => c.id === sourceClassroom.value);
    return found ? found.level : null;
});

const statusOptions = [
    { label: 'Naik Kelas', value: 'naik' },
    { label: 'Tinggal Kelas', value: 'tinggal' },
    { label: 'Lulus', value: 'lulus' },
    { label: 'Keluar', value: 'keluar' },
    { label: 'Batal / Reset', value: 'batal' }
];

const promotionSummary = computed(() => {
    const list = Object.values(promotions);
    const summary = { naik: 0, tinggal: 0, lulus: 0, keluar: 0, total: list.length };
    list.forEach(p => { if (summary[p.status] !== undefined) summary[p.status]++; });
    return summary;
});

const submitButtonLabel = computed(() => {
    const summary = promotionSummary.value;
    if (summary.lulus > 0 && (summary.naik + summary.tinggal) === 0) return 'Proses Kelulusan';
    return 'Proses Kenaikan Kelas';
});

const handleSourceYearChange = async () => {
    sourceClassroom.value = null;
    students.value = [];
    if (!sourceYear.value) return sourceClassrooms.value = [];
    try {
        const response = await axios.get(route('admin.classrooms.by-year', sourceYear.value));
        sourceClassrooms.value = response.data;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal load kelas' });
    }
};

const loadStudents = async () => {
    if (!sourceClassroom.value) return;
    try {
        const response = await axios.get(route('admin.classrooms.promotion.students', {
            classroom_id: sourceClassroom.value,
            academic_year_id: sourceYear.value
        }));
        students.value = response.data;
        // Reset promotions
        for (const key in promotions) delete promotions[key];
        students.value.forEach(std => {
            let currentStatus = 'naik'; // default if aktif
            if (std.pivot_status === 'retained') currentStatus = 'tinggal';
            else if (std.pivot_status === 'lulus') currentStatus = 'lulus';
            else if (std.pivot_status === 'keluar') currentStatus = 'keluar';
            else if (std.pivot_status === 'promoted') currentStatus = 'naik';
            
            promotions[std.id] = { student_id: std.id, status: currentStatus };
        });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal load siswa' });
    }
};

const setAllStatus = (status) => {
    students.value.forEach(std => { if (promotions[std.id]) promotions[std.id].status = status; });
};

const resetForm = () => {
    sourceYear.value = null;
    sourceClassroom.value = null;
    sourceClassrooms.value = [];
    students.value = [];
};

const submitPromotion = () => {
    confirm.require({
        message: 'Apakah Anda yakin ingin memproses data ini?',
        header: 'Konfirmasi',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            processing.value = true;
            router.post(route('admin.classrooms.promotion.store'), {
                source_year_id: sourceYear.value,
                source_classroom_id: sourceClassroom.value,
                promotions: Object.values(promotions)
            }, {
                onFinish: () => processing.value = false,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Proses kenaikan kelas/kelulusan selesai diproses.' });
                    resetForm();
                }
            });
        }
    });
};
</script>