<template>
    <AppLayout title="Jadwal Ujian CBT">
        <CbtTabMenu />
        
        <div class="card">
            <div class="flex justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Jadwal Ujian CBT</h2>
                    <span class="text-500 block mt-1">Kelola sesi ujian, pengacakan soal, dan target peserta ujian</span>
                </div>
                
                <div class="flex gap-2 align-items-center">
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="search" placeholder="Cari Ujian..." />
                    </IconField>
                    <Button label="Jadwalkan Ujian" icon="pi pi-plus" severity="primary" @click="openCreateModal" />
                </div>
            </div>

            <!-- Status Filter Tabs / Buttons -->
            <div class="flex gap-2 mb-3 flex-wrap">
                <Button 
                    :label="`Semua (${counts?.all ?? 0})`" 
                    :severity="!currentStatus ? 'primary' : 'secondary'" 
                    :outlined="!!currentStatus"
                    size="small"
                    icon="pi pi-list"
                    @click="filterStatus(null)"
                />
                <Button 
                    :label="`Ujian Aktif (${counts?.active ?? 0})`" 
                    :severity="currentStatus === 'active' ? 'success' : 'secondary'" 
                    :outlined="currentStatus !== 'active'"
                    size="small"
                    icon="pi pi-check-circle"
                    @click="filterStatus('active')"
                />
                <Button 
                    :label="`Ujian Non-Aktif (${counts?.inactive ?? 0})`" 
                    :severity="currentStatus === 'inactive' ? 'danger' : 'secondary'" 
                    :outlined="currentStatus !== 'inactive'"
                    size="small"
                    icon="pi pi-times-circle"
                    @click="filterStatus('inactive')"
                />
            </div>

            <div class="surface-card p-4 shadow-2 border-round">
                <DataTable :value="exams.data" :rows="10" stripedRows tableStyle="min-width: 50rem" dataKey="id">
                    <template #empty> Belum ada jadwal ujian dibuat. </template>

                    <Column field="title" header="Nama Ujian" sortable>
                        <template #body="{ data }">
                            <span class="font-bold text-lg text-900 block">{{ data.title }}</span>
                            <small class="text-600 block mt-1">Bank Soal: <b>{{ data.bank?.name || '-' }}</b></small>
                            <small class="text-500 block mt-1">Mapel: <b>{{ data.bank?.subject?.name || '-' }}</b></small>
                        </template>
                    </Column>

                    <Column header="Waktu & Durasi">
                        <template #body="{ data }">
                            <div class="flex flex-column gap-1 text-sm text-800">
                                <div><i class="pi pi-play-circle text-primary text-xs mr-1"></i> Mulai: <b>{{ formatDate(data.start_time) }}</b></div>
                                <div><i class="pi pi-stop-circle text-danger text-xs mr-1"></i> Selesai: <b>{{ formatDate(data.end_time) }}</b></div>
                                <div><i class="pi pi-clock text-600 text-xs mr-1"></i> Durasi: <b>{{ data.duration }} Menit</b></div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Target Kelas">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-1">
                                <Tag v-for="cls in data.classrooms" :key="cls.id" :value="cls.name" severity="info" />
                            </div>
                        </template>
                    </Column>

                    <Column header="Status" style="width: 14%">
                        <template #body="{ data }">
                            <div class="flex align-items-center gap-2">
                                <ToggleSwitch
                                    :modelValue="data.is_active"
                                    @update:modelValue="toggleActive(data)"
                                    v-tooltip.top="data.is_active ? 'Klik untuk nonaktifkan Ujian' : 'Klik untuk aktifkan Ujian'"
                                />
                                <Tag :value="data.is_active ? 'Aktif' : 'Non-Aktif'" :severity="data.is_active ? 'success' : 'danger'" class="text-xs" />
                            </div>
                        </template>
                    </Column>

                    <Column header="Ujian Mandiri" style="width: 13%">
                        <template #body="{ data }">
                            <div class="flex align-items-center gap-2">
                                <ToggleSwitch
                                    :modelValue="data.is_independent"
                                    @update:modelValue="toggleIndependent(data)"
                                    v-tooltip.top="data.is_independent ? 'Klik untuk nonaktifkan Ujian Mandiri' : 'Klik untuk aktifkan Ujian Mandiri'"
                                />
                                <Tag
                                    :value="data.is_independent ? 'Mandiri' : 'Reguler'"
                                    :severity="data.is_independent ? 'info' : 'secondary'"
                                    class="text-xs"
                                />
                            </div>
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 22%">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Link :href="route('cbt.exams.results', data.id)">
                                    <Button icon="pi pi-chart-bar" severity="info" text rounded v-tooltip.top="'Hasil Ujian'" />
                                </Link>
                                <Button icon="pi pi-pencil" severity="warning" text rounded v-tooltip.top="'Edit'" @click="openEditModal(data)" />
                                <Button icon="pi pi-trash" severity="danger" text rounded v-tooltip.top="'Hapus'" @click="confirmDelete(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
                <Pagination :links="exams.links" class="mt-4" />
            </div>
        </div>

        <!-- Create / Edit Dialog -->
        <Dialog v-model:visible="displayModal" :header="isEditing ? 'Edit Jadwal Ujian' : 'Jadwalkan Ujian Baru'" :modal="true" :style="{ width: '600px' }">
            <form @submit.prevent="submitForm" class="p-fluid">
                <div class="field mb-3">
                    <label for="title" class="font-medium">Nama / Judul Ujian <span class="text-red-500">*</span></label>
                    <InputText id="title" v-model="form.title" class="w-full" :class="{'p-invalid': form.errors.title}" placeholder="Contoh: Ujian Tengah Semester Kimia" />
                    <small class="p-error" v-if="form.errors.title">{{ form.errors.title }}</small>
                </div>

                <div class="field mb-3">
                    <label for="subject" class="font-medium">Mata Pelajaran <span class="text-red-500">*</span></label>
                    <Select 
                        v-model="form.subject_id" 
                        :options="subjects" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Pilih Mata Pelajaran Terlebih Dahulu" 
                        class="w-full"
                        filter
                    />
                </div>

                <div class="field mb-3" v-if="form.subject_id">
                    <label for="bank" class="font-medium">Bank Soal <span class="text-red-500">*</span></label>
                    <Select 
                        v-model="form.cbt_bank_id" 
                        :options="filteredBanks" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Pilih Bank Soal" 
                        class="w-full"
                        filter
                        :class="{'p-invalid': form.errors.cbt_bank_id}"
                    />
                    <small class="p-error" v-if="form.errors.cbt_bank_id">{{ form.errors.cbt_bank_id }}</small>
                </div>

                <div class="formgrid grid">
                    <div class="field col-6 mb-3">
                        <label for="start_time" class="font-medium">Waktu Mulai <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="start_time" v-model="form.start_time" class="p-inputtext w-full" :class="{'p-invalid': form.errors.start_time}" />
                        <small class="p-error" v-if="form.errors.start_time">{{ form.errors.start_time }}</small>
                    </div>

                    <div class="field col-6 mb-3">
                        <label for="end_time" class="font-medium">Waktu Selesai <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="end_time" v-model="form.end_time" class="p-inputtext w-full" :class="{'p-invalid': form.errors.end_time}" />
                        <small class="p-error" v-if="form.errors.end_time">{{ form.errors.end_time }}</small>
                    </div>
                </div>

                <div class="formgrid grid">
                    <div class="field col-6 mb-3">
                        <label for="duration" class="font-medium">Durasi Ujian (Menit) <span class="text-red-500">*</span></label>
                        <InputNumber id="duration" v-model="form.duration" class="w-full" :min="1" :useGrouping="false" :class="{'p-invalid': form.errors.duration}" />
                        <small class="p-error" v-if="form.errors.duration">{{ form.errors.duration }}</small>
                    </div>

                    <div class="field col-6 mb-3">
                        <label for="classroom" class="font-medium">Kelas Peserta <span class="text-red-500">*</span></label>
                        <MultiSelect 
                            v-model="form.classroom_ids" 
                            :options="filteredClassrooms" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Pilih Kelas" 
                            display="chip" 
                            class="w-full"
                            filter
                            :class="{'p-invalid': form.errors.classroom_ids}"
                        />
                        <small class="p-error" v-if="form.errors.classroom_ids">{{ form.errors.classroom_ids }}</small>
                    </div>
                </div>

                <div class="field mb-3">
                    <label for="grading" class="font-medium">Integrasi Kategori Nilai Guru (Opsional)</label>
                    <Select 
                        v-model="form.grading_component_id" 
                        :options="gradingComponents" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Pilih Kategori Nilai" 
                        class="w-full"
                        filter
                        showClear
                    >
                        <template #option="slotProps">
                            {{ slotProps.option.name }} <small class="text-500 block">{{ slotProps.option.subject?.name }}</small>
                        </template>
                    </Select>
                </div>

                <!-- Toggle Options -->
                <div class="field mb-3 mt-2">
                    <label class="font-bold text-900 mb-2 block uppercase text-xs tracking-wider text-500">Konfigurasi Pengacakan</label>
                    <div class="flex flex-column gap-3 bg-50 p-3 border-round border border-200">
                        <div class="flex align-items-center justify-content-between">
                            <span class="font-semibold text-sm">Acak Urutan Soal Siswa</span>
                            <ToggleSwitch v-model="form.shuffle_questions" />
                        </div>
                        <div class="flex align-items-center justify-content-between border-top-1 border-200 pt-3">
                            <span class="font-semibold text-sm">Acak Pilihan Jawaban (PG, List, Checklist)</span>
                            <ToggleSwitch v-model="form.shuffle_options" />
                        </div>
                        <div class="flex align-items-center justify-content-between border-top-1 border-200 pt-3">
                            <span class="font-semibold text-sm">Wajib Jawab Semua Soal (Tidak boleh kosong & ragu-ragu)</span>
                            <ToggleSwitch v-model="form.must_complete_all" />
                        </div>
                    </div>
                </div>

                <div class="field mb-4">
                    <div class="flex align-items-center justify-content-between bg-50 p-3 border-round border border-200">
                        <div>
                            <span class="font-bold text-sm text-900 block">Ujian Aktif & Dapat Diakses Siswa</span>
                            <small class="text-500">Siswa hanya dapat mengakses ujian yang aktif</small>
                        </div>
                        <ToggleSwitch v-model="form.is_active" />
                    </div>
                </div>

                <div class="field mb-4" v-if="($page.props.auth?.user?.roles ?? []).some(r => r.name === 'guru')">
                    <div class="flex align-items-center justify-content-between bg-blue-50 p-3 border-round border border-blue-200">
                        <div>
                            <span class="font-bold text-sm text-blue-900 block">Ujian Mandiri (Proktoring Cepat)</span>
                            <small class="text-blue-700">Otomatis buatkan ruang dan sesi, siswa dapat ujian tanpa token proktor khusus</small>
                        </div>
                        <ToggleSwitch v-model="form.is_independent" />
                    </div>
                </div>

                <div class="flex justify-content-end gap-2">
                    <Button label="Batal" severity="secondary" text @click="displayModal = false" />
                    <Button :label="isEditing ? 'Simpan Perubahan' : 'Buat Jadwal'" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>


    </AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

