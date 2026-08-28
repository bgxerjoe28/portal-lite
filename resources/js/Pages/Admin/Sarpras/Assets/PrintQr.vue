<template>
    <div class="min-h-screen bg-slate-100 p-4 print:p-0 print:bg-white">
        <Head title="Cetak Label QR Aset Sarpras" />

        <!-- Print Control Bar (Hidden on Print) -->
        <div class="surface-card p-3 border-round-xl shadow-2 mb-4 flex justify-content-between align-items-center print:hidden max-w-7xl mx-auto">
            <div class="flex align-items-center gap-3">
                <Button icon="pi pi-arrow-left" label="Kembali" class="p-button-outlined p-button-secondary" @click="router.get(route('admin.sarpras.assets.index'))" />
                <div>
                    <h3 class="font-bold text-lg m-0 text-900">Label QR Code Aset Sarpras</h3>
                    <p class="text-500 text-xs m-0">Total {{ assets.length }} label aset siap dicetak pada kertas stiker / label.</p>
                </div>
            </div>
            <div class="flex gap-2">
                <Button icon="pi pi-print" label="Cetak Label Sekarang" class="p-button-primary" @click="printWindow" />
            </div>
        </div>

        <!-- Printable QR Code Grid -->
        <div class="max-w-7xl mx-auto print:max-w-full">
            <div class="grid qr-grid">
                <div v-for="asset in assets" :key="asset.id" class="col-12 sm:col-6 md:col-4 lg:col-3 print:col-4 p-2">
                    <div class="asset-card border-2 border-dashed border-400 p-3 border-round-lg bg-white flex flex-column align-items-center text-center shadow-1 print:shadow-none">
                        <!-- School Header Small -->
                        <div class="font-extrabold text-xs uppercase tracking-wider text-primary border-bottom-1 border-300 pb-1 mb-2 w-full text-center">
                            {{ schoolName }}
                        </div>

                        <!-- QR Code Image with Center School Logo -->
                        <div class="relative inline-flex align-items-center justify-content-center mb-2" style="position: relative; display: inline-block;">
                            <img 
                                :src="`https://api.qrserver.com/v1/create-qr-code/?size=160x160&ecc=H&data=${encodeURIComponent(asset.qr_code_token)}`" 
                                alt="QR Code" 
                                width="130" 
                                height="130"
                                class="border-round block" 
                            />
                            <div 
                                v-if="schoolLogo" 
                                class="absolute bg-white border-circle flex align-items-center justify-content-center shadow-1"
                                style="position: absolute; width: 34px; height: 34px; top: 50%; left: 50%; transform: translate(-50%, -50%); border-radius: 50%; background-color: #ffffff !important; padding: 2px; box-shadow: 0 1px 4px rgba(0,0,0,0.3); z-index: 10;"
                            >
                                <img :src="schoolLogo" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 50%; display: block;" />
                            </div>
                        </div>

                        <!-- Asset Info -->
                        <div class="font-mono font-extrabold text-sm text-900 mb-1">{{ asset.asset_code }}</div>
                        <div class="font-bold text-xs text-800 line-clamp-1 mb-1">{{ asset.name }}</div>
                        <div class="text-xs text-500 font-mono">{{ asset.qr_code_token }}</div>

                        <!-- Footer -->
                        <div class="text-xxs text-400 mt-2 border-top-1 border-200 pt-1 w-full flex justify-content-between">
                            <span>SARPRAS</span>
                            <span>{{ asset.category }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';

defineProps({
    assets: Array,
});

const page = usePage();

const schoolName = computed(() => {
    return page.props.settings?.school_name || page.props.app?.settings?.school_name || 'SEKOLAH MENENGAH ATAS NEGERI 16 SEMARANG';
});

const schoolLogo = computed(() => {
    const logo = page.props.settings?.site_logo || page.props.app?.settings?.site_logo;
    if (!logo) return null;
    if (logo.startsWith('http') || logo.startsWith('/')) return logo;
    return `/storage/${logo}`;
});

const printWindow = () => {
    window.print();
};
</script>

<style scoped>
.text-xxs {
    font-size: 0.65rem;
}

@media print {
    body {
        background: transparent !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .asset-card {
        page-break-inside: avoid;
        border: 1px dashed #000 !important;
        margin-bottom: 8px;
    }
}
</style>
