<script setup>
import { ref, computed } from 'vue';
import { router, usePage, Head } from '@inertiajs/vue3';
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    subjects: {
        type: Array,
        default: () => []
    },
    myTka: {
        type: Object,
        default: null
    },
    myTkas: {
        type: Array,
        default: () => []
    },
    isRegistrationActive: {
        type: Boolean,
        default: false
    },
    isClassXII: {
        type: Boolean,
        default: false
    },
    student: Object,
    activeYear: Object,
});

const toast = useToast();
const confirm = useConfirm();
const isSubmitting = ref(false);
const tempSelected = ref([null, null]);

const selectedList = computed(() => {
    let list = [];
    if (props.myTkas && props.myTkas.length > 0) {
        list = [...props.myTkas];
    } else if (props.myTka) {
        list = [props.myTka];
    }
    
    if (list.length === 2) {
        list.sort((a, b) => {
            const codeA = a.subject?.code || '';
            const codeB = b.subject?.code || '';
            return codeA.localeCompare(codeB);
        });
    }
    return list;
});

const isComplete = computed(() => selectedList.value.length >= 2);

const isSelected = (subjectId) => {
    return selectedList.value.some(item => item.tka_subject_id === subjectId);
};

const availableSubjects = computed(() => {
    return props.subjects.filter(s => !isSelected(s.id));
});

const submitDirectChoice = (index) => {
    const subject = tempSelected.value[index];
    if (!subject) return;

    if (!props.isRegistrationActive) {
        toast.add({ severity: 'warn', summary: 'Pendaftaran Ditutup', detail: 'Saat ini pemilihan mata pelajaran TKA sedang ditutup.', life: 3000 });
        return;
    }
    if (!props.isClassXII) {
        toast.add({ severity: 'warn', summary: 'Khusus Kelas XII', detail: 'Pemilihan TKA hanya diperuntukkan bagi siswa Kelas XII.', life: 3000 });
        return;
    }

    isSubmitting.value = true;
    router.post(route('student.tka.register'), {
        tka_subject_id: subject.id
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            tempSelected.value[index] = null;
            toast.add({
                severity: 'success',
                summary: 'Berhasil',
                detail: 'Mata pelajaran TKA berhasil disimpan',
                life: 3000
            });
        },
        onError: () => {
            isSubmitting.value = false;
            toast.add({ severity: 'error', summary: 'Gagal', detail: 'Terjadi kesalahan saat menyimpan pilihan', life: 3000 });
        }
    });
};

const confirmLeave = (item) => {
    if (!props.isRegistrationActive) {
        toast.add({ severity: 'warn', summary: 'Pendaftaran Ditutup', detail: 'Tidak dapat membatalkan pilihan saat pendaftaran ditutup.', life: 3000 });
        return;
    }
    confirm.require({
        header: 'Konfirmasi Pembatalan',
        message: `Yakin ingin membatalkan pilihan mata pelajaran TKA "${item.subject?.name || ''}"?`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Ya, Batalkan',
        rejectLabel: 'Tidak',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('student.tka.leave'), {
                data: { tka_subject_id: item.tka_subject_id },
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Pilihan mata pelajaran TKA berhasil dibatalkan', life: 3000 });
                }
            });
        }
    });
};
</script>

