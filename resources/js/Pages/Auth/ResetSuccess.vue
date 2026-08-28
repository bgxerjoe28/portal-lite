<template>
    <div class="flex align-items-center justify-content-center min-h-screen surface-ground p-3">
        <div class="surface-card p-5 shadow-2 border-round w-full lg:w-4 text-center">
            <i class="pi pi-check-circle text-success text-6xl mb-4"></i>
            <h2 class="text-900 font-bold mb-2">Reset Berhasil!</h2>
            <p class="text-600 mb-4">Halo <b>{{ studentName }}</b>, password Anda telah diperbarui menjadi:</p>
            
            <div class="p-4 surface-100 border-round mb-4 border-2 border-dashed border-primary">
                <span class="text-3xl font-mono font-bold text-primary">{{ newPassword }}</span>
            </div>

            <p class="text-sm text-danger mb-5">
                <i class="pi pi-info-circle"></i> Segera ganti password ini setelah Anda login demi keamanan.
            </p>

            <Button label="Login Sekarang" icon="pi pi-sign-in" @click="$inertia.visit(route('login'))" class="w-full" />
        </div>
    </div>
</template>

<script setup>
import Button from 'primevue/button';
defineProps({
    newPassword: String,
    studentName: String
});
</script>