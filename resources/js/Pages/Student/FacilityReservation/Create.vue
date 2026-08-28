<template>
    <SiswaLayout title="Formulir Pengajuan Peminjaman Sarpras">
        <div class="p-3 md:p-4 max-w-4xl mx-auto">
            <!-- Header Bar -->
            <div class="flex align-items-center gap-3 mb-4">
                <Button icon="pi pi-arrow-left" class="p-button-outlined p-button-secondary" @click="router.get(route('student.sarpras.reservations.index'))" />
                <div>
                    <h2 class="text-2xl font-extrabold text-900 m-0">Formulir Peminjaman Ruang & Aset</h2>
                    <p class="text-600 m-0 mt-1 text-xs md:text-sm">Isi rincian permohonan, pilih fasilitas yang dibutuhkan, dan sertakan berkas proposal.</p>
                </div>
            </div>

            <!-- Warning Banner jika siswa belum terdaftar eskul -->
            <div v-if="extracurriculars.length === 0" class="p-4 bg-orange-50 border-1 border-orange-300 border-round-xl mb-4 text-orange-900 flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between gap-3 shadow-1">
                <div class="flex align-items-center gap-3">
                    <i class="pi pi-exclamation-triangle text-3xl text-orange-600"></i>
                    <div>
                        <div class="font-bold text-base">Anda Belum Terdaftar di Ekstrakulikuler Aktif</div>
                        <div class="text-xs text-orange-800 mt-1">
                            Sesuai ketentuan sekolah, seluruh peminjaman fasilitas (ruangan dan aset/peralatan) oleh siswa wajib mewakili ekstrakulikuler yang diikuti dengan persetujuan Pembina Eskul.
                        </div>
                    </div>
                </div>
                <Button 
                    label="Pilih Ekstrakulikuler" 
                    icon="pi pi-arrow-right" 
                    severity="warning" 
                    class="font-bold text-xs whitespace-nowrap"
                    @click="router.get(route('student.extracurriculars.index'))"
                />
            </div>

            <form @submit.prevent="submitForm">
                <div class="surface-card p-4 md:p-5 border-round-2xl shadow-3 mb-4 border-top-3 border-primary flex flex-column gap-4">
                    
                    <!-- 1. Tipe Peminjaman -->
                    <div>
                        <label class="font-bold text-sm text-900 block mb-2">Jenis Fasilitas yang Dibutuhkan <span class="text-red-500">*</span></label>
                        <div class="grid">
                            <div class="col-12 md:col-4">
                                <div 
                                    class="p-3 border-2 border-round-xl cursor-pointer transition-all flex align-items-center gap-3"
                                    :class="[
                                        form.type === 'both' ? 'border-primary bg-blue-50 text-primary font-bold' : 'border-200 hover:surface-100 text-700',
                                        extracurriculars.length === 0 ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                    @click="extracurriculars.length > 0 ? form.type = 'both' : null"
                                >
                                    <i class="pi pi-th-large text-2xl"></i>
                                    <div>
                                        <div class="text-sm">Ruangan & Peralatan</div>
                                        <div class="text-xxs font-normal text-500">Lab + Laptop, Aula + Sound, dll.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 md:col-4">
                                <div 
                                    class="p-3 border-2 border-round-xl cursor-pointer transition-all flex align-items-center gap-3"
                                    :class="[
                                        form.type === 'room_only' ? 'border-primary bg-blue-50 text-primary font-bold' : 'border-200 hover:surface-100 text-700',
                                        extracurriculars.length === 0 ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                    @click="extracurriculars.length > 0 ? form.type = 'room_only' : null"
                                >
                                    <i class="pi pi-building text-2xl"></i>
                                    <div>
                                        <div class="text-sm">Hanya Ruangan</div>
                                        <div class="text-xxs font-normal text-500">Kelas, Aula, Lapangan, dll.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 md:col-4">
                                <div 
                                    class="p-3 border-2 border-round-xl cursor-pointer transition-all flex align-items-center gap-3"
                                    :class="[
                                        form.type === 'asset_only' ? 'border-primary bg-blue-50 text-primary font-bold' : 'border-200 hover:surface-100 text-700',
                                        extracurriculars.length === 0 ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                    @click="extracurriculars.length > 0 ? form.type = 'asset_only' : null"
                                >
                                    <i class="pi pi-box text-2xl"></i>
                                    <div>
                                        <div class="text-sm">Hanya Peralatan / Aset</div>
                                        <div class="text-xxs font-normal text-500">Laptop, Kamera, Mic, dll.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Informasi Kegiatan -->
                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <label class="font-bold text-sm text-900 block mb-1">
                                Mewakili Ekstrakulikuler <span class="text-red-500">*</span>
                            </label>
                            <Select 
                                v-model="form.extracurricular_id" 
                                :options="extracurriculars" 
                                optionLabel="name" 
                                optionValue="id" 
                                :placeholder="extracurriculars.length > 0 ? 'Pilih Eskul yang Anda Ikuti' : 'Anda belum bergabung di ekstrakulikuler'" 
                                class="w-full"
                                :disabled="extracurriculars.length === 0"
                                required
                            />
                            <small v-if="extracurriculars.length > 0" class="text-500 text-xxs block mt-1">
                                <i class="pi pi-shield mr-1 text-primary"></i>Hanya menampilkan eskul aktif yang Anda ikuti. Permohonan akan diverifikasi oleh Pembina Eskul.
                            </small>
                            <small v-else class="text-red-600 text-xxs block mt-1 font-semibold">
                                Wajib mengikuti minimal 1 ekstrakulikuler untuk dapat mengajukan peminjaman sarpras.
                            </small>
                        </div>
                        <div class="col-12 md:col-6">
                            <label class="font-bold text-sm text-900 block mb-1">Estimasi Jumlah Peserta (Orang)</label>
                            <InputNumber v-model="form.participant_count" placeholder="Jumlah orang..." class="w-full" :min="1" />
                        </div>
                        <div class="col-12">
                            <label class="font-bold text-sm text-900 block mb-1">Nama Kegiatan <span class="text-red-500">*</span></label>
                            <InputText v-model="form.title" placeholder="Misal: Latihan Rutin Teater / Rapat Kerja OSIS" class="w-full" required />
                        </div>
                        <div class="col-12">
                            <label class="font-bold text-sm text-900 block mb-1">Tujuan & Keperluan <span class="text-red-500">*</span></label>
                            <Textarea v-model="form.purpose" rows="2" placeholder="Jelaskan secara singkat tujuan peminjaman fasilitas..." class="w-full" required />
                        </div>
                    </div>

                    <!-- 3. Jadwal Peminjaman & Anti-Collision Check -->
                    <div class="surface-50 p-3 md:p-4 border-round-xl border-1 border-200">
                        <div class="flex justify-content-between align-items-center mb-3">
                            <span class="font-bold text-sm text-900 flex align-items-center gap-2">
                                <i class="pi pi-clock text-primary"></i> Waktu Penggunaan & Cek Ketersediaan
                            </span>
                            <Button 
                                type="button" 
                                label="Cek Bentrok Jadwal" 
                                icon="pi pi-shield" 
                                class="p-button-outlined p-button-sm font-bold" 
                                :loading="isCheckingAvailability"
                                @click="runAvailabilityCheck" 
                            />
                        </div>

                        <div class="grid">
                            <div class="col-12 md:col-6">
                                <label class="font-bold text-xs text-700 block mb-1">Waktu Mulai <span class="text-red-500">*</span></label>
                                <input 
                                    v-model="form.start_time" 
                                    type="datetime-local" 
                                    class="w-full p-2 border-1 border-300 border-round text-sm" 
                                    required 
                                    @change="availabilityResult = null"
                                />
                            </div>
                            <div class="col-12 md:col-6">
                                <label class="font-bold text-xs text-700 block mb-1">Waktu Selesai <span class="text-red-500">*</span></label>
                                <input 
                                    v-model="form.end_time" 
                                    type="datetime-local" 
                                    class="w-full p-2 border-1 border-300 border-round text-sm" 
                                    required 
                                    @change="availabilityResult = null"
                                />
                            </div>
                        </div>

                        <!-- Availability Feedback Alert -->
                        <div v-if="availabilityResult" class="mt-3">
                            <div v-if="availabilityResult.valid" class="p-3 bg-green-50 border-1 border-green-300 border-round-lg text-green-800 text-xs flex align-items-center gap-2">
                                <i class="pi pi-check-circle text-green-600 text-lg"></i>
                                <span><strong>Jadwal Tersedia!</strong> Tidak ada tabrakan agenda atau peminjaman lain pada slot waktu ini.</span>
                            </div>
                            <div v-else class="p-3 bg-red-50 border-1 border-red-300 border-round-lg text-red-800 text-xs">
                                <div class="font-bold flex align-items-center gap-2 mb-1">
                                    <i class="pi pi-exclamation-triangle text-red-600"></i>
                                    <span>Jadwal Bertabrakan / Fasilitas Tidak Tersedia:</span>
                                </div>
                                <ul class="m-0 pl-4">
                                    <li v-for="(conf, idx) in availabilityResult.conflicts" :key="idx">{{ conf }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Pilihan Ruangan (If applicable) -->
                    <div v-if="['both', 'room_only'].includes(form.type)">
                        <label class="font-bold text-sm text-900 block mb-2">Pilih Ruangan <span class="text-red-500">*</span></label>
                        <div class="grid">
                            <div v-for="room in rooms" :key="room.id" class="col-12 sm:col-6 md:col-4">
                                <div 
                                    class="p-3 border-2 border-round-xl cursor-pointer transition-all flex flex-column justify-content-between h-full"
                                    :class="form.room_id === room.id ? 'border-primary bg-blue-50 shadow-2' : 'border-200 hover:surface-100'"
                                    @click="selectRoom(room.id)"
                                >
                                    <div>
                                        <div class="font-bold text-sm text-900 mb-1">{{ room.name }}</div>
                                        <div class="text-xxs text-600 font-mono mb-2">{{ room.code }} • Lokasi: {{ room.location || '-' }}</div>
                                    </div>
                                    <div class="flex justify-content-between align-items-center text-xxs text-500 border-top-1 border-100 pt-2">
                                        <span>Kapasitas: <strong>{{ room.capacity }} Orang</strong></span>
                                        <i v-if="form.room_id === room.id" class="pi pi-check-circle text-primary text-base font-bold"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Pilihan Aset / Peralatan (If applicable) -->
                    <div v-if="['both', 'asset_only'].includes(form.type)">
                        <label class="font-bold text-sm text-900 block mb-2">Pilih Aset / Peralatan Tambahan</label>
                        
                        <div class="grid">
                            <div v-for="asset in assets" :key="asset.id" class="col-12 sm:col-6 md:col-4">
                                <div 
                                    class="p-3 border-2 border-round-xl cursor-pointer transition-all flex justify-content-between align-items-center"
                                    :class="isAssetSelected(asset.id) ? 'border-primary bg-blue-50 font-bold' : 'border-200 hover:surface-100 text-700'"
                                    @click="toggleAsset(asset.id)"
                                >
                                    <div>
                                        <div class="text-xs font-bold text-900">{{ asset.name }}</div>
                                        <div class="text-xxs text-500 font-mono">{{ asset.asset_code }} • {{ asset.category }}</div>
                                    </div>
                                    <Checkbox :modelValue="isAssetSelected(asset.id)" :binary="true" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Upload Berkas Proposal / Surat Izin (PDF) -->
                    <div>
                        <label class="font-bold text-sm text-900 block mb-1">Unggah Surat Izin / Proposal Kegiatan (PDF, Maks 5MB)</label>
                        <p class="text-xxs text-500 m-0 mb-2">Wajib mengunggah berkas surat permohonan atau proposal untuk kegiatan resmi sekolah / eskul.</p>
                        <input 
                            type="file" 
                            accept="application/pdf" 
                            class="w-full p-2 border-1 border-300 border-round surface-50 text-xs" 
                            @change="onFileSelected"
                        />
                    </div>
                </div>

                <!-- Submit Bar -->
                <div class="flex justify-content-end gap-3 mb-6">
                    <Button label="Batal" icon="pi pi-times" class="p-button-text p-button-secondary" @click="router.get(route('student.sarpras.reservations.index'))" />
                    <Button label="Kirim Permohonan Peminjaman" icon="pi pi-send" type="submit" class="p-button-primary font-bold shadow-2 p-3" :loading="isSubmitting" />
                </div>
            </form>
        </div>
    </SiswaLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';

const props = defineProps({
    rooms: Array,
    assets: Array,
    extracurriculars: Array,
});

const toast = useToast();

const isCheckingAvailability = ref(false);
const isSubmitting = ref(false);
const availabilityResult = ref(null);

const form = ref({
    type: 'both',
    extracurricular_id: null,
    room_id: null,
    asset_ids: [],
    title: '',
    purpose: '',
    start_time: '',
    end_time: '',
    participant_count: null,
    proposal_file: null,
});

const selectRoom = (id) => {
    form.value.room_id = (form.value.room_id === id) ? null : id;
    availabilityResult.value = null;
};

const isAssetSelected = (id) => {
    return form.value.asset_ids.includes(id);
};

const toggleAsset = (id) => {
    if (isAssetSelected(id)) {
        form.value.asset_ids = form.value.asset_ids.filter(x => x !== id);
    } else {
        form.value.asset_ids.push(id);
    }
    availabilityResult.value = null;
};

const onFileSelected = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.type !== 'application/pdf') {
            toast.add({ severity: 'error', summary: 'Format Salah', detail: 'Hanya berkas PDF yang diperbolehkan', life: 3000 });
            return;
        }
        form.value.proposal_file = file;
    }
};

