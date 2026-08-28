<template>
    <AppLayout>
        <Head title="Pengaturan Situs" />
        <div class="card">
            <div class="flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Pengaturan Situs</h2>
                    <span class="text-500 block mt-1">Ubah konfigurasi umum identitas sekolah, logo, dan favicon situs.</span>
                </div>
            </div>

            <div class="surface-card p-4 shadow-2 border-round">
                <!-- Banner jika berada di server Staging -->
                <div v-if="isStaging" class="surface-ground border border-primary border-round p-4 mb-4 flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between gap-3">
                    <div class="flex align-items-center gap-3">
                        <div class="border-circle bg-primary-100 text-primary p-3 flex align-items-center justify-content-center text-xl">
                            <i class="pi pi-info-circle"></i>
                        </div>
                        <div>
                            <div class="flex align-items-center gap-2 mb-1">
                                <span class="font-bold text-900 text-lg">Lingkungan Server Uji Coba (Staging)</span>
                                <Tag value="STAGING" severity="warn"></Tag>
                            </div>
                            <span class="text-600 block text-sm">
                                Anda sedang mengakses server uji coba/staging. Perubahan data di sini tidak mempengaruhi sistem Production.
                            </span>
                        </div>
                    </div>
                    <a :href="productionUrl" target="_blank" class="p-button p-button-outlined p-button-primary no-underline flex align-items-center gap-2 white-space-nowrap font-bold">
                        <i class="pi pi-external-link"></i>
                        <span>Buka Situs Production ↗</span>
                    </a>
                </div>

                <form @submit.prevent="submitForm">
                    
