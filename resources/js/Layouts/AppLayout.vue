<template>
    <template v-if="shouldUseMobileLayout">
        <MobileLayout :title="title">
            <slot />
        </MobileLayout>
    </template>
    <template v-else>
        <Head :title="title ? `${title} - ${page.props.app?.settings?.school_name || 'Portal'}` : (page.props.app?.settings?.school_name || 'Portal')" />
        <Toast class="custom-toast"/>
        <ConfirmDialog>
            <template #message="slotProps">
                <div class="flex align-items-start gap-3">
                    <i :class="slotProps.message.icon" class="text-3xl text-orange-500"></i>
                    <div class="line-height-3">
                        <div class="font-medium text-900 mb-1">{{ slotProps.message.header }}</div>
                        <div class="text-700" v-html="slotProps.message.message"></div>
                    </div>
                </div>
            </template>
        </ConfirmDialog>

        <div class="min-h-screen surface-ground layout-root">

        <!-- ⚠️ IMPERSONATION BANNER -->
        <div
            v-if="page.props.impersonating?.active"
            class="impersonate-banner flex align-items-center justify-content-between px-4 py-2"
        >
            <div class="flex align-items-center gap-2">
                <i class="pi pi-exclamation-triangle text-xl"></i>
                <span class="font-bold">MODE IMPERSONATION AKTIF</span>
                <span class="opacity-80">— Anda sedang login sebagai <strong>{{ user?.name }}</strong> (bukan akun admin asli Anda)</span>
            </div>
            <button
                class="impersonate-exit-btn"
                @click="leaveImpersonate"
            >
                <i class="pi pi-sign-out mr-1"></i> Kembali sebagai {{ page.props.impersonating.admin_name }}
            </button>
        </div>
        <div class="surface-overlay py-3 px-5 shadow-2 flex justify-content-between align-items-center sticky top-0 z-5">
            <div class="flex align-items-center gap-3">
                <Button 
                    icon="pi pi-bars" 
                    @click="onMenuToggle" 
                    class="p-button-text p-button-secondary" 
                />
                
                <span class="text-900 font-bold text-xl cursor-pointer flex align-items-center gap-2" @click="router.get('/dashboard')">
                    <img v-if="page.props.app?.settings?.site_logo" :src="'/storage/' + page.props.app.settings.site_logo" class="h-2rem w-auto" alt="Logo" />
                    <i v-else class="pi pi-book text-primary text-2xl"></i>
                    <span>{{ page.props.app?.settings?.school_name || 'PORTAL SMA' }}</span>
                </span>
                <Tag v-if="page.props.app?.academic_year" :value="page.props.app.academic_year" severity="info" class="ml-3 hidden md:inline-flex"></Tag>
            </div>
            
            <div class="flex align-items-center">
                <div class="flex flex-column align-items-end mr-3">
                    <span class="text-900 font-semibold">{{ user?.name }}</span>
                    <small class="text-600 font-bold">{{ userRoles }}</small>
                </div>
                <div class="w-2.5rem h-2.5rem border-circle overflow-hidden shadow-1 border-2 border-primary flex align-items-center justify-content-center bg-slate-100 cursor-pointer hover:surface-100" @click="toggleProfileMenu" aria-haspopup="true" aria-controls="profile_menu">
                    <img v-if="user?.avatar_url" :src="user.avatar_url" class="w-full h-full object-cover" alt="Avatar" />
                    <i v-else class="pi pi-user text-primary text-lg"></i>
                </div>
                <Menu ref="profileMenu" id="profile_menu" :model="profileMenuItems" :popup="true" />
            </div>
        </div>

        <div class="flex relative main-container">
            <Drawer 
                v-model:visible="sidebarActive" 
                :modal="false" 
                :dismissable="false" 
                :showCloseIcon="false"
                class="h-screen surface-section border-right-1 border-300 shadow-2 p-0 fixed transition-all transition-duration-300" 
                style="top: 65px; width: 260px;"
            >
                <div class="px-4 pt-4 mb-2 text-primary font-bold uppercase text-xs">Menu Navigasi</div>
                <Divider class="mx-3" />
                
                <ul class="list-none p-2 m-0 overflow-y-auto" style="max-height: calc(100vh - 150px)">
                    <template v-for="item in menuItems" :key="item.label">
                        <li v-if="item.separator" class="layout-menuitem-category mt-4 mb-1 px-3 text-500 font-bold text-xs uppercase">
                            {{ item.label }}
                        </li>

                        <li v-else-if="item.to && !item.items">
                            <a v-ripple @click="onMenuClick(item)"
                                class="flex align-items-center cursor-pointer p-3 border-round transition-duration-150 w-full"
                                :class="[isUrlActive(item.to) ? 'surface-200 text-primary font-bold border-left-3 border-primary' : 'text-700 hover:surface-100']">
                                <i :class="[item.icon, 'mr-2']"></i>
                                <span>{{ item.label }}</span>
                            </a>
                        </li>

                        <li v-else-if="item.items">
                            <div @click="toggleMenu(item.label)"
                                class="flex align-items-center justify-content-between px-3 py-3 cursor-pointer text-700 font-semibold hover:surface-100 border-round">
                                <div class="flex align-items-center">
                                    <i :class="[item.icon, 'mr-2 text-primary']"></i>
                                    <span>{{ item.label }}</span>
                                </div>
                                <i class="pi text-xs" :class="isExpanded(item) ? 'pi-chevron-down' : 'pi-chevron-right'"></i>
                            </div>
                            <transition name="submenu">
                                <ul v-show="isExpanded(item)" class="list-none pl-3 overflow-hidden">
                                    <li v-for="child in item.items" :key="child.label">
                                        <a v-ripple @click="onMenuClick(child)"
                                            class="flex align-items-center cursor-pointer p-3 border-round w-full"
                                            :class="[isUrlActive(child.to) ? 'surface-200 text-primary font-bold border-left-3 border-primary' : 'text-700 hover:surface-100']">
                                            <i :class="[child.icon, 'mr-2 text-sm']"></i>
                                            <span class="text-sm">{{ child.label }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </transition>
                        </li>
                    </template>
                </ul>
            </Drawer>

            <div 
                class="flex-1 p-4 transition-all transition-duration-300 content-area"
                :style="{ marginLeft: sidebarActive ? '260px' : '0px' }"
            >
                <div class="animate-fade-in">
                    <slot />
                </div>
            </div>
        </div>
    </div>
    </template>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router, Head } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

