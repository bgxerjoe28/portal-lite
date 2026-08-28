<template>
    <Head :title="title ? `${title} - ${page.props.app?.settings?.school_name || 'Portal'}` : (page.props.app?.settings?.school_name || 'Portal')" />
    <Toast class="mobile-toast" />
    <ConfirmDialog />

    <div class="min-h-screen flex flex-column surface-ground pb-8">
        
        <!-- ⚠️ IMPERSONATION BANNER -->
        <div
            v-if="page.props.impersonating?.active"
            class="impersonate-banner flex align-items-center justify-content-between px-3 py-2"
        >
            <div class="flex align-items-center gap-2 text-xs md:text-sm">
                <i class="pi pi-exclamation-triangle text-base"></i>
                <span class="font-bold">MODE IMPERSONATION</span>
                <span class="hidden md:inline opacity-90">— Login sebagai <strong>{{ user?.name }}</strong></span>
            </div>
            <button
                class="impersonate-exit-btn"
                @click="leaveImpersonate"
            >
                <i class="pi pi-sign-out mr-1"></i> Kembali ke {{ page.props.impersonating.admin_name }}
            </button>
        </div>

        <header class="surface-overlay py-2 px-3 shadow-2 flex justify-content-between align-items-center sticky top-0 z-5">
            <div class="flex align-items-center gap-2">
                <i class="pi pi-graduation-cap text-primary text-xl"></i>
                <div class="flex flex-column">
                    <span class="text-900 font-bold text-sm">SISWA {{ page.props.app?.settings?.school_name || 'PORTAL SMA' }}</span>
                    <small class="text-600 text-xs font-bold">
                        {{ userRoles }} <span v-if="page.props.app?.academic_year" class="text-primary"> | {{ page.props.app.academic_year }}</span>
                    </small>
                </div>
            </div>
            
            <div class="flex align-items-center gap-2">
                <div class="w-2rem h-2rem border-circle overflow-hidden shadow-1 border border-primary flex align-items-center justify-content-center bg-slate-100 cursor-pointer" @click="toggleProfileMenu" aria-haspopup="true" aria-controls="profile_menu_siswa">
                    <img v-if="user?.avatar_url" :src="user.avatar_url" class="w-full h-full object-cover" alt="Avatar" />
                    <i v-else class="pi pi-user text-primary text-sm"></i>
                </div>
                <Menu ref="profileMenu" id="profile_menu_siswa" :model="profileMenuItems" :popup="true" />
            </div>
        </header>

        <main class="flex-1 p-3 animate-fade-in">
            <slot />
        </main>

        <nav class="fixed bottom-0 left-0 w-full surface-overlay shadow-5 border-top-1 border-200 z-5">
            <div class="flex justify-content-around align-items-center py-2">
                <button 
                    @click="router.get(route('student.dashboard'))" 
                    class="nav-item"
                    :class="{'active': isUrlActive(route('student.dashboard'))}"
                >
                    <i class="pi pi-home"></i>
                    <span>Beranda</span>
                </button>

                <button 
                    @click="router.get(route('student.presensi.index'))" 
                    class="nav-item"
                    :class="{'active': isUrlActive(route('student.presensi.index'))}"
                >
                    <i class="pi pi-map-marker"></i>
                    <span>Presensi</span>
                </button>
                <button 
                    @click="router.get(route('student.presensi.recap'))" 
                    class="nav-item"
                    :class="{'active': isUrlActive(route('student.presensi.recap'))}"
                >
                    <i class="pi pi-chart-line"></i>
                    <span>Rekap Presensi</span>
                </button>

                <button 
                    @click="router.get(route('student.grades.index'))" 
                    class="nav-item"
                    :class="{'active': isUrlActive('/student/grades') || isUrlActive('/student/nilai')}"
                >
                    <i class="pi pi-file-edit"></i>
                    <span>Rekap Nilai</span>
                </button>

                <button @click="showFullMenu = true" class="nav-item">
                    <i class="pi pi-th-large"></i>
                    <span>Menu</span>
                </button>
            </div>
        </nav>

        <Drawer 
            v-model:visible="showFullMenu" 
            position="bottom" 
            header="Navigasi Menu" 
            class="h-auto border-top-3 border-primary"
            style="max-height: 80vh;"
        >
            <div class="user-info-mini flex align-items-center p-3 surface-100 border-round mb-4">
                <div class="flex flex-column">
                    <span class="font-bold">{{ user?.name }}</span>
                    <small class="text-600">{{ userRoles }}</small>
                </div>
            </div>

            <ul class="list-none p-0 m-0">
                <template v-for="item in menuItems" :key="item.label">
                    <li v-if="item.separator" class="mt-4 mb-2 text-500 font-bold text-xs uppercase px-2">
                        {{ item.label }}
                    </li>
                    <li v-else-if="item.to" class="mb-1">
                        <a 
                            @click="onMenuClick(item)" 
                            class="flex align-items-center p-3 border-round hover:surface-100 cursor-pointer"
                            :class="{'text-primary font-bold surface-50': isUrlActive(item.to)}"
                        >
                            <i :class="[item.icon, 'mr-3']"></i>
                            <span>{{ item.label }}</span>
                        </a>
                    </li>
                    <li v-else-if="item.items" class="mb-1">
                        <div class="p-3 font-bold text-900 flex align-items-center">
                             <i :class="[item.icon, 'mr-3 text-primary']"></i>
                             {{ item.label }}
                        </div>
                        <ul class="list-none pl-5">
                            <li v-for="child in item.items" :key="child.label">
                                <a @click="onMenuClick(child)" class="flex align-items-center p-3 text-700">
                                    <i :class="[child.icon, 'mr-3 text-sm']"></i>
                                    <span>{{ child.label }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </template>
            </ul>
        </Drawer>

        <!-- ⚠️ MODAL PERINGATAN WAJIB EKSTRAKULIKULER -->
        <Dialog
            v-model:visible="showWarningModal"
            modal
            header="Perhatian: Wajib Mengikuti Ekstrakulikuler"
            :style="{ width: '90vw', maxWidth: '480px' }"
            :closable="true"
        >
            <div class="flex flex-column align-items-center text-center p-2">
                <div class="w-4rem h-4rem border-circle bg-orange-100 flex align-items-center justify-content-center mb-3">
                    <i class="pi pi-exclamation-triangle text-orange-500 text-3xl"></i>
                </div>
                <h3 class="m-0 mb-2 text-900">Belum Ada Ekstrakulikuler Terpilih</h3>
                <p class="text-700 line-height-3 m-0 mb-4">
                    {{ page.props.extracurricular_warning?.message || 'Anda diwajibkan mengikuti minimal 1 (satu) kegiatan Ekstrakulikuler pada tahun ajaran ini.' }}
                </p>
                <div class="flex gap-2 w-full">
                    <Button
                        label="Pilih Ekstrakulikuler Sekarang"
                        icon="pi pi-arrow-right"
                        iconPos="right"
                        class="p-button-primary flex-1"
                        @click="goToExtracurricular"
                    />
                </div>
            </div>
        </Dialog>

        <!-- ⚠️ MODAL PERINGATAN WAJIB MAPEL TKA (KELAS XII) -->
        <Dialog
            v-model:visible="showTkaWarningModal"
            modal
            header="Perhatian: Wajib Memilih 2 Mapel TKA"
            :style="{ width: '90vw', maxWidth: '480px' }"
            :closable="true"
            :closeOnEscape="true"
        >
            <div class="flex flex-column align-items-center text-center p-2">
                <div class="w-4rem h-4rem border-circle bg-blue-100 flex align-items-center justify-content-center mb-3">
                    <i class="pi pi-book text-blue-500 text-3xl"></i>
                </div>
                <h3 class="m-0 mb-2 text-900">
                    {{ page.props.tka_warning?.chosen_count === 1 ? 'Pilihan TKA Belum Lengkap (1/2 Mapel)' : 'Belum Memilih Mapel TKA (0/2 Mapel)' }}
                </h3>
                <p class="text-700 line-height-3 m-0 mb-4">
                    {{ page.props.tka_warning?.message || 'Anda adalah siswa Kelas XII yang diwajibkan memilih tepat 2 Mapel TKA.' }}
                </p>
                <div class="w-full text-left mb-3">
                    <label class="font-bold block mb-2 text-sm">1. Mata Pelajaran TKA Pertama:</label>
                    <Select 
                        v-model="selectedTkaSubject1" 
                        :options="subjectsForSelect1" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="-- Pilih Mapel TKA 1 --" 
                        class="w-full"
                        :disabled="page.props.tka_warning?.chosen_count >= 1"
                    />
                    <small v-if="page.props.tka_warning?.chosen_count >= 1" class="text-green-600 font-semibold block mt-1">
                        <i class="pi pi-check-circle mr-1"></i>Mapel pertama sudah tersimpan di sistem
                    </small>
                </div>
                <div class="w-full text-left mb-4">
                    <label class="font-bold block mb-2 text-sm">2. Mata Pelajaran TKA Kedua:</label>
                    <Select 
                        v-model="selectedTkaSubject2" 
                        :options="subjectsForSelect2" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="-- Pilih Mapel TKA 2 --" 
                        class="w-full"
                    />
                    <small v-if="selectedTkaSubject1 && selectedTkaSubject1 === selectedTkaSubject2" class="text-red-500 block mt-1">
                        *Mata pelajaran kedua harus berbeda dari mata pelajaran pertama
                    </small>
                </div>
                <div class="flex gap-2 w-full">
                    <Button
                        label="Nanti Saja"
                        icon="pi pi-times"
                        severity="secondary"
                        class="flex-1"
                        @click="showTkaWarningModal = false"
                    />
                    <Button
                        label="Simpan Pilihan (2 Mapel)"
                        icon="pi pi-save"
                        class="p-button-primary flex-1 font-bold"
                        :disabled="!canSubmitTka"
                        :loading="isSubmittingTka"
                        @click="submitTka"
                    />
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router, Head } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import Button from 'primevue/button';
import Drawer from 'primevue/drawer';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import Avatar from 'primevue/avatar';
import Menu from 'primevue/menu';
import Select from 'primevue/select';

