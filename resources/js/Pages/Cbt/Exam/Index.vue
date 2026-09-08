<template>
    <AppLayout title="Jadwal Ujian CBT">
        <CbtTabMenu />
        
        <div class="card">
            <div class="flex justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Jadwal Ujian CBT</h2>
                    <span class="text-500 block mt-1">Kelola sesi ujian, pengacakan soal, dan target peserta ujian</span>
                </div>
                
                <div class="flex gap-2 align-items-center">
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="search" placeholder="Cari Ujian..." />
                    </IconField>
                    <Button 
                        label="Jadwal Massal (PTS/PAS)" 
                        icon="pi pi-calendar-plus" 
                        severity="help" 
                        @click="openBatchModal" 
                        v-tooltip.top="'Jadwalkan banyak mapel ujian terpusat sekaligus untuk 18 rombel'"
                    />
                    <Button label="Jadwalkan Ujian" icon="pi pi-plus" severity="primary" @click="openCreateModal" />
                </div>
            </div>

            <!-- Status Filter Tabs / Buttons -->
            <div class="flex gap-2 mb-3 flex-wrap">
                <Button 
                    :label="`Semua (${counts?.all ?? 0})`" 
                    :severity="!currentStatus ? 'primary' : 'secondary'" 
                    :outlined="!!currentStatus"
                    size="small"
                    icon="pi pi-list"
                    @click="filterStatus(null)"
                />
                <Button 
                    :label="`Ujian Aktif (${counts?.active ?? 0})`" 
                    :severity="currentStatus === 'active' ? 'success' : 'secondary'" 
                    :outlined="currentStatus !== 'active'"
                    size="small"
                    icon="pi pi-check-circle"
                    @click="filterStatus('active')"
                />
                <Button 
                    :label="`Ujian Non-Aktif (${counts?.inactive ?? 0})`" 
                    :severity="currentStatus === 'inactive' ? 'danger' : 'secondary'" 
                    :outlined="currentStatus !== 'inactive'"
                    size="small"
                    icon="pi pi-times-circle"
                    @click="filterStatus('inactive')"
                />
            </div>

            <div class="surface-card p-4 shadow-2 border-round">
                <DataTable :value="exams.data" :rows="10" stripedRows tableStyle="min-width: 50rem" dataKey="id">
                    <template #empty> Belum ada jadwal ujian dibuat. </template>

                    <Column field="title" header="Nama Ujian" sortable>
                        <template #body="{ data }">
                            <span class="font-bold text-lg text-900 block">{{ data.title }}</span>
                            <small class="text-600 block mt-1">
                                Bank Soal: 
                                <Link 
                                    v-if="data.bank?.id || data.cbt_bank_id" 
                                    :href="route('cbt.bank.questions', data.bank?.id || data.cbt_bank_id)"
                                    class="font-bold text-primary hover:underline cursor-pointer inline-flex align-items-center gap-1"
                                    v-tooltip.top="'Lihat & Kelola Soal'"
                                >
                                    <span>{{ data.bank?.name || 'Lihat Soal' }}</span>
                                    <i class="pi pi-external-link text-xs"></i>
                                </Link>
                                <b v-else>-</b>
                            </small>
                            <small class="text-500 block mt-1">Mapel: <b>{{ data.bank?.subject?.name || '-' }}</b></small>
                        </template>
                    </Column>

                    <Column style="min-width: 220px;">
                        <template #header>
                            <div class="flex align-items-center justify-content-between w-full gap-2">
                                <span class="font-bold">Waktu & Durasi</span>
                                <Button 
                                    :icon="isAllTimeExpanded ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" 
                                    text 
                                    rounded 
                                    size="small" 
                                    class="p-0 text-xs w-2rem h-2rem text-500 hover:text-primary"
                                    :severity="isAllTimeExpanded ? 'primary' : 'secondary'"
                                    v-tooltip.top="isAllTimeExpanded ? 'Ringkas Semua Baris' : 'Buka Semua Rincian Waktu'"
                                    @click.stop="toggleAllTimeCollapse"
                                />
                            </div>
                        </template>
                        <template #body="{ data }">
                            <div 
                                class="cursor-pointer select-none py-1 group"
                                @click="toggleTimeCollapse(data.id)"
                                v-tooltip.top="isTimeExpanded(data.id) ? 'Klik untuk meringkas tampilan' : 'Klik untuk melihat rincian jam mulai & selesai'"
                            >
                                <!-- TAMPILAN RINGKAS (COLLAPSED) -->
                                <div v-if="!isTimeExpanded(data.id)" class="flex align-items-center justify-content-between gap-2">
                                    <div class="flex flex-column gap-1 text-sm text-800">
                                        <div class="font-semibold text-900 flex align-items-center gap-1">
                                            <i class="pi pi-calendar text-primary text-xs"></i>
                                            {{ formatCompactDateRange(data.start_time, data.end_time) }}
                                        </div>
                                        <div class="text-600 flex align-items-center gap-2 text-xs">
                                            <span><i class="pi pi-clock text-xs mr-1"></i>Durasi: <b>{{ data.duration }} Menit</b></span>
                                        </div>
                                    </div>
                                    <i class="pi pi-chevron-down text-xs text-400 group-hover:text-primary transition-colors"></i>
                                </div>

                                <!-- TAMPILAN RINCI (EXPANDED) -->
                                <div v-else class="flex flex-column gap-1 text-xs text-800">
                                    <div class="flex align-items-center justify-content-between border-bottom-1 border-200 pb-1 mb-1">
                                        <span class="font-bold text-primary text-xs flex align-items-center gap-1">
                                            <i class="pi pi-clock"></i> Rincian Jadwal
                                        </span>
                                        <i class="pi pi-chevron-up text-xs text-400 group-hover:text-primary transition-colors"></i>
                                    </div>
                                    <div><i class="pi pi-play-circle text-primary text-xs mr-1"></i> Mulai: <b>{{ formatDate(data.start_time) }}</b></div>
                                    <div><i class="pi pi-stop-circle text-danger text-xs mr-1"></i> Selesai: <b>{{ formatDate(data.end_time) }}</b></div>
                                    <div><i class="pi pi-hourglass text-600 text-xs mr-1"></i> Durasi: <b>{{ data.duration }} Menit</b></div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Target Kelas">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-1">
                                <Tag v-for="cls in data.classrooms" :key="cls.id" :value="cls.name" severity="info" />
                            </div>
                        </template>
                    </Column>

                    <Column header="Status" style="width: 14%">
                        <template #body="{ data }">
                            <div class="flex align-items-center gap-2">
                                <ToggleSwitch
                                    :modelValue="data.is_active"
                                    @update:modelValue="toggleActive(data)"
                                    v-tooltip.top="data.is_active ? 'Klik untuk nonaktifkan Ujian' : 'Klik untuk aktifkan Ujian'"
                                />
                                <Tag :value="data.is_active ? 'Aktif' : 'Non-Aktif'" :severity="data.is_active ? 'success' : 'danger'" class="text-xs" />
                            </div>
                        </template>
                    </Column>

                    <Column header="Ujian Mandiri" style="width: 13%">
                        <template #body="{ data }">
                            <div class="flex align-items-center gap-2">
                                <ToggleSwitch
                                    :modelValue="data.is_independent"
                                    @update:modelValue="toggleIndependent(data)"
                                    v-tooltip.top="data.is_independent ? 'Klik untuk nonaktifkan Ujian Mandiri' : 'Klik untuk aktifkan Ujian Mandiri'"
                                />
                                <Tag
                                    :value="data.is_independent ? 'Mandiri' : 'Reguler'"
                                    :severity="data.is_independent ? 'info' : 'secondary'"
                                    class="text-xs"
                                />
                            </div>
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 22%">
                        <template #body="{ data }">
                            <div class="flex gap-1 align-items-center">
                                <Link :href="`/cbt/exams/${data.id}/dry-run`" target="_blank">
                                    <Button icon="pi pi-desktop" severity="help" text rounded v-tooltip.top="'Simulasi Ujian CBT (Dry Run)'" />
                                </Link>
                                <Link :href="route('cbt.exams.results', data.id)">
                                    <Button icon="pi pi-chart-bar" severity="info" text rounded v-tooltip.top="'Hasil Ujian'" />
                                </Link>
                                <Button icon="pi pi-pencil" severity="warning" text rounded v-tooltip.top="'Edit'" @click="openEditModal(data)" />
                                <Button icon="pi pi-trash" severity="danger" text rounded v-tooltip.top="'Hapus'" @click="confirmDelete(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
                <Pagination :links="exams.links" class="mt-4" />
            </div>
        </div>

        <!-- Create / Edit Dialog -->
        <Dialog v-model:visible="displayModal" :header="isEditing ? 'Edit Jadwal Ujian' : 'Jadwalkan Ujian Baru'" :modal="true" :style="{ width: '600px' }">
            <form @submit.prevent="submitForm" class="p-fluid">
                <div class="field mb-3">
                    <label for="title" class="font-medium">Nama / Judul Ujian <span class="text-red-500">*</span></label>
                    <InputText id="title" v-model="form.title" class="w-full" :class="{'p-invalid': form.errors.title}" placeholder="Contoh: Ujian Tengah Semester Kimia" />
                    <small class="p-error" v-if="form.errors.title">{{ form.errors.title }}</small>
                </div>

                <div class="field mb-3">
                    <label for="subject" class="font-medium">Mata Pelajaran <span class="text-red-500">*</span></label>
                    <Select 
                        v-model="form.subject_id" 
                        :options="subjects" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Pilih Mata Pelajaran Terlebih Dahulu" 
                        class="w-full"
                        filter
                    />
                </div>

                <div class="field mb-3" v-if="form.subject_id">
                    <label for="bank" class="font-medium">Bank Soal <span class="text-red-500">*</span></label>
                    <Select 
                        v-model="form.cbt_bank_id" 
                        :options="filteredBanks" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Pilih Bank Soal" 
                        class="w-full"
                        filter
                        :class="{'p-invalid': form.errors.cbt_bank_id}"
                    />
                    <small class="p-error" v-if="form.errors.cbt_bank_id">{{ form.errors.cbt_bank_id }}</small>
                </div>

                <div class="field mb-3" v-if="sessionOptions.length > 0">
                    <label class="font-medium flex align-items-center gap-1">
                        <i class="pi pi-clock text-primary"></i> Tarik Waktu dari Sesi Ujian (Opsional)
                    </label>
                    <Select 
                        v-model="singleExamSessionId" 
                        :options="sessionOptions" 
                        optionLabel="label" 
                        optionValue="id" 
                        placeholder="-- Pilih Sesi Ujian (/cbt/sessions) --" 
                        class="w-full"
                        showClear
                        @change="applySingleExamSession"
                    />
                    <small class="text-500">Memilih sesi otomatis mengisi Jam Mulai, Jam Selesai, dan Durasi sesuai tanggal yang dipilih.</small>
                </div>

                <div class="formgrid grid">
                    <div class="field col-6 mb-3">
                        <label for="start_time" class="font-medium">Waktu Mulai <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="start_time" v-model="form.start_time" class="p-inputtext w-full" :class="{'p-invalid': form.errors.start_time}" />
                        <small class="p-error" v-if="form.errors.start_time">{{ form.errors.start_time }}</small>
                    </div>

                    <div class="field col-6 mb-3">
                        <label for="end_time" class="font-medium">Waktu Selesai <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="end_time" v-model="form.end_time" class="p-inputtext w-full" :class="{'p-invalid': form.errors.end_time}" />
                        <small class="p-error" v-if="form.errors.end_time">{{ form.errors.end_time }}</small>
                    </div>
                </div>

                <div class="formgrid grid">
                    <div class="field col-6 mb-3">
                        <label for="duration" class="font-medium">Durasi Ujian (Menit) <span class="text-red-500">*</span></label>
                        <InputNumber id="duration" v-model="form.duration" class="w-full" :min="1" :useGrouping="false" :class="{'p-invalid': form.errors.duration}" />
                        <small class="p-error" v-if="form.errors.duration">{{ form.errors.duration }}</small>
                    </div>

                    <div class="field col-6 mb-3">
                        <label for="classroom" class="font-medium">Kelas Peserta <span class="text-red-500">*</span></label>
                        <MultiSelect 
                            v-model="form.classroom_ids" 
                            :options="filteredClassrooms" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Pilih Kelas" 
                            display="chip" 
                            class="w-full"
                            filter
                            :class="{'p-invalid': form.errors.classroom_ids}"
                        />
                        <small class="p-error" v-if="form.errors.classroom_ids">{{ form.errors.classroom_ids }}</small>
                    </div>
                </div>

                <div class="field mb-3">
                    <label for="grading" class="font-medium">Integrasi Kategori Nilai Guru (Opsional)</label>
                    <Select 
                        v-model="form.grading_component_id" 
                        :options="gradingComponents" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Pilih Kategori Nilai" 
                        class="w-full"
                        filter
                        showClear
                    >
                        <template #option="slotProps">
                            {{ slotProps.option.name }} <small class="text-500 block">{{ slotProps.option.subject?.name }}</small>
                        </template>
                    </Select>
                </div>

                <!-- Toggle Options -->
                <div class="field mb-3 mt-2">
                    <label class="font-bold text-900 mb-2 block uppercase text-xs tracking-wider text-500">Konfigurasi Pengacakan</label>
                    <div class="flex flex-column gap-3 bg-50 p-3 border-round border border-200">
                        <div class="flex align-items-center justify-content-between">
                            <span class="font-semibold text-sm">Acak Urutan Soal Siswa</span>
                            <ToggleSwitch v-model="form.shuffle_questions" />
                        </div>
                        <div class="flex align-items-center justify-content-between border-top-1 border-200 pt-3">
                            <span class="font-semibold text-sm">Acak Pilihan Jawaban (PG, List, Checklist)</span>
                            <ToggleSwitch v-model="form.shuffle_options" />
                        </div>
                        <div class="flex align-items-center justify-content-between border-top-1 border-200 pt-3">
                            <span class="font-semibold text-sm">Wajib Jawab Semua Soal (Tidak boleh kosong & ragu-ragu)</span>
                            <ToggleSwitch v-model="form.must_complete_all" />
                        </div>
                    </div>
                </div>

                <div class="field mb-4">
                    <div class="flex align-items-center justify-content-between bg-50 p-3 border-round border border-200">
                        <div>
                            <span class="font-bold text-sm text-900 block">Ujian Aktif & Dapat Diakses Siswa</span>
                            <small class="text-500">Siswa hanya dapat mengakses ujian yang aktif</small>
                        </div>
                        <ToggleSwitch v-model="form.is_active" />
                    </div>
                </div>

                <div class="field mb-4" v-if="($page.props.auth?.user?.roles ?? []).some(r => r.name === 'guru')">
                    <div class="flex align-items-center justify-content-between bg-blue-50 p-3 border-round border border-blue-200">
                        <div>
                            <span class="font-bold text-sm text-blue-900 block">Ujian Mandiri (Proktoring Cepat)</span>
                            <small class="text-blue-700">Otomatis buatkan ruang dan sesi, siswa dapat ujian tanpa token proktor khusus</small>
                        </div>
                        <ToggleSwitch v-model="form.is_independent" />
                    </div>
                </div>

                <div class="flex justify-content-between align-items-center w-full">
                    <div>
                        <Link v-if="isEditing && editId" :href="`/cbt/exams/${editId}/dry-run`" target="_blank">
                            <Button label="Simulasi CBT (Dry Run)" icon="pi pi-desktop" severity="help" size="small" outlined type="button" />
                        </Link>
                    </div>
                    <div class="flex gap-2">
                        <Button label="Batal" severity="secondary" text @click="displayModal = false" />
                        <Button :label="isEditing ? 'Simpan Perubahan' : 'Buat Jadwal'" type="submit" :loading="form.processing" />
                    </div>
                </div>
            </form>
        </Dialog>

        <!-- Modal Penjadwalan Massal / Terpusat (PTS / PAS / SAS) -->
        <Dialog 
            v-model:visible="displayBatchModal" 
            header="Penjadwalan Ujian Terpusat / Massal (PTS / PAS / SAS)" 
            :modal="true" 
            :style="{ width: '96vw', maxWidth: '1400px' }"
            :closable="!isSubmittingBatch"
        >
            <div class="flex flex-column gap-3">
                <!-- PANEL 1: PRESET & KONFIGURASI CEPAT -->
                <div class="surface-ground p-3 border-round border border-300">
                    <div class="grid formgrid p-fluid">
                        <!-- Prefix Judul & Filter Bank Soal -->
                        <div class="field col-12 md:col-4 mb-2">
                            <div class="flex justify-content-between align-items-center mb-1">
                                <label class="font-bold text-sm text-900 m-0">Nama Kegiatan / Event</label>
                                <div class="flex align-items-center gap-1">
                                    <ToggleSwitch v-model="batchFilterByEvent" />
                                    <span class="text-xs text-600 font-medium">Filter Bank Soal</span>
                                </div>
                            </div>
                            <InputText v-model="batchPrefix" placeholder="Contoh: PTS_Gasal_26_27" class="p-inputtext-sm" />
                            <div class="flex justify-content-between align-items-center mt-1">
                                <small class="text-500">
                                    Format: <i>{{ batchPrefix ? batchPrefix + '_[Nama Mapel]' : '[Event]_[Nama Mapel]' }}</i>
                                </small>
                                <Tag 
                                    v-if="batchPrefix && batchFilterByEvent" 
                                    :value="`${filteredBatchBanks.length} Bank Cocok`" 
                                    :severity="filteredBatchBanks.length > 0 ? 'success' : 'warn'" 
                                    class="text-xs" 
                                />
                            </div>
                            <div v-if="batchPrefix && batchFilterByEvent && filteredBatchBanks.length === 0" class="text-xs text-orange-600 mt-1">
                                <i class="pi pi-exclamation-circle mr-1"></i>Belum ada Bank Soal berawalan "<b>{{ batchPrefix }}</b>". Pastikan nama bank soal diawali prefix tersebut atau nonaktifkan switch "Filter Bank Soal".
                            </div>
                        </div>

                        <!-- Tanggal Default & Terapkan -->
                        <div class="field col-6 md:col-4 mb-2">
                            <label class="font-bold text-sm text-900 block mb-1">Tanggal Mulai Default</label>
                            <div class="p-inputgroup">
                                <input type="date" v-model="batchDate" class="p-inputtext p-component p-inputtext-sm" />
                                <Button label="Terapkan Semua" size="small" severity="secondary" outlined @click="applyDateToAllRows" v-tooltip.top="'Terapkan tanggal ini ke semua baris di bawah'" />
                            </div>
                        </div>

                        <!-- Sesi Ujian Default & Terapkan (Ditarik dari /cbt/sessions) -->
                        <div class="field col-6 md:col-4 mb-2" v-if="sessionOptions.length > 0">
                            <div class="flex justify-content-between align-items-center mb-1">
                                <label class="font-bold text-sm text-900 m-0">Sesi Default (/cbt/sessions)</label>
                                <Button label="⚡ Urutkan Sesi 1,2,3..." size="small" severity="info" text class="p-0 text-xs font-bold" @click="autoSequenceSessions" v-tooltip.top="'Pasangkan baris berurutan dengan Sesi 1, Sesi 2, Sesi 3, dst.'" />
                            </div>
                            <div class="p-inputgroup">
                                <Select v-model="batchSelectedSessionId" :options="sessionOptions" optionLabel="label" optionValue="id" placeholder="Pilih Sesi Ujian" class="p-inputtext-sm" showClear />
                                <Button label="Terapkan" size="small" severity="secondary" outlined @click="applySessionToAllRows" v-tooltip.top="'Terapkan jam & durasi sesi ini ke semua baris'" />
                            </div>
                        </div>

                        <!-- Durasi Default Fallback -->
                        <div class="field col-6 md:col-4 mb-2" v-else>
                            <label class="font-bold text-sm text-900 block mb-1">Durasi Default (Menit)</label>
                            <div class="p-inputgroup">
                                <InputNumber v-model="batchDuration" :min="10" :max="360" class="p-inputtext-sm" />
                                <Button label="Terapkan Semua" size="small" severity="secondary" outlined @click="applyDurationToAllRows" v-tooltip.top="'Terapkan durasi & hitung ulang jam selesai semua baris'" />
                            </div>
                        </div>

                        <!-- Shortcut Pilihan Rombel Kelas -->
                        <div class="col-12 mt-1 mb-2">
                            <div class="bg-white p-3 border-round border border-200 shadow-1">
                                <div class="flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <span class="font-bold text-sm text-900 flex align-items-center gap-1">
                                        <i class="pi pi-users text-primary"></i> Target Rombel Massal (Shortcut 1-Klik):
                                    </span>
                                    <div class="flex flex-wrap gap-1">
                                        <Button label="+ Semua Kelas X (6 Rombel)" size="small" severity="info" outlined @click="selectClassesByLevel('10')" />
                                        <Button label="+ Semua Kelas XI (6 Rombel)" size="small" severity="info" outlined @click="selectClassesByLevel('11')" />
                                        <Button label="+ Semua Kelas XII (6 Rombel)" size="small" severity="info" outlined @click="selectClassesByLevel('12')" />
                                        <Button label="+ Semua 18 Rombel" size="small" severity="primary" outlined @click="selectAllClasses" />
                                        <Button label="Reset" size="small" severity="secondary" text @click="clearSelectedClasses" />
                                    </div>
                                </div>
                                <div class="flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="text-xs text-700">
                                        Rombel Terpilih (<b>{{ batchSelectedClasses.length }}</b> Rombel): 
                                        <span class="font-semibold text-primary">{{ getSelectedClassesNames(batchSelectedClasses) || 'Belum ada rombel dipilih' }}</span>
                                    </div>
                                    <Button 
                                        label="Terapkan Rombel Ini ke Seluruh Baris Jadwal" 
                                        icon="pi pi-check-square" 
                                        size="small" 
                                        severity="help" 
                                        @click="applyClassesToAllRows" 
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Toggle Opsi Default -->
                        <div class="col-12">
                            <div class="flex flex-wrap align-items-center gap-4 text-xs font-semibold text-700">
                                <div class="flex align-items-center gap-2">
                                    <ToggleSwitch v-model="batchShuffleQuestions" />
                                    <span>Acak Soal</span>
                                </div>
                                <div class="flex align-items-center gap-2">
                                    <ToggleSwitch v-model="batchShuffleOptions" />
                                    <span>Acak Opsi Jawaban</span>
                                </div>
                                <div class="flex align-items-center gap-2">
                                    <ToggleSwitch v-model="batchMustCompleteAll" />
                                    <span>Wajib Jawab Semua</span>
                                </div>
                                <div class="flex align-items-center gap-2">
                                    <ToggleSwitch v-model="batchIsActive" />
                                    <span>Status Ujian Langsung Aktif</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL 2: TABEL GRID JADWAL -->
                <div class="border-round border border-300 overflow-x-auto shadow-1" style="max-height: 52vh;">
                    <table class="w-full text-sm border-collapse bg-white">
                        <thead class="bg-slate-100 text-slate-800 text-xs uppercase sticky top-0 border-bottom-2 border-slate-300" style="z-index: 2;">
                            <tr>
                                <th class="p-2 text-center" style="width: 35px;">#</th>
                                <th class="p-2 text-left" style="min-width: 260px;">Bank Soal <span class="text-red-500">*</span></th>
                                <th class="p-2 text-left" style="min-width: 220px;">Judul Ujian <span class="text-red-500">*</span></th>
                                <th class="p-2 text-left" style="min-width: 130px;">Tanggal <span class="text-red-500">*</span></th>
                                <th class="p-2 text-left" style="min-width: 215px;">Sesi / Jam Ujian <span class="text-red-500">*</span></th>
                                <th class="p-2 text-center" style="min-width: 75px;">Durasi</th>
                                <th class="p-2 text-left" style="min-width: 200px;">Target Rombel <span class="text-red-500">*</span></th>
                                <th class="p-2 text-center" style="width: 85px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, idx) in batchRows" :key="row.id" class="border-bottom-1 border-200 hover:surface-hover">
                                <td class="p-2 text-center font-bold text-600">{{ idx + 1 }}</td>
                                <td class="p-2">
                                    <Select 
                                        v-model="row.cbt_bank_id" 
                                        :options="filteredBatchBanks" 
                                        optionLabel="name" 
                                        optionValue="id" 
                                        filter 
                                        showClear 
                                        placeholder="Pilih Bank Soal" 
                                        class="w-full text-xs p-inputtext-sm"
                                        @change="onBankChange(row)"
                                    >
                                        <template #option="{ option }">
                                            <div class="flex flex-column gap-1">
                                                <div class="font-semibold text-900">{{ option.name }}</div>
                                                <div class="flex align-items-center gap-2 text-xs text-500">
                                                    <Tag :value="option.subject?.name || 'Umum'" severity="info" class="text-xs py-0 px-1" />
                                                    <span v-if="option.teacher?.full_name"><i class="pi pi-user mr-1 text-xs"></i>{{ option.teacher.full_name }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </Select>
                                </td>
                                <td class="p-2">
                                    <InputText v-model="row.title" placeholder="Nama Jadwal Ujian" class="w-full text-xs p-inputtext-sm" />
                                </td>
                                <td class="p-2">
                                    <input type="date" v-model="row.date" class="p-inputtext p-component text-xs p-1 w-full" />
                                </td>
                                <td class="p-2">
                                    <Select 
                                        v-if="sessionOptions.length > 0"
                                        v-model="row.cbt_session_id" 
                                        :options="sessionOptions" 
                                        optionLabel="label" 
                                        optionValue="id" 
                                        placeholder="Pilih Sesi (/cbt/sessions)" 
                                        class="w-full text-xs p-inputtext-sm mb-1" 
                                        showClear
                                        @change="onRowSessionChange(row)"
                                    />
                                    <div class="flex align-items-center gap-1">
                                        <input 
                                            type="time" 
                                            v-model="row.start_time_str" 
                                            @change="onTimeOrDurationChange(row)" 
                                            class="p-inputtext p-component text-xs p-1" 
                                            style="width: 82px;" 
                                            v-tooltip.top="'Jam Mulai'"
                                        />
                                        <span class="text-xs">-</span>
                                        <input 
                                            type="time" 
                                            v-model="row.end_time_str" 
                                            class="p-inputtext p-component text-xs p-1" 
                                            style="width: 82px;" 
                                            v-tooltip.top="'Jam Selesai'"
                                        />
                                    </div>
                                </td>
                                <td class="p-2 text-center">
                                    <InputNumber 
                                        v-model="row.duration" 
                                        :min="1" 
                                        :max="360" 
                                        class="text-xs p-inputtext-sm w-full text-center" 
                                        @update:modelValue="onTimeOrDurationChange(row)" 
                                    />
                                </td>
                                <td class="p-2">
                                    <MultiSelect 
                                        v-model="row.classroom_ids" 
                                        :options="activeClassroomsList" 
                                        optionLabel="name" 
                                        optionValue="id" 
                                        filter 
                                        display="chip" 
                                        placeholder="Pilih Rombel" 
                                        class="w-full text-xs p-inputtext-sm" 
                                        :maxSelectedLabels="1" 
                                        :selectedItemsLabel="'{0} Rombel Terpilih'"
                                    />
                                </td>
                                <td class="p-2 text-center">
                                    <div class="flex justify-content-center gap-1">
                                        <Button icon="pi pi-copy" severity="secondary" text rounded size="small" @click="duplicateBatchRow(idx)" v-tooltip.top="'Duplikasi baris ini'" />
                                        <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="removeBatchRow(idx)" :disabled="batchRows.length <= 1" v-tooltip.top="'Hapus baris ini'" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PANEL 3: FOOTER & SUBMIT -->
                <div class="flex flex-wrap justify-content-between align-items-center gap-3 pt-2">
                    <div class="flex gap-2">
                        <Button label="+ Tambah 1 Baris" icon="pi pi-plus" size="small" severity="secondary" outlined @click="addBatchRow" />
                        <Button label="+ Tambah 5 Baris" icon="pi pi-plus" size="small" severity="secondary" outlined @click="addMultipleBatchRows(5)" />
                    </div>
                    <div class="text-sm font-semibold text-700">
                        Total <b class="text-primary text-base">{{ batchRows.length }}</b> jadwal ujian siap diproses.
                    </div>
                    <div class="flex gap-2">
                        <Button label="Batal" severity="secondary" text @click="displayBatchModal = false" :disabled="isSubmittingBatch" />
                        <Button 
                            :label="`Simpan Semua (${batchRows.length} Jadwal)`" 
                            icon="pi pi-check" 
                            severity="success" 
                            :loading="isSubmittingBatch" 
                            @click="submitBatchForm" 
                        />
                    </div>
                </div>
            </div>
        </Dialog>


    </AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

import Pagination from '@/Components/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CbtTabMenu from '../CbtTabMenu.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import InputNumber from 'primevue/inputnumber';
import ToggleSwitch from 'primevue/toggleswitch';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    exams: Object,
    banks: Array,
    classrooms: Array,
    allClassrooms: Array,
    subjects: Array,
    gradingComponents: Array,
    filters: Object,
    counts: Object,
    isCbtManager: Boolean,
    sessions: Array,
});

