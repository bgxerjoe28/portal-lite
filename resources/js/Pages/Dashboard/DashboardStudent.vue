<template>
    <SiswaLayout title="Dashboard Siswa">
        <!-- Tab Bar -->
        <div class="flex gap-2 mb-4 p-1 bg-slate-800 border-round-xl w-max">
            <button @click="activeTab = 'summary'" 
                    :class="[activeTab === 'summary' ? 'bg-blue-600 text-white font-bold shadow-2' : 'text-slate-400 hover:bg-slate-700/50 hover:text-white']" 
                    class="px-4 py-2 border-round-lg border-none cursor-pointer transition-all duration-200 text-sm">
                <i class="pi pi-home mr-2"></i>Beranda & Presensi
            </button>
            <button @click="activeTab = 'biodata'" 
                    :class="[activeTab === 'biodata' ? 'bg-blue-600 text-white font-bold shadow-2' : 'text-slate-400 hover:bg-slate-700/50 hover:text-white']" 
                    class="px-4 py-2 border-round-lg border-none cursor-pointer transition-all duration-200 text-sm">
                <i class="pi pi-user-edit mr-2"></i>Lengkapi Biodata
            </button>
        </div>

        <!-- TAB 1: SUMMARY & ATTENDANCE -->
        <div v-show="activeTab === 'summary'">
            <div class="surface-card p-3 border-round-xl shadow-2 mb-4 bg-gradient-to-r from-blue-700 to-indigo-900 text-white">
                <div class="flex align-items-center gap-3 mb-4">
                    <Avatar icon="pi pi-user" size="xlarge" shape="circle" class="bg-white-alpha-20" />
                    <div class="flex flex-column">
                        <span class="text-sm opacity-80">Halo,</span>
                        <span class="text-xl font-bold line-height-2">{{ $page.props.auth.user.name }}</span>
                        <div class="flex align-items-center gap-2 mt-1">
                            <Tag :value="$page.props.auth.user.student?.classrooms?.[0]?.name ?? 'Kelas -'" 
                                 severity="info" class="bg-white-alpha-20 border-none" />
                            <small class="opacity-70">NISN: {{ $page.props.auth.user.student?.nisn }}</small>
                        </div>
                    </div>
                </div>
                
                <div class="p-3 bg-white-alpha-10 border-round-lg flex justify-content-between align-items-center">
                    <div class="flex flex-column">
                        <span class="text-xs opacity-70 uppercase font-bold tracking-wider">Waktu Sekarang</span>
                        <span class="text-2xl font-mono font-bold">{{ liveTime }}</span>
                    </div>
                    <div class="text-right">
                        <span class="block text-sm font-semibold">{{ serverDate }}</span>
                        <small class="opacity-70">Siswa Aktif</small>
                    </div>
                </div>
            </div>

            <div class="surface-card p-3 border-round-xl shadow-2 mb-4">
                <div v-if="hasCheckOut" class="flex align-items-center gap-3 p-3 bg-blue-50 border-round-lg border border-blue-100">
                    <div class="flex align-items-center justify-content-center bg-blue-500 border-round-circle w-3rem h-3rem">
                        <i class="pi pi-home text-white text-xl"></i>
                    </div>
                    <div class="flex flex-column">
                        <span class="text-blue-900 font-bold">Presensi Hari Ini Selesai</span>
                        <small class="text-blue-600 font-medium">Pulang pukul {{ todayAttendance.clock_out }}</small>
                    </div>
                </div>

                <div v-else-if="props.attendance_status?.can_check_out" class="flex flex-column gap-3">
                    <div v-if="hasCheckIn" class="flex align-items-center gap-2 px-2 py-1 bg-green-50 border-round text-green-700">
                        <i class="pi pi-check text-xs"></i>
                        <small class="font-bold">Masuk: {{ todayAttendance.clock_in }}</small>
                    </div>
                    <Button label="PRESENSI PULANG" icon="pi pi-sign-out" 
                            severity="warning"
                            class="w-full py-3 font-bold shadow-3 border-round-xl bg-orange-500 border-orange-500 text-white" 
                            @click="router.get(route('student.presensi.index'))" raised />
                    <small class="text-center text-500 font-medium">Sesi pulang berakhir: {{ props.attendance_status.info.out_close }}</small>
                </div>

                <div v-else-if="hasCheckIn" class="flex align-items-center gap-3 p-3 bg-green-50 border-round-lg border border-green-100">
                    <div class="flex align-items-center justify-content-center bg-green-500 border-round-circle w-3rem h-3rem">
                        <i class="pi pi-check-circle text-white text-xl"></i>
                    </div>
                    <div class="flex flex-column">
                        <span class="text-green-800 font-bold">Sudah Presensi Masuk</span>
                        <small class="text-green-600">Pukul {{ todayAttendance.clock_in }} • Tunggu sesi pulang</small>
                    </div>
                </div>

                <div v-else-if="props.attendance_status?.can_check_in" class="flex flex-column gap-3">
                    <Button label="PRESENSI MASUK" icon="pi pi-fingerprint" 
                            class="w-full py-3 font-bold shadow-3 border-round-xl" 
                            @click="router.get(route('student.presensi.index'))" raised />
                    <small class="text-center text-500 font-medium">Sesi masuk berakhir: {{ props.attendance_status.info.in_close }}</small>
                </div>

                <div v-else class="flex align-items-center gap-3 p-3 bg-gray-100 border-round-lg border border-200 opacity-70">
                    <i class="pi pi-clock text-500 text-3xl"></i>
                    <div class="flex flex-column">
                        <span class="text-700 font-bold">Sesi Presensi Belum Dibuka</span>
                        <small class="text-500">Cek jadwal presensi Anda hari ini</small>
                    </div>
                </div>
            </div>

            <!-- AKSI CEPAT SISWA: CARD TUGAS SISWA -->
            <div class="mb-4">
                <div class="surface-card p-3 border-round-xl shadow-2 cursor-pointer transition-all hover:shadow-3 active:scale-95 border-left-4 border-indigo-600 bg-indigo-50/60 flex align-items-center justify-content-between" @click="router.get(route('student.assignments.index'))">
                    <div class="flex align-items-center gap-3">
                        <div class="p-3 bg-indigo-600 text-white border-round-xl shadow-1 flex align-items-center justify-content-center">
                            <i class="pi pi-file-edit text-2xl"></i>
                        </div>
                        <div>
                            <span class="block text-lg font-bold text-indigo-950">Tugas Siswa</span>
                            <small class="text-indigo-700 font-semibold">Lihat pekerjaan rumah & penugasan mata pelajaran</small>
                        </div>
                    </div>
                    <div class="flex align-items-center gap-2">
                        <Tag 
                            v-if="props.pendingAssignmentsCount > 0" 
                            :value="props.pendingAssignmentsCount + ' Belum Selesai'" 
                            severity="danger" 
                            class="font-bold px-3 py-2 text-xs shadow-1" 
                        />
                        <Tag 
                            v-else 
                            value="Semua Tugas Selesai" 
                            severity="success" 
                            class="font-bold px-3 py-2 text-xs" 
                        />
                        <i class="pi pi-chevron-right text-indigo-400 font-bold ml-1 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h3 class="text-900 font-bold mb-3 flex align-items-center gap-2 px-1">
                    <i class="pi pi-calendar-clock text-primary"></i>
                    Jadwal Pelajaran
                </h3>
                
                <div v-if="schedules.length > 0" class="flex flex-column gap-2">
                    <div v-for="item in schedules" :key="item.id" 
                         class="surface-card p-3 border-round-xl shadow-1 flex justify-content-between align-items-center border-left-3 border-primary">
                        <div class="flex flex-column gap-1">
                            <div class="flex align-items-center gap-2">
                                <span class="font-bold text-900">{{ item.subject?.name }}</span>
                                <Tag v-if="item.active_assignment?.has_submitted" value="Tugas Selesai" severity="success" class="text-xs" />
                            </div>
                            <small class="text-600 flex align-items-center gap-1">
                                <i class="pi pi-user text-xs"></i> {{ item.teacher?.name }}
                            </small>
                            <!-- Tombol Ada Tugas -->
                            <div v-if="item.active_assignment" class="mt-2">
                                <Button 
                                    :label="item.active_assignment.has_submitted ? 'Lihat Tugas Saya' : 'ADA TUGAS'" 
                                    :icon="item.active_assignment.has_submitted ? 'pi pi-check-circle' : 'pi pi-file-edit'" 
                                    :severity="item.active_assignment.has_submitted ? 'secondary' : 'warn'"
                                    size="small" 
                                    raised 
                                    class="font-bold"
                                    @click="router.get(route('student.assignments.show', item.active_assignment.id))" 
                                />
                                <small class="block text-500 text-xs mt-1">Batas Waktu: {{ item.active_assignment.due_at }}</small>
                            </div>
                        </div>

                        <div class="flex flex-column align-items-end">
                            <template v-for="detail in item.details" :key="detail.id">
                                <Tag severity="secondary" rounded class="px-2">
                                    <span class="text-xs font-bold">Jam {{ detail.start_slot }}-{{ detail.end_slot }}</span>
                                </Tag>
                                <small class="text-400 text-xs mt-1">{{ detail.duration }} JP</small>
                            </template>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center p-5 surface-100 border-round-xl border-dashed border-2 text-500">
                    Belum ada jadwal hari ini.
                </div>
            </div>

            <div class="grid grid-nogutter gap-3">
                <div class="col surface-card p-3 border-round-xl shadow-1 text-center border-bottom-3 border-green-500">
                    <span class="block text-xl font-bold text-900">{{ stats.H }}</span>
                    <span class="text-xs text-500 font-bold uppercase">Hadir</span>
                </div>
                <div class="col surface-card p-3 border-round-xl shadow-1 text-center border-bottom-3 border-red-500">
                    <span class="block text-xl font-bold text-900">{{ stats.A }}</span>
                    <span class="text-xs text-500 font-bold uppercase">Alfa</span>
                </div>
                <div class="col surface-card p-3 border-round-xl shadow-1 text-center border-bottom-3 border-blue-500">
                    <span class="block text-xl font-bold text-900">{{ stats.S }}</span>
                    <span class="text-xs text-500 font-bold uppercase">Sakit</span>
                </div>
                <div class="col surface-card p-3 border-round-xl shadow-1 text-center border-bottom-3 border-yellow-500">
                    <span class="block text-xl font-bold text-900">{{ stats.I }}</span>
                    <span class="text-xs text-500 font-bold uppercase">Izin</span>
                </div>
                <div class="col surface-card p-3 border-round-xl shadow-1 text-center border-bottom-3 border-orange-500">
                    <span class="block text-xl font-bold text-900">{{ stats.T }}</span>
                    <span class="text-xs text-500 font-bold uppercase">Terlambat</span>
                </div>
            </div>
        </div>

        <!-- TAB 2: BIODATA FORM (WIZARD STEPS) -->
        <div v-show="activeTab === 'biodata'">
            <div class="surface-card p-4 border-round-xl shadow-2 mb-4">
                
                <!-- Step Indicators -->
                <div class="flex justify-content-between align-items-center mb-6 overflow-x-auto pb-3 gap-4 border-bottom-1 border-100">
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

                <Message severity="info" class="mb-4" :closable="false" v-if="activeStep < 5">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-info-circle font-bold"></i>
                        <span>Kolom kosong akan otomatis diisi apabila ditemukan data kependudukan pendaftaran ulang Anda.</span>
                    </div>
                </Message>

                <form @submit.prevent="submitBiodata" class="p-fluid" novalidate>
                    
                    <!-- STEP 1: DATA PRIBADI -->
                    <div v-show="activeStep === 0">
                        <h3 class="text-blue-700 font-bold mb-4 border-bottom-1 border-100 pb-2">DATA PRIBADI</h3>
                        <div class="grid">
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Nama Lengkap</label>
                                <InputText v-model="form.full_name" class="w-full bg-slate-100 text-500" disabled />
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-2">Jenis Kelamin</label>
                                <div class="flex gap-4 mt-2">
                                    <div class="flex align-items-center">
                                        <RadioButton v-model="form.gender" :value="true" inputId="gender-l" disabled />
                                        <label for="gender-l" class="ml-2 text-500">Laki-laki</label>
                                    </div>
                                    <div class="flex align-items-center">
                                        <RadioButton v-model="form.gender" :value="false" inputId="gender-p" disabled />
                                        <label for="gender-p" class="ml-2 text-500">Perempuan</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">NISN</label>
                                <InputText v-model="form.nisn" class="w-full bg-slate-100 text-500" disabled />
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">NIK (Nomor Induk Kependudukan)</label>
                                <div class="flex">
                                    <InputText :type="showNik ? 'text' : 'password'" v-model="form.nik" class="w-full bg-slate-100 text-500 border-top-right-none border-bottom-right-none" disabled />
                                    <Button :icon="showNik ? 'pi pi-eye-slash' : 'pi pi-eye'" class="p-button-secondary border-top-left-none border-bottom-left-none" @click="showNik = !showNik" type="button" style="border-left: none;" />
                                </div>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Nomor Kartu Keluarga</label>
                                <InputText :value="maskedNoKk" class="w-full bg-slate-100 text-500" disabled />
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Nomor Registrasi Akta Lahir</label>
                                <InputText v-model="form.akta_no" class="w-full" :class="{'p-invalid': form.errors.akta_no}" />
                                <small v-if="form.errors.akta_no" class="p-error">{{ form.errors.akta_no }}</small>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Tempat Lahir <span class="text-red-500">*</span></label>
                                <InputText v-model="form.birth_place" class="w-full" :class="{'p-invalid': form.errors.birth_place}" />
                                <small v-if="form.errors.birth_place" class="p-error">{{ form.errors.birth_place }}</small>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Tanggal Lahir <span class="text-red-500">*</span></label>
                                <input type="date" v-model="form.birth_date" class="p-inputtext w-full" :class="{'p-invalid': form.errors.birth_date}" />
                                <small v-if="form.errors.birth_date" class="p-error">{{ form.errors.birth_date }}</small>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Agama <span class="text-red-500">*</span></label>
                                <Select v-model="form.religion_id" :options="religions" optionLabel="name" optionValue="id" placeholder="Pilih Agama" class="w-full" :class="{'p-invalid': form.errors.religion_id}" />
                                <small v-if="form.errors.religion_id" class="p-error">{{ form.errors.religion_id }}</small>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Kewarganegaraan <span class="text-red-500">*</span></label>
                                <Select v-model="form.citizenship" :options="['WNI', 'WNA']" class="w-full" :class="{'p-invalid': form.errors.citizenship}" />
                                <small v-if="form.errors.citizenship" class="p-error">{{ form.errors.citizenship }}</small>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Kebutuhan Khusus</label>
                                <InputText v-model="form.special_needs" class="w-full" />
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">No. Telpon / WA Siswa <span class="text-red-500">*</span></label>
                                <input :value="form.phone" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.phone = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': form.errors.phone}" />
                                <small v-if="form.errors.phone" class="p-error">{{ form.errors.phone }}</small>
                            </div>

                            <div class="col-12 field mb-3">
                                <label class="block font-semibold mb-1">Alamat Jalan <span class="text-red-500">*</span></label>
                                <Textarea v-model="form.address" rows="3" class="w-full" :class="{'p-invalid': form.errors.address}" />
                                <small v-if="form.errors.address" class="p-error">{{ form.errors.address }}</small>
                            </div>
                            <div class="col-6 md:col-3 field mb-3">
                                <label class="block font-semibold mb-1">RT <span class="text-red-500">*</span></label>
                                <input :value="form.rt" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.rt = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': form.errors.rt}" />
                                <small v-if="form.errors.rt" class="p-error">{{ form.errors.rt }}</small>
                            </div>
                            <div class="col-6 md:col-3 field mb-3">
                                <label class="block font-semibold mb-1">RW <span class="text-red-500">*</span></label>
                                <input :value="form.rw" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.rw = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': form.errors.rw}" />
                                <small v-if="form.errors.rw" class="p-error">{{ form.errors.rw }}</small>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Nama Dusun</label>
                                <InputText v-model="form.dusun" class="w-full" />
                            </div>
                            <div class="col-12 md:col-4 field mb-3">
                                <label class="block font-semibold mb-1">Kelurahan / Desa <span class="text-red-500">*</span></label>
                                <InputText v-model="form.kelurahan" class="w-full" :class="{'p-invalid': form.errors.kelurahan}" />
                                <small v-if="form.errors.kelurahan" class="p-error">{{ form.errors.kelurahan }}</small>
                            </div>
                            <div class="col-12 md:col-4 field mb-3">
                                <label class="block font-semibold mb-1">Kecamatan <span class="text-red-500">*</span></label>
                                <InputText v-model="form.kecamatan" class="w-full" :class="{'p-invalid': form.errors.kecamatan}" />
                                <small v-if="form.errors.kecamatan" class="p-error">{{ form.errors.kecamatan }}</small>
                            </div>
                            <div class="col-12 md:col-4 field mb-3">
                                <label class="block font-semibold mb-1">Kode Pos <span class="text-red-500">*</span></label>
                                <InputText v-model="form.postal_code" class="w-full" :class="{'p-invalid': form.errors.postal_code}" />
                                <small v-if="form.errors.postal_code" class="p-error">{{ form.errors.postal_code }}</small>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Jenis Tempat Tinggal</label>
                                <Select v-model="form.residence_type" :options="residenceOptions" placeholder="Pilih jenis tempat tinggal" class="w-full" />
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Moda Transportasi ke Sekolah</label>
                                <Select v-model="form.transportation" :options="transportationOptions" placeholder="Pilih moda transportasi" class="w-full" />
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Anak Ke (sesuai KK)</label>
                                <input type="number" v-model="form.child_order" min="1" class="p-inputtext w-full" />
                            </div>

                            <!-- ASAL SEKOLAH -->
                            <div class="col-12 mb-2">
                                <h4 class="text-blue-900 font-bold mb-2 mt-2">SEKOLAH ASAL</h4>
                            </div>
                            <div class="col-12 md:col-4 field mb-3">
                                <label class="block font-semibold mb-1">Pilihan Jenjang</label>
                                <Select v-model="form.prev_school_type" :options="['SMP', 'MTS']" placeholder="Pilih Jenjang" class="w-full" />
                            </div>
                            <div class="col-12 md:col-4 field mb-3">
                                <label class="block font-semibold mb-1">Negeri / Swasta</label>
                                <Select v-model="form.prev_school_status" :options="['Negeri', 'Swasta']" placeholder="Pilih Status" class="w-full" />
                            </div>
                            <div class="col-12 md:col-4 field mb-3">
                                <label class="block font-semibold mb-1">Nama Sekolah Asal</label>
                                <InputText v-model="form.prev_school_name" placeholder="Contoh: SMP NEGERI 1 JAKARTA" class="w-full" />
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: DATA ORANG TUA -->
                    <div v-show="activeStep === 1">
                        <h3 class="text-blue-700 font-bold mb-4 border-bottom-1 border-100 pb-2">DATA ORANG TUA KANDUNG</h3>
                        <div class="grid">
                            <!-- AYAH -->
                            <div class="col-12 mb-2">
                                <h4 class="text-blue-900 font-bold mb-2">DATA AYAH KANDUNG</h4>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Nama Lengkap Ayah <span class="text-red-500">*</span></label>
                                <InputText v-model="form.father_name" class="w-full" :class="{'p-invalid': form.errors.father_name}" />
                                <small v-if="form.errors.father_name" class="p-error">{{ form.errors.father_name }}</small>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-2">Status Hidup Ayah <span class="text-red-500">*</span></label>
                                <div class="flex gap-4 mt-2">
                                    <div class="flex align-items-center">
                                        <RadioButton v-model="form.father_deceased" :value="false" inputId="father-alive" />
                                        <label for="father-alive" class="ml-2">Masih Hidup</label>
                                    </div>
                                    <div class="flex align-items-center">
                                        <RadioButton v-model="form.father_deceased" :value="true" inputId="father-deceased" />
                                        <label for="father-deceased" class="ml-2">Sudah Meninggal</label>
                                    </div>
                                </div>
                            </div>

                            <template v-if="!form.father_deceased">
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">NIK Ayah</label>
                                    <InputText :value="maskedFatherNik" class="w-full bg-slate-100 text-500" disabled />
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">Tahun Lahir Ayah <span class="text-red-500">*</span></label>
                                    <input type="number" v-model="form.father_birth_year" placeholder="Contoh: 1975" class="p-inputtext w-full" :class="{'p-invalid': form.errors.father_birth_year}" />
                                    <small v-if="form.errors.father_birth_year" class="p-error">{{ form.errors.father_birth_year }}</small>
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">Pendidikan Terakhir Ayah <span class="text-red-500">*</span></label>
                                    <Select v-model="form.father_education" :options="educationOptions" placeholder="Pilih Pendidikan" class="w-full" :class="{'p-invalid': form.errors.father_education}" />
                                    <small v-if="form.errors.father_education" class="p-error">{{ form.errors.father_education }}</small>
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">Pekerjaan Ayah <span class="text-red-500">*</span></label>
                                    <Select v-model="form.father_job" :options="jobOptions" placeholder="Pilih Pekerjaan" class="w-full" :class="{'p-invalid': form.errors.father_job}" />
                                    <small v-if="form.errors.father_job" class="p-error">{{ form.errors.father_job }}</small>
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">Penghasilan Bulanan Ayah <span class="text-red-500">*</span></label>
                                    <Select v-model="form.father_income" :options="incomeOptions" placeholder="Pilih Penghasilan" class="w-full" :class="{'p-invalid': form.errors.father_income}" />
                                    <small v-if="form.errors.father_income" class="p-error">{{ form.errors.father_income }}</small>
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">No. Telpon / WA Ayah <span class="text-red-500">*</span></label>
                                    <input :value="form.father_phone" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.father_phone = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': form.errors.father_phone}" />
                                    <small v-if="form.errors.father_phone" class="p-error">{{ form.errors.father_phone }}</small>
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">Kebutuhan Khusus Ayah</label>
                                    <InputText v-model="form.father_special_needs" class="w-full" />
                                </div>
                            </template>

                            <!-- IBU -->
                            <div class="col-12 mb-2 mt-4 border-top-1 border-100 pt-4">
                                <h4 class="text-blue-900 font-bold mb-2">DATA IBU KANDUNG</h4>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Nama Lengkap Ibu <span class="text-red-500">*</span></label>
                                <InputText v-model="form.mother_name" class="w-full" :class="{'p-invalid': form.errors.mother_name}" />
                                <small v-if="form.errors.mother_name" class="p-error">{{ form.errors.mother_name }}</small>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-2">Status Hidup Ibu <span class="text-red-500">*</span></label>
                                <div class="flex gap-4 mt-2">
                                    <div class="flex align-items-center">
                                        <RadioButton v-model="form.mother_deceased" :value="false" inputId="mother-alive" />
                                        <label for="mother-alive" class="ml-2">Masih Hidup</label>
                                    </div>
                                    <div class="flex align-items-center">
                                        <RadioButton v-model="form.mother_deceased" :value="true" inputId="mother-deceased" />
                                        <label for="mother-deceased" class="ml-2">Sudah Meninggal</label>
                                    </div>
                                </div>
                            </div>

                            <template v-if="!form.mother_deceased">
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">NIK Ibu</label>
                                    <InputText :value="maskedMotherNik" class="w-full bg-slate-100 text-500" disabled />
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">Tahun Lahir Ibu <span class="text-red-500">*</span></label>
                                    <input type="number" v-model="form.mother_birth_year" placeholder="Contoh: 1980" class="p-inputtext w-full" :class="{'p-invalid': form.errors.mother_birth_year}" />
                                    <small v-if="form.errors.mother_birth_year" class="p-error">{{ form.errors.mother_birth_year }}</small>
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">Pendidikan Terakhir Ibu <span class="text-red-500">*</span></label>
                                    <Select v-model="form.mother_education" :options="educationOptions" placeholder="Pilih Pendidikan" class="w-full" :class="{'p-invalid': form.errors.mother_education}" />
                                    <small v-if="form.errors.mother_education" class="p-error">{{ form.errors.mother_education }}</small>
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">Pekerjaan Ibu <span class="text-red-500">*</span></label>
                                    <Select v-model="form.mother_job" :options="jobOptions" placeholder="Pilih Pekerjaan" class="w-full" :class="{'p-invalid': form.errors.mother_job}" />
                                    <small v-if="form.errors.mother_job" class="p-error">{{ form.errors.mother_job }}</small>
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">Penghasilan Bulanan Ibu <span class="text-red-500">*</span></label>
                                    <Select v-model="form.mother_income" :options="incomeOptions" placeholder="Pilih Penghasilan" class="w-full" :class="{'p-invalid': form.errors.mother_income}" />
                                    <small v-if="form.errors.mother_income" class="p-error">{{ form.errors.mother_income }}</small>
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">No. Telpon / WA Ibu <span class="text-red-500">*</span></label>
                                    <input :value="form.mother_phone" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.mother_phone = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': form.errors.mother_phone}" />
                                    <small v-if="form.errors.mother_phone" class="p-error">{{ form.errors.mother_phone }}</small>
                                </div>
                                <div class="col-12 md:col-6 field mb-3">
                                    <label class="block font-semibold mb-1">Kebutuhan Khusus Ibu</label>
                                    <InputText v-model="form.mother_special_needs" class="w-full" />
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- STEP 3: DATA WALI -->
                    <div v-show="activeStep === 2">
                        <h3 class="text-blue-700 font-bold mb-4 border-bottom-1 border-100 pb-2">DATA WALI (OPSIONAL)</h3>
                        <div class="mb-4 flex align-items-center">
                            <Checkbox v-model="has_guardian" :binary="true" inputId="check-guardian" :disabled="isGuardianDisabled" />
                            <label for="check-guardian" class="ml-2 font-semibold cursor-pointer" :class="{'text-500': isGuardianDisabled}">Siswa memiliki wali (tidak tinggal bersama orang tua)</label>
                        </div>

                        <div class="grid" v-if="has_guardian">
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Nama Lengkap Wali <span v-if="!isGuardianDisabled" class="text-red-500">*</span></label>
                                <InputText v-model="form.guardian_name" class="w-full" :class="{'p-invalid': form.errors.guardian_name, 'bg-slate-100 text-500': isGuardianDisabled}" :disabled="isGuardianDisabled" />
                                <small v-if="form.errors.guardian_name && !isGuardianDisabled" class="p-error">{{ form.errors.guardian_name }}</small>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">NIK Wali</label>
                                <InputText v-if="isGuardianDisabled" :value="maskedGuardianNik" class="w-full bg-slate-100 text-500" disabled />
                                <input v-else :value="form.guardian_nik" @input="e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 16); form.guardian_nik = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': form.errors.guardian_nik}" placeholder="Masukkan NIK Wali jika ada" />
                                <small v-if="form.errors.guardian_nik && !isGuardianDisabled" class="p-error">{{ form.errors.guardian_nik }}</small>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Tahun Lahir Wali</label>
                                <input type="number" v-model="form.guardian_birth_year" placeholder="Contoh: 1980" class="p-inputtext w-full" :class="{'bg-slate-100 text-500': isGuardianDisabled}" :disabled="isGuardianDisabled" />
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Pendidikan Terakhir Wali</label>
                                <Select v-model="form.guardian_education" :options="educationOptions" placeholder="Pilih Pendidikan" class="w-full" :class="{'bg-slate-100 text-500': isGuardianDisabled}" :disabled="isGuardianDisabled" />
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Pekerjaan Wali</label>
                                <Select v-model="form.guardian_job" :options="jobOptions" placeholder="Pilih Pekerjaan" class="w-full" :class="{'bg-slate-100 text-500': isGuardianDisabled}" :disabled="isGuardianDisabled" />
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Penghasilan Bulanan Wali</label>
                                <Select v-model="form.guardian_income" :options="incomeOptions" placeholder="Pilih Penghasilan" class="w-full" :class="{'bg-slate-100 text-500': isGuardianDisabled}" :disabled="isGuardianDisabled" />
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">No. Telpon / WA Wali <span v-if="!isGuardianDisabled" class="text-red-500">*</span></label>
                                <input :value="form.guardian_phone" @input="e => { e.target.value = e.target.value.replace(/\D/g, ''); form.guardian_phone = e.target.value; }" class="p-inputtext p-component w-full" :class="{'p-invalid': form.errors.guardian_phone, 'bg-slate-100 text-500': isGuardianDisabled}" :disabled="isGuardianDisabled" />
                                <small v-if="form.errors.guardian_phone && !isGuardianDisabled" class="p-error">{{ form.errors.guardian_phone }}</small>
                            </div>
                        </div>
                        <div v-else class="text-center p-5 surface-100 border-round-xl border-dashed border-2 text-500">
                            Wali opsional. Centang pilihan di atas jika Anda tinggal bersama wali.
                        </div>
                    </div>

                    <!-- STEP 4: DATA PERIODIK -->
                    <div v-show="activeStep === 3">
                        <h3 class="text-blue-700 font-bold mb-4 border-bottom-1 border-100 pb-2">DATA PERIODIK SISWA</h3>
                        <div class="grid">
                            <div class="col-12 md:col-4 field mb-3">
                                <label class="block font-semibold mb-1">Tinggi Badan (cm) <span class="text-red-500">*</span></label>
                                <input type="number" v-model="form.height" placeholder="Contoh: 165" class="p-inputtext w-full" :class="{'p-invalid': form.errors.height}" />
                                <small v-if="form.errors.height" class="p-error">{{ form.errors.height }}</small>
                            </div>
                            <div class="col-12 md:col-4 field mb-3">
                                <label class="block font-semibold mb-1">Berat Badan (kg) <span class="text-red-500">*</span></label>
                                <input type="number" v-model="form.weight" placeholder="Contoh: 55" class="p-inputtext w-full" :class="{'p-invalid': form.errors.weight}" />
                                <small v-if="form.errors.weight" class="p-error">{{ form.errors.weight }}</small>
                            </div>
                            <div class="col-12 md:col-4 field mb-3">
                                <label class="block font-semibold mb-1">Lingkar Kepala (cm) <span class="text-red-500">*</span></label>
                                <input type="number" v-model="form.head_circumference" placeholder="Contoh: 54" class="p-inputtext w-full" :class="{'p-invalid': form.errors.head_circumference}" />
                                <small v-if="form.errors.head_circumference" class="p-error">{{ form.errors.head_circumference }}</small>
                            </div>
                            <div class="col-12 md:col-4 field mb-3">
                                <label class="block font-semibold mb-1">Jarak ke Sekolah (km) <span class="text-red-500">*</span></label>
                                <div class="flex">
                                    <input type="number" step="0.1" v-model="form.distance_to_school_km" placeholder="Contoh: 1.5" class="p-inputtext w-full border-top-right-none border-bottom-right-none bg-slate-100 text-500" disabled />
                                    <Button icon="pi pi-map-marker" class="p-button-warning border-top-left-none border-bottom-left-none" @click="openMapModal" type="button" />
                                </div>
                                <small v-if="form.errors.distance_to_school_km" class="p-error">{{ form.errors.distance_to_school_km }}</small>
                            </div>
                            <div class="col-12 md:col-4 field mb-3">
                                <label class="block font-semibold mb-1">Waktu Tempuh ke Sekolah (menit) <span class="text-red-500">*</span></label>
                                <input type="number" v-model="form.travel_time_minutes" placeholder="Contoh: 15" class="p-inputtext w-full" :class="{'p-invalid': form.errors.travel_time_minutes}" />
                                <small v-if="form.errors.travel_time_minutes" class="p-error">{{ form.errors.travel_time_minutes }}</small>
                            </div>
                            <div class="col-12 md:col-4 field mb-3">
                                <label class="block font-semibold mb-1">Jumlah Saudara Kandung <span class="text-red-500">*</span></label>
                                <input type="number" v-model="form.sibling_count" placeholder="Contoh: 2" class="p-inputtext w-full" :class="{'p-invalid': form.errors.sibling_count}" />
                                <small v-if="form.errors.sibling_count" class="p-error">{{ form.errors.sibling_count }}</small>
                            </div>
                            <div class="col-12 md:col-6 field mb-3">
                                <label class="block font-semibold mb-1">Email Kontak Rincian / Periodik</label>
                                <InputText type="email" v-model="form.periodik_phone" placeholder="Contoh: email@domain.com" class="w-full" />
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5: UPLOAD BERKAS -->
                    <div v-show="activeStep === 4">
                        <h3 class="text-blue-700 font-bold mb-4 border-bottom-1 border-100 pb-2">UPLOAD DOKUMEN PENDUKUNG</h3>
                        <div class="grid">
                            <!-- KK -->
                            <div class="col-12 md:col-6 field mb-4">
                                <label class="block font-semibold mb-1">Kartu Keluarga (KK)</label>
                                <input type="file" @change="onFileChange($event, 'file_kk')" accept="image/*,application/pdf" class="w-full p-inputtext" />
                                <div v-if="props.student?.file_kk" class="mt-2 text-sm">
                                    <a :href="`/storage/${props.student.file_kk}`" target="_blank" class="text-blue-500 hover:underline inline-flex align-items-center gap-1">
                                        <i class="pi pi-external-link"></i> Lihat berkas KK terunggah
                                    </a>
                                </div>
                            </div>
                            <!-- AKTA -->
                            <div class="col-12 md:col-6 field mb-4">
                                <label class="block font-semibold mb-1">Akta Kelahiran</label>
                                <input type="file" @change="onFileChange($event, 'file_akta')" accept="image/*,application/pdf" class="w-full p-inputtext" />
                                <div v-if="props.student?.file_akta" class="mt-2 text-sm">
                                    <a :href="`/storage/${props.student.file_akta}`" target="_blank" class="text-blue-500 hover:underline inline-flex align-items-center gap-1">
                                        <i class="pi pi-external-link"></i> Lihat berkas Akta terunggah
                                    </a>
                                </div>
                            </div>
                            <!-- IJAZAH -->
                            <div class="col-12 md:col-6 field mb-4">
                                <label class="block font-semibold mb-1">Ijazah / Surat Keterangan Lulus</label>
                                <input type="file" @change="onFileChange($event, 'file_ijazah')" accept="image/*,application/pdf" class="w-full p-inputtext" />
                                <div v-if="props.student?.file_ijazah" class="mt-2 text-sm">
                                    <a :href="`/storage/${props.student.file_ijazah}`" target="_blank" class="text-blue-500 hover:underline inline-flex align-items-center gap-1">
                                        <i class="pi pi-external-link"></i> Lihat berkas Ijazah terunggah
                                    </a>
                                </div>
                            </div>
                            <!-- FOTO -->
                            <div class="col-12 md:col-6 field mb-4">
                                <label class="block font-semibold mb-1">Pas Foto</label>
                                <input type="file" @change="onFileChange($event, 'file_foto')" accept="image/*,application/pdf" class="w-full p-inputtext" />
                                <div v-if="props.student?.file_foto" class="mt-2 text-sm">
                                    <a :href="`/storage/${props.student.file_foto}`" target="_blank" class="text-blue-500 hover:underline inline-flex align-items-center gap-1">
                                        <i class="pi pi-external-link"></i> Lihat pas foto terunggah
                                    </a>
                                </div>
                            </div>
                            <!-- LAINNYA -->
                            <div class="col-12 md:col-6 field mb-4">
                                <label class="block font-semibold mb-1">Berkas Pendukung Lain (KIP/PKH/Prestasi)</label>
                                <input type="file" @change="onFileChange($event, 'file_other')" accept="image/*,application/pdf" class="w-full p-inputtext" />
                                <div v-if="props.student?.file_other" class="mt-2 text-sm">
                                    <a :href="`/storage/${props.student.file_other}`" target="_blank" class="text-blue-500 hover:underline inline-flex align-items-center gap-1">
                                        <i class="pi pi-external-link"></i> Lihat berkas pendukung terunggah
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 6: RINGKASAN & KONFIRMASI -->
                    <div v-show="activeStep === 5">
                        <h3 class="text-blue-700 font-bold mb-2 border-bottom-1 border-100 pb-2">KONFIRMASI AKHIR</h3>
                        <p class="text-sm text-600 mb-4">Harap periksa kembali seluruh data yang Anda masukkan sebelum melakukan penyimpanan akhir.</p>

                        <div class="border border-200 border-round overflow-hidden shadow-2 bg-50 p-4 mb-4 text-slate-800">
                            <!-- 1. Data Pribadi -->
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
                                    <div class="col-6 md:col-3 font-semibold text-600">Agama:</div>
                                    <div class="col-6 md:col-3">{{ getReligionName(form.religion_id) }}</div>
                                    <div class="col-6 md:col-3 font-semibold text-600">No. WA / Phone:</div>
                                    <div class="col-6 md:col-9">{{ form.phone }}</div>
                                    <div class="col-6 md:col-3 font-semibold text-600">Sekolah Asal:</div>
                                    <div class="col-6 md:col-9">{{ form.prev_school_type }} {{ form.prev_school_status }} {{ form.prev_school_name }}</div>
                                </div>
                            </div>

                            <!-- 2. Data Orang Tua -->
                            <div class="mb-4">
                                <h4 class="text-blue-700 font-bold m-0 mb-2 border-bottom-1 border-100 pb-1">2. DATA ORANG TUA</h4>
                                <div class="grid text-sm">
                                    <div class="col-6 md:col-3 font-semibold text-600">Nama Ayah:</div>
                                    <div class="col-6 md:col-3">{{ form.father_name }}</div>
                                    <div class="col-6 md:col-3 font-semibold text-600">Nama Ibu:</div>
                                    <div class="col-6 md:col-3">{{ form.mother_name }}</div>
                                    <div class="col-6 md:col-3 font-semibold text-600">No WA Ayah / Ibu:</div>
                                    <div class="col-6 md:col-9">{{ form.father_phone || '-' }} / {{ form.mother_phone || '-' }}</div>
                                </div>
                            </div>

                            <!-- 3. Data Wali -->
                            <div class="mb-4" v-if="has_guardian">
                                <h4 class="text-blue-700 font-bold m-0 mb-2 border-bottom-1 border-100 pb-1">3. DATA WALI</h4>
                                <div class="grid text-sm">
                                    <div class="col-6 md:col-3 font-semibold text-600">Nama Wali:</div>
                                    <div class="col-6 md:col-9">{{ form.guardian_name }}</div>
                                    <div class="col-6 md:col-3 font-semibold text-600">No WA Wali:</div>
                                    <div class="col-6 md:col-9">{{ form.guardian_phone }}</div>
                                </div>
                            </div>

                            <!-- 4. Data Periodik -->
                            <div>
                                <h4 class="text-blue-700 font-bold m-0 mb-2 border-bottom-1 border-100 pb-1">4. DATA PERIODIK</h4>
                                <div class="grid text-sm">
                                    <div class="col-6 md:col-3 font-semibold text-600">Tinggi / Berat:</div>
                                    <div class="col-6 md:col-3">{{ form.height }} cm / {{ form.weight }} kg</div>
                                    <div class="col-6 md:col-3 font-semibold text-600">Jarak / Waktu Tempuh:</div>
                                    <div class="col-6 md:col-3">{{ form.distance_to_school_km }} km / {{ form.travel_time_minutes }} menit</div>
                                </div>
                            </div>
                        </div>

                        <!-- Agreement Checkbox -->
                        <div class="flex align-items-center mb-4 mt-4 bg-yellow-50 p-3 border border-yellow-200 border-round-lg text-slate-800">
                            <input type="checkbox" id="agree" v-model="agree" class="cursor-pointer mr-2 w-5 h-5" required />
                            <label for="agree" class="text-sm text-800 cursor-pointer">Dengan ini saya menyatakan bahwa data biodata yang saya masukkan adalah benar, valid, dan dapat dipertanggungjawabkan.</label>
                        </div>
                    </div>

                    <!-- NAVIGATION & SUBMIT BUTTONS -->
                    <div class="flex justify-content-between align-items-center mt-6 pt-4 border-top-1 border-100">
                        <Button 
                            type="button" 
                            label="Kembali" 
                            icon="pi pi-chevron-left" 
                            class="p-button-outlined w-max px-4" 
                            :disabled="activeStep === 0" 
                            @click="prevStep" 
                        />
                        
                        <Button 
                            v-if="activeStep < steps.length - 1" 
                            type="button" 
                            label="Lanjutkan" 
                            icon="pi pi-chevron-right" 
                            iconPos="right" 
                            class="bg-blue-600 hover:bg-blue-700 border-none w-max px-4 text-white" 
                            @click="nextStep" 
                        />

                        <Button 
                            v-else 
                            type="submit" 
                            label="SIMPAN BIODATA SISWA" 
                            icon="pi pi-save" 
                            class="bg-green-600 hover:bg-green-700 border-none w-max px-6 text-white" 
                            :loading="form.processing"
                            :disabled="!agree" 
                        />
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL PETA KOORDINAT & JARAK -->
        <div v-if="showMapModal" class="fixed inset-0 z-50 flex align-items-center justify-content-center bg-black-alpha-50 p-4" style="background: rgba(0, 0, 0, 0.5); z-index: 9999;">
            <div class="surface-card border-round-xl shadow-4 max-w-lg w-full overflow-hidden flex flex-column" style="max-width: 550px; background: white;">
                <div class="p-4 bg-blue-700 text-white font-bold flex justify-content-between align-items-center">
                    <span class="text-lg"><i class="pi pi-map-marker mr-2"></i>Tentukan Lokasi Rumah</span>
                    <button @click="showMapModal = false" class="text-white bg-transparent border-none cursor-pointer text-2xl font-bold line-height-1">&times;</button>
                </div>
                <div class="p-4 flex-1">
                    <div class="relative w-full mb-3" style="height: 350px;">
                        <div v-if="isMapLoading" class="absolute inset-0 flex align-items-center justify-content-center bg-gray-100 border-round shadow-2" style="z-index: 10;">
                            <div class="flex flex-column align-items-center">
                                <i class="pi pi-spin pi-spinner text-blue-500" style="font-size: 2.5rem"></i>
                                <span class="mt-2 text-600 font-semibold">Memuat Peta...</span>
                            </div>
                        </div>
                        <div id="map-modal-container" class="border border-300 border-round overflow-hidden shadow-2 w-full h-full"></div>
                    </div>
                    <div class="grid">
                        <div class="col-6 field mb-0">
                            <label class="block text-xs font-semibold mb-1 text-600">Lintang (Latitude)</label>
                            <InputText :value="tempLatitude" readonly class="w-full text-sm bg-gray-100" />
                        </div>
                        <div class="col-6 field mb-0">
                            <label class="block text-xs font-semibold mb-1 text-600">Bujur (Longitude)</label>
                            <InputText :value="tempLongitude" readonly class="w-full text-sm bg-gray-100" />
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-blue-50 border-round-lg text-blue-900 flex align-items-center justify-content-between">
                        <span class="font-semibold text-sm">Estimasi Jarak ke Sekolah:</span>
                        <span class="text-xl font-bold text-blue-700">{{ computedDistance }} km</span>
                    </div>
                </div>
                <div class="p-4 bg-gray-50 border-top-1 border-100 flex justify-content-end gap-2">
                    <Button label="Batal" class="p-button-outlined p-button-secondary border-round-lg text-sm px-4" @click="showMapModal = false" />
                    <Button label="Simpan Lokasi" class="bg-blue-600 hover:bg-blue-700 border-none border-round-lg text-white text-sm px-4" @click="saveLocationFromMap" />
                </div>
            </div>
        </div>
    </SiswaLayout>