<Tabs value="0" class="w-full">
    <TabList>
        <Tab value="0"><div class="flex align-items-center gap-2">
            <i class="pi pi-home text-primary"></i>
            <span class="font-bold">Profil Sekolah</span>
        </div></Tab>
        <Tab value="1"><div class="flex align-items-center gap-2">
            <i class="pi pi-image text-primary"></i>
            <span class="font-bold">Logo</span>
        </div></Tab>
        <Tab value="4"><div class="flex align-items-center gap-2">
            <i class="pi pi-file text-primary"></i>
            <span class="font-bold">Kop Sekolah</span>
        </div></Tab>
        <Tab value="2"><div class="flex align-items-center gap-2">
            <i class="pi pi-calendar-clock text-primary"></i>
            <span class="font-bold">Presensi</span>
        </div></Tab>
        <Tab value="3"><div class="flex align-items-center gap-2">
            <i class="pi pi-shield text-primary"></i>
            <span class="font-bold">Status & Akses Situs</span>
        </div></Tab>
    </TabList>
    <TabPanels>

                        
                        <!-- TAB 1: PROFIL SEKOLAH -->
                        <TabPanel value="0">
                            
                            
                            <div class="formgrid grid mt-3">
                                <div class="field col-12 mb-4">
                                    <label for="school_name" class="font-bold mb-2 block">Nama Sekolah <span class="text-red-500">*</span></label>
                                    <InputText id="school_name" v-model="form.school_name" class="w-full" :class="{'p-invalid': form.errors.school_name}" required />
                                    <small class="p-error block mt-1" v-if="form.errors.school_name">{{ form.errors.school_name }}</small>
                                </div>

                                <div class="field col-12 mb-4">
                                    <label for="school_address" class="font-bold mb-2 block">Alamat</label>
                                    <Textarea id="school_address" v-model="form.school_address" rows="3" class="w-full" :class="{'p-invalid': form.errors.school_address}" autoResize />
                                    <small class="p-error block mt-1" v-if="form.errors.school_address">{{ form.errors.school_address }}</small>
                                </div>

                                <div class="field col-12 md:col-6 mb-4">
                                    <label for="school_city" class="font-bold mb-2 block">Kota/Kabupaten</label>
                                    <InputText id="school_city" v-model="form.school_city" class="w-full" :class="{'p-invalid': form.errors.school_city}" placeholder="Contoh: Semarang" />
                                    <small class="p-error block mt-1" v-if="form.errors.school_city">{{ form.errors.school_city }}</small>
                                </div>

                                <div class="field col-12 md:col-6 mb-4">
                                    <label for="school_province" class="font-bold mb-2 block">Provinsi</label>
                                    <InputText id="school_province" v-model="form.school_province" class="w-full" :class="{'p-invalid': form.errors.school_province}" placeholder="Contoh: Jawa Tengah" />
                                    <small class="p-error block mt-1" v-if="form.errors.school_province">{{ form.errors.school_province }}</small>
                                </div>

                                <div class="field col-12 md:col-6 mb-4">
                                    <label for="school_postal_code" class="font-bold mb-2 block">Kode Pos</label>
                                    <InputText id="school_postal_code" v-model="form.school_postal_code" class="w-full" :class="{'p-invalid': form.errors.school_postal_code}" placeholder="Contoh: 50213" />
                                    <small class="p-error block mt-1" v-if="form.errors.school_postal_code">{{ form.errors.school_postal_code }}</small>
                                </div>

                                <div class="field col-12 md:col-6 mb-4">
                                    <label for="school_phone" class="font-bold mb-2 block">Telepon</label>
                                    <InputText id="school_phone" v-model="form.school_phone" class="w-full" :class="{'p-invalid': form.errors.school_phone}" />
                                    <small class="p-error block mt-1" v-if="form.errors.school_phone">{{ form.errors.school_phone }}</small>
                                </div>

                                <div class="field col-12 md:col-6 mb-4">
                                    <label for="school_website" class="font-bold mb-2 block">Laman</label>
                                    <InputText id="school_website" v-model="form.school_website" class="w-full" :class="{'p-invalid': form.errors.school_website}" placeholder="Contoh: sman16smg.sch.id" />
                                    <small class="p-error block mt-1" v-if="form.errors.school_website">{{ form.errors.school_website }}</small>
                                </div>

                                <div class="field col-12 md:col-6 mb-4">
                                    <label for="school_email" class="font-bold mb-2 block">Pos-el</label>
                                    <InputText id="school_email" type="email" v-model="form.school_email" class="w-full" :class="{'p-invalid': form.errors.school_email}" placeholder="Contoh: sman16smg@gmail.com" />
                                    <small class="p-error block mt-1" v-if="form.errors.school_email">{{ form.errors.school_email }}</small>
                                </div>
                                
                                <div class="col-12 mt-2">
                                    <h4 class="m-0 mb-3 text-900 font-bold border-top-1 border-200 pt-4">Kepala Sekolah</h4>
                                </div>
                                <div class="field col-12 md:col-6 mb-4">
                                    <label for="principal_name" class="font-bold mb-2 block">Nama Kepala Sekolah</label>
                                    <InputText id="principal_name" v-model="form.principal_name" class="w-full" :class="{'p-invalid': form.errors.principal_name}" />
                                    <small class="p-error block mt-1" v-if="form.errors.principal_name">{{ form.errors.principal_name }}</small>
                                </div>

                                <div class="field col-12 md:col-6 mb-4">
                                    <label for="principal_nip" class="font-bold mb-2 block">NIP Kepala Sekolah</label>
                                    <InputText id="principal_nip" v-model="form.principal_nip" class="w-full" :class="{'p-invalid': form.errors.principal_nip}" />
                                    <small class="p-error block mt-1" v-if="form.errors.principal_nip">{{ form.errors.principal_nip }}</small>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB 2: LOGO & FAVICON -->
                        <TabPanel value="1">
                            

                            <div class="grid mt-3">
                                <div class="col-12 md:col-4">
                                    <div class="surface-border border border-round p-4 flex flex-column align-items-center h-full">
                                        <h4 class="font-bold mt-0 mb-3 text-900">Logo Sekolah</h4>
                                        
                                        <!-- Preview -->
                                        <div class="w-12rem h-12rem border-round surface-100 flex align-items-center justify-content-center overflow-hidden mb-3 border border-300">
                                            <img v-if="logoPreview" :src="logoPreview" class="max-w-full max-h-full object-contain" alt="Preview Logo" />
                                            <img v-else-if="settings.site_logo" :src="'/storage/' + settings.site_logo" class="max-w-full max-h-full object-contain" alt="Current Logo" />
                                            <i v-else class="pi pi-image text-5xl text-300"></i>
                                        </div>

                                        <div class="text-center text-500 text-sm mb-3">
                                            Format: JPG, JPEG, PNG, SVG (Maks. 2MB)
                                        </div>

                                        <input type="file" ref="logoInput" @change="handleLogoChange" accept="image/*" class="hidden" />
                                        <div class="flex gap-2">
                                            <Button label="Pilih Logo" icon="pi pi-upload" size="small" @click="$refs.logoInput.click()" />
                                            <Button v-if="logoPreview" label="Batal" severity="secondary" size="small" outlined @click="clearLogo" />
                                        </div>
                                        <small class="p-error block mt-2" v-if="form.errors.site_logo">{{ form.errors.site_logo }}</small>
                                    </div>
                                </div>

                                <div class="col-12 md:col-4">
                                    <div class="surface-border border border-round p-4 flex flex-column align-items-center h-full">
                                        <h4 class="font-bold mt-0 mb-3 text-900">Favicon Situs</h4>
                                        
                                        <!-- Preview -->
                                        <div class="w-6rem h-6rem border-round surface-100 flex align-items-center justify-content-center overflow-hidden mb-3 border border-300">
                                            <img v-if="faviconPreview" :src="faviconPreview" class="w-2rem h-2rem object-contain" alt="Preview Favicon" />
                                            <img v-else-if="settings.site_favicon" :src="'/storage/' + settings.site_favicon" class="w-2rem h-2rem object-contain" alt="Current Favicon" />
                                            <i v-else class="pi pi-desktop text-4xl text-300"></i>
                                        </div>

                                        <div class="text-center text-500 text-sm mb-3">
                                            Format: ICO, PNG, JPG (Maks. 512KB)
                                        </div>

                                        <input type="file" ref="faviconInput" @change="handleFaviconChange" accept=".ico,image/png,image/x-icon" class="hidden" />
                                        <div class="flex gap-2">
                                            <Button label="Pilih Favicon" icon="pi pi-upload" size="small" @click="$refs.faviconInput.click()" />
                                            <Button v-if="faviconPreview" label="Batal" severity="secondary" size="small" outlined @click="clearFavicon" />
                                        </div>
                                        <small class="p-error block mt-2" v-if="form.errors.site_favicon">{{ form.errors.site_favicon }}</small>
                                    </div>
                                </div>
                                <!-- UPLOAD LOGO PEMDA -->
                                <div class="col-12 md:col-4">
                                    <div class="surface-border border border-round p-4 flex flex-column align-items-center h-full">
                                        <h4 class="font-bold mt-0 mb-3 text-900">Logo Pemda</h4>
                                        
                                        <!-- Preview -->
                                        <div class="w-12rem h-12rem border-round surface-100 flex align-items-center justify-content-center overflow-hidden mb-3 border border-300">
                                            <img v-if="logoPemdaPreview" :src="logoPemdaPreview" class="max-w-full max-h-full object-contain" alt="Preview Logo Pemda" />
                                            <img v-else-if="settings.site_logo_pemda" :src="'/storage/' + settings.site_logo_pemda" class="max-w-full max-h-full object-contain" alt="Current Logo Pemda" />
                                            <i v-else class="pi pi-image text-5xl text-300"></i>
                                        </div>

                                        <div class="text-center text-500 text-sm mb-3">
                                            Format: JPG, JPEG, PNG, SVG (Maks. 2MB)
                                        </div>

                                        <input type="file" ref="logoPemdaInput" @change="handleLogoPemdaChange" accept="image/*" class="hidden" />
                                        <div class="flex gap-2">
                                            <Button label="Pilih Logo Pemda" icon="pi pi-upload" size="small" @click="$refs.logoPemdaInput.click()" />
                                            <Button v-if="logoPemdaPreview" label="Batal" severity="secondary" size="small" outlined @click="clearLogoPemda" />
                                        </div>
                                        <small class="p-error block mt-2" v-if="form.errors.site_logo_pemda">{{ form.errors.site_logo_pemda }}</small>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB KOP SEKOLAH -->
                        <TabPanel value="4">
                            <div class="formgrid grid mt-3">
                                <div class="col-12 mb-4">
                                    <h4 class="m-0 mb-3 text-900 font-bold">Kop Utama Sekolah</h4>
                                    <p class="text-600 text-sm mt-0 mb-4">
                                        Data ini akan digunakan sebagai teks utama pada Kop Surat di seluruh dokumen laporan (seperti Laporan Ekstrakurikuler).
                                    </p>
                                </div>
                                <div class="field col-12 mb-4">
                                    <label for="kop_pemprov" class="font-bold mb-2 block">Baris 1 Kop (Contoh: PEMERINTAH PROVINSI JAWA TENGAH)</label>
                                    <InputText id="kop_pemprov" v-model="form.kop_pemprov" class="w-full" :class="{'p-invalid': form.errors.kop_pemprov}" />
                                    <small class="p-error block mt-1" v-if="form.errors.kop_pemprov">{{ form.errors.kop_pemprov }}</small>
                                </div>

                                <div class="field col-12 mb-4">
                                    <label for="kop_dinas" class="font-bold mb-2 block">Baris 2 Kop (Contoh: DINAS PENDIDIKAN)</label>
                                    <InputText id="kop_dinas" v-model="form.kop_dinas" class="w-full" :class="{'p-invalid': form.errors.kop_dinas}" />
                                    <small class="p-error block mt-1" v-if="form.errors.kop_dinas">{{ form.errors.kop_dinas }}</small>
                                </div>
                                
                                <div class="col-12 mt-3 p-4 surface-100 border-round">
                                    <h5 class="m-0 mb-3 text-700 font-bold">Preview Kop Surat</h5>
                                    <div class="flex align-items-center justify-content-between border-bottom-1 border-600 pb-3" style="border-bottom-width: 3px !important; border-bottom-style: double !important;">
                                        
                                        <!-- Logo Kiri -->
                                        <div class="w-6rem h-6rem flex flex-shrink-0 align-items-center justify-content-center">
                                            <img v-if="logoPemdaPreview" :src="logoPemdaPreview" class="max-w-full max-h-full object-contain" />
                                            <img v-else-if="settings.site_logo_pemda" :src="'/storage/' + settings.site_logo_pemda" class="max-w-full max-h-full object-contain" />
                                            <i v-else class="pi pi-image text-3xl text-400"></i>
                                        </div>

                                        <!-- Teks Tengah -->
                                        <div class="text-center flex-1 px-3" style="line-height: 1.2; font-family: Tahoma, sans-serif;">
                                            <div style="font-size: 14pt; font-weight: normal; white-space: nowrap;">{{ form.kop_pemprov || 'PEMERINTAH PROVINSI JAWA TENGAH' }}</div>
                                            <div style="font-size: 14pt; font-weight: bold; white-space: nowrap;">{{ form.kop_dinas || 'DINAS PENDIDIKAN' }}</div>
                                            <div style="font-size: 16pt; font-weight: bold; white-space: nowrap;">{{ form.school_name || 'NAMA SEKOLAH' }}</div>
                                            <div style="font-size: 9pt; font-weight: normal; margin-top: 5px;">
                                                {{ form.school_address }} {{ form.school_city }} {{ form.school_province }} {{ form.school_postal_code ? 'Kode Pos ' + form.school_postal_code : '' }}<br>
                                                Telepon {{ form.school_phone }} Laman {{ form.school_website }}<br>
                                                Pos-el {{ form.school_email }}
                                            </div>
                                        </div>

                                        <!-- Logo Kanan -->
                                        <div class="w-6rem h-6rem flex flex-shrink-0 align-items-center justify-content-center">
                                            <img v-if="logoPreview" :src="logoPreview" class="max-w-full max-h-full object-contain" />
                                            <img v-else-if="settings.site_logo" :src="'/storage/' + settings.site_logo" class="max-w-full max-h-full object-contain" />
                                            <i v-else class="pi pi-image text-3xl text-400"></i>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB 3: PRESENSI -->
                        <TabPanel value="2">
                            

                            <div class="formgrid grid mt-3">
                                <div class="col-12 mb-4">
                                    <h4 class="m-0 mb-3 text-900 font-bold">Lokasi Presensi</h4>
                                </div>
                                <div class="field col-12 md:col-4 mb-4">
                                    <label for="school_latitude" class="font-bold mb-2 block">Latitude</label>
                                    <InputText id="school_latitude" v-model="form.school_latitude" class="w-full" :class="{'p-invalid': form.errors.school_latitude}" @input="updateMap" />
                                    <small class="p-error block mt-1" v-if="form.errors.school_latitude">{{ form.errors.school_latitude }}</small>
                                </div>

                                <div class="field col-12 md:col-4 mb-4">
                                    <label for="school_longitude" class="font-bold mb-2 block">Longitude</label>
                                    <InputText id="school_longitude" v-model="form.school_longitude" class="w-full" :class="{'p-invalid': form.errors.school_longitude}" @input="updateMap" />
                                    <small class="p-error block mt-1" v-if="form.errors.school_longitude">{{ form.errors.school_longitude }}</small>
                                </div>

                                <div class="field col-12 md:col-4 mb-4">
                                    <label for="school_radius" class="font-bold mb-2 block">Radius (Meter)</label>
                                    <InputText id="school_radius" v-model="form.school_radius" type="number" class="w-full" :class="{'p-invalid': form.errors.school_radius}" @input="updateMap" />
                                    <small class="p-error block mt-1" v-if="form.errors.school_radius">{{ form.errors.school_radius }}</small>
                                </div>
                                
                                <div class="field col-12 mb-4">
                                    <div id="map" class="w-full border-round" style="height: 400px; z-index: 1;"></div>
                                    <small class="text-500 mt-2 block">Geser marker (ikon pin) atau klik pada peta untuk menentukan titik koordinat sekolah.</small>
                                </div>
                                <div class="col-12 mb-4 mt-2">
                                    <h4 class="m-0 mb-3 text-900 font-bold border-top-1 border-200 pt-4">Jadwal Otomatis Absensi</h4>
                                    <p class="text-600 text-sm mt-0 mb-4">
                                        Setiap hari sekolah, sistem akan otomatis membuat record <strong>Tanpa Keterangan (A)</strong>
                                        untuk semua siswa aktif pada jam yang ditentukan. Status berubah saat siswa presensi.
                                    </p>
                                    <div class="field col-12 md:col-4">
                                        <label for="attendance_seed_time" class="font-bold mb-2 block">
                                            Jam Seed Absensi
                                            <i class="pi pi-info-circle text-400 ml-1" v-tooltip="'Jam sistem membuat record Tanpa Keterangan otomatis. Disarankan 03:00–04:00 (sebelum siswa pagi)'">
                                            </i>
                                        </label>
                                        <InputText
                                            id="attendance_seed_time"
                                            v-model="form.attendance_seed_time"
                                            type="time"
                                            class="w-full"
                                            :class="{'p-invalid': form.errors.attendance_seed_time}"
                                        />
                                        <small class="p-error block mt-1" v-if="form.errors.attendance_seed_time">
                                            {{ form.errors.attendance_seed_time }}
                                        </small>
                                        <small class="text-500 block mt-1">Format 24 jam. Perubahan efektif mulai besok.</small>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <h4 class="m-0 mb-1 text-900 font-bold">Window Waktu Presensi</h4>
                                    <p class="text-600 text-sm mt-0 mb-4">
                                        Tentukan berapa menit sebelum/sesudah jam masuk & pulang siswa diizinkan presensi.
                                    </p>
                                    <div class="grid">
                                        <div class="field col-12 md:col-3 mb-4">
                                            <label class="font-bold mb-2 block">Buka Masuk (menit sebelum)</label>
                                            <InputText
                                                id="checkin_window_before"
                                                v-model="form.checkin_window_before"
                                                type="number" min="0" max="480"
                                                class="w-full"
                                                :class="{'p-invalid': form.errors.checkin_window_before}"
                                            />
                                            <small class="text-500 block mt-1">Default: 60 menit</small>
                                        </div>
                                        <div class="field col-12 md:col-3 mb-4">
                                            <label class="font-bold mb-2 block">Tutup Masuk (menit sesudah)</label>
                                            <InputText
                                                id="checkin_window_after"
                                                v-model="form.checkin_window_after"
                                                type="number" min="0" max="480"
                                                class="w-full"
                                                :class="{'p-invalid': form.errors.checkin_window_after}"
                                            />
                                            <small class="text-500 block mt-1">Default: 120 menit</small>
                                        </div>
                                        <div class="field col-12 md:col-3 mb-4">
                                            <label class="font-bold mb-2 block">Buka Pulang (menit sebelum)</label>
                                            <InputText
                                                id="checkout_window_before"
                                                v-model="form.checkout_window_before"
                                                type="number" min="0" max="480"
                                                class="w-full"
                                                :class="{'p-invalid': form.errors.checkout_window_before}"
                                            />
                                            <small class="text-500 block mt-1">Default: 60 menit</small>
                                        </div>
                                        <div class="field col-12 md:col-3 mb-4">
                                            <label class="font-bold mb-2 block">Tutup Pulang (menit sesudah)</label>
                                            <InputText
                                                id="checkout_window_after"
                                                v-model="form.checkout_window_after"
                                                type="number" min="0" max="480"
                                                class="w-full"
                                                :class="{'p-invalid': form.errors.checkout_window_after}"
                                            />
                                            <small class="text-500 block mt-1">Default: 240 menit</small>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-blue-50 border-round border border-blue-200 text-sm text-blue-800">
                                        <i class="pi pi-clock mr-2"></i>
                                        <strong>Contoh:</strong> Jika jam masuk 07:00, Buka Masuk 60 mnt &amp; Tutup Masuk 120 mnt →
                                        siswa bisa presensi masuk antara <strong>06:00 – 09:00</strong>.
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB 4: STATUS & AKSES SITUS -->
                        <TabPanel value="3">
                            <div class="formgrid grid mt-3">
                                <div class="field col-12 mb-4">
                                    <div class="surface-border border border-round p-4 flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between gap-3">
                                        <div>
                                            <label class="font-bold text-900 block mb-1 text-lg">Status Aktif Situs (Maintenance Mode)</label>
                                            <span class="text-600 text-sm block line-height-3">
                                                Jika dinonaktifkan, situs akan masuk ke mode pemeliharaan (Maintenance Mode). Hanya akun Administrator yang dapat login dan mengakses situs ini. Siswa dan Guru akan dilarang masuk sementara.
                                            </span>
                                        </div>
                                        <div class="flex align-items-center gap-3">
                                            <span :class="form.site_active ? 'text-green-600 font-bold' : 'text-red-500 font-bold'">
                                                {{ form.site_active ? 'Situs Aktif' : 'Non-Aktif (Maintenance)' }}
                                            </span>
                                            <ToggleSwitch v-model="form.site_active" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>


                    
    </TabPanels>
