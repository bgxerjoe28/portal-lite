<template>
    <AppLayout title="Scanner QR Serah Terima & Pengembalian Sarpras">
        <div class="p-4 max-w-5xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-5">
                <div class="inline-flex align-items-center justify-content-center bg-primary-100 border-circle p-3 mb-2">
                    <i class="pi pi-qrcode text-primary text-4xl"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-900 m-0">Scanner Digital Sarpras</h2>
                <p class="text-600 m-0 mt-1">Scan kode E-Permit siswa atau Tag QR Aset fisik untuk serah terima & pengembalian seketika.</p>
            </div>

            <!-- Scanner / Input Token Box -->
            <div class="surface-card p-4 border-round-2xl shadow-2 mb-4 border-top-3 border-primary">
                <div class="flex flex-column md:flex-row gap-2 align-items-center">
                    <IconField iconPosition="left" class="w-full">
                        <InputIcon class="pi pi-qrcode text-primary text-xl" />
                        <InputText 
                            ref="tokenInputRef"
                            v-model="tokenInput" 
                            placeholder="Arahkan Barcode Scanner USB atau ketik Token QR / Kode Peminjaman (misal: PERMIT-XXXX, AST-XXXX)..." 
                            class="w-full text-lg p-3 font-mono" 
                            @keydown.enter="verifyToken"
                            autofocus
                        />
                    </IconField>
                    <Button 
                        label="Verifikasi" 
                        icon="pi pi-search" 
                        class="p-button-primary p-3 w-full md:w-auto font-bold flex-shrink-0" 
                        :loading="isVerifying" 
                        @click="verifyToken" 
                    />
                </div>
                <div class="text-xs text-500 mt-2 flex align-items-center gap-1">
                    <i class="pi pi-info-circle text-primary"></i>
                    <span>Mendukung scanner barcode USB, kamera smartphone, atau input manual kode izin peminjaman.</span>
                </div>
            </div>

            <!-- Verification Result Card -->
            <div v-if="verificationResult" class="surface-card p-4 border-round-2xl shadow-3 animate-fade-in border-1 border-300">
                <!-- Case 1: Reservation Verified -->
                <div v-if="verificationResult.type === 'reservation'">
                    <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 border-bottom-1 border-200 pb-3 mb-3">
                        <div>
                            <span class="font-mono text-xs font-bold text-primary">{{ currentReservation.reservation_code }}</span>
                            <h3 class="text-2xl font-extrabold text-900 m-0 mt-1">{{ currentReservation.title }}</h3>
                            <div class="text-sm text-600 mt-1">
                                Pemohon: <strong>{{ currentReservation.user?.name }}</strong> 
                                <span v-if="currentReservation.extracurricular" class="text-indigo-600">({{ currentReservation.extracurricular.name }})</span>
                            </div>
                        </div>
                        <Tag :value="getStatusLabel(currentReservation.status)" :severity="getStatusSeverity(currentReservation.status)" class="text-sm px-3 py-2" />
                    </div>

                    <!-- Details Grid -->
                    <div class="grid mb-4">
                        <div class="col-12 md:col-6">
                            <div class="surface-50 p-3 border-round-lg">
                                <span class="text-xs text-500 font-bold uppercase block mb-1">Jadwal Penggunaan</span>
                                <div class="text-sm font-bold text-900">{{ formatDateTime(currentReservation.start_time) }}</div>
                                <div class="text-xs text-600">s/d {{ formatDateTime(currentReservation.end_time) }}</div>
                            </div>
                        </div>
                        <div class="col-12 md:col-6">
                            <div class="surface-50 p-3 border-round-lg">
                                <span class="text-xs text-500 font-bold uppercase block mb-1">Fasilitas / Ruangan</span>
                                <div v-if="currentReservation.room" class="text-sm font-bold text-primary">
                                    <i class="pi pi-building mr-1"></i> {{ currentReservation.room.name }}
                                </div>
                                <div v-if="currentReservation.assets && currentReservation.assets.length > 0" class="text-xs text-700 mt-1">
                                    <i class="pi pi-box mr-1 text-orange-500"></i> {{ currentReservation.assets.length }} Item Aset
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Checklist -->
                    <div v-if="currentReservation.assets && currentReservation.assets.length > 0" class="mb-4">
                        <h4 class="text-sm font-bold text-900 uppercase tracking-wider mb-2">Daftar Aset yang Dipinjam:</h4>
                        <div class="border-1 border-200 border-round-xl overflow-hidden">
                            <DataTable :value="currentReservation.assets" responsiveLayout="scroll" class="p-datatable-sm">
                                <Column header="Aset">
                                    <template #body="{ data }">
                                        <div class="font-bold text-sm text-900">{{ data.name }}</div>
                                        <div class="font-mono text-xs text-500">{{ data.asset_code }}</div>
                                    </template>
                                </Column>
                                <Column header="Kondisi Awal" field="condition">
                                    <template #body="{ data }">
                                        <Tag :value="data.condition" severity="success" />
                                    </template>
                                </Column>
                                <!-- Return Condition Selector (Only if In Use) -->
                                <Column v-if="currentReservation.status === 'in_use'" header="Kondisi Saat Pengembalian" style="min-width: 220px">
                                    <template #body="{ data }">
                                        <Select 
                                            v-model="assetReturnForms[data.id].return_condition" 
                                            :options="returnConditionOptions" 
                                            optionLabel="label" 
                                            optionValue="value" 
                                            class="w-full text-xs" 
                                        />
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </div>

                    <!-- Action Section: Handover (If Approved) -->
                    <div v-if="currentReservation.status === 'approved'" class="surface-100 p-4 border-round-xl border-1 border-blue-200">
                        <h4 class="text-lg font-bold text-blue-900 m-0 mb-2 flex align-items-center gap-2">
                            <i class="pi pi-check-circle text-blue-600 text-xl"></i>
                            Konfirmasi Serah Terima Barang & Pembukaan Ruangan
                        </h4>
                        <p class="text-xs text-700 m-0 mb-3">
                            Pastikan fisik barang dalam keadaan lengkap dan peminjam telah menunjukkan kartu pelajar / identitas resmi.
                        </p>
                        <div class="mb-3">
                            <label class="text-xs font-bold block mb-1">Catatan Serah Terima (Opsional)</label>
                            <InputText v-model="handoverNotes" placeholder="Misal: Diserahkan 1 tas laptop lengkap dengan charger..." class="w-full" />
                        </div>
                        <Button 
                            label="Konfirmasi Serah Terima (Mulai Peminjaman)" 
                            icon="pi pi-arrow-right" 
                            class="p-button-success font-bold w-full p-3" 
                            :loading="isSubmittingAction"
                            @click="submitHandover" 
                        />
                    </div>

                    <!-- Action Section: Return (If In Use) -->
                    <div v-else-if="currentReservation.status === 'in_use'" class="surface-100 p-4 border-round-xl border-1 border-purple-200">
                        <h4 class="text-lg font-bold text-purple-900 m-0 mb-2 flex align-items-center gap-2">
                            <i class="pi pi-box text-purple-600 text-xl"></i>
                            Pemeriksaan Fisik & Konfirmasi Pengembalian
                        </h4>
                        <p class="text-xs text-700 m-0 mb-3">
                            Periksa seluruh komponen barang dan kebersihan ruangan sebelum menyelesaikan peminjaman.
                        </p>

                        <div class="mb-3">
                            <label class="text-xs font-bold block mb-1">Catatan Pengembalian (Opsional)</label>
                            <InputText v-model="returnNotes" placeholder="Misal: Dikembalikan dalam keadaan bersih dan lengkap..." class="w-full" />
                        </div>

                        <!-- Incident Checkbox -->
                        <div class="surface-card p-3 border-round-lg border-1 border-300 mb-3">
                            <div class="flex align-items-center">
                                <Checkbox v-model="hasDamage" :binary="true" inputId="has_damage" />
                                <label for="has_damage" class="ml-2 font-bold text-sm text-red-600 cursor-pointer">
                                    <i class="pi pi-exclamation-triangle mr-1"></i>
                                    Laporkan Kerusakan / Kehilangan Barang (Buat Berita Acara)
                                </label>
                            </div>

                            <!-- Incident Sub-form -->
                            <div v-if="hasDamage" class="mt-3 pt-3 border-top-1 border-200 flex flex-column gap-2 animate-fade-in">
                                <div>
                                    <label class="text-xs font-bold block mb-1">Jenis Insiden</label>
                                    <Select v-model="damageForm.damage_type" :options="[{ label: 'Kerusakan Fisik', value: 'damaged' }, { label: 'Barang Hilang', value: 'lost' }]" optionLabel="label" optionValue="value" class="w-full" />
                                </div>
                                <div>
                                    <label class="text-xs font-bold block mb-1">Kronologi Kerusakan / Kehilangan</label>
                                    <Textarea v-model="damageForm.description" rows="2" placeholder="Jelaskan bagian mana yang rusak atau kronologi..." class="w-full" />
                                </div>
                                <div>
                                    <label class="text-xs font-bold block mb-1">Estimasi Biaya Ganti Rugi / Servis (Rp)</label>
                                    <InputNumber v-model="damageForm.compensation_fee" placeholder="0" class="w-full" />
                                </div>
                            </div>
                        </div>

                        <Button 
                            label="Konfirmasi Pengembalian Selesai" 
                            icon="pi pi-check" 
                            class="p-button-primary font-bold w-full p-3" 
                            :loading="isSubmittingAction"
                            @click="submitReturn" 
                        />
                    </div>

                    <!-- Other Statuses Notice -->
                    <div v-else class="surface-50 p-4 border-round-xl text-center">
                        <i class="pi pi-info-circle text-2xl text-500 mb-2"></i>
                        <p class="text-sm text-700 m-0">Peminjaman ini saat ini berstatus <strong>{{ getStatusLabel(currentReservation.status) }}</strong>.</p>
                    </div>
                </div>

                <!-- Case 2: Asset Verified Directly -->
                <div v-else-if="verificationResult.type === 'asset'">
                    <div class="border-bottom-1 border-200 pb-3 mb-3">
                        <span class="font-mono text-xs font-bold text-primary">{{ verificationResult.asset.asset_code }}</span>
                        <h3 class="text-2xl font-extrabold text-900 m-0 mt-1">{{ verificationResult.asset.name }}</h3>
                        <div class="text-sm text-600 mt-1">Kategori: {{ verificationResult.asset.category }} • Lokasi: {{ verificationResult.asset.location || 'Sarpras' }}</div>
                    </div>

                    <div v-if="verificationResult.active_reservation" class="surface-50 p-3 border-round-lg border-1 border-200">
                        <div class="font-bold text-sm text-900 mb-1">Aset ini terdaftar dalam Peminjaman Aktif:</div>
                        <div class="text-xs font-mono text-primary">{{ verificationResult.active_reservation.reservation_code }} - {{ verificationResult.active_reservation.title }}</div>
                        <div class="text-xs text-600 mt-1">Peminjam: {{ verificationResult.active_reservation.user?.name }}</div>
                        <Button label="Buka Data Peminjaman Terkait" icon="pi pi-external-link" class="p-button-outlined p-button-sm mt-3" @click="loadReservationDirectly(verificationResult.active_reservation)" />
                    </div>
                    <div v-else class="surface-50 p-4 border-round-lg text-center text-500">
                        Aset ini sedang tidak dalam peminjaman aktif (Status: {{ verificationResult.asset.status }}).
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Checkbox from 'primevue/checkbox';
import InputNumber from 'primevue/inputnumber';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const toast = useToast();