const confirm = useConfirm();
const toast = useToast();
const displayModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const search = ref(props.filters?.search || '');
const currentStatus = ref(props.filters?.status || null);
const singleExamSessionId = ref(null);

// Format opsi sesi dari /cbt/sessions
const sessionOptions = computed(() => {
    if (!props.sessions || props.sessions.length === 0) return [];
    return props.sessions.map(s => {
        const start = s.start_time ? s.start_time.substring(0, 5) : '';
        const end = s.end_time ? s.end_time.substring(0, 5) : '';
        
        let duration = 60;
        if (start && end) {
            const [sh, sm] = start.split(':').map(Number);
            const [eh, em] = end.split(':').map(Number);
            let diff = (eh * 60 + em) - (sh * 60 + sm);
            if (diff < 0) diff += 24 * 60;
            duration = diff > 0 ? diff : 60;
        }

        return {
            id: s.id,
            name: s.name,
            label: `${s.name} (${start} - ${end})`,
            start_time: start,
            end_time: end,
            duration: duration,
        };
    });
});

const applySingleExamSession = () => {
    if (!singleExamSessionId.value) return;
    const session = sessionOptions.value.find(s => s.id === singleExamSessionId.value);
    if (!session) return;
    
    let baseDate = new Date().toISOString().split('T')[0];
    if (form.start_time && form.start_time.includes('T')) {
        baseDate = form.start_time.split('T')[0];
    }
    
    form.start_time = `${baseDate}T${session.start_time}`;
    form.end_time = `${baseDate}T${session.end_time}`;
    form.duration = session.duration;
};

