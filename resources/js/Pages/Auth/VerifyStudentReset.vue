<template>
    <div class="flex align-items-center justify-content-center min-h-screen surface-ground p-3">
        <div class="surface-card p-4 shadow-2 border-round w-full lg:w-4">
            <div class="text-center mb-5">
                <i class="pi pi-user-lock text-primary text-5xl mb-3"></i>
                <div class="text-900 text-2xl font-bold mb-2">Self-Reset Password</div>
                <p class="text-600">Verifikasi data siswa untuk reset password</p>
            </div>

            <form @submit.prevent="submit">
                <div class="field mb-3">
                    <label class="font-bold">Email Login</label>
                    <InputText v-model="form.email" class="w-full" placeholder="nisn@sman16.net" />
                </div>

                <div class="field mb-4">
                    <label class="font-bold">Tanggal Lahir</label>
                    <InputText type="date" v-model="form.birth_date" class="w-full" format="yyyy-MM-dd"/>
                    <small class="text-500">Pastikan Tanggal Lahir Anda Benar</small>
                </div>

                <div v-if="$page.props.errors.message" class="p-message p-message-error mb-4 p-2 text-sm border-round">
                    {{ $page.props.errors.message }}
                </div>
                <div class="flex flex-column gap-2">
                    <Button 
                        label="Verifikasi Data" 
                        icon="pi pi-check-circle" 
                        type="submit" 
                        class="w-full" 
                        :loading="form.processing" 
                    />
                    <Button 
                        label="Batal / Kembali" 
                        icon="pi pi-arrow-left" 
                        severity="secondary" 
                        text 
                        class="w-full" 
                        @click="$inertia.visit(route('login'))" 
                    />
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const form = useForm({
    email: '',
    birth_date: ''
});

const submit = () => form.post(route('password.verify.submit'));
</script>