const tokenInput = ref('');
const tokenInputRef = ref(null);
const isVerifying = ref(false);
const isSubmittingAction = ref(false);

const verificationResult = ref(null);
const currentReservation = ref(null);

const handoverNotes = ref('');
const returnNotes = ref('');
const hasDamage = ref(false);

const assetReturnForms = reactive({});

const damageForm = reactive({
    damage_type: 'damaged',
    description: '',
    compensation_fee: 0,
});

const returnConditionOptions = [
    { label: 'Kondisi Baik (Normal)', value: 'good' },
    { label: 'Rusak Ringan (Perlu Servis)', value: 'minor_damage' },
    { label: 'Rusak Berat', value: 'heavy_damage' },
    { label: 'Hilang', value: 'lost' },
];

const getStatusLabel = (status) => {
    switch(status) {
        case 'pending_coach': return 'Menunggu Pembina';
        case 'pending_sarpras': return 'Menunggu Sarpras';
        case 'approved': return 'Disetujui (Siap Diambil)';
        case 'in_use': return 'Sedang Digunakan';
        case 'completed': return 'Selesai';
        case 'rejected': return 'Ditolak';
        case 'cancelled': return 'Dibatalkan';
        case 'incident': return 'Insiden Kerusakan';
        default: return status;
    }
};

