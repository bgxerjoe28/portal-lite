<template>
    <Dialog
        v-model:visible="visible"
        header="Presensi Siswa"
        modal
        :maximized="true"
        :closable="false"
        class="presensi-dialog"
    >
        <!-- LIST SISWA -->
        <div class="presensi-list">

            <div
                v-for="(student, index) in students"
                :key="student.id"
                class="presensi-item"
            >
                <!-- NO -->
                <div class="presensi-no">
                    {{ index + 1 }}
                </div>

                <!-- INFO SISWA -->
                <div class="presensi-info">
                    <span class="student-name">
                        {{ student.full_name }}
                    </span>
                    <div class="flex flex-wrap align-items-center gap-1 mt-1">
                        <Tag 
                            v-if="student.nisn && student.nisn !== '-'" 
                            :value="'NISN: ' + student.nisn" 
                            severity="secondary" 
                            class="text-xs px-2 py-0" 
                        />
                        <Tag 
                            :value="student.gender_label || (student.gender ? 'L' : 'P')" 
                            :severity="(student.gender_label === 'L' || student.gender) ? 'info' : 'danger'" 
                            class="text-xs font-bold px-2 py-0" 
                        />
                        <Tag 
                            v-if="student.religion_name && student.religion_name !== '-'" 
                            :value="student.religion_name" 
                            severity="help" 
                            class="text-xs px-2 py-0" 
                        />
                    </div>
                </div>

                <!-- AKSI -->
                <Button
                    :label="getLabel(presensi[student.id])"
                    :icon="getIcon(presensi[student.id])"
                    :severity="getSeverity(presensi[student.id])"
                    :outlined="presensi[student.id] !== 'hadir'"
                    class="presensi-btn"
                    @click="toggleStatus(student.id)"
                />
            </div>

        </div>

        <!-- Selfie Guru Camera Area (Mobile) -->
        <div v-if="showSelfie" class="mx-2 my-4 p-3 border border-300 border-round bg-gray-50 flex flex-column align-items-center justify-content-center gap-3">
            <div class="font-bold text-800 text-base flex align-items-center gap-2">
                <i class="pi pi-camera text-primary"></i>
                Selfie Presensi Guru <span class="text-red-500">*</span>
            </div>
            
            <!-- Video feed for live camera -->
            <div v-if="!selfiePreview" class="relative overflow-hidden border-round shadow-3 bg-black" style="width: 100%; max-width: 480px; aspect-ratio: 4/3;">
                <video 
                    ref="videoElement" 
                    autoplay 
                    playsinline 
                    muted 
                    class="w-full h-full" 
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
                    Gagal Mengakses Kamera
                </span>
                <span class="text-xs font-normal text-600">Pastikan Anda telah memberikan izin akses kamera pada browser browser HP/Laptop Anda.</span>
            </small>
        </div>

        <!-- FOOTER -->
        <template #footer>
            <div class="presensi-footer flex flex-column gap-2 w-full">
                <!-- LEGENDA STATUS & HARIAN -->
                <div class="presensi-legend">
                    <span class="legend-item hadir">Hadir</span>
                    <span class="legend-item alpa">Tidak Hadir</span>
                </div>

                <!-- INFO HARIAN TERKUNCI -->
                <div class="text-xs text-500 text-center">
                    Siswa berstatus Sakit/Izin di presensi harian otomatis terkunci.
                </div>

                <!-- ACTION -->
                <div class="presensi-actions">
                    <Button
                        label="Batal"
                        text
                        severity="secondary"
                        class="flex-1"
                        @click="visible = false"
                    />
                    <Button
                        label="Simpan"
                        icon="pi pi-save"
                        severity="primary"
                        class="flex-1"
                        :disabled="showSelfie && !selfieFile"
                        @click="submit"
                    />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed, onBeforeUnmount } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const visible = defineModel('visible')
