<template>
    <Head :title="title" />
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
                <i class="pi pi-book text-primary text-xl"></i>
                <div class="flex flex-column">
                    <span class="text-900 font-bold text-sm">PORTAL SMA 16</span>
                    <small class="text-600 text-xs font-bold">
                        {{ userRoles }} <span v-if="page.props.app?.academic_year" class="text-primary"> | {{ page.props.app.academic_year }}</span>
                    </small>
                </div>
            </div>
            
            <div class="flex align-items-center gap-2">
                <div class="w-2rem h-2rem border-circle overflow-hidden shadow-1 border border-primary flex align-items-center justify-content-center bg-slate-100 cursor-pointer" @click="toggleProfileMenu" aria-haspopup="true" aria-controls="profile_menu_mobile">
                    <img v-if="user?.avatar_url" :src="user.avatar_url" class="w-full h-full object-cover" alt="Avatar" />
                    <i v-else class="pi pi-user text-primary text-sm"></i>
                </div>
                <Menu ref="profileMenu" id="profile_menu_mobile" :model="profileMenuItems" :popup="true" />
            </div>
        </header>

        <main class="flex-1 p-3 animate-fade-in">
            <slot />
        </main>

        <nav class="fixed bottom-0 left-0 w-full surface-overlay shadow-5 border-top-1 border-200 z-5">
            <div class="flex justify-content-around align-items-center py-2">
                <button 
                    @click="router.get(dynamicDashboardUrl)" 
                    class="nav-item"
                    :class="{'active': isUrlActive(dynamicDashboardUrl)}"
                >
                    <i class="pi pi-home"></i>
                    <span>Beranda</span>
                </button>

                <button 
                    @click="router.get(middleNavItem.to)" 
                    class="nav-item"
                    :class="{'active': isUrlActive(middleNavItem.to)}"
                >
                    <i :class="middleNavItem.icon"></i>
                    <span>{{ middleNavItem.label }}</span>
                </button>

                <!-- TOMBOL SURAT (KEPALA SEKOLAH, GURU, GURU BK, PEGAWAI) -->
                <button 
                    v-if="hasSuratBottomNav"
                    @click="router.get(suratBottomNavUrl)" 
                    class="nav-item"
                    :class="{'active': isUrlActive('/surat')}"
                >
                    <i class="pi pi-envelope"></i>
                    <span>Surat</span>
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
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router, Head } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

// PrimeVue
import Toast from 'primevue/toast';
import Button from 'primevue/button';
import Drawer from 'primevue/drawer';
import ConfirmDialog from 'primevue/confirmdialog';
import Tag from 'primevue/tag';
import Menu from 'primevue/menu';

const showFullMenu = ref(false);
const page = usePage();
const toast = useToast();
const props = defineProps({ title: String });
const menuItems = computed(() => page.props.auth?.menu || []);
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
            router.get('/profile');
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
const rolesArray = computed(() => user.value?.roles?.map(r => r.name.toUpperCase()) || []);
const userRoles = computed(() => rolesArray.value.join(', ') || 'GUEST');

const isKepsek = computed(() => {
    const roles = rolesArray.value.map(r => r.toUpperCase());
    return roles.includes('KEPALA SEKOLAH') || roles.includes('KEPSEK');
});

const isGuru = computed(() => {
    const roles = rolesArray.value.map(r => r.toUpperCase());
    return roles.includes('GURU') || roles.includes('GURU BK');
});

const hasSuratBottomNav = computed(() => {
    const roles = rolesArray.value.map(r => r.toUpperCase());
    return isKepsek.value || isGuru.value || roles.includes('PEGAWAI') || roles.includes('STAF') || roles.includes('STAFF');
});

const suratBottomNavUrl = computed(() => {
    if (isKepsek.value) {
        return '/surat/dashboard';
    }
    const roles = rolesArray.value.map(r => r.toUpperCase());
    if (roles.includes('PEGAWAI') || roles.includes('STAF') || roles.includes('STAFF')) {
        return '/surat/dashboard';
    }
    return '/surat/kalender';
});

const roleDashboardMap = {
    'ADMIN': '/admin/dashboard',
    'GURU': '/teacher/dashboard',
    'SISWA': '/student/dashboard',
    'PEGAWAI': '/staf/dashboard',
    'STAF': '/staf/dashboard',
    'STAFF': '/staf/dashboard',
    'KARYAWAN': '/staf/dashboard',
    'KEPALA SEKOLAH': '/ks/dashboard',
    'KEPSEK': '/ks/dashboard',
    'GURU BK': '/bk/dashboard'
};

const middleNavItem = computed(() => {
    const roles = rolesArray.value.map(r => r.toUpperCase());
    
    if (roles.includes('KEPALA SEKOLAH') || roles.includes('KEPSEK')) {
        return {
            label: 'Kalender',
            icon: 'pi pi-calendar',
            to: '/surat/kalender'
        };
    }
    if (roles.includes('GURU BK')) {
        return {
            label: 'Absensi',
            icon: 'pi pi-users',
            to: '/bk/monitoring/absences'
        };
    }
    if (roles.includes('PEGAWAI') || roles.includes('STAF') || roles.includes('STAFF')) {
        return {
            label: 'Surat Masuk',
            icon: 'pi pi-inbox',
            to: '/surat/masuk'
        };
    }
    if (roles.includes('SISWA')) {
        return {
            label: 'Jadwal',
            icon: 'pi pi-calendar',
            to: '/student/schedule'
        };
    }
    // Default untuk Guru
    return {
        label: 'Agenda',
        icon: 'pi pi-book',
        to: '/teacher/agenda'
    };
});

const dynamicDashboardUrl = computed(() => {
       
    // Cari role pertama yang cocok dengan map kita
    for (const roleName of rolesArray.value) {
        if (roleDashboardMap[roleName]) {
            return roleDashboardMap[roleName];
        }
    }
    
    return '/dashboard'; // Fallback jika tidak ada yang cocok
});

const isUrlActive = (url) => {
    if (!url) return false;
    const currentPath = page.url; 
    return currentPath === url || (url !== '/dashboard' && currentPath.startsWith(url));
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

// Flash Message Handler (Sama dengan AppLayout)
watch(() => page.props.flash, (flash) => {
    if (!flash) return;
    if (flash.success) {
        toast.add({ severity: 'success', summary: 'Berhasil', detail: flash.success, life: 3000 });
    }
    if (flash.error) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: flash.error, life: 5000 });
    }
}, { deep: true, immediate: true });
</script>

<style scoped>
/* Bottom Nav Styling */
.nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: none;
    border: none;
    color: #64748b; /* slate-500 */
    gap: 4px;
    cursor: pointer;
    transition: color 0.2s;
}

.nav-item i {
    font-size: 1.4rem;
}

.nav-item span {
    font-size: 0.7rem;
    font-weight: 600;
}

.nav-item.active {
    color: var(--primary-color);
}

/* Toast untuk Mobile agar tidak menutupi header */
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