<template>
    <Dialog v-model:visible="visible" header="Presensi Siswa" modal style="width: 700px" :closable="false">
        <DataTable :value="students" stripedRows scrollable scrollHeight="400px">
            <Column header="No.">
                <template #body="{ index }">
                    <span class="text-600">{{ index + 1 }}</span>
                </template>
            </Column>
            <Column field="full_name" header="Nama Siswa" />

            <Column header="Status" style="width: 220px">
                <template #body="{ data }">
                    <div v-if="data.locked_status" class="flex flex-column gap-1">
                        <Tag 
                            :value="getPermitLabel(data.locked_status)" 
                            :severity="getSeverity(data.locked_status)" 
                            class="w-full"
                        />
                        <small class="text-xs italic text-500 text-center">{{ data.permit_reason }}</small>
                    </div>
                    <div v-else class="flex align-items-center gap-3">
                        <Tag v-if="data.is_late" value="TERLAMBAT" severity="warning" icon="pi pi-clock" />
                        <Tag v-if="data.is_dispensasi" value="DISPENSASI" severity="info" icon="pi pi-info-circle" />
                        <Button 
                            :label="presensi[data.id] === 'hadir' ? 'HADIR' : 'ABSEN'" 
                            :icon="presensi[data.id] === 'hadir' ? 'pi pi-check-circle' : 'pi pi-times-circle'"
                            :severity="presensi[data.id] === 'hadir' ? 'success' : 'danger'"
                            :outlined="presensi[data.id] !== 'hadir'"
                            class="w-8rem"
                            @click="toggleStatus(data.id)"
                        />                   
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Selfie Guru Camera Area -->
        <div v-if="showSelfie" class="my-4 p-4 border border-300 border-round bg-gray-50 flex flex-column align-items-center justify-content-center gap-3">
            <div class="font-bold text-800 text-lg flex align-items-center gap-2">
                <i class="pi pi-camera text-primary"></i>
                Selfie Presensi Guru <span class="text-red-500">*</span>
            </div>
            
            <!-- Video feed for live camera -->
            <div v-if="!selfiePreview" class="relative overflow-hidden border-round shadow-3 bg-black animate-fadein" style="width: 100%; max-width: 480px; aspect-ratio: 4/3;">
                <video 
                    ref="videoElement" 
                    autoplay 
                    playsinline 
                    muted 
                    class="w-full h-full animate-fadein" 
                    style="transform: scaleX(-1); object-fit: cover;"
                ></video>
                
                <div v-if="!isCameraActive && !cameraError" class="absolute inset-0 flex flex-column align-items-center justify-content-center bg-black-alpha-70 text-white gap-2">
                    <i class="pi pi-spin pi-spinner text-2xl"></i>
                    <span>Mengaktifkan Kamera...</span>
                </div>
                
                <div v-if="isCameraActive" class="absolute bottom-0 left-0 right-0 p-3 flex justify-content-center" style="background: linear-gradient(transparent, rgba(0,0,0,0.6))">
                    <Button 
                        icon="pi pi-camera" 
                        severity="primary" 
                        class="p-button-rounded p-button-lg shadow-4 border-2 border-white"
                        style="width: 3.5rem; height: 3.5rem;"
                        @click="capturePhoto"
                    />
                </div>
            </div>
            
            <!-- Preview of captured photo -->
            <div v-else class="relative flex flex-column align-items-center justify-content-center w-full" style="max-width: 480px;">
                <img :src="selfiePreview" class="w-full border-round shadow-3 border border-200" style="aspect-ratio: 4/3; object-fit: cover;" />
                <div class="absolute bottom-0 left-0 right-0 p-3 flex justify-content-center border-round-bottom" style="background: linear-gradient(transparent, rgba(0,0,0,0.6))">
                    <Button 
                        label="Ambil Ulang" 
                        icon="pi pi-refresh" 
                        severity="danger" 
                        class="shadow-4"
                        @click="removeSelfie" 
                    />
                </div>
            </div>
            
            <!-- Hidden Canvas for capturing -->
            <canvas ref="canvasElement" class="hidden"></canvas>
            
            <!-- Error message if camera is blocked/fails -->
            <small v-if="cameraError" class="p-error text-center font-semibold flex flex-column align-items-center gap-2">
                <span class="flex align-items-center gap-1">
                    <i class="pi pi-exclamation-triangle"></i>
                    {{ cameraError }}
                </span>
                <Button label="Coba Lagi" icon="pi pi-refresh" severity="secondary" size="small" @click="startCamera" />
            </small>
        </div>
        
        <div class="flex flex-wrap gap-3 p-3 bg-gray-50 border-round font-bold text-sm">
            <span class="text-700">Total Siswa: {{ props.students.length }}</span>
            
            <span class="text-green-600">Hadir: {{ hadirCount }}</span>
            
            <span class="text-red-600">Absen: {{ totalTidakHadir }}</span>

            <span v-if="telatCount > 0" class="text-indigo-600">Terlambat: {{ telatCount }}</span>

            <span v-if="sakitCount > 0" class="text-blue-600">Sakit: {{ sakitCount }}</span>
            <span v-if="izinCount > 0" class="text-orange-600">Izin: {{ izinCount }}</span>
            <span v-if="alfaCount > 0" class="text-red-600">Tanpa Keterangan: {{ alfaCount }}</span>
            <span v-if="dispenCount > 0" class="text-purple-600">Dispen: {{ dispenCount }}</span>
        </div>

        <div class="flex justify-content-end gap-2 mt-4">
            <Button label="Batal" text severity="secondary" @click="visible = false" />
            <Button 
                label="Simpan Agenda & Presensi" 
                icon="pi pi-check" 
                severity="success" 
                :disabled="showSelfie && !selfieFile"
                @click="submit" 
            />
        </div>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed, onBeforeUnmount } from 'vue'
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import SelectButton from 'primevue/selectbutton'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import imageCompression from 'browser-image-compression'