// Custom Layout for Mobile / Guru Force
import MobileLayout from '@/Layouts/MobileLayout.vue';

// PrimeVue Components
import Toast from 'primevue/toast';
import Button from 'primevue/button';
import Drawer from 'primevue/drawer';
import Divider from 'primevue/divider';
import ConfirmDialog from 'primevue/confirmdialog';
import Menu from 'primevue/menu';
import Tag from 'primevue/tag';

const sidebarActive = ref(true);
const props = defineProps({ title: String });
const onMenuToggle = () => {
    sidebarActive.value = !sidebarActive.value;
};
const page = usePage();
const toast = useToast();
const openMenus = ref({});
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

// AMBIL DATA DARI BACKEND (Shared via HandleInertiaRequests)
const menuItems = computed(() => page.props.auth?.menu || []);
const user = computed(() => page.props.auth?.user);
const userRoles = computed(() => user.value?.roles?.map(r => r.name).join(', ').toUpperCase() || 'GUEST');
const shouldUseMobileLayout = computed(() => {
    if (!user.value) return false;
    return !user.value.roles?.some(r => r.name.toLowerCase() === 'admin');
});

// LOGIC: Cek apakah menu sedang aktif berdasarkan URL
const isUrlActive = (url) => {
    if (!url) return false;
    const currentPath = page.url; 
    return currentPath === url || (url !== '/dashboard' && currentPath.startsWith(url));
};

// LOGIC: Cek apakah ada anak menu yang aktif (untuk auto-expand parent)
const isAnyChildActive = (items) => {
    return items?.some(child => isUrlActive(child.to));
};

// LOGIC: Gabungan antara klik manual dan auto-expand
const isExpanded = (item) => {
    return openMenus.value[item.label] || isAnyChildActive(item.items);
};

const toggleMenu = (label) => {
    openMenus.value[label] = !openMenus.value[label];
};

const onMenuClick = (item) => {
    if (item.to) {
        router.get(item.to);
    }
};

const logout = () => {
    router.post('/logout');
};

const leaveImpersonate = () => {
    router.post(page.props.impersonating.leave_url);
};


// Watch Flash Messages dari Laravel
let lastSuccessFlash = null;
let lastErrorFlash = null;

watch(
  () => page.props.flash,
  (flash) => {
    if (!flash) return;
    
    if (flash.success && flash.success !== lastSuccessFlash) {
      lastSuccessFlash = flash.success;
      const isRestore = flash.success.toLowerCase().includes('restore') || flash.success.toLowerCase().includes('dipulihkan');
      toast.add({
        severity: 'success',
        summary: isRestore ? '✅ Restore Berhasil' : 'Berhasil',
        detail: flash.success,
        life: isRestore ? 8000 : 3000
      });
    }

    if (flash.error && flash.error !== lastErrorFlash) {
      lastErrorFlash = flash.error;
      toast.add({
        severity: 'error',
        summary: 'Gagal',
        detail: flash.error,
        life: 7000
      });
    }
  },
  { deep: true, immediate: true }
);

</script>
<style>
/* Jangan gunakan 'scoped' agar bisa menembus portal PrimeVue */
.p-toast {
    top: 80px !important; /* Berikan jarak lebih dari tinggi Topbar (65px) */
    z-index: 9999 !important; /* Pastikan paling depan, mengalahkan Topbar */
}
.layout-root {
    overflow-x: hidden;
}
.main-container {
    width: 100%;
    overflow: hidden;
}
.content-area {
    overflow-x: hidden;
    width: 100%;
}
</style>
<style scoped>
.submenu-enter-active, .submenu-leave-active { transition: max-height 0.3s ease-out, opacity 0.2s ease; }
.submenu-enter-from, .submenu-leave-to { max-height: 0; opacity: 0; }
.submenu-enter-to, .submenu-leave-from { max-height: 1000px; opacity: 1; }

.animate-fade-in { animation: fadeIn 0.4s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
<style>
/* Impersonation Banner - global (no scoped) */
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
    padding: 4px 14px;
    font-size: 0.8rem;
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