</template>

<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import RadioButton from 'primevue/radiobutton';
import Message from 'primevue/message';
import Checkbox from 'primevue/checkbox';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    todayAttendance: Object,
    attendance_status: Object,
    schedules: Array,
    pendingAssignmentsCount: Number,
    stats: Object,
    serverDate: String,
    student: Object,
    religions: Array,
    school_latitude: String,
    school_longitude: String,
});

const showNik = ref(false);

const maskNik = (val) => {
    const str = String(val || '');
    if (!str) return '';
    if (str.length <= 6) return '*'.repeat(str.length);
    return str.substring(0, 6) + '*'.repeat(str.length - 6);
};

const maskedNoKk = computed(() => {
    const val = String(form.no_kk || '');
    if (!val) return '';
    if (val.length <= 5) return '*'.repeat(val.length);
    return val.slice(0, -5) + '*****';
});

const maskedFatherNik = computed(() => maskNik(form.father_nik));
const maskedMotherNik = computed(() => maskNik(form.mother_nik));
const maskedGuardianNik = computed(() => maskNik(form.guardian_nik));

const isGuardianDisabled = computed(() => !!props.student?.guardian_name);

// Map modal states & methods
const showMapModal = ref(false);
const mapModal = ref(null);
const markerModal = ref(null);
const tempLatitude = ref('');
const tempLongitude = ref('');
const isMapLoading = ref(true);

const calculateDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371; // Earth's radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon/2) * dLon/2;
    // Fix standard Haversine formula calculation logic
    const aFixed = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(aFixed), Math.sqrt(1 - aFixed));
    return parseFloat((R * c).toFixed(2));
};

const openMapModal = () => {
    tempLatitude.value = form.latitude || props.school_latitude || '-7.024644700237852';
    tempLongitude.value = form.longitude || props.school_longitude || '110.30857222330609';
    showMapModal.value = true;

    if (!window.L) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => {
            initModalMap();
        };
        document.head.appendChild(script);
    } else {
        setTimeout(() => {
            initModalMap();
        }, 200);
    }
};

const initModalMap = () => {
    const lat = parseFloat(tempLatitude.value);
    const lng = parseFloat(tempLongitude.value);

    if (mapModal.value) {
        mapModal.value.remove();
        mapModal.value = null;
        markerModal.value = null;
    }

    isMapLoading.value = true;
    mapModal.value = L.map('map-modal-container').setView([lat, lng], 15);
    
    const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        crossOrigin: true
    }).addTo(mapModal.value);

    tileLayer.on('load', () => {
        isMapLoading.value = false;
    });

    markerModal.value = L.marker([lat, lng], { draggable: true }).addTo(mapModal.value);

    markerModal.value.on('dragend', () => {
        const pos = markerModal.value.getLatLng();
        tempLatitude.value = pos.lat.toFixed(6);
        tempLongitude.value = pos.lng.toFixed(6);
    });

    mapModal.value.on('click', (e) => {
        const pos = e.latlng;
        markerModal.value.setLatLng(pos);
        tempLatitude.value = pos.lat.toFixed(6);
        tempLongitude.value = pos.lng.toFixed(6);
    });

    setTimeout(() => {
        if (mapModal.value) {
            mapModal.value.invalidateSize();
        }
    }, 200);
};