const props = defineProps({ title: String });
const page = usePage();
const toast = useToast();
const showFullMenu = ref(false);

const showWarningModal = ref(false);

watch(() => page.props.extracurricular_warning, (warning) => {
    if (warning && warning.show && !page.url.startsWith('/student/extracurriculars')) {
        showWarningModal.value = true;
    } else {
        showWarningModal.value = false;
    }
}, { immediate: true });

const goToExtracurricular = () => {
    showWarningModal.value = false;
    router.get(route('student.extracurriculars.index'));
};

// --- TKA REGISTRATION MODAL LOGIC ---
const showTkaWarningModal = ref(false);
const selectedTkaSubject1 = ref(null);
const selectedTkaSubject2 = ref(null);
const isSubmittingTka = ref(false);

watch([() => page.url, () => page.props.tka_warning], ([url, warning]) => {
    if (warning && warning.show && !page.url.startsWith('/student/tka')) {
        showTkaWarningModal.value = true;
        const chosenIds = warning.chosen_ids || [];
        if (chosenIds.length >= 1) {
            selectedTkaSubject1.value = chosenIds[0];
        } else {
            selectedTkaSubject1.value = null;
        }
        if (chosenIds.length >= 2) {
            selectedTkaSubject2.value = chosenIds[1];
        } else {
            selectedTkaSubject2.value = null;
        }
    } else {
        showTkaWarningModal.value = false;
    }
}, { immediate: true });