import Pagination from '@/Components/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CbtTabMenu from '../CbtTabMenu.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import InputNumber from 'primevue/inputnumber';
import ToggleSwitch from 'primevue/toggleswitch';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    exams: Object,
    banks: Array,
    classrooms: Array,
    subjects: Array,
    gradingComponents: Array,
    filters: Object,
    counts: Object,
});

const confirm = useConfirm();
const toast = useToast();
const displayModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const search = ref(props.filters?.search || '');
const currentStatus = ref(props.filters?.status || null);

const filterStatus = (statusVal) => {
    currentStatus.value = statusVal;
    const query = {};
    if (search.value) query.search = search.value;
    if (statusVal) query.status = statusVal;
    
    router.get(route('cbt.exams.index'), query, { 
        preserveState: true, 
        replace: true, 
        preserveScroll: true 
    });
};

const form = useForm({
    subject_id: null,
    cbt_bank_id: null,
    title: '',
    duration: 60,
    start_time: '',
    end_time: '',
    shuffle_questions: true,
    shuffle_options: true,
    must_complete_all: false,
    is_active: true,
    is_independent: false,
    grading_component_id: null,
    classroom_ids: [],
});

// Computed list of classrooms that match the selected subject
const filteredClassrooms = computed(() => {
    if (!form.subject_id) return props.classrooms;
    // Support both single subject_id and array of subject_ids on classroom objects
    return props.classrooms.filter(c => {
        if (Array.isArray(c.subject_ids)) {
            return c.subject_ids.includes(form.subject_id);
        }
        return c.subject_id === form.subject_id;
    });
});