const computedDistance = computed(() => {
    const lat1 = parseFloat(props.school_latitude) || -7.024644700237852;
    const lon1 = parseFloat(props.school_longitude) || 110.30857222330609;
    const lat2 = parseFloat(tempLatitude.value);
    const lon2 = parseFloat(tempLongitude.value);

    if (isNaN(lat2) || isNaN(lon2)) return 0;
    return calculateDistance(lat1, lon1, lat2, lon2);
});

const saveLocationFromMap = () => {
    form.latitude = tempLatitude.value;
    form.longitude = tempLongitude.value;
    form.distance_to_school_km = computedDistance.value;
    showMapModal.value = false;
};

const activeTab = ref('summary');
const activeStep = ref(0);
const agree = ref(false);
const toast = useToast();
const has_guardian = ref(props.student?.guardian_name ? true : false);

// Steps matching Daftar Ulang
const steps = [
    { label: 'Data Pribadi' },
    { label: 'Orang Tua' },
    { label: 'Wali' },
    { label: 'Periodik' },
    { label: 'Upload Berkas' },
    { label: 'Konfirmasi' }
];

// Dropdown options matching the new design guidelines
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

const filterNumber = (field, maxDigits = null) => {
    let value = String(form[field] || '').replace(/[^0-9]/g, '');
    if (maxDigits !== null) {
        value = value.substring(0, maxDigits);
    }
    form[field] = value;
};