<template>
    <SiswaLayout title="Pilihan TKA">
        <div class="max-w-6xl mx-auto flex flex-column gap-4 pb-6">

            <!-- HEADER BANNER -->
            <div class="surface-card p-4 md:p-5 border-round-2xl shadow-3 border-1 border-primary-100 bg-gradient-to-r from-primary-50 to-white flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center gap-3">
                <div class="flex align-items-center gap-3">
                    <div class="w-4rem h-4rem border-circle bg-primary-100 flex align-items-center justify-content-center shadow-1 flex-shrink-0">
                        <i class="pi pi-book text-primary text-3xl"></i>
                    </div>
                    <div>
                        <div class="flex align-items-center gap-2 mb-1">
                            <span class="text-xs font-bold text-primary uppercase tracking-wider">Tes Kompetensi Akademik (TKA) - Wajib 2 Mapel</span>
                            <Tag 
                                :value="isRegistrationActive ? 'Pendaftaran Buka' : 'Pendaftaran Tutup'" 
                                :severity="isRegistrationActive ? 'success' : 'secondary'"
                            />
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-900 m-0">Pilihan Mata Pelajaran TKA</h1>
                        <p class="text-600 text-sm m-0 mt-1">
                            Siswa Kelas XII diwajibkan memilih tepat <strong class="text-primary font-bold">2 Mata Pelajaran TKA</strong> untuk Tahun Ajaran 
                            <strong class="text-800">{{ activeYear?.name || '-' }}</strong>
                        </p>
                    </div>
                </div>

                <div class="flex align-items-center gap-2">
                    <span v-if="isClassXII" class="text-xs font-bold bg-blue-100 text-blue-800 px-3 py-2 border-round-3xl flex align-items-center gap-2">
                        <i class="pi pi-check-circle"></i> Status: Siswa Kelas XII
                    </span>
                </div>
            </div>

            <!-- WARNING JIKA BUKAN KELAS XII -->
            <div v-if="!isClassXII" class="p-4 border-round-xl bg-orange-50 border-1 border-orange-200 flex align-items-start gap-3">
                <i class="pi pi-exclamation-triangle text-orange-600 text-2xl mt-1"></i>
                <div>
                    <h3 class="m-0 text-orange-900 font-bold mb-1">Perhatian: Khusus Siswa Kelas XII</h3>
                    <p class="m-0 text-orange-800 text-sm line-height-3">
                        Pemilihan dan editing Mata Pelajaran TKA saat ini diperuntukkan secara khusus bagi siswa Kelas XII. Data kelas Anda saat ini tercatat di luar Kelas XII.
                    </p>
                </div>
            </div>

            <!-- CARD PILIHAN SAAT INI (MY TKA) -->
            <div class="surface-card p-4 md:p-5 border-round-2xl shadow-2 border-1 border-200">
                <div class="flex align-items-center justify-content-between mb-3">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-star-fill text-yellow-500 text-xl"></i>
                        <h2 class="text-xl font-bold text-900 m-0">Mata Pelajaran TKA Pilihan Saya</h2>
                    </div>
                    <Tag 
                        :value="`${selectedList.length}/2 Mapel ${selectedList.length === 2 ? '(Lengkap)' : '(Belum Lengkap)'}`" 
                        :severity="selectedList.length === 2 ? 'success' : (selectedList.length === 1 ? 'warning' : 'secondary')" 
                    />
                </div>

                <div class="flex flex-column gap-3">
                    <template v-for="index in 2" :key="index">
                        <div v-if="selectedList[index - 1]" class="p-4 border-round-xl bg-green-50 border-1 border-green-200 flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between gap-4">
                            <div class="flex align-items-center gap-3">
                                <div class="w-4rem h-4rem border-circle bg-green-500 text-white flex align-items-center justify-content-center text-xl font-bold shadow-2 flex-shrink-0">
                                    {{ index }}
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-green-700 uppercase bg-green-100 px-2 py-1 border-round">
                                        {{ selectedList[index - 1].subject?.code || 'TKA' }}
                                    </span>
                                    <h3 class="text-xl md:text-2xl font-bold text-green-900 m-0 mt-2">
                                        {{ selectedList[index - 1].subject?.name || 'Mata Pelajaran Terpilih' }}
                                    </h3>
                                    <p class="text-green-800 text-xs m-0 mt-1">
                                        Pilihan ke-{{ index }} tersimpan pada sistem untuk Tahun Ajaran {{ activeYear?.name || '-' }}.
                                    </p>
                                </div>
                            </div>

                            <div v-if="isRegistrationActive && isClassXII" class="flex gap-2 w-full md:w-auto">
                                <Button 
                                    label="Batalkan Pilihan Ini" 
                                    icon="pi pi-trash" 
                                    severity="danger" 
                                    outlined 
                                    class="w-full md:w-auto font-bold"
                                    @click="confirmLeave(selectedList[index - 1])"
                                />
                            </div>
                        </div>

                        <div v-else class="p-4 border-round-xl bg-blue-50 border-1 border-blue-200 flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between gap-4">
                            <div class="flex align-items-center gap-3 w-full">
                                <div class="w-4rem h-4rem border-circle bg-blue-100 flex align-items-center justify-content-center text-xl font-bold shadow-2 flex-shrink-0 text-blue-600">
                                    {{ index }}
                                </div>
                                <div class="w-full">
                                    <h4 class="m-0 text-blue-900 font-bold mb-2">Pilih Mata Pelajaran ke-{{ index }}</h4>
                                    <div class="flex flex-column md:flex-row align-items-start md:align-items-center gap-2 w-full">
                                        <Dropdown 
                                            v-if="isRegistrationActive && isClassXII"
                                            v-model="tempSelected[index - 1]" 
                                            :options="availableSubjects" 
                                            optionLabel="name" 
                                            placeholder="Pilih Mata Pelajaran TKA..." 
                                            class="w-full md:w-25rem"
                                        >
                                            <template #option="slotProps">
                                                <div class="flex align-items-center">
                                                    <span class="text-xs font-bold bg-primary-50 text-primary px-2 py-1 border-round mr-2">{{ slotProps.option.code || 'TKA' }}</span>
                                                    <span>{{ slotProps.option.name }}</span>
                                                </div>
                                            </template>
                                        </Dropdown>
                                        <span v-else class="text-sm text-blue-800">
                                            {{ !isClassXII ? 'Khusus Kelas XII' : 'Pendaftaran Ditutup' }}
                                        </span>
                                        
                                        <Button 
                                            v-if="isRegistrationActive && isClassXII"
                                            label="Simpan Pilihan" 
                                            icon="pi pi-check" 
                                            class="p-button-primary font-bold w-full md:w-auto mt-2 md:mt-0"
                                            :loading="isSubmitting"
                                            :disabled="!tempSelected[index - 1]"
                                            @click="submitDirectChoice(index - 1)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </SiswaLayout>
</template>
