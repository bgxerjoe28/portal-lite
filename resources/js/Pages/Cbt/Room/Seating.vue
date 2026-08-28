<template>
    <AppLayout title="Atur Tempat Duduk">
        <div class="card">
            <!-- Header section -->
            <div class="flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
                <div class="flex align-items-center gap-2">
                    <Link :href="route('cbt.rooms.index')">
                        <Button icon="pi pi-arrow-left" severity="secondary" text rounded />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-900 m-0">Atur Tempat Duduk: {{ room.name }}</h2>
                        <span class="text-500 block mt-1">
                            Kapasitas: <b>{{ room.capacity }} Kursi</b> | Terisi: <b>{{ filledSeatsCount }} Kursi</b>
                        </span>
                    </div>
                </div>

                <div class="flex gap-2 align-items-center">
                    <Button label="Import Denah" icon="pi pi-upload" severity="primary" @click="displayImportModal = true" />
                </div>
            </div>

            <!-- Seat Grid (6x6) -->
            <div class="surface-card p-4 shadow-2 border-round">
                <div class="grid-layout">
                    <div 
                        v-for="seatNum in 36" 
                        :key="seatNum" 
                        class="seat-box border-round border transition-all duration-150 relative flex flex-column justify-content-between p-3"
                        :class="seatingMap[seatNum] 
                            ? 'bg-blue-50 border-blue-200 shadow-sm' 
                            : 'bg-slate-50 border-dashed border-300 hover:bg-slate-100 hover:border-400 cursor-pointer'"
                        @click="!seatingMap[seatNum] && openAssignModal(seatNum)"
                    >
                        <!-- Top Row: Seat Number & Delete button -->
                        <div class="flex justify-content-between align-items-center w-full">
                            <span class="seat-badge text-xs font-bold text-600 px-2 py-1 border-round bg-slate-200">
                                Kursi {{ seatNum }}
                            </span>
                            <Button 
                                v-if="seatingMap[seatNum]"
                                icon="pi pi-trash" 
                                severity="danger" 
                                text 
                                rounded 
                                size="small"
                                class="h-2rem w-2rem"
                                v-tooltip.top="'Kosongkan Kursi'"
                                @click.stop="confirmClearSeat(seatNum)"
                            />
                        </div>

                        <!-- Center: Student Details or Empty State -->
                        <div class="my-3 flex-grow-1 flex flex-column justify-content-center">
                            <div v-if="seatingMap[seatNum]">
                                <div class="font-bold text-slate-800 text-sm truncate" :title="seatingMap[seatNum].student_name">
                                    {{ seatingMap[seatNum].student_name }}
                                </div>
                                <div class="text-xs text-slate-500 mt-1">
                                    NISN: {{ seatingMap[seatNum].nisn }}
                                </div>
                                <div class="mt-2">
                                    <span class="bg-blue-100 text-blue-800 font-semibold px-2 py-0.5 border-round text-xxs">
                                        {{ seatingMap[seatNum].classroom_name }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="flex flex-column align-items-center justify-content-center text-400 py-3">
                                <i class="pi pi-plus text-lg mb-1"></i>
                                <span class="text-xs font-semibold">Duduki</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seat Assignment Modal -->
        <Dialog v-model:visible="displayModal" :header="'Pilih Siswa untuk Kursi ' + activeSeat" :modal="true" :style="{ width: '550px' }">
            <div class="p-fluid">
                <!-- Search bar -->
                <div class="field mb-3 relative">
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="searchQuery" placeholder="Cari siswa berdasarkan nama, NISN, atau kelas..." class="w-full" />
                    </IconField>
                </div>

                <!-- Students list -->
                <div class="students-list border-round border border-300 p-2 overflow-y-auto mb-4 bg-white" style="max-height: 300px;">
                    <div 
                        v-for="s in filteredStudents" 
                        :key="s.id"
                        class="student-item flex justify-content-between align-items-center p-3 border-round cursor-pointer hover:bg-slate-50 border-bottom-1 border-100"
                        @click="selectStudent(s)"
                        :class="selectedStudent?.id === s.id ? 'bg-blue-50 border border-blue-300 font-semibold' : ''"
                    >
                        <div>
                            <span class="font-bold text-slate-800 block">{{ s.name }}</span>
                            <small class="text-slate-500 block mt-1">NISN: {{ s.nisn }} | Kelas: <b>{{ s.classroom_name }}</b></small>
                        </div>
                        <i v-if="selectedStudent?.id === s.id" class="pi pi-check-circle text-blue-600 text-lg"></i>
                    </div>
                    <div v-if="filteredStudents.length === 0" class="text-center text-500 py-5">
                        Siswa tidak ditemukan atau sudah memiliki kursi.
                    </div>
                </div>

                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" severity="secondary" text @click="displayModal = false" />
                    <Button label="Tempatkan" :disabled="!selectedStudent" @click="submitAssignment" />
                </div>
            </div>
        </Dialog>

        <!-- Seating Import Modal -->
        <Dialog v-model:visible="displayImportModal" header="Import Denah Tempat Duduk" :modal="true" :style="{ width: '450px' }">
            <div class="p-fluid">
                <p class="text-sm text-slate-600 mb-3 line-height-3">
                    Unggah file Excel (.xlsx, .xls) atau CSV yang berisi data penempatan kursi siswa.
                </p>
                <div class="p-3 bg-50 border-round border border-200 mb-4 text-xs text-700">
                    <span class="font-bold block mb-1">Format file harus memiliki kolom:</span>
                    <ul class="m-0 pl-3">
                        <li><b>nisn</b>: Nomor Induk Siswa Nasional</li>
                        <li><b>kursi</b>: Nomor kursi (angka 1 - 36)</li>
                    </ul>
                    <a href="#" @click.prevent="downloadTemplate" class="text-primary font-bold block mt-2 hover:underline">
                        <i class="pi pi-download mr-1"></i> Download Template Excel
                    </a>
                </div>
                
                <div class="field mb-4">
                    <label for="importFile" class="font-semibold text-sm block mb-1">Pilih File</label>
                    <input 
                        type="file" 
                        id="importFile" 
                        ref="fileInputRef"
                        accept=".xlsx,.xls,.csv" 
                        class="p-inputtext w-full"
                        @change="handleFileChange"
                    />
                </div>

                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" severity="secondary" text @click="displayImportModal = false" />
                    <Button label="Mulai Import" icon="pi pi-upload" :loading="isImporting" :disabled="!selectedFile" @click="submitImport" />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';

const props = defineProps({
    room: Object,
    seatingMap: Object,
    students: Array,
});

const confirm = useConfirm();
const toast = useToast();

const displayModal = ref(false);
const activeSeat = ref(null);
const searchQuery = ref('');
const selectedStudent = ref(null);

// Import Seating state
const displayImportModal = ref(false);
const fileInputRef = ref(null);
const selectedFile = ref(null);
const isImporting = ref(false);

const handleFileChange = (event) => {
    selectedFile.value = event.target.files[0] || null;
};

const downloadTemplate = () => {
    window.open(route('cbt.rooms.download-template'), '_blank');
};

const submitImport = () => {
    if (!selectedFile.value) return;

    isImporting.value = true;
    const formData = new FormData();
    formData.append('file', selectedFile.value);

    router.post(route('cbt.rooms.import-seating', props.room.id), formData, {
        onSuccess: () => {
            displayImportModal.value = false;
            selectedFile.value = null;
            if (fileInputRef.value) fileInputRef.value.value = '';
            toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Denah tempat duduk berhasil di-impor.', life: 3000 });
        },
        onFinish: () => {
            isImporting.value = false;
        }
    });
};

const filledSeatsCount = computed(() => {
    return Object.values(props.seatingMap).filter(seat => seat !== null).length;
});

const filteredStudents = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();
    if (!query) return props.students;
    
    return props.students.filter(s => 
        s.name.toLowerCase().includes(query) ||
        s.nisn.toLowerCase().includes(query) ||
        s.classroom_name.toLowerCase().includes(query)
    );
});