const visible = defineModel('visible')
const getPermitLabel = (t) => ({ 'S': 'SAKIT', 'I': 'IZIN', 'D': 'DISPEN', 'A': 'TANPA KETERANGAN' }[t])
const getSeverity = (t) => ({ 'S': 'info', 'I': 'warn', 'D': 'help', 'A': 'danger' }[t])
const props = defineProps({
    students: { type: Array, required: true },
    showSelfie: { type: Boolean, default: false }
})
const emit = defineEmits(['submit'])

const presensi = ref({})
const selfieFile = ref(null)
const selfiePreview = ref(null)

// Camera State
const videoElement = ref(null)
const canvasElement = ref(null)
const stream = ref(null)
const isCameraActive = ref(false)
const cameraError = ref(null)

const startCamera = async () => {
    cameraError.value = null
    isCameraActive.value = false
    try {
        const constraints = {
            video: {
                facingMode: 'user', // Kamera depan / selfie
                width: { ideal: 640 },
                height: { ideal: 480 }
            },
            audio: false
        }
        
        if (stream.value) {
            stopCamera()
        }
        
        const mediaStream = await navigator.mediaDevices.getUserMedia(constraints)
        stream.value = mediaStream
        if (videoElement.value) {
            videoElement.value.srcObject = mediaStream
            videoElement.value.onloadedmetadata = () => {
                videoElement.value.play().catch(e => console.error("Gagal memutar video:", e))
            }
        }
        isCameraActive.value = true
    } catch (err) {
        console.error('Gagal mengakses kamera:', err)
        cameraError.value = 'Kamera tidak dapat diakses. Harap izinkan akses kamera di browser Anda untuk mengambil selfie.'
    }
}

const stopCamera = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop())
        stream.value = null
    }
    isCameraActive.value = false
}

const capturePhoto = () => {
    if (videoElement.value && canvasElement.value) {
        const video = videoElement.value
        const canvas = canvasElement.value
        const context = canvas.getContext('2d')
        
        canvas.width = video.videoWidth || 640
        canvas.height = video.videoHeight || 480
        
        // Mirror canvas drawing to match video preview look
        context.translate(canvas.width, 0)
        context.scale(-1, 1)
        context.drawImage(video, 0, 0, canvas.width, canvas.height)
        context.setTransform(1, 0, 0, 1, 0, 0)
        
        canvas.toBlob(async (blob) => {
            if (blob) {
                const originalFile = new File([blob], `selfie_${Date.now()}.jpg`, { type: 'image/jpeg' })
                
                try {
                    // Konfigurasi kompresi
                    const options = {
                        maxSizeMB: 0.1, // Maksimal ~100 KB
                        maxWidthOrHeight: 800, // Dimensi maksimal
                        useWebWorker: true
                    }
                    
                    const compressedFile = await imageCompression(originalFile, options)
                    selfieFile.value = compressedFile
                    selfiePreview.value = URL.createObjectURL(compressedFile)
                } catch (error) {
                    console.error('Kompresi gagal, menggunakan gambar asli:', error)
                    selfieFile.value = originalFile
                    selfiePreview.value = URL.createObjectURL(originalFile)
                }
                
                stopCamera()
            }
        }, 'image/jpeg', 0.8)
    }
}