// ==================== PENJADWALAN MASSAL (BATCH) ====================
const displayBatchModal = ref(false);
const isSubmittingBatch = ref(false);
const batchPrefix = ref('PTS_Gasal_26_27');
const batchFilterByEvent = ref(true);
const batchSelectedSessionId = ref(null);
const batchDuration = ref(90);
const batchDate = ref(new Date().toISOString().split('T')[0]);
const batchSelectedClasses = ref([]);
const batchShuffleQuestions = ref(true);
const batchShuffleOptions = ref(true);
const batchMustCompleteAll = ref(false);
const batchIsActive = ref(true);
const batchRows = ref([]);

let batchRowCounter = 0;

const activeClassroomsList = computed(() => {
    return (props.allClassrooms && props.allClassrooms.length > 0) ? props.allClassrooms : props.classrooms;
});

// Filter Bank Soal berdasarkan isian Nama Kegiatan / Event (misal: PTS_Gasal_26_27)
const filteredBatchBanks = computed(() => {
    if (!props.banks) return [];
    if (!batchFilterByEvent.value || !batchPrefix.value || !batchPrefix.value.trim()) {
        return props.banks;
    }
    const term = batchPrefix.value.trim().toLowerCase();
    return props.banks.filter(b => b.name && b.name.toLowerCase().includes(term));
});