const subjectsForSelect1 = computed(() => {
    return page.props.tka_warning?.subjects || [];
});

const subjectsForSelect2 = computed(() => {
    const subjects = page.props.tka_warning?.subjects || [];
    return subjects.filter(s => s.id !== selectedTkaSubject1.value);
});

const canSubmitTka = computed(() => {
    if (!selectedTkaSubject1.value || !selectedTkaSubject2.value) return false;
    if (selectedTkaSubject1.value === selectedTkaSubject2.value) return false;
    return true;
});

const submitTka = () => {
    if (!canSubmitTka.value) return;
    isSubmittingTka.value = true;
    router.post(route('student.tka.register'), {
        tka_subject_ids: [selectedTkaSubject1.value, selectedTkaSubject2.value]
    }, {
        onSuccess: () => {
            isSubmittingTka.value = false;
            showTkaWarningModal.value = false;
            toast.add({ severity: 'success', summary: 'Berhasil', detail: '2 Mata Pelajaran TKA berhasil disimpan', life: 3000 });
        },
        onError: () => {
            isSubmittingTka.value = false;
            toast.add({ severity: 'error', summary: 'Gagal', detail: 'Terjadi kesalahan saat menyimpan pilihan mapel TKA', life: 3000 });
        }
    });
};
// ------------------------------------

