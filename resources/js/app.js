import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// 1. Import PrimeVue Core
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import Ripple from 'primevue/ripple';
import { ZiggyVue } from 'ziggy-js';
import Tooltip from 'primevue/tooltip';
import { getPaginationProps, DEFAULT_ROWS_PER_PAGE_OPTIONS } from './Utils/pagination';

// 2. Import Tema Baru (Aura) - Khusus PrimeVue v4
import Aura from '@primeuix/themes/aura';

// 3. Import Icon & Utilities (Ini tetap sama)
import 'primeicons/primeicons.css';
import 'primeflex/primeflex.css';

const appName = import.meta.env.VITE_APP_NAME || 'Portal SMA';

createInertiaApp({
    title: (title) => title ? `${title} ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });
        vueApp.config.globalProperties.$pagination = getPaginationProps;
        vueApp.config.globalProperties.$defaultRowsPerPage = DEFAULT_ROWS_PER_PAGE_OPTIONS;

        return vueApp
            .use(plugin)
            // 4. Konfigurasi Theme di sini
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: '.my-app-dark', // Biar tidak otomatis dark mode dulu
                    }
                },
                ripple: true,
                locale: {
                    startsWith: 'Dimulai dengan',
                    contains: 'Berisi',
                    notContains: 'Tidak berisi',
                    endsWith: 'Diakhiri dengan',
                    equals: 'Sama dengan',
                    notEquals: 'Tidak sama dengan',
                    noFilter: 'Tanpa Filter',
                    lt: 'Kurang dari',
                    lte: 'Kurang dari atau sama',
                    gt: 'Lebih dari',
                    gte: 'Lebih dari atau sama',
                    dateIs: 'Tanggal sama',
                    dateIsNot: 'Tanggal tidak sama',
                    dateBefore: 'Tanggal sebelum',
                    dateAfter: 'Tanggal setelah',
                    clear: 'Bersihkan',
                    apply: 'Terapkan',
                    matchAll: 'Cocok semua',
                    matchAny: 'Cocok salah satu',
                    addRule: 'Tambah aturan',
                    removeRule: 'Hapus aturan',
                    accept: 'Ya',
                    reject: 'Tidak',
                    choose: 'Pilih',
                    upload: 'Unggah',
                    cancel: 'Batal',
                    completed: 'Selesai',
                    pending: 'Menunggu',
                    dayNames: ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],
                    dayNamesShort: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
                    dayNamesMin: ['Mg','Sn','Sl','Rb','Km','Jm','Sb'],
                    monthNames: [
                        'Januari','Februari','Maret','April','Mei','Juni',
                        'Juli','Agustus','September','Oktober','November','Desember'
                    ],
                    monthNamesShort: [
                        'Jan','Feb','Mar','Apr','Mei','Jun',
                        'Jul','Agu','Sep','Okt','Nov','Des'
                    ],
                    today: 'Hari ini',
                    weekHeader: 'Minggu pertama'
                }
            })
            .use(ToastService)
            .use(ConfirmationService)
            .use(ZiggyVue)
            .directive('ripple', Ripple)
            .directive('tooltip', Tooltip)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});