</Tabs>

                    <div class="flex justify-content-end gap-2 mt-4 pt-3 border-top-1 border-200">
                        <Button type="submit" label="Simpan Pengaturan" icon="pi pi-save" :loading="form.processing" />
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Head, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';

const props = defineProps({
    settings: Object,
    isStaging: Boolean,
    productionUrl: String,
});

const toast = useToast();

// Local form setup using Inertia useForm
const form = useForm({
    school_name: props.settings.school_name || '',
    school_address: props.settings.school_address || '',
    school_phone: props.settings.school_phone || '',
    school_email: props.settings.school_email || '',
    principal_name: props.settings.principal_name || '',
    principal_nip: props.settings.principal_nip || '',
    kop_pemprov: props.settings.kop_pemprov || '',
    kop_dinas: props.settings.kop_dinas || '',
    school_city: props.settings.school_city || '',
    school_province: props.settings.school_province || 'Jawa Tengah',
    school_postal_code: props.settings.school_postal_code || '',
    school_website: props.settings.school_website || '',
    school_latitude: props.settings.school_latitude || '-7.024644700237852',
    school_longitude: props.settings.school_longitude || '110.30859975367461',
    school_radius: props.settings.school_radius || '100',
    site_logo: null,
    site_logo_pemda: null,
    site_favicon: null,
    // Presensi
    attendance_seed_time:   props.settings.attendance_seed_time   || '03:00',
    checkin_window_before:  props.settings.checkin_window_before  || '60',
    checkin_window_after:   props.settings.checkin_window_after   || '120',
    checkout_window_before: props.settings.checkout_window_before || '60',
    checkout_window_after:  props.settings.checkout_window_after  || '240',
    site_active: props.settings.site_active ?? true,
});