const getStatusSeverity = (status) => {
    switch(status) {
        case 'pending_coach': case 'pending_sarpras': return 'warn';
        case 'approved': return 'info';
        case 'in_use': return 'help';
        case 'completed': return 'success';
        case 'rejected': case 'cancelled': case 'incident': return 'danger';
        default: return 'secondary';
    }
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const verifyToken = async () => {
    if (!tokenInput.value.trim()) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Silakan masukkan kode token QR terlebih dahulu', life: 3000 });
        return;
    }

    isVerifying.value = true;
    try {
        const response = await axios.post(route('admin.sarpras.scanner.verify'), {
            token: tokenInput.value.trim(),
        });

        verificationResult.value = response.data;
        if (response.data.type === 'reservation') {
            setupReservationData(response.data.data);
        }

        toast.add({ severity: 'success', summary: 'QR Terbaca', detail: 'Data berhasil diverifikasi sistem', life: 3000 });
    } catch (err) {
        verificationResult.value = null;
        currentReservation.value = null;
        toast.add({ severity: 'error', summary: 'Verifikasi Gagal', detail: err.response?.data?.message || 'Kode QR tidak dikenali', life: 4000 });
    } finally {
        isVerifying.value = false;
    }
};

const setupReservationData = (res) => {
    currentReservation.value = res;
    handoverNotes.value = '';
    returnNotes.value = '';
    hasDamage.value = false;

    // Initialize asset return state
    if (res.assets) {
        res.assets.forEach(asset => {
            assetReturnForms[asset.id] = {
                return_condition: 'good',
                notes: '',
            };
        });
    }
};