const calculateEndTimeStr = (startTimeStr, durationMinutes) => {
    if (!startTimeStr) return '';
    const parts = startTimeStr.split(':');
    if (parts.length < 2) return '';
    const hours = parseInt(parts[0], 10);
    const minutes = parseInt(parts[1], 10);
    const dur = parseInt(durationMinutes, 10) || 0;
    
    let totalMinutes = hours * 60 + minutes + dur;
    const newHours = Math.floor(totalMinutes / 60) % 24;
    const newMinutes = totalMinutes % 60;
    
    return `${String(newHours).padStart(2, '0')}:${String(newMinutes).padStart(2, '0')}`;
};

const createEmptyBatchRow = (defaultBankId = null, defaultSessionId = null, date = null, classroomIds = []) => {
    batchRowCounter++;
    const rowDate = date || batchDate.value || new Date().toISOString().split('T')[0];
    const rowSessionId = defaultSessionId !== undefined ? defaultSessionId : batchSelectedSessionId.value;
    
    let rowStartTime = '07:30';
    let rowEndTime = '09:00';
    let rowDuration = batchDuration.value || 90;

    if (rowSessionId) {
        const sess = sessionOptions.value.find(s => s.id === rowSessionId);
        if (sess) {
            rowStartTime = sess.start_time;
            rowEndTime = sess.end_time;
            rowDuration = sess.duration;
        }
    } else {
        rowEndTime = calculateEndTimeStr(rowStartTime, rowDuration);
    }

    let title = '';
    if (defaultBankId) {
        const bank = props.banks.find(b => b.id === defaultBankId);
        if (bank) {
            if (batchPrefix.value && bank.name.toLowerCase().includes(batchPrefix.value.trim().toLowerCase())) {
                title = bank.name;
            } else if (batchPrefix.value) {
                const subjectName = bank.subject?.name || bank.name;
                title = `${batchPrefix.value}_${subjectName}`;
            } else {
                title = bank.name;
            }
        }
    }

    return {
        id: `row_${Date.now()}_${batchRowCounter}`,
        cbt_bank_id: defaultBankId,
        cbt_session_id: rowSessionId || null,
        title: title,
        date: rowDate,
        start_time_str: rowStartTime,
        end_time_str: rowEndTime,
        duration: rowDuration,
        classroom_ids: classroomIds.length > 0 ? [...classroomIds] : [...batchSelectedClasses.value],
    };
};