// File upload previews
const logoPreview = ref(null);
const logoPemdaPreview = ref(null);
const faviconPreview = ref(null);

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.site_logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const clearLogo = () => {
    form.site_logo = null;
    logoPreview.value = null;
};

const handleLogoPemdaChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.site_logo_pemda = file;
        logoPemdaPreview.value = URL.createObjectURL(file);
    }
};

const clearLogoPemda = () => {
    form.site_logo_pemda = null;
    logoPemdaPreview.value = null;
};

const handleFaviconChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.site_favicon = file;
        faviconPreview.value = URL.createObjectURL(file);
    }
};

const clearFavicon = () => {
    form.site_favicon = null;
    faviconPreview.value = null;
};

// Submit form
const submitForm = () => {
    form.post(route('admin.settings.update'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Sukses', detail: 'Pengaturan situs berhasil diperbarui.', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Gagal', detail: 'Terjadi kesalahan saat memvalidasi form.', life: 3000 });
        }
    });
};

let map = null;
let marker = null;
let circle = null;

const initMap = () => {
    const mapElement = document.getElementById('map');
    if (!mapElement || map) return;

    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: '/leaflet/marker-icon-2x.png',
        iconUrl: '/leaflet/marker-icon.png',
        shadowUrl: '/leaflet/marker-shadow.png'
    });

    const lat = parseFloat(form.school_latitude) || -7.024644700237852;
    const lng = parseFloat(form.school_longitude) || 110.30859975367461;
    const radius = parseFloat(form.school_radius) || 100;

    map = L.map('map').setView([lat, lng], 17);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    
    circle = L.circle([lat, lng], {
        color: 'red',
        fillColor: '#f03',
        fillOpacity: 0.2,
        radius: radius
    }).addTo(map);

    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        form.school_latitude = position.lat.toFixed(8);
        form.school_longitude = position.lng.toFixed(8);
        circle.setLatLng(position);
    });

    map.on('click', function (e) {
        const position = e.latlng;
        form.school_latitude = position.lat.toFixed(8);
        form.school_longitude = position.lng.toFixed(8);
        marker.setLatLng(position);
        circle.setLatLng(position);
    });
};

const updateMap = () => {
    if (map && marker && circle) {
        const lat = parseFloat(form.school_latitude) || -7.024644700237852;
        const lng = parseFloat(form.school_longitude) || 110.30859975367461;
        const radius = parseFloat(form.school_radius) || 100;
        
        const newLatLng = new L.LatLng(lat, lng);
        marker.setLatLng(newLatLng);
        circle.setLatLng(newLatLng);
        circle.setRadius(radius);
        map.panTo(newLatLng);
    }
};

onMounted(() => {
    // Gunakan ResizeObserver agar peta me-render dengan benar saat tab dibuka
    const resizeObserver = new ResizeObserver(() => {
        if (!map) {
            initMap();
        } else {
            map.invalidateSize();
        }
    });
    
    // Perlu sedikit jeda agar DOM ter-render
    setTimeout(() => {
        const mapEl = document.getElementById('map');
        if (mapEl) {
            resizeObserver.observe(mapEl);
            // Inisialisasi awal jika belum
            initMap();
        }
    }, 500);
});
</script>