const form = useForm({
    full_name: (props.student?.full_name || '').toUpperCase(),
    gender: props.student?.gender !== null ? props.student?.gender : true,
    nisn: props.student?.nisn || '',
    prev_school_type: props.student?.prev_school_type || '',
    prev_school_status: props.student?.prev_school_status || '',
    prev_school_name: props.student?.prev_school_name || '',
    nik: props.student?.nik || '',
    no_kk: props.student?.no_kk || '',
    birth_place: props.student?.birth_place || '',
    birth_date: props.student?.birth_date ? props.student.birth_date.split('T')[0] : '',
    akta_no: props.student?.akta_no || '',
    religion_id: props.student?.religion_id || '',
    citizenship: props.student?.citizenship || 'WNI',
    special_needs: props.student?.special_needs || '',
    address: props.student?.address || '',
    rt: props.student?.rt || '',
    rw: props.student?.rw || '',
    dusun: props.student?.dusun || '',
    kelurahan: props.student?.kelurahan || '',
    kecamatan: props.student?.kecamatan || '',
    postal_code: props.student?.postal_code || '',
    latitude: props.student?.latitude || '',
    longitude: props.student?.longitude || '',
    residence_type: props.student?.residence_type || '',
    transportation: props.student?.transportation || '',
    child_order: props.student?.child_order || 1,
    phone: props.student?.phone || '',

    // Data Ayah
    father_name: props.student?.father_name || '',
    father_deceased: props.student?.father_deceased === 1 || props.student?.father_deceased === true || false,
    father_nik: props.student?.father_nik || '',
    father_birth_year: props.student?.father_birth_year || '',
    father_education: props.student?.father_education || '',
    father_job: props.student?.father_job || '',
    father_income: props.student?.father_income || '',
    father_special_needs: props.student?.father_special_needs || '',
    father_phone: props.student?.father_phone || '',

    // Data Ibu
    mother_name: props.student?.mother_name || '',
    mother_deceased: props.student?.mother_deceased === 1 || props.student?.mother_deceased === true || false,
    mother_nik: props.student?.mother_nik || '',
    mother_birth_year: props.student?.mother_birth_year || '',
    mother_education: props.student?.mother_education || '',
    mother_job: props.student?.mother_job || '',
    mother_income: props.student?.mother_income || '',
    mother_special_needs: props.student?.mother_special_needs || '',
    mother_phone: props.student?.mother_phone || '',

    // Data Wali
    guardian_name: props.student?.guardian_name || '',
    guardian_nik: props.student?.guardian_nik || '',
    guardian_birth_year: props.student?.guardian_birth_year || '',
    guardian_education: props.student?.guardian_education || '',
    guardian_job: props.student?.guardian_job || '',
    guardian_income: props.student?.guardian_income || '',
    guardian_phone: props.student?.guardian_phone || '',

    // Data Periodik
    height: props.student?.height || '',
    weight: props.student?.weight || '',
    head_circumference: props.student?.head_circumference || '',
    distance_to_school_km: props.student?.distance_to_school_km || '',
    travel_time_minutes: props.student?.travel_time_minutes || '',
    sibling_count: props.student?.sibling_count || '',
    periodik_phone: props.student?.periodik_phone || '',

    // Files
    file_kk: null,
    file_akta: null,
    file_ijazah: null,
    file_foto: null,
    file_other: null,
});

