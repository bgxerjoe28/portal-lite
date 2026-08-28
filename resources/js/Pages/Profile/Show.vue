<template>
    <MobileLayout title="Profil Pengguna">
        <div class="p-3">
            <!-- Profile Main Card -->
            <div class="surface-card p-4 border-round-xl shadow-2 mb-4 flex flex-column align-items-center relative overflow-hidden bg-gradient-to-b from-blue-50 to-white">
                <!-- Avatar Upload Section -->
                <div class="relative cursor-pointer mb-3 group" @click="triggerFileInput">
                    <div class="w-7rem h-7rem border-circle overflow-hidden shadow-3 border-2 border-primary flex align-items-center justify-content-center bg-slate-100">
                        <img v-if="avatarPreview || profileUser.avatar_url" :src="avatarPreview || profileUser.avatar_url" class="w-full h-full object-cover" alt="Foto Profil" />
                        <div v-else class="flex flex-column align-items-center text-400">
                            <!-- Fallback default svg avatars -->
                            <i v-if="form.gender === true" class="pi pi-user text-5xl text-blue-500"></i>
                            <i v-else-if="form.gender === false" class="pi pi-user text-5xl text-pink-500"></i>
                            <i v-else class="pi pi-user text-5xl"></i>
                        </div>
                    </div>
                    <!-- Camera Edit Overlay -->
                    <div class="absolute bottom-0 right-0 bg-primary text-white border-circle w-2rem h-2rem flex align-items-center justify-content-center shadow-2 group-hover:scale-110 transition-transform">
                        <i class="pi pi-camera text-sm"></i>
                    </div>
                </div>

                <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="onFileChange" />

                <!-- User Name & Role Header -->
                <h3 class="text-xl font-bold m-0 text-900 text-center">{{ profileUser.name }}</h3>
                <span class="text-xs font-bold text-600 bg-blue-100 text-blue-700 py-1 px-3 border-round-lg mt-2 uppercase tracking-wider">
                    {{ profileUser.role }}
                </span>
            </div>

            <!-- Profile Info Form -->
            <div class="surface-card p-4 border-round-xl shadow-2">
                <form @submit.prevent="submitProfile">
                    <div class="flex flex-column gap-4">
                        <!-- Name Field (Disabled) -->
                        <div class="flex flex-column gap-2">
                            <label class="font-semibold text-900 text-sm">Nama Lengkap</label>
                            <InputText :value="profileUser.name" class="w-full bg-slate-50 text-500 border-200" disabled />
                            <small class="text-500 text-xs">Nama lengkap disinkronkan dari database sekolah.</small>
                        </div>

                        <!-- Email Field (Disabled) -->
                        <div class="flex flex-column gap-2">
                            <label class="font-semibold text-900 text-sm">Alamat Email</label>
                            <InputText :value="profileUser.email" class="w-full bg-slate-50 text-500 border-200" disabled />
                        </div>

                        <!-- Role Field (Disabled) -->
                        <div class="flex flex-column gap-2">
                            <label class="font-semibold text-900 text-sm">Peran Pengguna (Role)</label>
                            <InputText :value="profileUser.role" class="w-full bg-slate-50 text-500 border-200" disabled />
                        </div>

                        <!-- Gender Field (Disabled for teachers, editable for staff/others) -->
                        <div class="flex flex-column gap-2">
                            <label class="font-semibold text-900 text-sm">Jenis Kelamin</label>
                            
                            <!-- If teacher: Readonly/Disabled -->
                            <div v-if="profileUser.is_teacher" class="flex gap-4 p-3 bg-slate-50 border-round border border-200">
                                <div class="flex align-items-center gap-2">
                                    <RadioButton :modelValue="profileUser.gender" :value="true" disabled />
                                    <span class="text-700 font-medium text-sm">Laki-laki</span>
                                </div>
                                <div class="flex align-items-center gap-2">
                                    <RadioButton :modelValue="profileUser.gender" :value="false" disabled />
                                    <span class="text-700 font-medium text-sm">Perempuan</span>
                                </div>
                            </div>

                            <!-- If staff/pegawai/others: Editable -->
                            <div v-else class="flex gap-4 p-3 border-round border border-300">
                                <div class="flex align-items-center gap-2 cursor-pointer" @click="form.gender = true">
                                    <RadioButton v-model="form.gender" :value="true" />
                                    <span class="text-900 font-medium text-sm">Laki-laki</span>
                                </div>
                                <div class="flex align-items-center gap-2 cursor-pointer" @click="form.gender = false">
                                    <RadioButton v-model="form.gender" :value="false" />
                                    <span class="text-900 font-medium text-sm">Perempuan</span>
                                </div>
                            </div>
                            <small class="text-500 text-xs" v-if="profileUser.is_teacher">
                                Jenis kelamin otomatis diambil dari data induk Guru Anda.
                            </small>
                            <small class="text-500 text-xs" v-else>
                                Silakan lengkapi jenis kelamin Anda untuk melengkapi profil staf.
                            </small>
                        </div>

                        <!-- Save Button -->
                        <div class="mt-2">
                            <Button 
                                type="submit" 
                                label="SIMPAN PERUBAHAN" 
                                icon="pi pi-check" 
                                class="w-full py-3 font-bold border-round-lg shadow-2"
                                :loading="isSaving"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import Button from 'primevue/button';

const props = defineProps({
    profileUser: Object
});

const fileInput = ref(null);
const avatarPreview = ref(null);
const isSaving = ref(false);

const form = useForm({
    _method: 'put', // Method spoofing to handle Laravel multipart file uploads via PUT
    gender: props.profileUser.gender,
    avatar: null
});

const triggerFileInput = () => {
    fileInput.value.click();
};

const onFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.avatar = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const submitProfile = () => {
    isSaving.value = true;
    form.post('/profile', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isSaving.value = false;
            avatarPreview.value = null; // Reset local preview as it is now saved in backend
        },
        onError: () => {
            isSaving.value = false;
        }
    });
};
</script>

<style scoped>
.object-cover {
    object-fit: cover;
}
</style>