const openAssignModal = (seatNum) => {
    activeSeat.value = seatNum;
    searchQuery.value = '';
    selectedStudent.value = null;
    displayModal.value = true;
};

const selectStudent = (student) => {
    selectedStudent.value = student;
};

const submitAssignment = () => {
    if (!selectedStudent.value || !activeSeat.value) return;

    router.post(route('cbt.rooms.assign-seat', props.room.id), {
        student_id: selectedStudent.value.id,
        seat_number: activeSeat.value,
    }, {
        onSuccess: () => {
            displayModal.value = false;
            toast.add({ severity: 'success', summary: 'Berhasil', detail: `Siswa berhasil ditempatkan di kursi ${activeSeat.value}`, life: 3000 });
        }
    });
};

const confirmClearSeat = (seatNum) => {
    confirm.require({
        message: `Kosongkan kursi nomor <b>${seatNum}</b>? Siswa di kursi ini tidak akan terdaftar di ruangan ini.`,
        header: 'Konfirmasi Kosongkan Kursi',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('cbt.rooms.clear-seat', { id: props.room.id, seat_number: seatNum }), {}, {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Berhasil', detail: `Kursi ${seatNum} berhasil dikosongkan`, life: 3000 });
                }
            });
        }
    });
};
</script>

<style scoped>
.grid-layout {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 1rem;
}

.seat-box {
    min-height: 120px;
    height: auto;
    min-width: 0;
}

.text-xxs {
    font-size: 0.65rem;
}

@media (max-width: 1024px) {
    .grid-layout {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .grid-layout {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
