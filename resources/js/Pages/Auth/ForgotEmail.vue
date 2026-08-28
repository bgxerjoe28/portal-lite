<template>
    <Head title="Lupa Email" />

    <div class="flex align-items-center justify-content-center min-h-screen surface-ground p-3">
        <div class="surface-card p-4 shadow-2 border-round w-full lg:w-4">
            <div class="text-center mb-5">
                <i class="pi pi-envelope text-primary text-5xl mb-3"></i>
                <div class="text-900 text-2xl font-bold mb-2">Lupa Email Login</div>
                <p v-if="!found_email" class="text-600">Masukkan NISN dan Tanggal Lahir untuk mengetahui email login Anda</p>
            </div>

            <!-- RESULT: Email ditemukan -->
            <div v-if="found_email" class="mb-4">
                <Message severity="success" :closable="false" class="mb-3">
                    <div>
                        <div class="font-bold mb-1">Email ditemukan untuk {{ student_name }}:</div>
                        <div class="text-lg font-mono bg-green-50 p-2 border-round mt-1 text-center select-all">
                            {{ found_email }}
                        </div>
                    </div>
                </Message>
                <Button 
                    label="Kembali ke Login" 
                    icon="pi pi-sign-in" 
                    class="w-full" 
                    @click="$inertia.visit(route('login'))" 
                />
            </div>

            <!-- FORM -->
            <form v-else @submit.prevent="submit">
                <div v-if="$page.props.errors.message" class="p-message p-message-error mb-4 p-2 text-sm border-round">
                    {{ $page.props.errors.message }}
                </div>

                <div class="field mb-3">
                    <label class="font-bold">NISN</label>
                    <InputText v-model="form.nisn" class="w-full" placeholder="Masukkan NISN Anda" />
                    <small v-if="form.errors.nisn" class="text-red-500">{{ form.errors.nisn }}</small>
                </div>

                <div class="field mb-4">
                    <label class="font-bold">Tanggal Lahir</label>
                    <InputText type="date" v-model="form.birth_date" class="w-full" />
                    <small class="text-500">Pastikan Tanggal Lahir Anda sesuai data sekolah</small>
                </div>

                <div class="flex flex-column gap-2">
                    <Button 
                        label="Cari Email Saya" 
                        icon="pi pi-search" 
                        type="submit" 
                        class="w-full" 
                        :loading="form.processing" 
                    />
                    <Button 
                        label="Kembali ke Login" 
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
import { useForm, Head } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';

defineProps({
    found_email: String,
    student_name: String,
});

const form = useForm({
    nisn: '',
    birth_date: ''
});

const submit = () => form.post(route('email.forgot.submit'));
</script>
