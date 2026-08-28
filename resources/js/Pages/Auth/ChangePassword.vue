<script setup>
import { useForm, router } from '@inertiajs/vue3' // Tambahkan router di sini
import Button from 'primevue/button'
import Password from 'primevue/password'
import Message from 'primevue/message'
import Divider from 'primevue/divider'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onFinish: () => {
            form.reset();
        },
        onError: () => {
            // Fokus kembali ke input jika ada error (opsional)
            form.reset('password', 'password_confirmation');
        }
    })
}
const logout = () => {
    // Pilihan A: Balik ke halaman sebelumnya
    router.post(route('logout'));
    
    // Pilihan B: Paksa balik ke Dashboard (jika ingin lebih pasti)
    // router.get(route('dashboard'));
}
</script>

<template>
    <div class="min-h-screen flex align-items-center justify-content-center bg-bluegray-50 p-3">
        <div class="surface-card p-5 shadow-8 border-round-xl w-full md:w-30rem">
            <div class="text-center mb-5">
                <div class="bg-blue-500 border-circle inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px">
                    <i class="pi pi-lock text-3xl text-white"></i>
                </div>
                <div class="text-900 text-2xl font-medium mb-2">Keamanan Akun</div>
                <span class="text-600 font-medium line-height-3">Silakan perbarui password Anda untuk keamanan data portal SMA.</span>
            </div>

            <Message severity="warn" :closable="false" class="mb-4">
                Wajib ganti password pada login pertama kali atau secara berkala.
            </Message>

            <form @submit.prevent="submit" class="flex flex-column gap-3">
                <div class="flex flex-column gap-2">
                    <label for="current_password" class="font-semibold">Password Saat Ini</label>
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-shield z-2" />
                        <Password 
                            id="current_password"
                            v-model="form.current_password" 
                            toggleMask 
                            :feedback="false"
                            placeholder="Masukkan password lama"
                            class="w-full"
                            inputClass="w-full p-3 pl-5"
                            :class="{ 'p-invalid': form.errors.current_password }"
                        />
                    </IconField>
                    <small class="p-error">{{ form.errors.current_password }}</small>
                </div>

                <Divider />

                <div class="flex flex-column gap-2">
                    <label for="password" class="font-semibold">Password Baru</label>
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-key z-2" />
                        <Password 
                            id="password"
                            v-model="form.password" 
                            toggleMask 
                            placeholder="Masukkan password baru"
                            class="w-full"
                            inputClass="w-full p-3 pl-5"
                            :class="{ 'p-invalid': form.errors.password }"
                        >
                            <template #header>
                                <h6 class="mt-0">Pilih password yang kuat</h6>
                            </template>
                            <template #footer>
                                <Divider />
                                <p class="mt-2">Saran:</p>
                                <ul class="pl-2 ml-2 mt-0" style="line-height: 1.5">
                                    <li>Minimal satu huruf kecil</li>
                                    <li>Minimal satu huruf besar</li>
                                    <li>Minimal satu angka</li>
                                    <li>Minimal 8 karakter</li>
                                </ul>
                            </template>
                        </Password>
                    </IconField>
                    <small class="p-error">{{ form.errors.password }}</small>
                </div>

                <div class="flex flex-column gap-2">
                    <label for="password_confirmation" class="font-semibold">Konfirmasi Password Baru</label>
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-check-circle z-2" />
                        <Password 
                            id="password_confirmation"
                            v-model="form.password_confirmation" 
                            toggleMask 
                            :feedback="false"
                            placeholder="Ulangi password baru"
                            class="w-full"
                            inputClass="w-full p-3 pl-5"
                            :class="{ 'p-invalid': form.errors.password_confirmation }"
                        />
                    </IconField>
                    <small class="p-error">{{ form.errors.password_confirmation }}</small>
                </div>

                <div class="flex gap-3 mt-4">
                    <Button 
                        type="button" 
                        label="Keluar Sesi" 
                        icon="pi pi-power-off" 
                        class="flex-1 p-3 font-bold"
                        severity="danger"
                        text
                        @click="logout"
                    />
                    
                    <Button 
                        type="submit" 
                        label="Perbarui Password" 
                        icon="pi pi-save" 
                        class="flex-1 p-3 font-bold shadow-2"
                        :loading="form.processing"
                    />
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
/* Menyesuaikan z-index ikon agar tidak tertutup overlay password */
</style>