// Reset selected classrooms when subject changes (only when creating, not editing)
// flush: 'sync' agar watcher berjalan synchronous saat subject_id di-set di openEditModal
// sehingga reset terjadi SEBELUM classroom_ids diisi ulang dari data edit
watch(() => form.subject_id, () => {
    if (!isEditing.value) {
        form.classroom_ids = [];
    }
}, { flush: 'sync' });

const filteredBanks = computed(() => {
    if (!form.subject_id) return [];
    return props.banks.filter(b => b.subject_id === form.subject_id);
});

let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const query = {};
        if (val) query.search = val;
        if (currentStatus.value) query.status = currentStatus.value;
        router.get(route('cbt.exams.index'), query, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    displayModal.value = true;
};

const openEditModal = (data) => {
    isEditing.value = true;
    editId.value = data.id;
    form.subject_id = data.bank?.subject_id || null;
    form.cbt_bank_id = data.cbt_bank_id;
    form.title = data.title;
    form.duration = data.duration;
    form.start_time = toInputDatetime(data.start_time);
    form.end_time = toInputDatetime(data.end_time);
    form.shuffle_questions = data.shuffle_questions;
    form.shuffle_options = data.shuffle_options;
    form.must_complete_all = data.must_complete_all ?? false;
    form.is_active = data.is_active;
    form.is_independent = data.is_independent ?? false;
    form.grading_component_id = data.grading_component_id || null;
    form.classroom_ids = data.classrooms.map(c => c.id);
    displayModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('cbt.exams.update', editId.value), {
            onSuccess: () => {
                displayModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('cbt.exams.store'), {
            onSuccess: () => {
                displayModal.value = false;
                form.reset();
            }
        });
    }
};