const onRowSessionChange = (row) => {
    if (!row.cbt_session_id) return;
    const session = sessionOptions.value.find(s => s.id === row.cbt_session_id);
    if (session) {
        row.start_time_str = session.start_time;
        row.end_time_str = session.end_time;
        row.duration = session.duration;
    }
};

const applySessionToAllRows = () => {
    if (!batchSelectedSessionId.value) {
        toast.add({
            severity: 'warn',
            summary: 'Sesi Belum Dipilih',
            detail: 'Pilih sesi default terlebih dahulu.',
            life: 3000
        });
        return;
    }
    const session = sessionOptions.value.find(s => s.id === batchSelectedSessionId.value);
    if (!session) return;

    batchRows.value.forEach(row => {
        row.cbt_session_id = session.id;
        row.start_time_str = session.start_time;
        row.end_time_str = session.end_time;
        row.duration = session.duration;
    });
    toast.add({
        severity: 'info',
        summary: 'Sesi Diterapkan',
        detail: `Sesi "${session.name}" (${session.start_time} - ${session.end_time}) diterapkan ke semua baris.`,
        life: 2500
    });
};

const autoSequenceSessions = () => {
    if (sessionOptions.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'Belum Ada Sesi',
            detail: 'Data sesi ujian belum dibuat di /cbt/sessions.',
            life: 3000
        });
        return;
    }
    batchRows.value.forEach((row, idx) => {
        const sessionIdx = idx % sessionOptions.value.length;
        const session = sessionOptions.value[sessionIdx];
        row.cbt_session_id = session.id;
        row.start_time_str = session.start_time;
        row.end_time_str = session.end_time;
        row.duration = session.duration;
    });
    toast.add({
        severity: 'success',
        summary: 'Sesi Berurutan Diterapkan',
        detail: `Baris jadwal otomatis dipasangkan dengan ${sessionOptions.value.length} sesi berurutan.`,
        life: 2500
    });
};