const removeSelfie = () => {
    selfieFile.value = null
    if (selfiePreview.value) {
        URL.revokeObjectURL(selfiePreview.value)
    }
    selfiePreview.value = null
    if (visible.value && props.showSelfie) {
        setTimeout(startCamera, 100)
    }
}

// Watch visible state to start/stop camera stream
watch(() => visible.value, (isVisible) => {
    if (isVisible && props.showSelfie && !selfieFile.value) {
        setTimeout(startCamera, 300)
    } else {
        stopCamera()
    }
})

onBeforeUnmount(() => {
    stopCamera()
})

const toggleStatus = (id) => {
    presensi.value[id] = presensi.value[id] === 'hadir' ? 'absen' : 'hadir';
}

// Opsi status
const statusOptions = [
    { label: 'Hadir', value: 'hadir', icon: 'pi pi-check-circle' },
    { label: 'Absen', value: 'absen', icon: 'pi pi-times-circle' }
]

/* LOGIKA PENGUNCI: Jika diklik lagi dan jadi null, kembalikan ke nilai sebelumnya */
const ensureSelection = (id) => {
    if (presensi.value[id] === null) {
        // Jika user mencoba meng-unclick, kita paksa kembali ke 'absen' atau 'hadir'
        // Tapi biasanya di PrimeVue v4, ini bisa dicek via unselectable prop
        presensi.value[id] = 'hadir'; 
    }
}


// INITIALIZE: Sinkronkan presensi saat students berubah
watch(() => props.students, (newStudents) => {
    // 🔑 KUNCINYA: Buat objek kosong baru, jangan meng-copy presensi.value yang lama
    const freshPresensi = {}; 
    
    if (newStudents && newStudents.length > 0) {
        newStudents.forEach(s => {
            // Isi hanya dengan ID siswa yang baru masuk lewat props
            freshPresensi[s.id] = s.locked_status||'hadir';
        });
    }
    
    // Ganti total dengan data yang benar-benar fresh
    presensi.value = freshPresensi;
    // Reset selfie files when student list changes (typically on schedule change)
    removeSelfie()
}, { immediate: true });

/* COMPUTED UNTUK RINGKASAN */

// Hadir murni (H)
const hadirCount = computed(() => 
    Object.values(presensi.value).filter(v => v === 'hadir' || v === 'H').length
);

// Terlambat (T)
const telatCount = computed(() => 
    Object.values(presensi.value).filter(v => v === 'T').length
);

// Rekap "Tidak Hadir" (S+I+A+D)
// 🔑 KUNCI: Kita pastikan 'T' (Terlambat) TIDAK dihitung sebagai Absen
const totalTidakHadir = computed(() => 
    Object.values(presensi.value).filter(v => ['S', 'I', 'A', 'D', 'absen'].includes(v)).length
);

// Detail Breakdown
const sakitCount = computed(() => Object.values(presensi.value).filter(v => v === 'S').length);
const izinCount = computed(() => Object.values(presensi.value).filter(v => v === 'I').length);
const alfaCount = computed(() => Object.values(presensi.value).filter(v => v === 'A' || v === 'absen').length);
const dispenCount = computed(() => Object.values(presensi.value).filter(v => v === 'D').length);
const submit = () => {
    if (props.showSelfie && !selfieFile.value) {
        return
    }
    if (props.showSelfie) {
        emit('submit', {
            presensi: presensi.value,
            selfie: selfieFile.value
        })
    } else {
        emit('submit', presensi.value)
    }
}
</script>

<style scoped>
/* Mewarnai tombol aktif agar kontras */
:deep(.p-selectbutton .p-button.p-highlight:first-child) {
    background-color: #22c55e !important; /* Green 500 */
    color: white !important;
}

:deep(.p-selectbutton .p-button.p-highlight:last-child) {
    background-color: #ef4444 !important; /* Red 500 */
    color: white !important;
}
</style>