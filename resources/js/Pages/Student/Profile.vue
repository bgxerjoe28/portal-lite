<template>
    <div class="min-h-screen bg-slate-900 py-6 px-4 flex flex-column align-items-center">
        <Toast />
        <!-- Header -->
        <div class="w-full max-w-5xl mb-4 text-white form-header bg-slate-800 p-4 border-round-xl shadow-4 gap-3">
            <div class="form-header-title-wrapper">
                <h1 class="text-xl md:text-2xl font-bold m-0 flex items-start gap-2">
                    <i class="pi pi-user text-blue-400 mt-1"></i>
                    <span>Portal Biodata Siswa</span>
                </h1>
                <p class="text-slate-400 m-0 mt-1 text-sm md:text-base">Lengkapi dan perbarui data diri Anda dengan teliti.</p>
            </div>
            <div class="form-header-badges">
                <span class="text-xs md:text-sm bg-blue-900 text-blue-200 py-1 px-3 border-round-lg font-semibold whitespace-nowrap">
                    NISN: {{ student.nisn }}
                </span>
                <span v-if="student.nis" class="text-xs md:text-sm bg-purple-900 text-purple-200 py-1 px-3 border-round-lg font-semibold whitespace-nowrap">
                    NIS: {{ student.nis }}
                </span>
                <div class="ml-auto md:ml-0">
                    <Button icon="pi pi-power-off" severity="danger" text rounded @click="logout" v-tooltip.left="'Keluar'" />
                </div>
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="w-full max-w-5xl bg-white border-round-xl shadow-6 p-4 md:p-6 mb-8">
            <!-- Step Indicators -->
            <div class="flex justify-content-between align-items-center mb-6 overflow-x-auto pb-2 gap-4 border-bottom-1 border-100">
                <div 
                    v-for="(s, idx) in steps" 
                    :key="idx" 
                    class="flex align-items-center gap-2 cursor-pointer pb-3 whitespace-nowrap"
                    :class="[activeStep === idx ? 'text-blue-600 font-bold border-bottom-2 border-blue-600' : 'text-500']"
                    @click="goToStep(idx)"
                >
                    <span 
                        class="border-circle flex align-items-center justify-content-center text-sm font-semibold"
                        :class="[activeStep === idx ? 'bg-blue-600 text-white' : 'bg-100 text-600']"
                        style="width: 28px; height: 28px;"
                    >
                        {{ idx + 1 }}
                    </span>
                    <span>{{ s.label }}</span>
                </div>
            </div>

            <!-- Validation Errors Global -->
            <Message v-if="hasErrors" severity="error" class="mb-4" :closable="true">
                Formulir memiliki beberapa kesalahan input. Harap periksa setiap langkah.
            </Message>

            <!-- FORM STEPS CONTENT -->
            <form @submit.prevent="submitForm">

                <!-- STEP 1: DATA PRIBADI -->
                <div v-show="activeStep === 0">
                    <h3 class="text-xl font-bold text-blue-900 mb-4 border-bottom-1 border-100 pb-2">DATA PRIBADI</h3>
                    <div class="grid">
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Nama Lengkap</label>
                            <InputText v-model="form.full_name" readonly class="w-full bg-100 text-700 font-bold" />
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Jenis Kelamin <span class="text-red-500">*</span></label>
                            <div class="flex gap-4 mt-2">
                                <div class="flex align-items-center">
                                    <RadioButton v-model="form.gender" :value="true" inputId="gender-l" />
                                    <label for="gender-l" class="ml-2">Laki-laki</label>
                                </div>
                                <div class="flex align-items-center">
                                    <RadioButton v-model="form.gender" :value="false" inputId="gender-p" />
                                    <label for="gender-p" class="ml-2">Perempuan</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">NISN <span class="text-red-500">*</span></label>
                            <input :value="form.nisn" @input="e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10); form.nisn = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.nisn}" required placeholder="Masukkan 10 digit NISN" />
                            <small class="text-red-500" v-if="errors.nisn">{{ errors.nisn }}</small>
                        </div>

                        <!-- ASAL SEKOLAH -->
                        <div class="col-12 md:col-3 field mb-3">
                            <label class="block font-semibold mb-2">Jenjang Sekolah Asal <span class="text-red-500">*</span></label>
                            <div class="flex gap-4 mt-2">
                                <div class="flex align-items-center">
                                    <RadioButton v-model="form.prev_school_type" value="SMP" inputId="school-type-smp" />
                                    <label for="school-type-smp" class="ml-2 cursor-pointer text-sm">SMP</label>
                                </div>
                                <div class="flex align-items-center">
                                    <RadioButton v-model="form.prev_school_type" value="MTS" inputId="school-type-mts" />
                                    <label for="school-type-mts" class="ml-2 cursor-pointer text-sm">MTS</label>
                                </div>
                            </div>
                            <small class="text-red-500" v-if="errors.prev_school_type">{{ errors.prev_school_type }}</small>
                        </div>

                        <div class="col-12 md:col-3 field mb-3">
                            <label class="block font-semibold mb-2">Status Sekolah Asal <span class="text-red-500">*</span></label>
                            <div class="flex gap-4 mt-2">
                                <div class="flex align-items-center">
                                    <RadioButton v-model="form.prev_school_status" value="Negeri" inputId="school-status-negeri" />
                                    <label for="school-status-negeri" class="ml-2 cursor-pointer text-sm">Negeri</label>
                                </div>
                                <div class="flex align-items-center">
                                    <RadioButton v-model="form.prev_school_status" value="Swasta" inputId="school-status-swasta" />
                                    <label for="school-status-swasta" class="ml-2 cursor-pointer text-sm">Swasta</label>
                                </div>
                            </div>
                            <small class="text-red-500" v-if="errors.prev_school_status">{{ errors.prev_school_status }}</small>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Nama Sekolah Asal <span class="text-red-500">*</span></label>
                            <input :value="form.prev_school_name" @input="e => form.prev_school_name = e.target.value.toUpperCase()" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.prev_school_name}" required placeholder="Contoh: SMP NEGERI 1 JAKARTA" />
                            <small class="text-red-500" v-if="errors.prev_school_name">{{ errors.prev_school_name }}</small>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">NIK / No. KITAS (WNA) <span class="text-red-500">*</span></label>
                            <input :value="form.nik" @input="e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 16); form.nik = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.nik}" required placeholder="Masukkan NIK 16 digit" />
                            <small class="text-red-500" v-if="errors.nik">{{ errors.nik }}</small>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">No. KK <span class="text-red-500">*</span></label>
                            <input :value="form.no_kk" @input="e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 16); form.no_kk = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.no_kk}" required placeholder="Masukkan No KK 16 digit" />
                            <small class="text-red-500" v-if="errors.no_kk">{{ errors.no_kk }}</small>
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">No. Registrasi Akta Lahir</label>
                            <input :value="form.akta_no" @input="e => form.akta_no = e.target.value" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.akta_no}" placeholder="Opsional, semua karakter" />
                            <small class="text-red-500" v-if="errors.akta_no">{{ errors.akta_no }}</small>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Tempat Lahir <span class="text-red-500">*</span></label>
                            <input :value="form.birth_place" @input="e => { e.target.value = e.target.value.replace(/[^a-zA-Z\s\.]/g, '').toUpperCase(); form.birth_place = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.birth_place}" required placeholder="Contoh: JAKARTA" />
                            <small class="text-red-500" v-if="errors.birth_place">{{ errors.birth_place }}</small>
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Tanggal Lahir <span class="text-red-500">*</span></label>
                            <InputText v-model="form.birth_date" type="date" class="w-full" :class="{'p-invalid': errors.birth_date}" required />
                            <small class="text-red-500" v-if="errors.birth_date">{{ errors.birth_date }}</small>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Agama & Kepercayaan <span class="text-red-500">*</span></label>
                            <Select v-model="form.religion_id" :options="religions" optionLabel="name" optionValue="id" placeholder="Pilih Agama" class="w-full" :class="{'p-invalid': errors.religion_id}" required />
                            <small class="text-red-500" v-if="errors.religion_id">{{ errors.religion_id }}</small>
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Kewarganegaraan <span class="text-red-500">*</span></label>
                            <Select v-model="form.citizenship" :options="['WNI', 'WNA']" class="w-full" required />
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Berkebutuhan Khusus</label>
                            <InputText v-model="form.special_needs" placeholder="Isi jika ada, kosongkan jika tidak" class="w-full" />
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Anak Ke- <span class="text-red-500">*</span></label>
                            <InputText v-model="form.child_order" type="number" min="1" class="w-full" :class="{'p-invalid': errors.child_order}" required />
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">No. WA Murid <span class="text-red-500">*</span></label>
                            <input :value="form.phone" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.phone = e.target.value; }" placeholder="Contoh: 081234567890" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.phone}" required />
                            <small class="text-red-500" v-if="errors.phone">{{ errors.phone }}</small>
                        </div>

                        <div class="col-12 field mb-3">
                            <label class="block font-semibold mb-2">Alamat Jalan <span class="text-red-500">*</span></label>
                            <textarea :value="form.address" @input="e => { e.target.value = e.target.value.replace(/[^a-zA-Z0-9\s\.\-]/g, ''); form.address = e.target.value; }" rows="3" class="p-textarea p-component p-inputtext w-full" :class="{'p-invalid': errors.address}" required placeholder="Alfanumerik, titik, hubung saja"></textarea>
                            <small class="text-red-500" v-if="errors.address">{{ errors.address }}</small>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">RT <span class="text-red-500">*</span></label>
                            <input :value="form.rt" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.rt = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.rt}" required />
                            <small class="text-red-500" v-if="errors.rt">{{ errors.rt }}</small>
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">RW <span class="text-red-500">*</span></label>
                            <input :value="form.rw" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.rw = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.rw}" required />
                            <small class="text-red-500" v-if="errors.rw">{{ errors.rw }}</small>
                        </div>

                        <div class="col-12 md:col-4 field mb-3">
                            <label class="block font-semibold mb-2">Nama Kelurahan/Desa <span class="text-red-500">*</span></label>
                            <input :value="form.kelurahan" @input="e => { e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, ''); form.kelurahan = e.target.value; }" @blur="form.kelurahan = toTitleCase(form.kelurahan)" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.kelurahan}" required placeholder="Contoh: Merdeka" />
                            <small class="text-red-500" v-if="errors.kelurahan">{{ errors.kelurahan }}</small>
                        </div>
                        <div class="col-12 md:col-4 field mb-3">
                            <label class="block font-semibold mb-2">Kecamatan <span class="text-red-500">*</span></label>
                            <input :value="form.kecamatan" @input="e => { e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, ''); form.kecamatan = e.target.value; }" @blur="form.kecamatan = toTitleCase(form.kecamatan)" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.kecamatan}" required placeholder="Contoh: Kebayoran" />
                            <small class="text-red-500" v-if="errors.kecamatan">{{ errors.kecamatan }}</small>
                        </div>
                        <div class="col-12 md:col-4 field mb-3">
                            <label class="block font-semibold mb-2">Kode Pos <span class="text-red-500">*</span></label>
                            <input :value="form.postal_code" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.postal_code = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.postal_code}" required />
                            <small class="text-red-500" v-if="errors.postal_code">{{ errors.postal_code }}</small>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Tempat Tinggal <span class="text-red-500">*</span></label>
                            <Select v-model="form.residence_type" :options="residenceOptions" placeholder="Pilih Tempat Tinggal" class="w-full" required />
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Moda Transportasi <span class="text-red-500">*</span></label>
                            <Select v-model="form.transportation" :options="transportationOptions" placeholder="Pilih Transportasi" class="w-full" required />
                        </div>

                        <!-- MAP POINTING FOR LAT/LNG -->
                        <div class="col-12 field mb-3">
                            <h4 class="text-lg font-bold text-gray-800 mb-2">Peta Lokasi Rumah (Koordinat Lintang & Bujur)</h4>
                            <p class="text-sm text-500 mb-3">Geser pin pada peta atau klik peta pada posisi rumah Anda untuk memetakan koordinat secara otomatis.</p>
                            <div id="map-container" class="border border-300 border-round overflow-hidden mb-3 shadow-2" style="height: 350px;"></div>
                            
                            <div class="grid">
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-2">Lintang (Latitude)</label>
                                    <InputText v-model="form.latitude" placeholder="-6.200000" class="w-full" readonly />
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-2">Bujur (Longitude)</label>
                                    <InputText v-model="form.longitude" placeholder="106.816666" class="w-full" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: DATA ORANG TUA (AYAH KANDUNG & IBU KANDUNG) -->
                <div v-show="activeStep === 1">
                    <div class="grid">
                        <!-- AYAH -->
                        <div class="col-12 lg:col-6">
                            <h3 class="text-xl font-bold text-blue-900 mb-4 border-bottom-1 border-100 pb-2">DATA AYAH KANDUNG</h3>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">Nama Lengkap Ayah Kandung <span class="text-red-500">*</span></label>
                                <input :value="form.father_name" @input="e => { e.target.value = e.target.value.replace(/[^a-zA-Z\s\,\.\-]/g, ''); form.father_name = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.father_name}" required />
                                <small class="text-red-500" v-if="errors.father_name">{{ errors.father_name }}</small>
                            </div>
                            <div class="field mb-3 flex align-items-center gap-2">
                                <Checkbox v-model="form.father_deceased" :binary="true" inputId="father-deceased" @change="handleFatherDeceasedChange" />
                                <label for="father-deceased" class="font-semibold text-gray-700 cursor-pointer">Sudah Meninggal</label>
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">NIK Ayah <span v-if="!form.father_deceased" class="text-red-500">*</span></label>
                                <input :value="form.father_nik" @input="e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 16); form.father_nik = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.father_nik}" :disabled="form.father_deceased" :required="!form.father_deceased" />
                                <small class="text-red-500" v-if="errors.father_nik">{{ errors.father_nik }}</small>
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">Tahun Lahir <span v-if="!form.father_deceased" class="text-red-500">*</span></label>
                                <input :value="form.father_birth_year" type="text" inputmode="numeric" @input="e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4); form.father_birth_year = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.father_birth_year}" :disabled="form.father_deceased" :required="!form.father_deceased" />
                                <small class="text-red-500" v-if="errors.father_birth_year">{{ errors.father_birth_year }}</small>
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">Pendidikan <span v-if="!form.father_deceased" class="text-red-500">*</span></label>
                                <Select v-model="form.father_education" :options="educationOptions" class="w-full" :class="{'p-invalid': errors.father_education}" :disabled="form.father_deceased" :required="!form.father_deceased" />
                                <small class="text-red-500" v-if="errors.father_education">{{ errors.father_education }}</small>
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">Pekerjaan <span v-if="!form.father_deceased" class="text-red-500">*</span></label>
                                <Select v-model="form.father_job" :options="jobOptions" class="w-full" :class="{'p-invalid': errors.jobOptions}" :disabled="form.father_deceased" :required="!form.father_deceased" />
                                <small class="text-red-500" v-if="errors.father_job">{{ errors.father_job }}</small>
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">Penghasilan Bulanan <span v-if="!form.father_deceased" class="text-red-500">*</span></label>
                                <Select v-model="form.father_income" :options="incomeOptions" class="w-full" :class="{'p-invalid': errors.father_income}" :disabled="form.father_deceased" :required="!form.father_deceased" />
                                <small class="text-red-500" v-if="errors.father_income">{{ errors.father_income }}</small>
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">Berkebutuhan Khusus</label>
                                <input :value="form.father_special_needs" @input="e => form.father_special_needs = e.target.value" placeholder="Kosongkan jika tidak" class="p-inputtext p-component w-full" :disabled="form.father_deceased" />
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">No. WA Ayah <span v-if="!form.father_deceased" class="text-red-500">*</span></label>
                                <input :value="form.father_phone" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.father_phone = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.father_phone}" :disabled="form.father_deceased" :required="!form.father_deceased" />
                                <small class="text-red-500" v-if="errors.father_phone">{{ errors.father_phone }}</small>
                            </div>
                        </div>

                        <!-- IBU -->
                        <div class="col-12 lg:col-6 mt-5 lg:mt-0">
                            <h3 class="text-xl font-bold text-blue-900 mb-4 border-bottom-1 border-100 pb-2">DATA IBU KANDUNG</h3>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">Nama Lengkap Ibu Kandung <span class="text-red-500">*</span></label>
                                <input :value="form.mother_name" @input="e => { e.target.value = e.target.value.replace(/[^a-zA-Z\s\,\.]/g, ''); form.mother_name = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.mother_name}" required />
                                <small class="text-red-500" v-if="errors.mother_name">{{ errors.mother_name }}</small>
                            </div>
                            <div class="field mb-3 flex align-items-center gap-2">
                                <Checkbox v-model="form.mother_deceased" :binary="true" inputId="mother-deceased" @change="handleMotherDeceasedChange" />
                                <label for="mother-deceased" class="font-semibold text-gray-700 cursor-pointer">Sudah Meninggal</label>
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">NIK Ibu <span v-if="!form.mother_deceased" class="text-red-500">*</span></label>
                                <input :value="form.mother_nik" @input="e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 16); form.mother_nik = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.mother_nik}" :disabled="form.mother_deceased" :required="!form.mother_deceased" />
                                <small class="text-red-500" v-if="errors.mother_nik">{{ errors.mother_nik }}</small>
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">Tahun Lahir <span v-if="!form.mother_deceased" class="text-red-500">*</span></label>
                                <input :value="form.mother_birth_year" type="text" inputmode="numeric" @input="e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4); form.mother_birth_year = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.mother_birth_year}" :disabled="form.mother_deceased" :required="!form.mother_deceased" />
                                <small class="text-red-500" v-if="errors.mother_birth_year">{{ errors.mother_birth_year }}</small>
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">Pendidikan <span v-if="!form.mother_deceased" class="text-red-500">*</span></label>
                                <Select v-model="form.mother_education" :options="educationOptions" class="w-full" :class="{'p-invalid': errors.mother_education}" :disabled="form.mother_deceased" :required="!form.mother_deceased" />
                                <small class="text-red-500" v-if="errors.mother_education">{{ errors.mother_education }}</small>
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">Pekerjaan <span v-if="!form.mother_deceased" class="text-red-500">*</span></label>
                                <Select v-model="form.mother_job" :options="jobOptions" class="w-full" :class="{'p-invalid': errors.mother_job}" :disabled="form.mother_deceased" :required="!form.mother_deceased" />
                                <small class="text-red-500" v-if="errors.mother_job">{{ errors.mother_job }}</small>
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">Penghasilan Bulanan <span v-if="!form.mother_deceased" class="text-red-500">*</span></label>
                                <Select v-model="form.mother_income" :options="incomeOptions" class="w-full" :class="{'p-invalid': errors.mother_income}" :disabled="form.mother_deceased" :required="!form.mother_deceased" />
                                <small class="text-red-500" v-if="errors.mother_income">{{ errors.mother_income }}</small>
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">Berkebutuhan Khusus</label>
                                <input :value="form.mother_special_needs" @input="e => form.mother_special_needs = e.target.value" placeholder="Kosongkan jika tidak" class="p-inputtext p-component w-full" :disabled="form.mother_deceased" />
                            </div>
                            <div class="field mb-3">
                                <label class="block font-semibold mb-2">No. WA Ibu <span v-if="!form.mother_deceased" class="text-red-500">*</span></label>
                                <input :value="form.mother_phone" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.mother_phone = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.mother_phone}" :disabled="form.mother_deceased" :required="!form.mother_deceased" />
                                <small class="text-red-500" v-if="errors.mother_phone">{{ errors.mother_phone }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: DATA WALI (OPTIONAL) -->
                <div v-show="activeStep === 2">
                    <h3 class="text-xl font-bold text-blue-900 mb-2 border-bottom-1 border-100 pb-2">DATA WALI</h3>
                    
                    <div class="flex align-items-center mb-4 mt-3">
                        <input type="checkbox" id="has_guardian" v-model="has_guardian" class="cursor-pointer mr-2 w-5 h-5" />
                        <label for="has_guardian" class="font-semibold cursor-pointer">Siswa memiliki wali (tidak tinggal bersama orang tua kandung)</label>
                    </div>

                    <div v-if="has_guardian" class="grid transition-all duration-300">
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Nama Wali <span class="text-red-500">*</span></label>
                            <input :value="form.guardian_name" @input="e => form.guardian_name = e.target.value" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.guardian_name}" :required="has_guardian" />
                            <small class="text-red-500" v-if="errors.guardian_name">{{ errors.guardian_name }}</small>
                            <small class="text-red-500 font-semibold block mt-1" v-if="showWaliWarning">
                                <i class="pi pi-exclamation-triangle mr-1"></i>Nama Wali tidak boleh sama dengan Nama Ayah / Ibu Kandung (ayah / ibu bukan diisi di wali).
                            </small>
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">NIK Wali <span class="text-red-500">*</span></label>
                            <input :value="form.guardian_nik" @input="e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 16); form.guardian_nik = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.guardian_nik}" :required="has_guardian" placeholder="Masukkan NIK Wali 16 digit" />
                            <small class="text-red-500" v-if="errors.guardian_nik">{{ errors.guardian_nik }}</small>
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Tahun Lahir <span class="text-red-500">*</span></label>
                            <input :value="form.guardian_birth_year" type="text" inputmode="numeric" @input="e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4); form.guardian_birth_year = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.guardian_birth_year}" :required="has_guardian" />
                            <small class="text-red-500" v-if="errors.guardian_birth_year">{{ errors.guardian_birth_year }}</small>
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Pendidikan <span class="text-red-500">*</span></label>
                            <Select v-model="form.guardian_education" :options="educationOptions" class="w-full" :class="{'p-invalid': errors.guardian_education}" :required="has_guardian" />
                            <small class="text-red-500" v-if="errors.guardian_education">{{ errors.guardian_education }}</small>
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Pekerjaan <span class="text-red-500">*</span></label>
                            <Select v-model="form.guardian_job" :options="jobOptions" class="w-full" :class="{'p-invalid': errors.guardian_job}" :required="has_guardian" />
                            <small class="text-red-500" v-if="errors.guardian_job">{{ errors.guardian_job }}</small>
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Penghasilan Bulanan <span class="text-red-500">*</span></label>
                            <Select v-model="form.guardian_income" :options="incomeOptions" class="w-full" :class="{'p-invalid': errors.guardian_income}" :required="has_guardian" />
                            <small class="text-red-500" v-if="errors.guardian_income">{{ errors.guardian_income }}</small>
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">No. WA Wali <span class="text-red-500">*</span></label>
                            <input :value="form.guardian_phone" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.guardian_phone = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': errors.guardian_phone}" :required="has_guardian" />
                            <small class="text-red-500" v-if="errors.guardian_phone">{{ errors.guardian_phone }}</small>
                        </div>
                    </div>
                    <div v-else class="text-600 bg-50 p-4 border-round-lg text-center">
                        <i class="pi pi-info-circle text-2xl mb-2 text-blue-500 block"></i>
                        Data Wali tidak diaktifkan. Anda terhitung tinggal bersama orang tua kandung.
                    </div>
                </div>

                <!-- STEP 4: DATA PERIODIK SISWA -->
                <div v-show="activeStep === 3">
                    <h3 class="text-xl font-bold text-blue-900 mb-4 border-bottom-1 border-100 pb-2">DATA PERIODIK SISWA</h3>
                    <div class="grid">
                        <div class="col-12 md:col-4 field mb-3">
                            <label class="block font-semibold mb-2">Tinggi Badan (cm) <span class="text-red-500">*</span></label>
                            <InputText v-model="form.height" type="number" min="50" step="any" class="w-full" :class="{'p-invalid': errors.height}" required />
                            <small class="text-red-500" v-if="errors.height">{{ errors.height }}</small>
                        </div>
                        <div class="col-12 md:col-4 field mb-3">
                            <label class="block font-semibold mb-2">Berat Badan (kg) <span class="text-red-500">*</span></label>
                            <InputText v-model="form.weight" type="number" min="10" step="any" class="w-full" :class="{'p-invalid': errors.weight}" required />
                            <small class="text-red-500" v-if="errors.weight">{{ errors.weight }}</small>
                        </div>
                        <div class="col-12 md:col-4 field mb-3">
                            <label class="block font-semibold mb-2">Lingkar Kepala (cm) <span class="text-red-500">*</span></label>
                            <InputText v-model="form.head_circumference" type="number" min="20" step="any" class="w-full" :class="{'p-invalid': errors.head_circumference}" required />
                            <small class="text-red-500" v-if="errors.head_circumference">{{ errors.head_circumference }}</small>
                        </div>

                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Jarak Tempat Tinggal ke Sekolah <span class="text-red-500">*</span></label>
                            <div class="flex align-items-center">
                                <InputText v-model="form.distance_to_school_km" type="number" step="0.1" min="0" class="w-full" :class="{'p-invalid': errors.distance_to_school_km}" required />
                                <span class="ml-2 font-medium text-600">km</span>
                            </div>
                            <small class="text-red-500" v-if="errors.distance_to_school_km">{{ errors.distance_to_school_km }}</small>
                            <small class="text-500 block mt-1">Sebutkan dalam kilometer</small>
                        </div>
                        <div class="col-12 md:col-6 field mb-3">
                            <label class="block font-semibold mb-2">Waktu Tempuh ke Sekolah (Jam / Menit) <span class="text-red-500">*</span></label>
                            <div class="flex align-items-center gap-2">
                                <div class="flex align-items-center w-full">
                                    <InputText v-model="tempuhJam" type="number" min="0" placeholder="0" class="w-full text-center" :class="{'p-invalid': errors.travel_time_minutes}" @input="updateTravelTime" required />
                                    <span class="ml-2 font-medium text-600">Jam</span>
                                </div>
                                <div class="flex align-items-center w-full">
                                    <InputText v-model="tempuhMenit" type="number" min="0" max="59" placeholder="0" class="w-full text-center" :class="{'p-invalid': errors.travel_time_minutes}" @input="updateTravelTime" required />
                                    <span class="ml-2 font-medium text-600">Menit</span>
                                </div>
                            </div>
                            <small class="text-red-500" v-if="errors.travel_time_minutes">{{ errors.travel_time_minutes }}</small>
                        </div>

                        <div class="col-12 field mb-3">
                            <label class="block font-semibold mb-2">Jumlah Saudara Kandung <span class="text-red-500">*</span></label>
                            <InputText v-model="form.sibling_count" type="number" min="0" class="w-full" :class="{'p-invalid': errors.sibling_count}" required />
                            <small class="text-red-500" v-if="errors.sibling_count">{{ errors.sibling_count }}</small>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: UPLOAD DOKUMEN -->
                <div v-show="activeStep === 4">
                    <h3 class="text-xl font-bold text-blue-900 mb-2 border-bottom-1 border-100 pb-2">UNGGAH DOKUMEN</h3>
                    <p class="text-sm text-600 mb-4">Berkas yang diperbolehkan adalah PDF, JPG, JPEG, atau PNG dengan ukuran maksimal 2MB per berkas.</p>

                    <div class="flex flex-column gap-4">
                        <!-- UPLOAD CARDS -->
                        <div v-for="fileField in filesMeta" :key="fileField.name" class="p-3 border border-round flex flex-column md:flex-row justify-content-between align-items-center gap-3" :class="[errors[fileField.name] ? 'border-red-500 bg-red-50' : 'border-200 bg-50']">
                            <div class="flex align-items-center gap-3">
                                <div class="bg-blue-100 text-blue-600 flex align-items-center justify-content-center border-circle" style="width: 45px; height: 45px;">
                                    <i class="pi pi-file text-xl"></i>
                                </div>
                                <div>
                                    <span class="block font-bold text-gray-800">{{ fileField.label }} <span v-if="fileField.required" class="text-red-500">*</span></span>
                                    <span class="text-sm text-500">{{ fileField.desc }}</span>
                                    <small class="text-red-500 block mt-1" v-if="errors[fileField.name]">{{ errors[fileField.name] }}</small>
                                </div>
                            </div>
                            <div class="flex align-items-center gap-3">
                                <!-- Existing File Indicator -->
                                <span v-if="student[fileField.name] && !filePreviews[fileField.name]" class="text-sm bg-green-100 text-green-700 py-1 px-3 border-round-lg font-semibold">
                                    <i class="pi pi-check-circle mr-1"></i>Sudah Diunggah
                                </span>
                                <!-- Selected File to Upload Indicator -->
                                <span v-if="filePreviews[fileField.name]" class="text-sm bg-blue-100 text-blue-700 py-1 px-3 border-round-lg font-semibold">
                                    <i class="pi pi-file mr-1"></i>{{ filePreviews[fileField.name] }}
                                </span>
                                
                                <input 
                                    type="file" 
                                    :id="fileField.name" 
                                    class="hidden" 
                                    accept=".pdf,image/*" 
                                    @change="handleFileChange($event, fileField.name)" 
                                />
                                <Button 
                                    type="button" 
                                    :label="student[fileField.name] || filePreviews[fileField.name] ? 'Ganti File' : 'Pilih File'" 
                                    icon="pi pi-upload" 
                                    severity="secondary" 
                                    size="small"
                                    @click="triggerFileInput(fileField.name)" 
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 6: RINGKASAN & KONFIRMASI -->
                <div v-show="activeStep === 5">
                    <h3 class="text-xl font-bold text-blue-900 mb-2 border-bottom-1 border-100 pb-2">KONFIRMASI AKHIR</h3>
                    <p class="text-sm text-600 mb-4">Harap periksa kembali seluruh data yang Anda masukkan sebelum menyimpan data secara permanen.</p>

                    <!-- Summary Accordion/Grid -->
                    <div class="border border-200 border-round overflow-hidden shadow-2 bg-50 p-4">
                        <div class="mb-4">
                            <h4 class="text-blue-700 font-bold m-0 mb-2 border-bottom-1 border-100 pb-1">1. DATA PRIBADI</h4>
                            <div class="grid text-sm">
                                <div class="col-6 md:col-3 font-semibold text-600">Nama Lengkap:</div>
                                <div class="col-6 md:col-3">{{ form.full_name }}</div>
                                <div class="col-6 md:col-3 font-semibold text-600">Jenis Kelamin:</div>
                                <div class="col-6 md:col-3">{{ form.gender ? 'Laki-laki' : 'Perempuan' }}</div>
                                <div class="col-6 md:col-3 font-semibold text-600">NISN:</div>
                                <div class="col-6 md:col-3">{{ form.nisn }}</div>
                                <div class="col-6 md:col-3 font-semibold text-600">NIK:</div>
                                <div class="col-6 md:col-3">{{ form.nik }}</div>
                                <div class="col-6 md:col-3 font-semibold text-600">Koordinat:</div>
                                <div class="col-6 md:col-9">{{ form.latitude }}, {{ form.longitude }}</div>
                                <div class="col-6 md:col-3 font-semibold text-600">No. WA:</div>
                                <div class="col-6 md:col-9">{{ form.phone }}</div>
                                <div class="col-6 md:col-3 font-semibold text-600">Sekolah Asal:</div>
                                <div class="col-6 md:col-9">{{ form.prev_school_type }} {{ form.prev_school_status }} {{ form.prev_school_name }}</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4 class="text-blue-700 font-bold m-0 mb-2 border-bottom-1 border-100 pb-1">2. DATA ORANG TUA</h4>
                            <div class="grid text-sm">
                                <div class="col-6 md:col-3 font-semibold text-600">Nama Ayah:</div>
                                <div class="col-6 md:col-3">{{ form.father_name }}</div>
                                <div class="col-6 md:col-3 font-semibold text-600">Nama Ibu:</div>
                                <div class="col-6 md:col-3">{{ form.mother_name }}</div>
                                <div class="col-6 md:col-3 font-semibold text-600">No WA Ayah / Ibu:</div>
                                <div class="col-6 md:col-9">{{ form.father_phone }} / {{ form.mother_phone }}</div>
                            </div>
                        </div>

                        <div class="mb-4" v-if="has_guardian">
                            <h4 class="text-blue-700 font-bold m-0 mb-2 border-bottom-1 border-100 pb-1">3. DATA WALI</h4>
                            <div class="grid text-sm">
                                <div class="col-6 md:col-3 font-semibold text-600">Nama Wali:</div>
                                <div class="col-6 md:col-9">{{ form.guardian_name }}</div>
                                <div class="col-6 md:col-3 font-semibold text-600">Pekerjaan Wali:</div>
                                <div class="col-6 md:col-9">{{ form.guardian_job }}</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4 class="text-blue-700 font-bold m-0 mb-2 border-bottom-1 border-100 pb-1">4. DATA PERIODIK & BERKAS</h4>
                            <div class="grid text-sm">
                                <div class="col-6 md:col-3 font-semibold text-600">Tinggi / Berat / Kepala:</div>
                                <div class="col-6 md:col-3">{{ form.height }} cm / {{ form.weight }} kg / {{ form.head_circumference }} cm</div>
                                <div class="col-6 md:col-3 font-semibold text-600">Jarak / Waktu Tempuh:</div>
                                <div class="col-6 md:col-3">{{ form.distance_to_school_km }} km / {{ form.travel_time_minutes }} menit</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex align-items-center mb-4 mt-4 bg-yellow-50 p-3 border border-yellow-200 border-round-lg">
                        <input type="checkbox" id="agree" v-model="agree" class="cursor-pointer mr-2 w-5 h-5" required />
                        <label for="agree" class="text-sm text-800 cursor-pointer">Dengan ini saya menyatakan bahwa data biodata yang saya masukkan adalah benar, valid, dan dapat dipertanggungjawabkan.</label>
                    </div>
                </div>

                <!-- NAVIGATION BUTTONS -->
                <div class="flex justify-content-between align-items-center mt-6 pt-4 border-top-1 border-100">
                    <Button 
                        type="button" 
                        label="Kembali" 
                        icon="pi pi-chevron-left" 
                        class="p-button-outlined" 
                        :disabled="activeStep === 0" 
                        @click="prevStep" 
                    />
                    
                    <Button 
                        v-if="activeStep < steps.length - 1" 
                        type="button" 
                        label="Lanjutkan" 
                        icon="pi pi-chevron-right" 
                        iconPos="right" 
                        class="bg-blue-600 hover:bg-blue-700 border-none" 
                        @click="nextStep" 
                    />

                    <Button 
                        v-else 
                        type="submit" 
                        label="Simpan Biodata" 
                        icon="pi pi-check" 
                        class="bg-green-600 hover:bg-green-700 border-none" 
                        :loading="form.processing"
                        :disabled="!agree" 
                    />
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import RadioButton from 'primevue/radiobutton';
import Message from 'primevue/message';
import Checkbox from 'primevue/checkbox';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    student: Object,
    religions: Array
});

