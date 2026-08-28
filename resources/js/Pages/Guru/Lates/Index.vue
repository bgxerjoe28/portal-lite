<template>
    <AppLayout title="Siswa Terlambat">
        <div class="card border-0 shadow-none">
            <div class="flex flex-column gap-2 mb-5">
                <div class="flex align-items-center gap-3">
                    <i class="pi pi-clock text-teal-600 text-2xl"></i>
                    <h2 class="text-2xl font-bold m-0 text-900">
                        Pencatatan Siswa Terlambat
                    </h2>
                </div>
                <p class="text-500 text-sm">
                    Pendataan siswa yang datang terlambat — {{ formatDate(today) }}
                </p>
            </div>

            <div class="grid justify-content mb-6">
                <div class="col-12 lg:col-8">
                    <div class="surface-card p-5 shadow-2 border-round-xl border-left-4 border-teal-500">
                        <h3 class="text-lg font-semibold mb-4 text-900 flex align-items-center gap-2">
                            <i class="pi pi-file-edit text-teal-600"></i>
                            Formulir Keterlambatan
                        </h3>
                        
                        <div class="grid formgrid p-fluid">
                        <div class="field col-12 mb-4">
                            <label for="student-search" class="font-semibold text-lg block mb-2">Nama Siswa / NIS</label>
                            <IconField iconPosition="left" class="w-full">
                                <InputIcon class="pi pi-search" />
                                <AutoComplete
                                    id="student-search"
                                    v-model="selectedStudent"
                                    :suggestions="studentOptions"
                                    @complete="searchStudents"
                                    optionLabel="full_name"
                                    placeholder="Cari nama siswa atau NIS…"
                                    @item-select="onStudentSelect"
                                    autofocus
                                    class="w-full"
                                    inputClass="w-full py-3 text-base"
                                    :inputStyle="{'padding-left': '3.5rem'}"
                                    
                                >
                                    <template #option="slotProps">
                                        <div class="flex align-items-center gap-3">
                                            <Avatar icon="pi pi-user" shape="circle" class="bg-teal-100 text-teal-600" />
                                            <div class="flex flex-column">
                                                <span class="font-semibold">{{ slotProps.option.full_name }}</span>
                                                <small class="text-500">NIS {{ slotProps.option.nis }}</small>
                                            </div>
                                        </div>
                                    </template>
                                </AutoComplete>
                            </IconField>
                        </div>

                            <div class="field col-12 mb-4">
                                <label for="reason" class="font-semibold text-lg block mb-2">Keterangan Terlambat</label>
                                <Textarea
                                    id="reason"
                                    v-model="form.reason"
                                    rows="4"
                                    placeholder="Catatan keterlambatan siswa…"
                                    class="w-full text-base"
                                    autoResize
                                />
                            </div>

                            <div class="col-12 mt-3">
                                <Button
                                    label="Simpan Data"
                                    icon="pi pi-check"
                                    class="w-full font-semibold py-3"
                                    severity="primary"
                                    @click="submit"
                                    :loading="form.processing"
                                    :disabled="!form.student_id"
                                    raised
                                />
                                <p class="text-center text-500 mt-3 text-sm italic">
                                    <i class="pi pi-lock mr-1"></i> Data akan otomatis terkunci di Agenda Mengajar Guru.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DataTable :value="lates" stripedRows responsiveLayout="stack" class="p-datatable-sm shadow-2 border-round-lg overflow-hidden">
                <template #header>
                    <div class="flex align-items-center justify-content-between px-3 py-2">
                        <span class="font-semibold text-900">
                            Daftar Terlambat Hari Ini
                        </span>
                        <Tag :value="lates.length + ' siswa'" severity="info" />
                    </div>
                </template>
                <template #empty>
                    <div class="p-4 text-center text-500">Belum ada data siswa terlambat hari ini.</div>
                </template>

                <Column field="student.full_name" header="Nama Siswa">
                    <template #body="{ data }">
                        <div class="flex align-items-center gap-3">
                            <Avatar
                                :label="data.student.full_name.charAt(0)"
                                shape="circle"
                                class="bg-primary text-white"
                            />
                            <div>
                                <div class="font-semibold">{{ data.student.full_name }}</div>
                                <small class="text-500">NIS {{ data.student.nis }}</small>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column field="student.current_classroom.name" header="Kelas">
                    <template #body="{ data }">
                        <Tag severity="info" :value="data.student.current_classroom?.name" class="px-3 py-2 text-sm" rounded />
                    </template>
                </Column>
                <Column field="reason" header="Keterangan Terlambat" class="text-lg" />
                <Column header="Aksi" style="width: 140px" class="text-center">
                    <template #body="{ data }"> <div class="flex justify-content-center gap-2">
                            <Button 
                                icon="pi pi-file-pdf" 
                                severity="danger" 
                                rounded 
                                text
                                v-tooltip.top="'Simpan PDF'"
                                @click="downloadPdf(data.id)" 
                            />
                            <Button 
                                icon="pi pi-print" 
                                severity="info" 
                                rounded 
                                v-tooltip.top="'Cetak Surat'"
                                @click="printLate(data.id)" 
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                @click="confirmDelete(data.id)"
                                v-tooltip.top="'Hapus'"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import AutoComplete from 'primevue/autocomplete';
import Avatar from 'primevue/avatar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import Textarea from 'primevue/textarea';
import Tooltip from 'primevue/tooltip';

dayjs.locale('id');

const props = defineProps({
    lates: Array,
    today: String
});

const selectedStudent = ref(null);
const studentOptions = ref([]);

const form = useForm({
    student_id: null,
    reason: '', // Field ini akan menampung keterangan terlambat
});

const searchStudents = async (event) => {
    if (event.query.length < 3) return;
    try {
        const response = await axios.get(route('lates.search'), {
            params: { query: event.query }
        });
        studentOptions.value = response.data;
    } catch (error) {
        console.error(error);
    }
};

const onStudentSelect = (event) => {
    form.student_id = event.value.id;
};

const submit = () => {
    form.post(route('lates.store'), {
        onSuccess: () => {
            form.reset();
            selectedStudent.value = null;
        }
    });
};

const confirmDelete = (id) => {
    if (confirm('Hapus catatan keterlambatan ini?')) {
        router.delete(route('lates.destroy', id));
    }
};
const printLate = (id) => {
    window.open(route('lates.print', id), '_blank');
};
const downloadPdf = (id) => {
    window.location.href = route('lates.pdf', id);
};

const formatDate = (val) => dayjs(val).format('dddd, DD MMMM YYYY');
</script>
<style scoped>
/* Tambahan style agar tombol berwarna teal */
.p-button.p-button-teal {
    background-color: #14b8a6; /* Warna teal-500 dari Tailwind */
    border-color: #14b8a6;
}
.p-button.p-button-teal:enabled:hover,
.p-button.p-button-teal:not(button):hover {
    background-color: #0d9488; /* Warna teal-600 dari Tailwind */
    border-color: #0d9488;
}
/* Style untuk textarea agar tidak terlalu tinggi defaultnya */
.p-inputtextarea {
    resize: vertical; /* Hanya bisa di-resize vertikal */
    min-height: 80px;
}
</style>