const getReligionName = (id) => {
    const r = props.religions?.find(item => item.id === id);
    return r ? r.name : '-';
};

const validateCurrentStep = () => {
    form.clearErrors();
    const errorsToSet = {};

    if (activeStep.value === 0) {
        if (!form.full_name) errorsToSet.full_name = 'Nama Lengkap wajib diisi.';
        if (!form.nisn) errorsToSet.nisn = 'NISN wajib diisi.';
        else if (form.nisn.length !== 10) errorsToSet.nisn = 'NISN harus 10 digit.';
        if (!form.nik) errorsToSet.nik = 'NIK wajib diisi.';
        else if (form.nik.length !== 16) errorsToSet.nik = 'NIK harus 16 digit.';
        if (!form.no_kk) errorsToSet.no_kk = 'Nomor KK wajib diisi.';
        else if (form.no_kk.length !== 16) errorsToSet.no_kk = 'Nomor KK harus 16 digit.';
        if (!form.birth_place) errorsToSet.birth_place = 'Tempat Lahir wajib diisi.';
        if (!form.birth_date) errorsToSet.birth_date = 'Tanggal Lahir wajib diisi.';
        if (!form.religion_id) errorsToSet.religion_id = 'Agama wajib diisi.';
        if (!form.citizenship) errorsToSet.citizenship = 'Kewarganegaraan wajib diisi.';
        if (!form.address) errorsToSet.address = 'Alamat Jalan wajib diisi.';
        if (!form.rt) errorsToSet.rt = 'RT wajib diisi.';
        if (!form.rw) errorsToSet.rw = 'RW wajib diisi.';
        if (!form.kelurahan) errorsToSet.kelurahan = 'Kelurahan wajib diisi.';
        if (!form.kecamatan) errorsToSet.kecamatan = 'Kecamatan wajib diisi.';
        if (!form.postal_code) errorsToSet.postal_code = 'Kode Pos wajib diisi.';
        if (!form.phone) errorsToSet.phone = 'No. Telpon / WA Siswa wajib diisi.';
    }
    else if (activeStep.value === 1) {
        if (!form.father_name) errorsToSet.father_name = 'Nama Ayah wajib diisi.';
        if (!form.father_deceased) {
            if (!form.father_nik) errorsToSet.father_nik = 'NIK Ayah wajib diisi.';
            else if (form.father_nik.length !== 16) errorsToSet.father_nik = 'NIK Ayah harus 16 digit.';
            if (!form.father_birth_year) errorsToSet.father_birth_year = 'Tahun Lahir Ayah wajib diisi.';
            if (!form.father_education) errorsToSet.father_education = 'Pendidikan Ayah wajib diisi.';
            if (!form.father_job) errorsToSet.father_job = 'Pekerjaan Ayah wajib diisi.';
            if (!form.father_income) errorsToSet.father_income = 'Penghasilan Ayah wajib diisi.';
            if (!form.father_phone) errorsToSet.father_phone = 'No. WA Ayah wajib diisi.';
        }
        if (!form.mother_name) errorsToSet.mother_name = 'Nama Ibu wajib diisi.';
        if (!form.mother_deceased) {
            if (!form.mother_nik) errorsToSet.mother_nik = 'NIK Ibu wajib diisi.';
            else if (form.mother_nik.length !== 16) errorsToSet.mother_nik = 'NIK Ibu harus 16 digit.';
            if (!form.mother_birth_year) errorsToSet.mother_birth_year = 'Tahun Lahir Ibu wajib diisi.';
            if (!form.mother_education) errorsToSet.mother_education = 'Pendidikan Ibu wajib diisi.';
            if (!form.mother_job) errorsToSet.mother_job = 'Pekerjaan Ibu wajib diisi.';
            if (!form.mother_income) errorsToSet.mother_income = 'Penghasilan Ibu wajib diisi.';
            if (!form.mother_phone) errorsToSet.mother_phone = 'No. WA Ibu wajib diisi.';
        }
    }
    else if (activeStep.value === 2) {
        if (has_guardian.value) {
            if (!form.guardian_name) errorsToSet.guardian_name = 'Nama Wali wajib diisi.';
            if (!form.guardian_phone) errorsToSet.guardian_phone = 'No. WA Wali wajib diisi.';
        }
    }
    else if (activeStep.value === 3) {
        if (form.height === null || form.height === undefined || form.height === '') errorsToSet.height = 'Tinggi Badan wajib diisi.';
        if (form.weight === null || form.weight === undefined || form.weight === '') errorsToSet.weight = 'Berat Badan wajib diisi.';
        if (form.head_circumference === null || form.head_circumference === undefined || form.head_circumference === '') errorsToSet.head_circumference = 'Lingkar Kepala wajib diisi.';
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

const prevStep = () => {
    if (activeStep.value > 0) {
        activeStep.value--;
    }
};

const nextStep = () => {
    if (validateCurrentStep()) {
        activeStep.value++;
    } else {
        toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Harap lengkapi semua isian wajib (*) sebelum melanjutkan.', life: 5000 });
    }
};

const goToStep = (step) => {
    if (step < activeStep.value) {
        activeStep.value = step;
    } else {
        if (validateCurrentStep()) {
            activeStep.value = step;
        } else {
            toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Harap lengkapi semua isian wajib (*) sebelum melanjutkan.', life: 5000 });
        }
    }
};

const onFileChange = (e, field) => {
    const file = e.target.files[0];
    if (file) {
        const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        const extension = file.name.split('.').pop().toLowerCase();
        
        if (!allowedExtensions.includes(extension)) {
            toast.add({ severity: 'error', summary: 'Format Tidak Valid', detail: 'Berkas yang diperbolehkan adalah PDF, JPG, JPEG, atau PNG.', life: 5000 });
            e.target.value = '';
            form[field] = null;
            return;
        }

        if (file.size > 2048 * 1024) {
            toast.add({ severity: 'error', summary: 'File Terlalu Besar', detail: 'Maksimal ukuran file adalah 2MB.', life: 5000 });
            e.target.value = '';
            form[field] = null;
            return;
        }
        form[field] = file;
    }
};

const submitBiodata = () => {
    if (!validateCurrentStep()) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Harap periksa isian data Anda.', life: 3000 });
        return;
    }

    form.post(route('student.biodata.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Sukses', detail: 'Biodata berhasil disimpan.', life: 3000 });
            activeStep.value = 0; // Reset to first step
            agree.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menyimpan biodata. Silakan periksa isian Anda.', life: 3000 });
        }
    });
};

// Helper untuk mengecek status presensi
const hasCheckIn = computed(() => !!props.todayAttendance?.clock_in);
const hasCheckOut = computed(() => !!props.todayAttendance?.clock_out);

// Logic Jam Real-time
const liveTime = ref('');
const updateClock = () => {
    liveTime.value = new Date().toLocaleTimeString('id-ID', {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
    });
};

let timer;
onMounted(() => {
    updateClock();
    timer = setInterval(updateClock, 1000);
});
onUnmounted(() => clearInterval(timer));
</script>

<style scoped>
.bg-gradient-to-r { background: linear-gradient(to right, #1d4ed8, #4338ca); }
.font-mono { font-family: 'Monaco', 'Consolas', monospace; }
</style>