const toast = useToast();
const activeStep = ref(0);
const agree = ref(false);
const has_guardian = ref(props.student.guardian_name ? true : false);

const steps = [
    { label: 'Data Pribadi' },
    { label: 'Orang Tua' },
    { label: 'Wali' },
    { label: 'Periodik' },
    { label: 'Upload Berkas' },
    { label: 'Konfirmasi' }
];

const educationOptions = ['SD / Sederajat', 'SMP / Sederajat', 'SMA / Sederajat', 'D1', 'D2', 'D3', 'S1 / D4', 'S2', 'S3', 'Tidak Sekolah'];
const jobOptions = [
    'PNS / TNI / POLRI', 'Karyawan Swasta', 'Wirausaha / Pedagang', 'Petani / Peternak / Nelayan', 
    'Buruh / Pekerja Lepas', 'Ibu Rumah Tangga', 'Tidak Bekerja', 'Lainnya'
];
const incomeOptions = [
    'Kurang dari Rp 1.000.000',
    'Rp 1.000.000 - Rp 3.000.000',
    'Rp 3.000.000 - Rp 5.000.000',
    'Rp 5.000.000 - Rp 10.000.000',
    'Lebih dari Rp 10.000.000',
    'Tidak Berpenghasilan'
];
const residenceOptions = ['Bersama Orang Tua', 'Bersama Wali', 'Kos / Kontrakan', 'Asrama', 'Panti Asuhan', 'Lainnya'];
const transportationOptions = ['Jalan Kaki', 'Sepeda Motor', 'Mobil Pribadi', 'Sepeda', 'Angkutan Umum / Bus', 'Jemputan Sekolah', 'Lainnya'];