const props = defineProps({
    students: { type: Array, required: true },
    initialData: { type: Object, default: () => ({}) },
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
                facingMode: 'user', // Selfie camera
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
        
        canvas.toBlob((blob) => {
            if (blob) {
                const file = new File([blob], `selfie_${Date.now()}.jpg`, { type: 'image/jpeg' })
                selfieFile.value = file
                selfiePreview.value = URL.createObjectURL(file)
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

const getLabel = (status) => {
    switch (status) {
        case 'hadir':
        case 'H':
        case true:
            return 'HADIR'
        case 'S':
            return 'SAKIT'
        case 'I':
            return 'IZIN'
        case 'D':
            return 'DISPEN'
        case 'T':
            return 'TERLAMBAT'
        case 'A':
        case 'tidak_hadir':
        case false:
            return 'TIDAK HADIR'
        default:
            return 'TIDAK HADIR'
    }
}

const getIcon = (status) => {
    switch (status) {
        case 'hadir':
        case 'H':
        case true:
            return 'pi pi-check-circle'
        case 'S':
            return 'pi pi-plus-circle'
        case 'I':
            return 'pi pi-info-circle'
        case 'D':
            return 'pi pi-star'
        case 'T':
            return 'pi pi-clock'
        case 'A':
        case 'tidak_hadir':
        case false:
            return 'pi pi-times-circle'
        default:
            return 'pi pi-times-circle'
    }
}

const getSeverity = (status) => {
    switch (status) {
        case 'hadir':
        case 'H':
        case true:
            return 'success'
        case 'S':
        case 'I':
        case 'D':
        case 'T':
            return 'warning'
        case 'A':
        case 'tidak_hadir':
        case false:
            return 'danger'
        default:
            return 'danger'
    }
}

const toggleStatus = (id) => {
    presensi.value[id] =
        presensi.value[id] === 'hadir'
            ? 'tidak_hadir'
            : 'hadir'
}

const initPresensi = () => {
    const freshPresensi = {}
    if (props.students && props.students.length > 0) {
        props.students.forEach(s => {
            if (props.initialData && props.initialData[s.id] !== undefined) {
                freshPresensi[s.id] = props.initialData[s.id]
            } else {
                freshPresensi[s.id] = 'hadir'
            }
        })
    }
    presensi.value = freshPresensi
}

watch(
    () => props.students,
    () => {
        initPresensi()
        // Reset selfie files
        selfieFile.value = null
        if (selfiePreview.value) {
            URL.revokeObjectURL(selfiePreview.value)
        }
        selfiePreview.value = null
        
        if (visible.value && props.showSelfie) {
            setTimeout(startCamera, 100)
        }
    },
    { immediate: true, deep: true }
)

watch(
    () => props.initialData,
    () => {
        initPresensi()
    },
    { immediate: true, deep: true }
)

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

const hadirCount = computed(() =>
    Object.values(presensi.value).filter(v => v === 'hadir').length
)
const absenCount = computed(() =>
    Object.values(presensi.value).filter(v => v !== 'hadir' && v !== 'H' && v !== true).length
)

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
/* ===== DIALOG ===== */
:deep(.p-dialog-content) {
    padding: 0.5rem !important;
    background-color: var(--surface-ground);
}

:deep(.p-dialog-footer) {
    padding: 1rem !important;
    border-top: 1px solid var(--surface-200);
}

/* ===== LIST ===== */
.presensi-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 0.25rem;
}

/* ===== ITEM ===== */
.presensi-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--surface-card);
    border-radius: 0.75rem;
    box-shadow: var(--shadow-1);
}

/* NO */
.presensi-no {
    min-width: 2rem;
    text-align: center;
    font-weight: 600;
    color: var(--text-color-secondary);
}

/* INFO */
.presensi-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.student-name {
    font-weight: 600;
    color: var(--text-color);
}

.student-meta {
    font-size: 0.75rem;
    color: var(--text-color-secondary);
}

/* BUTTON */
.presensi-btn {
    width: 100%;
    max-width: 7.5rem;
}

/* ===== FOOTER ===== */
.presensi-footer {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.presensi-summary {
    display: flex;
    justify-content: space-around;
    padding: 0.75rem;
    background: var(--surface-100);
    border-radius: 0.75rem;
    font-weight: 600;
}

.presensi-summary .hadir {
    color: var(--green-600);
}

.presensi-summary .absen {
    color: var(--red-600);
}

.presensi-actions {
    display: flex;
    gap: 0.5rem;
}
</style>