const selectClassesByLevel = (levelStr) => {
    const lvlNum = parseInt(levelStr, 10);
    const targetIds = activeClassroomsList.value
        .filter(c => c.level == lvlNum || c.level == levelStr || c.name.startsWith(levelStr === '10' ? 'X ' : (levelStr === '11' ? 'XI ' : 'XII ')))
        .map(c => c.id);
    
    const merged = new Set([...batchSelectedClasses.value, ...targetIds]);
    batchSelectedClasses.value = Array.from(merged);
};

const selectAllClasses = () => {
    batchSelectedClasses.value = activeClassroomsList.value.map(c => c.id);
};

const clearSelectedClasses = () => {
    batchSelectedClasses.value = [];
};

const getSelectedClassesNames = (ids) => {
    if (!ids || ids.length === 0) return '';
    return activeClassroomsList.value
        .filter(c => ids.includes(c.id))
        .map(c => c.name)
        .join(', ');
};

const applyClassesToAllRows = () => {
    if (batchSelectedClasses.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'Rombel Belum Dipilih',
            detail: 'Pilih shortcut kelas terlebih dahulu (contoh: Semua Kelas X).',
            life: 3000
        });
        return;
    }
    batchRows.value.forEach(row => {
        row.classroom_ids = [...batchSelectedClasses.value];
    });
    toast.add({
        severity: 'success',
        summary: 'Target Rombel Diterapkan',
        detail: `${batchSelectedClasses.value.length} rombel berhasil disalin ke seluruh baris jadwal.`,
        life: 2500
    });
};

const applyDateToAllRows = () => {
    if (!batchDate.value) return;
    batchRows.value.forEach(row => {
        row.date = batchDate.value;
    });
    toast.add({
        severity: 'info',
        summary: 'Tanggal Diterapkan',
        detail: `Tanggal ${batchDate.value} diterapkan ke semua baris.`,
        life: 2000
    });
};

const applyDurationToAllRows = () => {
    if (!batchDuration.value) return;
    batchRows.value.forEach(row => {
        row.duration = batchDuration.value;
        if (row.start_time_str) {
            row.end_time_str = calculateEndTimeStr(row.start_time_str, batchDuration.value);
        }
    });
    toast.add({
        severity: 'info',
        summary: 'Durasi Diterapkan',
        detail: `Durasi ${batchDuration.value} menit diterapkan & jam selesai dihitung ulang.`,
        life: 2000
    });
};

const onTimeOrDurationChange = (row) => {
    if (row.start_time_str && row.duration) {
        row.end_time_str = calculateEndTimeStr(row.start_time_str, row.duration);
    }
};