const filesMeta = [
    { name: 'file_kk', label: 'Kartu Keluarga (KK)', required: false, desc: 'Unggah pindaian Kartu Keluarga resmi terbaru.' },
    { name: 'file_akta', label: 'Akta Kelahiran', required: false, desc: 'Unggah pindaian Akta Kelahiran resmi.' },
    { name: 'file_ijazah', label: 'Ijazah / SKL', required: false, desc: 'Surat Keterangan Lulus atau Ijazah SMP/sederajat.' },
    { name: 'file_foto', label: 'Pas Foto', required: false, desc: 'Pas foto berwarna terbaru' },
    { name: 'file_other', label: 'Berkas Pendukung Lainnya', required: false, desc: 'Piagam prestasi, KIP, PKH, dll (bila ada, gabungkan menjadi 1 berkas).' }
];

const filePreviews = ref({
    file_kk: '',
    file_akta: '',
    file_ijazah: '',
    file_foto: '',
    file_other: ''
});

// Initialize form
const form = useForm({
    // Data Pribadi
    full_name: (props.student.full_name || '').toUpperCase(),
    gender: props.student.gender !== null ? props.student.gender : true,
    nisn: props.student.nisn || '',
    prev_school_type: props.student.prev_school_type || '',
    prev_school_status: props.student.prev_school_status || '',
    prev_school_name: props.student.prev_school_name || '',
    nik: props.student.nik || '',
    no_kk: props.student.no_kk || '',
    birth_place: props.student.birth_place || '',
    birth_date: props.student.birth_date ? props.student.birth_date.split('T')[0] : '',
    akta_no: props.student.akta_no || '',
    religion_id: props.student.religion_id || '',
    citizenship: props.student.citizenship || 'WNI',
    special_needs: props.student.special_needs || '',
    address: props.student.address || '',
    rt: props.student.rt || '',
    rw: props.student.rw || '',
    kelurahan: props.student.kelurahan || '',
    kecamatan: props.student.kecamatan || '',
    postal_code: props.student.postal_code || '',
    latitude: props.student.latitude || '',
    longitude: props.student.longitude || '',
    residence_type: props.student.residence_type || '',
    transportation: props.student.transportation || '',
    child_order: props.student.child_order || 1,
    phone: props.student.phone || '',

    // Data Ayah
    father_name: props.student.father_name || '',
    father_deceased: props.student.father_deceased === 1 || props.student.father_deceased === true || false,
    father_nik: props.student.father_nik || '',
    father_birth_year: props.student.father_birth_year || '',
    father_education: props.student.father_education || '',
    father_job: props.student.father_job || '',
    father_income: props.student.father_income || '',
    father_special_needs: props.student.father_special_needs || '',
    father_phone: props.student.father_phone || '',

    // Data Ibu
    mother_name: props.student.mother_name || '',
    mother_deceased: props.student.mother_deceased === 1 || props.student.mother_deceased === true || false,
    mother_nik: props.student.mother_nik || '',
    mother_birth_year: props.student.mother_birth_year || '',
    mother_education: props.student.mother_education || '',
    mother_job: props.student.mother_job || '',
    mother_income: props.student.mother_income || '',
    mother_special_needs: props.student.mother_special_needs || '',
    mother_phone: props.student.mother_phone || '',

    // Data Wali
    guardian_name: props.student.guardian_name || '',
    guardian_nik: props.student.guardian_nik || '',
    guardian_birth_year: props.student.guardian_birth_year || '',
    guardian_education: props.student.guardian_education || '',
    guardian_job: props.student.guardian_job || '',
    guardian_income: props.student.guardian_income || '',
    guardian_phone: props.student.guardian_phone || '',
    has_guardian: has_guardian,

    // Data Periodik
    height: props.student.height || '',
    weight: props.student.weight || '',
    head_circumference: props.student.head_circumference || '',
    distance_to_school_km: props.student.distance_to_school_km || '',
    travel_time_minutes: props.student.travel_time_minutes || '',
    sibling_count: props.student.sibling_count || '',

    // Files
    file_kk: null,
    file_akta: null,
    file_ijazah: null,
    file_foto: null,
    file_other: null,
});

