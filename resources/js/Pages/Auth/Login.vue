<template>
    <Head :title="'Login - ' + ($page.props.app?.settings?.school_name || 'Portal SMA')" />

    <div class="flex align-items-center justify-content-center min-h-screen surface-ground p-3">
        <div class="surface-card p-4 shadow-2 border-round w-full lg:w-4">
            <div class="text-center mb-4">
                <img v-if="$page.props.app?.settings?.site_logo" :src="'/storage/' + $page.props.app.settings.site_logo" class="h-4rem w-auto mx-auto mb-3" alt="Logo" />
                <div v-else class="flex justify-content-center mb-3">
                    <i class="pi pi-graduation-cap text-primary text-5xl"></i>
                </div>
                <div class="text-900 text-3xl font-medium mb-1">{{ $page.props.app?.settings?.school_name || 'Portal SMA' }}</div>
                <div class="text-700 font-bold mb-2">Tahun Ajaran {{ academic_year || $page.props.app?.academic_year_name }}</div>
                <span class="text-600 font-medium line-height-3">Masuk ke akun Anda</span>
            </div>

            <!-- Staging Banner & Changelog Button -->
            <Message v-if="$page.props.app?.is_staging" severity="info" :closable="false" class="mb-3">
                <div class="flex flex-column sm:flex-row align-items-start sm:align-items-center justify-content-between gap-2">
                    <div class="text-xs">
                        <strong class="text-blue-700">🚧 Server Uji Coba (Staging):</strong> Ini lingkungan pengujian.
                    </div>
                    <div class="flex align-items-center gap-2">
                        <Button 
                            label="📋 Changelog" 
                            size="small" 
                            severity="info" 
                            outlined 
                            class="p-button-xs text-xs font-bold"
                            @click="showChangelogModal = true"
                        />
                        <a v-if="$page.props.app?.production_url" :href="$page.props.app.production_url" class="font-bold text-xs text-primary underline" target="_blank">Situs Utama ↗</a>
                    </div>
                </div>
            </Message>

            <Message v-if="!$page.props.app?.settings?.site_active" severity="warn" :closable="false" class="mb-3">
                <strong>Mode Pemeliharaan (Non-Aktif):</strong> Sistem sedang dalam perbaikan/pemeliharaan. Hanya Administrator yang dapat masuk.
            </Message>
            <Message v-if="form.errors.email" severity="error" class="mb-3" :closable="false">
                {{ form.errors.email }}
            </Message>
            <Message v-if="$page.props.flash?.error" severity="error" class="mb-3">
                {{ $page.props.flash.error }}
            </Message>
            <Message v-if="$page.props.flash?.success" severity="success" class="mb-3">
                {{ $page.props.flash.success }}
            </Message>

            <form @submit.prevent="submit" class="mb-4">
                <label for="email" class="block text-900 font-medium mb-2">Email</label>
                <InputText 
                    id="email" 
                    v-model="form.email" 
                    type="email" 
                    class="w-full mb-3" 
                    autocomplete="username"
                    :class="{'p-invalid': form.errors.email}" 
                    placeholder="Masukkan email terdaftar" />

                <label for="password" class="block text-900 font-medium mb-2">Password</label>
                <Password 
                    inputId="password" 
                    v-model="form.password" 
                    :toggleMask="true" 
                    :feedback="false" 
                    class="w-full mb-3" 
                    inputClass="w-full p-3"
                    placeholder="Masukkan password"
                    :inputProps="{ name: 'password',autocomplete: 'current-password' }"
                />
                <div class="flex justify-content-between mb-4">
                    <Link :href="route('email.forgot.form')" class="text-sm font-medium no-underline text-primary cursor-pointer hover:underline">
                        Lupa Email?
                    </Link>
                    <Link :href="route('password.verify.form')" class="text-sm font-medium no-underline text-primary cursor-pointer hover:underline">
                        Lupa Password?
                    </Link>
                </div>

                <Button type="submit" label="Sign In" icon="pi pi-user" class="w-full" :loading="form.processing" />
            </form>
            
            <div class="border-top-1 surface-border pt-3 text-center" v-if="!$page.props.app?.settings?.lock_daftar_ulang_login">
                <p class="text-600 text-sm mb-2">Calon Murid Baru (Daftar Ulang)?</p>
                <Link href="/daftar-ulang/login" class="no-underline">
                    <Button label="Login Calon Murid Baru" icon="pi pi-user-plus" severity="success" outlined class="w-full" />
                </Link>
            </div>
            
            <div class="text-center mt-3 flex flex-column align-items-center gap-1">
                <small class="text-xs text-gray-500">
                    v{{ $page.props.app?.version }}
                    <span v-if="$page.props.app?.build">• build {{ $page.props.app.build }}</span>
                </small>

                <Button 
                    v-if="$page.props.app?.is_staging"
                    label="📋 Lihat Catatan Rilis & Changelog (Staging)" 
                    text 
                    severity="help" 
                    size="small"
                    class="text-xs p-1"
                    @click="showChangelogModal = true"
                />
            </div>
        </div>
    </div>

    <!-- MODAL DIALOG CHANGELOG STAGING -->
    <Dialog 
        v-model:visible="showChangelogModal" 
        header="📋 Catatan Rilis & Changelog (Mode Staging)" 
        modal 
        style="width: 90vw; max-width: 680px;"
    >
        <div class="p-2 flex flex-column gap-3">
            <div class="p-3 surface-100 border-round border surface-border flex align-items-center justify-content-between">
                <div>
                    <span class="font-bold text-sm text-900 block">Sistem Portal SMA - Staging Release</span>
                    <small class="text-600">Catatan pembaruan fitur versi terbaru yang aktif di server pengujian.</small>
                </div>
                <Tag :value="`v${$page.props.app?.version || '2.8.1'}`" severity="info" class="font-bold text-xs px-3 py-1" />
            </div>

            <div v-if="!$page.props.app?.changelog || $page.props.app.changelog.length === 0" class="p-4 text-center text-500 text-xs">
                Belum ada catatan changelog.
            </div>

            <div v-else class="max-h-25rem overflow-y-auto flex flex-column gap-3 pr-2">
                <div 
                    v-for="(rel, idx) in $page.props.app.changelog" 
                    :key="idx"
                    class="p-3 border-round surface-card border surface-border"
                >
                    <div class="flex justify-content-between align-items-center mb-2 pb-2 border-bottom-1 surface-border">
                        <div class="flex align-items-center gap-2">
                            <Tag :value="`v${rel.version}`" severity="primary" class="text-2xs" />
                            <span class="font-bold text-900 text-sm">Versi {{ rel.version }}</span>
                        </div>
                        <small class="text-500 text-2xs">{{ rel.date }}</small>
                    </div>

                    <ul class="m-0 pl-3 text-xs text-700 list-none flex flex-column gap-1" v-html="formatChangelogContent(rel.content)"></ul>
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Tutup" icon="pi pi-check" severity="secondary" @click="showChangelogModal = false" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';

const props = defineProps({
    academic_year: String
});

const showChangelogModal = ref(false);

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    setTimeout(() => {
        form.post('/login', {
            onFinish: () => form.reset('password'),
        });
    }, 0);
};

const formatChangelogContent = (content) => {
    if (!content) return '';
    return content
        .replace(/### (Added|Changed|Fixed|Removed|Security)/g, '<li class="font-bold text-xs text-primary mt-2 mb-1">▶ $1:</li>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/- (.*)/g, '<li class="ml-3 mb-1 text-xs text-700">• $1</li>');
};
</script>