const onBankChange = (row) => {
    if (!row.cbt_bank_id) return;
    const bank = props.banks.find(b => b.id === row.cbt_bank_id);
    if (bank) {
        if (batchPrefix.value && bank.name.toLowerCase().includes(batchPrefix.value.trim().toLowerCase())) {
            row.title = bank.name;
        } else if (batchPrefix.value) {
            const subjectName = bank.subject?.name || bank.name;
            row.title = `${batchPrefix.value}_${subjectName}`;
        } else {
            row.title = bank.name;
        }
    }
};

watch(batchPrefix, (newPrefix) => {
    batchRows.value.forEach(row => {
        if (row.cbt_bank_id) {
            const bank = props.banks.find(b => b.id === row.cbt_bank_id);
            if (bank) {
                if (newPrefix && bank.name.toLowerCase().includes(newPrefix.trim().toLowerCase())) {
                    row.title = bank.name;
                } else if (newPrefix) {
                    const subjectName = bank.subject?.name || bank.name;
                    row.title = `${newPrefix}_${subjectName}`;
                } else {
                    row.title = bank.name;
                }
            }
        }
    });
});

const addBatchRow = () => {
    const nextSessionIdx = batchRows.value.length % (sessionOptions.value.length || 1);
    const nextSessionId = sessionOptions.value.length > nextSessionIdx ? sessionOptions.value[nextSessionIdx].id : null;
    batchRows.value.push(createEmptyBatchRow(null, nextSessionId));
};

const addMultipleBatchRows = (count = 5) => {
    for (let i = 0; i < count; i++) {
        const nextSessionIdx = batchRows.value.length % (sessionOptions.value.length || 1);
        const nextSessionId = sessionOptions.value.length > nextSessionIdx ? sessionOptions.value[nextSessionIdx].id : null;
        batchRows.value.push(createEmptyBatchRow(null, nextSessionId));
    }
};

const duplicateBatchRow = (index) => {
    const source = batchRows.value[index];
    batchRowCounter++;
    batchRows.value.splice(index + 1, 0, {
        id: `row_${Date.now()}_${batchRowCounter}`,
        cbt_bank_id: source.cbt_bank_id,
        cbt_session_id: source.cbt_session_id || null,
        title: source.title,
        date: source.date,
        start_time_str: source.start_time_str,
        end_time_str: source.end_time_str,
        duration: source.duration,
        classroom_ids: [...source.classroom_ids],
    });
};

const removeBatchRow = (index) => {
    if (batchRows.value.length > 1) {
        batchRows.value.splice(index, 1);
    }
};

const openBatchModal = () => {
    if (batchRows.value.length === 0) {
        const todayStr = new Date().toISOString().split('T')[0];
        batchDate.value = todayStr;
        const rows = [];
        const sessionCount = sessionOptions.value.length;
        for (let i = 0; i < 3; i++) {
            const sessId = sessionCount > i ? sessionOptions.value[i].id : (sessionCount > 0 ? sessionOptions.value[0].id : null);
            rows.push(createEmptyBatchRow(null, sessId, todayStr));
        }
        batchRows.value = rows;
    }
    displayBatchModal.value = true;
};

const submitBatchForm = () => {
    if (batchRows.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'Data Kosong',
            detail: 'Tambahkan minimal 1 baris jadwal ujian.',
            life: 3000
        });
        return;
    }

    // Validasi baris per baris
    for (let i = 0; i < batchRows.value.length; i++) {
        const row = batchRows.value[i];
        const rowNum = i + 1;

        if (!row.cbt_bank_id) {
            toast.add({
                severity: 'error',
                summary: `Baris #${rowNum} Belum Lengkap`,
                detail: 'Silakan pilih Bank Soal terlebih dahulu.',
                life: 4000
            });
            return;
        }

        if (!row.title || !row.title.trim()) {
            toast.add({
                severity: 'error',
                summary: `Baris #${rowNum} Belum Lengkap`,
                detail: 'Judul / Nama Ujian tidak boleh kosong.',
                life: 4000
            });
            return;
        }

        if (!row.date) {
            toast.add({
                severity: 'error',
                summary: `Baris #${rowNum} Belum Lengkap`,
                detail: 'Tanggal ujian wajib diisi.',
                life: 4000
            });
            return;
        }

        if (!row.start_time_str || !row.end_time_str) {
            toast.add({
                severity: 'error',
                summary: `Baris #${rowNum} Belum Lengkap`,
                detail: 'Jam mulai dan jam selesai wajib diisi.',
                life: 4000
            });
            return;
        }

        if (row.end_time_str <= row.start_time_str) {
            toast.add({
                severity: 'error',
                summary: `Baris #${rowNum} Waktu Tidak Valid`,
                detail: 'Jam selesai harus lebih besar dari jam mulai.',
                life: 4000
            });
            return;
        }

        if (!row.classroom_ids || row.classroom_ids.length === 0) {
            toast.add({
                severity: 'error',
                summary: `Baris #${rowNum} Belum Lengkap`,
                detail: 'Target rombel kelas minimal harus memilih 1 kelas.',
                life: 4000
            });
            return;
        }
    }

    const payload = {
        exams: batchRows.value.map(row => ({
            cbt_bank_id: row.cbt_bank_id,
            title: row.title.trim(),
            duration: parseInt(row.duration, 10) || 60,
            start_time: `${row.date} ${row.start_time_str.length === 5 ? row.start_time_str + ':00' : row.start_time_str}`,
            end_time: `${row.date} ${row.end_time_str.length === 5 ? row.end_time_str + ':00' : row.end_time_str}`,
            classroom_ids: row.classroom_ids,
            shuffle_questions: batchShuffleQuestions.value,
            shuffle_options: batchShuffleOptions.value,
            must_complete_all: batchMustCompleteAll.value,
            is_active: batchIsActive.value,
            is_independent: false,
            grading_component_id: null,
        }))
    };

    isSubmittingBatch.value = true;
    router.post(route('cbt.exams.batch'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            displayBatchModal.value = false;
            toast.add({
                severity: 'success',
                summary: 'Penjadwalan Massal Berhasil',
                detail: `${payload.exams.length} jadwal ujian berhasil disimpan!`,
                life: 4000
            });
            batchRows.value = [];
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join(' | ') || 'Gagal menyimpan jadwal massal. Periksa kembali input Anda.';
            toast.add({
                severity: 'error',
                summary: 'Gagal Menyimpan',
                detail: errorMsg,
                life: 6000
            });
        },
        onFinish: () => {
            isSubmittingBatch.value = false;
        }
    });
};