const errors = computed(() => form.errors);
const hasErrors = computed(() => Object.keys(form.errors).length > 0);

// Travel time helper refs & watch
const tempuhJam = ref(0);
const tempuhMenit = ref(0);

const updateTravelTime = () => {
    form.travel_time_minutes = (parseInt(tempuhJam.value) || 0) * 60 + (parseInt(tempuhMenit.value) || 0);
};

watch(() => props.student.travel_time_minutes, (newVal) => {
    const totalMinutes = newVal !== null && newVal !== undefined ? parseInt(newVal) : 0;
    tempuhJam.value = Math.floor(totalMinutes / 60);
    tempuhMenit.value = totalMinutes % 60;
    form.travel_time_minutes = totalMinutes;
}, { immediate: true });

// Leaflet.js map logic
let map = null;
let marker = null;

const initMap = () => {
    const initialLat = parseFloat(form.latitude) || -6.200000;
    const initialLng = parseFloat(form.longitude) || 106.816666;

    if (!map) {
        map = L.map('map-container').setView([initialLat, initialLng], 14);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

        marker.on('dragend', () => {
            const position = marker.getLatLng();
            form.latitude = position.lat.toFixed(6);
            form.longitude = position.lng.toFixed(6);
        });

        map.on('click', (event) => {
            const position = event.latlng;
            marker.setLatLng(position);
            form.latitude = position.lat.toFixed(6);
            form.longitude = position.lng.toFixed(6);
        });

        if (!form.latitude || !form.longitude) {
            form.latitude = initialLat.toFixed(6);
            form.longitude = initialLng.toFixed(6);
        }
    } else {
        setTimeout(() => {
            map.invalidateSize();
        }, 100);
    }
};