const runAvailabilityCheck = async () => {
    if (!form.value.start_time || !form.value.end_time) {
        toast.add({ severity: 'warn', summary: 'Lengkapi Tanggal', detail: 'Silakan tentukan waktu mulai dan selesai terlebih dahulu', life: 3000 });
        return;
    }

    isCheckingAvailability.value = true;
    try {
        const response = await axios.post(route('student.sarpras.reservations.check-availability'), {
            type: form.value.type,
            room_id: form.value.room_id,
            asset_ids: form.value.asset_ids,
            start_time: form.value.start_time,
            end_time: form.value.end_time,
        });

        availabilityResult.value = response.data;
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Error', detail: err.response?.data?.message || 'Gagal mengecek ketersediaan', life: 3000 });
    } finally {
        isCheckingAvailability.value = false;
    }
};

const submitForm = () => {
    if (!form.value.extracurricular_id) {
        toast.add({
            severity: 'warn',
            summary: 'Eskul Wajib Dipilih',
            detail: 'Peminjaman fasilitas sarpras (ruangan maupun aset) wajib mewakili kegiatan ekstrakulikuler yang Anda ikuti.',
            life: 4000
        });
        return;
    }

    if (['both', 'room_only'].includes(form.value.type) && !form.value.room_id) {
        toast.add({
            severity: 'warn',
            summary: 'Pilih Ruangan',
            detail: 'Silakan pilih ruangan yang ingin dipinjam.',
            life: 3000
        });
        return;
    }

    if (form.value.type === 'asset_only' && (!form.value.asset_ids || form.value.asset_ids.length === 0)) {
        toast.add({
            severity: 'warn',
            summary: 'Pilih Peralatan',
            detail: 'Minimal pilih 1 peralatan/aset yang ingin dipinjam.',
            life: 3000
        });
        return;
    }

    isSubmitting.value = true;
    const formData = new FormData();
    formData.append('type', form.value.type);
    formData.append('title', form.value.title);
    formData.append('purpose', form.value.purpose);
    formData.append('start_time', form.value.start_time);
    formData.append('end_time', form.value.end_time);

    if (form.value.extracurricular_id) {
        formData.append('extracurricular_id', form.value.extracurricular_id);
    }
    if (form.value.participant_count) {
        formData.append('participant_count', form.value.participant_count);
    }
    if (form.value.room_id) {
        formData.append('room_id', form.value.room_id);
    }
    if (form.value.asset_ids && form.value.asset_ids.length > 0) {
        form.value.asset_ids.forEach((id, idx) => {
            formData.append(`asset_ids[${idx}]`, id);
        });
    }
    if (form.value.proposal_file instanceof File) {
        formData.append('proposal_file', form.value.proposal_file);
    }

    router.post(route('student.sarpras.reservations.store'), formData, {
        onError: (errors) => {
            const firstErr = Object.values(errors)[0];
            toast.add({ severity: 'error', summary: 'Gagal Mengirim', detail: firstErr, life: 5000 });
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};
</script>

<style scoped>
.text-xxs {
    font-size: 0.7rem;
}
</style>