const confirmDelete = (data) => {
    confirm.require({
        message: `Hapus jadwal ujian <b>${data.title}</b>? Tindakan ini akan menghapus riwayat pengerjaan siswa untuk ujian ini.`,
        header: 'Konfirmasi Hapus Ujian',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('cbt.exams.destroy', data.id), {
                onSuccess: () => {}
            });
        }
    });
};

// Parse datetime string dari server
const parseLocalDate = (dateStr) => {
    if (!dateStr) return null;
    const d = new Date(dateStr);
    return isNaN(d.getTime()) ? null : d;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = parseLocalDate(dateStr);
    if (!d) return '-';
    return d.toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }) + ' WIB';
};

// Untuk input datetime-local: format YYYY-MM-DDTHH:mm dalam local time
const toInputDatetime = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    
    // Get local parts
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const toggleIndependent = (data) => {
    router.patch(route('cbt.exams.toggle-independent', data.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: data.is_independent ? 'warn' : 'success',
                summary: data.is_independent ? 'Ujian Mandiri Dinonaktifkan' : 'Ujian Mandiri Diaktifkan',
                detail: data.is_independent
                    ? `"${data.title}" kembali ke mode reguler.`
                    : `"${data.title}" sekarang dalam mode Ujian Mandiri.`,
                life: 3000
            });
        }
    });
};

const toggleActive = (data) => {
    router.patch(route('cbt.exams.toggle-active', data.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: data.is_active ? 'warn' : 'success',
                summary: data.is_active ? 'Ujian Dinonaktifkan' : 'Ujian Diaktifkan',
                detail: data.is_active
                    ? `"${data.title}" berhasil dinonaktifkan.`
                    : `"${data.title}" berhasil diaktifkan.`,
                life: 3000
            });
        }
    });
};

</script>