onMounted(() => {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(link);

    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = () => {
        initMap();
    };
    document.head.appendChild(script);
});

// File Handling
const triggerFileInput = (id) => {
    document.getElementById(id).click();
};

const handleFileChange = (event, fieldName) => {
    const file = event.target.files[0];
    if (file) {
        const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        const extension = file.name.split('.').pop().toLowerCase();
        
        if (!allowedExtensions.includes(extension)) {
            toast.add({ severity: 'error', summary: 'Format Tidak Valid', detail: 'Berkas yang diperbolehkan adalah PDF, JPG, JPEG, atau PNG.', life: 5000 });
            event.target.value = '';
            form[fieldName] = null;
            filePreviews.value[fieldName] = '';
            return;
        }

        if (file.size > 2048 * 1024) {
            toast.add({ severity: 'error', summary: 'File Terlalu Besar', detail: 'Maksimal ukuran file adalah 2MB.', life: 5000 });
            event.target.value = '';
            form[fieldName] = null;
            filePreviews.value[fieldName] = '';
            return;
        }
        form[fieldName] = file;
        filePreviews.value[fieldName] = file.name;
    }
};

// Utilities & Validation
const toTitleCase = (str) => {
    if (!str) return '';
    return str.replace(/\w\S*/g, (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase());
};

const validateCurrentStep = () => {
    form.clearErrors();
    const errorsToSet = {};

    if (activeStep.value === 0) {
        if (!form.nisn) errorsToSet.nisn = 'NISN wajib diisi.';
        else if (String(form.nisn).length !== 10) errorsToSet.nisn = 'NISN harus 10 digit.';

        if (!form.prev_school_type) errorsToSet.prev_school_type = 'Jenjang Sekolah asal wajib dipilih.';
        if (!form.prev_school_status) errorsToSet.prev_school_status = 'Status Sekolah asal wajib dipilih.';
        if (!form.prev_school_name) errorsToSet.prev_school_name = 'Nama Sekolah asal wajib diisi.';

        if (!form.nik) errorsToSet.nik = 'NIK wajib diisi.';
        else if (String(form.nik).length !== 16) errorsToSet.nik = 'NIK harus 16 digit.';

        if (!form.no_kk) errorsToSet.no_kk = 'No. KK wajib diisi.';
        else if (String(form.no_kk).length !== 16) errorsToSet.no_kk = 'No. KK harus 16 digit.';

        if (!form.birth_place) errorsToSet.birth_place = 'Tempat Lahir wajib diisi.';
        if (!form.birth_date) errorsToSet.birth_date = 'Tanggal Lahir wajib diisi.';
        if (!form.religion_id) errorsToSet.religion_id = 'Agama wajib diisi.';
        if (!form.citizenship) errorsToSet.citizenship = 'Kewarganegaraan wajib diisi.';
        
        if (!form.phone) errorsToSet.phone = 'No. WA wajib diisi.';
        if (!form.address) errorsToSet.address = 'Alamat Jalan wajib diisi.';
        
        if (!form.rt) errorsToSet.rt = 'RT wajib diisi.';
        if (!form.rw) errorsToSet.rw = 'RW wajib diisi.';
        if (!form.kelurahan) errorsToSet.kelurahan = 'Nama Kelurahan/Desa wajib diisi.';
        if (!form.kecamatan) errorsToSet.kecamatan = 'Kecamatan wajib diisi.';
        if (!form.postal_code) errorsToSet.postal_code = 'Kode Pos wajib diisi.';
        if (!form.residence_type) errorsToSet.residence_type = 'Tempat Tinggal wajib diisi.';
        if (!form.transportation) errorsToSet.transportation = 'Moda Transportasi wajib diisi.';
    } 
    else if (activeStep.value === 1) {
        if (!form.father_name) errorsToSet.father_name = 'Nama Ayah wajib diisi.';
        if (!form.father_deceased) {
            if (!form.father_nik) errorsToSet.father_nik = 'NIK Ayah wajib diisi.';
            else if (String(form.father_nik).length !== 16) errorsToSet.father_nik = 'NIK Ayah harus 16 digit.';

            if (!form.father_birth_year) errorsToSet.father_birth_year = 'Tahun Lahir Ayah wajib diisi.';
            else if (String(form.father_birth_year).length !== 4) errorsToSet.father_birth_year = 'Tahun Lahir Ayah harus 4 digit.';

            if (!form.father_education) errorsToSet.father_education = 'Pendidikan Ayah wajib diisi.';
            if (!form.father_job) errorsToSet.father_job = 'Pekerjaan Ayah wajib diisi.';
            if (!form.father_income) errorsToSet.father_income = 'Penghasilan Ayah wajib diisi.';
            if (!form.father_phone) errorsToSet.father_phone = 'No. WA Ayah wajib diisi.';
        }

        if (!form.mother_name) errorsToSet.mother_name = 'Nama Ibu wajib diisi.';
        if (!form.mother_deceased) {
            if (!form.mother_nik) errorsToSet.mother_nik = 'NIK Ibu wajib diisi.';
            else if (String(form.mother_nik).length !== 16) errorsToSet.mother_nik = 'NIK Ibu harus 16 digit.';

            if (!form.mother_birth_year) errorsToSet.mother_birth_year = 'Tahun Lahir Ibu wajib diisi.';
            else if (String(form.mother_birth_year).length !== 4) errorsToSet.mother_birth_year = 'Tahun Lahir Ibu harus 4 digit.';

            if (!form.mother_education) errorsToSet.mother_education = 'Pendidikan Ibu wajib diisi.';
            if (!form.mother_job) errorsToSet.mother_job = 'Pekerjaan Ibu wajib diisi.';
            if (!form.mother_income) errorsToSet.mother_income = 'Penghasilan Ibu wajib diisi.';
            if (!form.mother_phone) errorsToSet.mother_phone = 'No. WA Ibu wajib diisi.';
        }
    } 
    else if (activeStep.value === 2) {
        if (has_guardian.value) {
            if (!form.guardian_name) errorsToSet.guardian_name = 'Nama Wali wajib diisi.';
            if (!form.guardian_nik) errorsToSet.guardian_nik = 'NIK Wali wajib diisi.';
            else if (String(form.guardian_nik).length !== 16) errorsToSet.guardian_nik = 'NIK Wali harus 16 digit.';

            if (!form.guardian_birth_year) errorsToSet.guardian_birth_year = 'Tahun Lahir Wali wajib diisi.';
            else if (String(form.guardian_birth_year).length !== 4) errorsToSet.guardian_birth_year = 'Tahun Lahir Wali harus 4 digit.';

            if (!form.guardian_education) errorsToSet.guardian_education = 'Pendidikan Wali wajib diisi.';
            if (!form.guardian_job) errorsToSet.guardian_job = 'Pekerjaan Wali wajib diisi.';
            if (!form.guardian_income) errorsToSet.guardian_income = 'Penghasilan Wali wajib diisi.';
            if (!form.guardian_phone) errorsToSet.guardian_phone = 'No. WA Wali wajib diisi.';
        }
    } 
    else if (activeStep.value === 3) {
        if (form.height === null || form.height === undefined || form.height === '') errorsToSet.height = 'Tinggi Badan wajib diisi.';
        else {
            const hVal = parseFloat(form.height);
            if (isNaN(hVal) || hVal < 50 || hVal > 250) errorsToSet.height = 'Tinggi Badan harus di antara 50 - 250 cm.';
        }
        
        if (form.weight === null || form.weight === undefined || form.weight === '') errorsToSet.weight = 'Berat Badan wajib diisi.';
        else {
            const wVal = parseFloat(form.weight);
            if (isNaN(wVal) || wVal < 10 || wVal > 200) errorsToSet.weight = 'Berat Badan harus di antara 10 - 200 kg.';
        }
        
        if (form.head_circumference === null || form.head_circumference === undefined || form.head_circumference === '') errorsToSet.head_circumference = 'Lingkar Kepala wajib diisi.';
        else {
            const hcVal = parseFloat(form.head_circumference);
            if (isNaN(hcVal) || hcVal < 20 || hcVal > 100) errorsToSet.head_circumference = 'Lingkar Kepala harus di antara 20 - 100 cm.';
        }
        if (form.distance_to_school_km === null || form.distance_to_school_km === undefined || form.distance_to_school_km === '') errorsToSet.distance_to_school_km = 'Jarak ke sekolah wajib diisi.';
        if (form.travel_time_minutes === null || form.travel_time_minutes === undefined || form.travel_time_minutes === '') errorsToSet.travel_time_minutes = 'Waktu tempuh wajib diisi.';
        if (form.sibling_count === null || form.sibling_count === undefined || form.sibling_count === '') errorsToSet.sibling_count = 'Jumlah saudara kandung wajib diisi.';
    }

    if (Object.keys(errorsToSet).length > 0) {
        form.setError(errorsToSet);
        return false;
    }

    return true;
};

const showWaliWarning = computed(() => {
    if (!form.guardian_name || !has_guardian.value) return false;
    const gName = form.guardian_name.trim().toLowerCase();
    const fName = form.father_name?.trim().toLowerCase();
    const mName = form.mother_name?.trim().toLowerCase();
    return (fName && gName === fName) || (mName && gName === mName);
});

const handleFatherDeceasedChange = () => {
    if (form.father_deceased) {
        form.father_nik = '';
        form.father_birth_year = '';
        form.father_education = '';
        form.father_job = 'Lainnya';
        form.father_income = 'Tidak Berpenghasilan';
        form.father_special_needs = '';
        form.father_phone = '';
    }
};

const handleMotherDeceasedChange = () => {
    if (form.mother_deceased) {
        form.mother_nik = '';
        form.mother_birth_year = '';
        form.mother_education = '';
        form.mother_job = 'Lainnya';
        form.mother_income = 'Tidak Berpenghasilan';
        form.mother_special_needs = '';
        form.mother_phone = '';
    }
};

const isSaving = ref(false);

const saveDraftAndNavigate = (targetStep) => {
    if (targetStep > activeStep.value) {
        if (!validateCurrentStep()) {
            toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Harap lengkapi semua isian wajib (*) sebelum melanjutkan.', life: 5000 });
            return;
        }
    }

    if (targetStep > 2 && showWaliWarning.value) {
        toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Nama Wali tidak boleh sama dengan Nama Ayah / Ibu Kandung (ayah / ibu bukan diisi di wali).', life: 5000 });
        return;
    }

    isSaving.value = true;
    
    router.post('/student/profile/draft', {
        ...form.data(),
        has_guardian: has_guardian.value,
        current_step: activeStep.value
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isSaving.value = false;
            activeStep.value = targetStep;
            if (targetStep === 0) {
                setTimeout(() => {
                    if (map) map.invalidateSize();
                }, 150);
            }
            toast.add({ severity: 'success', summary: 'Sukses', detail: 'Draf biodata berhasil disimpan.', life: 2000 });
        },
        onError: () => {
            isSaving.value = false;
            toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal menyimpan draf.', life: 3000 });
        }
    });
};