const filterStatus = (statusVal) => {
    currentStatus.value = statusVal;
    const query = {};
    if (search.value) query.search = search.value;
    if (statusVal) query.status = statusVal;
    
    router.get(route('cbt.exams.index'), query, { 
        preserveState: true, 
        replace: true, 
        preserveScroll: true 
    });
};

const form = useForm({
    subject_id: null,
    cbt_bank_id: null,
    title: '',
    duration: 60,
    start_time: '',
    end_time: '',
    shuffle_questions: true,
    shuffle_options: true,
    must_complete_all: false,
    is_active: true,
    is_independent: false,
    grading_component_id: null,
    classroom_ids: [],
});

// Computed list of classrooms that match the selected subject
const filteredClassrooms = computed(() => {
    if (!form.subject_id) return props.classrooms;
    // Support both single subject_id and array of subject_ids on classroom objects
    return props.classrooms.filter(c => {
        if (Array.isArray(c.subject_ids)) {
            return c.subject_ids.includes(form.subject_id);
        }
        return c.subject_id === form.subject_id;
    });
});

// Reset selected classrooms when subject changes (only when creating, not editing)
// flush: 'sync' agar watcher berjalan synchronous saat subject_id di-set di openEditModal
// sehingga reset terjadi SEBELUM classroom_ids diisi ulang dari data edit
watch(() => form.subject_id, () => {
    if (!isEditing.value) {
        form.classroom_ids = [];
    }
}, { flush: 'sync' });

const filteredBanks = computed(() => {
    if (!form.subject_id) return [];
    return props.banks.filter(b => b.subject_id === form.subject_id);
});

let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const query = {};
        if (val) query.search = val;
        if (currentStatus.value) query.status = currentStatus.value;
        router.get(route('cbt.exams.index'), query, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});

const openCreateModal = () => {
    isEditing.value = false;
    singleExamSessionId.value = null;
    form.reset();
    displayModal.value = true;
};

const openEditModal = (data) => {
    isEditing.value = true;
    singleExamSessionId.value = null;
    editId.value = data.id;
    form.subject_id = data.bank?.subject_id || null;
    form.cbt_bank_id = data.cbt_bank_id;
    form.title = data.title;
    form.duration = data.duration;
    form.start_time = toInputDatetime(data.start_time);
    form.end_time = toInputDatetime(data.end_time);
    form.shuffle_questions = data.shuffle_questions;
    form.shuffle_options = data.shuffle_options;
    form.must_complete_all = data.must_complete_all ?? false;
    form.is_active = data.is_active;
    form.is_independent = data.is_independent ?? false;
    form.grading_component_id = data.grading_component_id || null;
    form.classroom_ids = data.classrooms.map(c => c.id);
    displayModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('cbt.exams.update', editId.value), {
            onSuccess: () => {
                displayModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('cbt.exams.store'), {
            onSuccess: () => {
                displayModal.value = false;
                form.reset();
            }
        });
    }
};

const confirmDelete = (data) => {
    confirm.require({
        message: `Hapus jadwal ujian <b>${data.title}</b>? Tindakan ini akan menghapus riwayat pengerjaan siswa untuk ujian ini.`,
        header: 'Konfirmasi Hapus Ujian',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('cbt.exams.destroy', data.id), {
                onSuccess: () => {}
            });
        }
    });
};

// Parse datetime string dari server
const parseLocalDate = (dateStr) => {
    if (!dateStr) return null;
    const d = new Date(dateStr);
    return isNaN(d.getTime()) ? null : d;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = parseLocalDate(dateStr);
    if (!d) return '-';
    return d.toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }) + ' WIB';
};

// ==================== COLLAPSIBLE WAKTU & DURASI ====================
const expandedTimeRows = ref({});
const isAllTimeExpanded = ref(false);

const toggleTimeCollapse = (examId) => {
    expandedTimeRows.value[examId] = !isTimeExpanded(examId);
};

const toggleAllTimeCollapse = () => {
    isAllTimeExpanded.value = !isAllTimeExpanded.value;
    const newState = isAllTimeExpanded.value;
    expandedTimeRows.value = {};
    if (props.exams?.data) {
        props.exams.data.forEach(e => {
            expandedTimeRows.value[e.id] = newState;
        });
    }
};

const isTimeExpanded = (examId) => {
    return expandedTimeRows.value[examId] ?? isAllTimeExpanded.value;
};

const formatCompactDateRange = (startStr, endStr) => {
    if (!startStr) return '-';
    const start = parseLocalDate(startStr);
    if (!start) return '-';
    
    const end = parseLocalDate(endStr);
    const day = start.getDate();
    const month = start.toLocaleString('id-ID', { month: 'short' });
    const year = start.getFullYear();
    const startHour = String(start.getHours()).padStart(2, '0') + ':' + String(start.getMinutes()).padStart(2, '0');
    
    if (!end) {
        return `${day} ${month} ${year}, ${startHour} WIB`;
    }
    
    const endHour = String(end.getHours()).padStart(2, '0') + ':' + String(end.getMinutes()).padStart(2, '0');
    
    const isSameDay = start.getFullYear() === end.getFullYear() &&
                      start.getMonth() === end.getMonth() &&
                      start.getDate() === end.getDate();
                      
    if (isSameDay) {
        return `${day} ${month} ${year} • ${startHour} - ${endHour} WIB`;
    } else {
        const endDay = end.getDate();
        const endMonth = end.toLocaleString('id-ID', { month: 'short' });
        return `${day} ${month} ${startHour} - ${endDay} ${endMonth} ${endHour} WIB`;
    }
};

// Untuk input datetime-local: format YYYY-MM-DDTHH:mm dalam local time
const toInputDatetime = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    
    // Get local parts
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const toggleIndependent = (data) => {
    router.patch(route('cbt.exams.toggle-independent', data.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: data.is_independent ? 'warn' : 'success',
                summary: data.is_independent ? 'Ujian Mandiri Dinonaktifkan' : 'Ujian Mandiri Diaktifkan',
                detail: data.is_independent
                    ? `"${data.title}" kembali ke mode reguler.`
                    : `"${data.title}" sekarang dalam mode Ujian Mandiri.`,
                life: 3000
            });
        }
    });
};

const toggleActive = (data) => {
    router.patch(route('cbt.exams.toggle-active', data.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: data.is_active ? 'warn' : 'success',
                summary: data.is_active ? 'Ujian Dinonaktifkan' : 'Ujian Diaktifkan',
                detail: data.is_active
                    ? `"${data.title}" berhasil dinonaktifkan.`
                    : `"${data.title}" berhasil diaktifkan.`,
                life: 3000
            });
        }
    });
};

</script>