const loadReservationDirectly = (res) => {
    verificationResult.value = {
        type: 'reservation',
        data: res,
    };
    setupReservationData(res);
};

const submitHandover = async () => {
    if (!currentReservation.value) return;

    isSubmittingAction.value = true;
    try {
        const response = await axios.post(route('admin.sarpras.scanner.handover'), {
            reservation_id: currentReservation.value.id,
            notes: handoverNotes.value,
        });

        currentReservation.value = response.data.reservation;
        toast.add({ severity: 'success', summary: 'Serah Terima Berhasil', detail: response.data.message, life: 4000 });
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Terjadi kesalahan', life: 4000 });
    } finally {
        isSubmittingAction.value = false;
    }
};

const submitReturn = async () => {
    if (!currentReservation.value) return;

    isSubmittingAction.value = true;
    try {
        const response = await axios.post(route('admin.sarpras.scanner.return'), {
            reservation_id: currentReservation.value.id,
            asset_returns: assetReturnForms,
            notes: returnNotes.value,
            has_damage: hasDamage.value,
            damage_type: damageForm.damage_type,
            damage_description: damageForm.description,
            compensation_fee: damageForm.compensation_fee,
        });

        currentReservation.value = response.data.reservation;
        toast.add({ severity: 'success', summary: 'Pengembalian Selesai', detail: response.data.message, life: 4000 });
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Terjadi kesalahan', life: 4000 });
    } finally {
        isSubmittingAction.value = false;
    }
};
</script>