const goToStep = (stepIdx) => {
    if (stepIdx !== activeStep.value) {
        saveDraftAndNavigate(stepIdx);
    }
};

const nextStep = () => {
    if (activeStep.value < steps.length - 1) {
        saveDraftAndNavigate(activeStep.value + 1);
    }
};

const prevStep = () => {
    if (activeStep.value > 0) {
        activeStep.value--;
        if (activeStep.value === 0) {
            setTimeout(() => {
                if (map) map.invalidateSize();
            }, 150);
        }
    }
};

const logout = () => {
    if (confirm('Apakah Anda yakin ingin keluar?')) {
        router.post('/logout');
    }
};

const submitForm = () => {
    if (showWaliWarning.value) {
        toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Nama Wali tidak boleh sama dengan Nama Ayah / Ibu Kandung.', life: 5000 });
        return;
    }
    form.post('/student/profile', {
        forceFormData: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Sukses', detail: 'Biodata berhasil disimpan permanen.', life: 3000 });
        }
    });
};
</script>

<style scoped>
.bg-slate-900 {
    background-color: #0f172a;
}
.bg-slate-800 {
    background-color: #1e293b;
}
.text-blue-900 {
    color: #1e3a8a;
}
.text-blue-600 {
    color: #2563eb;
}
.border-circle {
    border-radius: 50%;
}
.gap-4 {
    gap: 1rem;
}
.gap-2 {
    gap: 0.5rem;
}
.gap-3 {
    gap: 0.75rem;
}
.whitespace-nowrap {
    white-space: nowrap;
}
.overflow-x-auto {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}
.overflow-x-auto::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 2px;
}
.form-header {
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    align-items: start !important;
}
@media (min-width: 768px) {
    .form-header {
        flex-direction: row !important;
        align-items: center !important;
    }
}
.form-header-title-wrapper {
    width: 100% !important;
}
@media (min-width: 768px) {
    .form-header-title-wrapper {
        width: auto !important;
    }
}
.form-header-badges {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    gap: 0.5rem !important;
    width: 100% !important;
}
@media (min-width: 768px) {
    .form-header-badges {
        width: auto !important;
        gap: 0.75rem !important;
    }
}
</style>