const user = computed(() => page.props.auth?.user);

const profileMenu = ref();
const toggleProfileMenu = (event) => {
    profileMenu.value.toggle(event);
};
const profileMenuItems = ref([
    {
        label: 'Profil Saya',
        icon: 'pi pi-user',
        command: () => {
            router.get('/student/profile');
        }
    },
    {
        label: 'Ganti Password',
        icon: 'pi pi-key',
        command: () => {
            router.get('/change-password');
        }
    },
    {
        label: 'Logout',
        icon: 'pi pi-power-off',
        command: () => {
            logout();
        }
    }
]);

const menuItems = computed(() => page.props.auth?.menu || []);
const userRoles = computed(() => user.value?.roles?.map(r => r.name.toUpperCase()).join(', ') || 'SISWA');

const isUrlActive = (url) => {
    if (!url) return false;
    const currentPath = page.url; 
    return currentPath === url || currentPath.startsWith(url);
};

const onMenuClick = (item) => {
    if (item.to) {
        showFullMenu.value = false;
        router.get(item.to);
    }
};

const logout = () => {
    router.post('/logout');
};

const leaveImpersonate = () => {
    router.post(page.props.impersonating.leave_url);
};

// Flash Message Handler
watch(() => page.props.flash, (flash) => {
    if (!flash) return;
    if (flash.success) toast.add({ severity: 'success', summary: 'Berhasil', detail: flash.success, life: 3000 });
    if (flash.error) toast.add({ severity: 'error', summary: 'Gagal', detail: flash.error, life: 5000 });
}, { deep: true, immediate: true });
</script>

<style scoped>
.nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: none;
    border: none;
    color: #64748b;
    gap: 4px;
    cursor: pointer;
    transition: color 0.2s;
}
.nav-item i { font-size: 1.4rem; }
.nav-item span { font-size: 0.7rem; font-weight: 600; }
.nav-item.active { color: var(--primary-color); }

:deep(.mobile-toast) {
    top: 70px !important;
    left: 10px !important;
    right: 10px !important;
    width: auto !important;
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
<style>
/* Impersonation Banner - global */
.impersonate-banner {
    background: linear-gradient(90deg, #b91c1c, #dc2626);
    color: white;
    font-size: 0.83rem;
    position: sticky;
    top: 0;
    z-index: 9999;
    border-bottom: 2px solid #991b1b;
    box-shadow: 0 2px 8px rgba(185,28,28,0.4);
}
.impersonate-exit-btn {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1.5px solid rgba(255,255,255,0.5);
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s;
    white-space: nowrap;
    flex-shrink: 0;
}
.impersonate-exit-btn:hover {
    background: rgba(255,255,255,0.3);
}
</style>