<template>
    <MobileLayout title="Dashboard Guru">
        <div class="p-3">
            <div class="flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-2xl font-bold m-0 text-900">
                        Halo, {{ teacher_info.nama_panggilan }} {{ user?.name.split(' ')[0] }}!
                    </h2>
                    <p class="text-600 m-0">SMA Negeri 16 Semarang</p>
                </div>
                <Avatar icon="pi pi-user" size="large" shape="circle" class="bg-primary-100 text-primary" />
            </div>

            <div class="grid mb-4">
                <div class="col-6">
                    <div class="surface-card p-3 border-round shadow-1 border-left-3 border-blue-500">
                        <div class="text-500 font-medium text-sm mb-2">Kelas Hari Ini</div>
                        <div class="text-900 font-bold text-xl">{{ stats.today_classes }} Sesi</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="surface-card p-3 border-round shadow-1 border-left-3 border-green-500">
                        <div class="text-500 font-medium text-sm mb-2">Progres Presensi</div>
                        <div class="text-900 font-bold text-xl">{{ stats.percentage }}%</div>
                    </div>
                </div>
            </div>

            <!-- 📬 CARD DISPOSISI KEPALA SEKOLAH (JIKA ADA DISPOSISI PENDING) -->
            <div v-if="disposisi_pending?.length > 0" class="surface-card p-3 border-round-xl shadow-2 mb-4 border-left-4 border-orange-500 bg-orange-50">
                <div class="flex justify-content-between align-items-center mb-2">
                    <div class="flex align-items-center gap-2">
                        <div class="bg-orange-100 p-2 border-round-lg text-orange-600">
                            <i class="pi pi-send text-xl font-bold"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold m-0 text-900">Disposisi Kepala Sekolah</h3>
                            <p class="text-xs text-orange-700 m-0 font-medium">Ada {{ disposisi_pending.length }} arahan / tugas yang perlu Anda tindak lanjuti</p>
                        </div>
                    </div>
                    <Tag severity="warn" :value="`${disposisi_pending.length} Tugas`" />
                </div>

                <div class="flex flex-column gap-2 mt-3">
                    <div 
                        v-for="item in disposisi_pending" 
                        :key="item.id"
                        class="surface-card p-3 border-round-lg border-1 surface-border shadow-1"
                    >
                        <div class="flex justify-content-between align-items-start mb-1">
                            <span class="text-xs font-bold text-primary">{{ item.disposisi?.surat_masuk?.no_surat_masuk }}</span>
                            <span class="text-xs text-500 font-medium">{{ item.disposisi?.surat_masuk?.asal_surat }}</span>
                        </div>
                        <div class="text-sm font-semibold text-900 mb-1 line-clamp-1" :title="stripHtml(item.disposisi?.surat_masuk?.perihal)">
                            {{ stripHtml(item.disposisi?.surat_masuk?.perihal) }}
                        </div>
                        <div class="text-xs text-700 bg-slate-50 p-2 border-round border-1 surface-border mb-2 italic line-clamp-2" v-html="item.disposisi?.isi_disposisi"></div>
                        <div class="flex justify-content-end">
                            <Link :href="route('surat.masuk.show', item.disposisi?.surat_masuk?.id)" class="no-underline">
                                <Button label="Buka & Lapor Tindak Lanjut" icon="pi pi-arrow-right" size="small" severity="warn" class="text-xs py-1 px-2" raised />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🏫 CARD REKAP PRESENSI WALI KELAS (HANYA MUNCUL JIKA WALI KELAS) -->
            <div v-if="is_walikelas && wali_kelas_summary" class="surface-card p-3 border-round shadow-2 mb-4 border-left-4 border-primary">
                <div class="flex justify-content-between align-items-center mb-2">
                    <div class="flex align-items-center gap-2">
                        <div class="bg-primary-100 p-2 border-round">
                            <i class="pi pi-users text-primary text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold m-0 text-900">Rekap Kelas {{ wali_kelas_summary.classroom_name }}</h3>
                            <p class="text-xs text-500 m-0">Presensi {{ wali_kelas_summary.date }}</p>
                        </div>
                    </div>
                    <Tag :value="wali_kelas_summary.summary.total + ' Siswa'" severity="info" class="font-bold" />
                </div>

                <p class="text-xs text-600 m-0 mb-2 italic">Klik status di bawah untuk melihat daftar nama siswa:</p>

                <!-- Grid Status Interaktif -->
                <div class="grid text-center">
                    <div class="col-4 md:col-2 p-1" @click="openStatusDialog('H', 'Hadir (Tepat Waktu)')">
                        <div class="p-2 border-round border border-green-200 bg-green-50 active:scale-95 transition-transform cursor-pointer shadow-1">
                            <div class="text-xs font-bold text-green-700 mb-1">H (HADIR)</div>
                            <div class="text-xl font-bold text-green-800">{{ wali_kelas_summary.summary.H || 0 }}</div>
                        </div>
                    </div>

                    <div class="col-4 md:col-2 p-1" @click="openStatusDialog('T', 'Terlambat')">
                        <div class="p-2 border-round border border-orange-200 bg-orange-50 active:scale-95 transition-transform cursor-pointer shadow-1">
                            <div class="text-xs font-bold text-orange-700 mb-1">T (LATE)</div>
                            <div class="text-xl font-bold text-orange-800">{{ wali_kelas_summary.summary.T || 0 }}</div>
                        </div>
                    </div>

                    <div class="col-4 md:col-2 p-1" @click="openStatusDialog('PA', 'Pulang Awal')">
                        <div class="p-2 border-round border border-yellow-200 bg-yellow-50 active:scale-95 transition-transform cursor-pointer shadow-1">
                            <div class="text-xs font-bold text-yellow-800 mb-1">PA (AWAL)</div>
                            <div class="text-xl font-bold text-yellow-900">{{ wali_kelas_summary.summary.PA || 0 }}</div>
                        </div>
                    </div>

                    <div class="col-4 md:col-2 p-1" @click="openStatusDialog('T-PA', 'Terlambat & Pulang Awal')">
                        <div class="p-2 border-round border border-purple-200 bg-purple-50 active:scale-95 transition-transform cursor-pointer shadow-1">
                            <div class="text-xs font-bold text-purple-700 mb-1">T-PA</div>
                            <div class="text-xl font-bold text-purple-800">{{ wali_kelas_summary.summary['T-PA'] || 0 }}</div>
                        </div>
                    </div>

                    <div class="col-4 md:col-2 p-1" @click="openStatusDialog('S', 'Sakit')">
                        <div class="p-2 border-round border border-blue-200 bg-blue-50 active:scale-95 transition-transform cursor-pointer shadow-1">
                            <div class="text-xs font-bold text-blue-700 mb-1">S (SAKIT)</div>
                            <div class="text-xl font-bold text-blue-800">{{ wali_kelas_summary.summary.S || 0 }}</div>
                        </div>
                    </div>

                    <div class="col-4 md:col-2 p-1" @click="openStatusDialog('I', 'Izin')">
                        <div class="p-2 border-round border border-teal-200 bg-teal-50 active:scale-95 transition-transform cursor-pointer shadow-1">
                            <div class="text-xs font-bold text-teal-700 mb-1">I (IZIN)</div>
                            <div class="text-xl font-bold text-teal-800">{{ wali_kelas_summary.summary.I || 0 }}</div>
                        </div>
                    </div>

                    <div class="col-4 md:col-2 p-1" @click="openStatusDialog('D', 'Dispensasi')">
                        <div class="p-2 border-round border border-indigo-200 bg-indigo-50 active:scale-95 transition-transform cursor-pointer shadow-1">
                            <div class="text-xs font-bold text-indigo-700 mb-1">D (DISPEN)</div>
                            <div class="text-xl font-bold text-indigo-800">{{ wali_kelas_summary.summary.D || 0 }}</div>
                        </div>
                    </div>

                    <div class="col-4 md:col-2 p-1" @click="openStatusDialog('TCO', 'Tidak Check-Out')">
                        <div class="p-2 border-round border border-amber-200 bg-amber-50 active:scale-95 transition-transform cursor-pointer shadow-1">
                            <div class="text-xs font-bold text-amber-700 mb-1">TCO (TDK OUT)</div>
                            <div class="text-xl font-bold text-amber-800">{{ wali_kelas_summary.summary.TCO || 0 }}</div>
                        </div>
                    </div>

                    <div class="col-4 md:col-2 p-1" @click="openStatusDialog('TCI', 'Tidak Check-In')">
                        <div class="p-2 border-round border border-orange-300 bg-orange-50 active:scale-95 transition-transform cursor-pointer shadow-1">
                            <div class="text-xs font-bold text-orange-800 mb-1">TCI (TDK IN)</div>
                            <div class="text-xl font-bold text-orange-900">{{ wali_kelas_summary.summary.TCI || 0 }}</div>
                        </div>
                    </div>

                    <div class="col-4 md:col-2 p-1" @click="openTHDialog">
                        <div class="p-2 border-round border border-pink-200 bg-pink-50 active:scale-95 transition-transform cursor-pointer shadow-1">
                            <div class="text-xs font-bold text-pink-700 mb-1">TH (TDK HADIR)</div>
                            <div class="text-xl font-bold text-pink-800">{{ wali_kelas_summary.summary.TH || 0 }}</div>
                        </div>
                    </div>

                    <div class="col-4 md:col-2 p-1" @click="openStatusDialog('A', 'Tanpa Keterangan / Tidak Absen')">
                        <div class="p-2 border-round border border-red-200 bg-red-50 active:scale-95 transition-transform cursor-pointer shadow-1">
                            <div class="text-xs font-bold text-red-700 mb-1">A (TANPA KETERANGAN)</div>
                            <div class="text-xl font-bold text-red-800">{{ wali_kelas_summary.summary.A || 0 }}</div>
                        </div>
                    </div>

                    <div class="col-4 md:col-2 p-1" v-if="(wali_kelas_summary.summary.FM || 0) > 0" @click="openStatusDialog('FM', 'Force Majeure')">
                        <div class="p-2 border-round border border-gray-200 bg-gray-50 active:scale-95 transition-transform cursor-pointer shadow-1">
                            <div class="text-xs font-bold text-gray-600 mb-1">FM</div>
                            <div class="text-xl font-bold text-gray-700">{{ wali_kelas_summary.summary.FM || 0 }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-2 text-right">
                    <Button 
                        label="Laporan Detail Harian »" 
                        icon="pi pi-file" 
                        text 
                        size="small" 
                        @click="router.get(route('guru.kesiswaan.reports.attendance.index', { tab: 'daily', classroom_id: wali_kelas_summary.classroom_id }))"
                    />
                </div>
            </div>

            <!-- MODAL INTERAKTIF DAFTAR SISWA PER STATUS -->
            <Dialog 
                v-model:visible="showStudentModal" 
                :header="'Siswa Status: ' + selectedStatusTitle" 
                modal 
                style="width: 90vw; max-width: 500px;"
            >
                <div class="py-2">
                    <div v-if="filteredWaliStudents.length === 0" class="text-center p-4 text-500">
                        <i class="pi pi-check-circle text-3xl text-green-500 mb-2 block"></i>
                        <p class="m-0 font-bold">Tidak ada siswa dengan status ini hari ini.</p>
                    </div>

                    <div v-else class="flex flex-column gap-2">
                        <div 
                            v-for="(student, idx) in filteredWaliStudents" 
                            :key="student.student_id || idx"
                            class="p-3 border-round surface-50 border surface-border flex align-items-center justify-content-between"
                        >
                            <div class="flex flex-column gap-1">
                                <span class="font-bold text-900 text-sm">{{ idx + 1 }}. {{ student.name }}</span>
                                <span class="text-xs text-500">NISN/NIS: {{ student.nisn_nis }} ({{ student.gender }})</span>
                                <div v-if="student.check_in || student.check_out" class="text-xs text-600">
                                    Check-In: <strong>{{ student.check_in || '-' }}</strong> | Out: <strong>{{ student.check_out || '-' }}</strong>
                                </div>
                                <span v-if="student.keterangan" class="text-xs text-orange-600 font-medium">Ket: {{ student.keterangan }}</span>
                            </div>
                            <Tag :value="student.status" :severity="student.status_severity || 'info'" class="font-bold" />
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button label="Tutup" severity="secondary" size="small" @click="showStudentModal = false" />
                </template>
            </Dialog>

            <!-- MODAL KHUSUS TH (TIDAK HADIR) - Detail: mapel & jam -->
            <Dialog
                v-model:visible="showTHModal"
                header="Siswa Tidak Hadir (TH) Hari Ini"
                modal
                style="width: 92vw; max-width: 520px;"
            >
                <div class="py-2">
                    <div v-if="!wali_kelas_summary?.th_students?.length" class="text-center p-4 text-500">
                        <i class="pi pi-check-circle text-3xl text-green-500 mb-2 block"></i>
                        <p class="m-0 font-bold">Tidak ada siswa yang Tidak Hadir (TH) hari ini. 🎉</p>
                    </div>

                    <div v-else class="flex flex-column gap-3">
                        <div
                            v-for="(student, idx) in wali_kelas_summary.th_students"
                            :key="student.student_id || idx"
                            class="border-round border surface-border overflow-hidden"
                        >
                            <!-- Header siswa -->
                            <div class="px-3 py-2 bg-pink-50 border-bottom-1 border-pink-100 flex align-items-center gap-2">
                                <i class="pi pi-user text-pink-600"></i>
                                <div>
                                    <span class="font-bold text-900 text-sm">{{ idx + 1 }}. {{ student.name }}</span>
                                    <span class="text-xs text-500 ml-2">(NIS: {{ student.nis }})</span>
                                </div>
                            </div>
                            <!-- Daftar mapel yang dibolos -->
                            <div class="px-3 py-2 flex flex-column gap-1">
                                <div
                                    v-for="(mapel, mIdx) in student.mapel_absen"
                                    :key="mIdx"
                                    class="flex align-items-center gap-2 text-xs"
                                >
                                    <Tag severity="danger" :value="'Jam ' + mapel.jam_ke" class="text-xs" />
                                    <span class="font-semibold text-800">{{ mapel.subject }}</span>
                                    <span class="text-400">— {{ mapel.guru }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button label="Tutup" severity="secondary" size="small" @click="showTHModal = false" />
                </template>
            </Dialog>

            <div v-if="activeAgenda" class="surface-card p-3 border-round shadow-2 mb-4 bg-primary text-white">
                <div class="flex justify-content-between align-items-center mb-3">
                    <span class="font-bold uppercase text-xs opacity-80">Update Terakhir</span>
                    <Tag severity="warn" :value="'Jam Ke ' + (activeAgenda.start_slot ?? activeAgenda.schedule_detail?.start_slot ?? '-')" />
                </div>
                <h3 class="text-xl font-bold m-0 mb-1">
                    {{ activeAgenda.classroom?.name }} - {{ activeAgenda.subject?.name }}
                </h3>
                <p class="m-0 text-sm opacity-90 mb-3 line-height-3">
                    {{ activeAgenda.materi_pembelajaran || 'Belum ada ringkasan materi' }}
                </p>
                <Button 
                    label="Lihat Detail" 
                    icon="pi pi-search" 
                    severity="secondary" 
                    size="small" 
                    fluid 
                    @click="router.get(route('guru.agenda.show', activeAgenda.id))"
                />
            </div>
            
            <div v-else class="surface-card p-4 border-round shadow-1 mb-4 text-center border-1 border-dashed border-300">
                <i class="pi pi-calendar-times text-400 text-3xl mb-2"></i>
                <p class="m-0 text-500 text-sm">Belum ada agenda yang diisi hari ini.</p>
            </div>

            <h3 class="text-lg font-bold mb-3 text-900">Aksi Cepat</h3>
            <div class="grid text-center mb-4">

                <!-- Selalu tampil: Isi Agenda -->
                <div class="col-3" @click="router.get(route('guru.agenda.create'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform">
                        <i class="pi pi-calendar-plus text-primary text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Isi Agenda</span>
                </div>

                <!-- Selalu tampil: Penugasan (PR) -->
                <div class="col-3" @click="router.get(route('guru.assignments.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-indigo-50">
                        <i class="pi pi-file-edit text-indigo-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Tugas</span>
                </div>

                <!-- Selalu tampil: Cetak Agenda -->
                <div class="col-3" @click="router.get(route('guru.reports.personal'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform">
                        <i class="pi pi-print text-primary text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Cetak Agenda</span>
                </div>

                <!-- Selalu tampil: Rekap Presensi Siswa Mapel -->
                <div class="col-3" @click="router.get(route('guru.attendance-recap.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-green-50">
                        <i class="pi pi-list-check text-green-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Rekap Siswa</span>
                </div>

                <!-- Hanya untuk Guru Pembina Ekstrakulikuler -->
                <div v-if="is_extracurricular_teacher" class="col-3" @click="router.get(route('guru.kesiswaan.extracurriculars.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-pink-50">
                        <i class="pi pi-star text-pink-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Ekstrakulikuler</span>
                </div>

                <!-- Hanya Wali Kelas: Jurnal Kelas -->
                <div v-if="is_walikelas" class="col-3" @click="router.get(route('guru.reports.classroom'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-blue-50">
                        <i class="pi pi-users text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Jurnal Kelas</span>
                </div>

                <!-- Hanya Wali Kelas: Rekap Absen -->
                <div v-if="is_walikelas" class="col-3" @click="router.get(route('guru.kesiswaan.reports.attendance.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-teal-50">
                        <i class="pi pi-calendar text-teal-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Rekap Absen</span>
                </div>

                <!-- Selalu tampil: Pinjam Sarpras & Aset Guru -->
                <div class="col-3" @click="router.get(route('guru.sarpras.reservations.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-cyan-50">
                        <i class="pi pi-box text-cyan-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Pinjam Aset</span>
                </div>

                <!-- Selalu tampil: Lapor Rusak (semua guru bisa lapor) -->
                <div class="col-3" @click="router.get(route('facility.reports.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-orange-50 relative">
                        <i class="pi pi-wrench text-orange-600 text-xl"></i>
                        <span v-if="unread_facility_reports > 0" class="absolute top-0 right-0 bg-red-500 text-white border-circle flex align-items-center justify-content-center text-xs font-bold" style="width: 18px; height: 18px; transform: translate(30%, -30%)">
                            {{ unread_facility_reports }}
                        </span>
                    </div>
                    <span class="text-xs font-bold text-700">Lapor Rusak</span>
                </div>

                <!-- Hanya yang punya manage-facility-reports: Kelola Sarpras -->
                <div v-if="hasPermission('manage-facility-reports')" class="col-3" @click="router.get(route('admin.facility.reports.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-purple-50">
                        <i class="pi pi-shield text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Kelola Sarpras</span>
                </div>

                <!-- Hanya yang punya manage-discipline: Tata Tertib -->
                <div v-if="hasPermission('manage-discipline')" class="col-3" @click="router.get(route('guru.kesiswaan.discipline.violations.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-red-50">
                        <i class="pi pi-exclamation-circle text-red-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Tata Tertib</span>
                </div>

                <!-- Hanya yang punya manage-permits: Perizinan -->
                <div v-if="hasPermission('manage-permits')" class="col-3" @click="router.get(route('permits.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-green-50">
                        <i class="pi pi-file-check text-green-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Perizinan</span>
                </div>

                <!-- Hanya yang punya manage-lates: Terlambat -->
                <div v-if="hasPermission('manage-lates')" class="col-3" @click="router.get(route('lates.index'))">
                    <div class="p-3 surface-card border-round shadow-1 mb-1 active:scale-95 transition-transform bg-yellow-50">
                        <i class="pi pi-clock text-yellow-700 text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-700">Terlambat</span>
                </div>

            </div>

            <!-- 📅 KALENDER AKADEMIK (READ-ONLY MOBILE OPTIMIZED, COLLAPSIBLE) -->
            <div class="surface-card p-3 border-round shadow-2 mb-4">
                <div 
                    class="flex justify-content-between align-items-center cursor-pointer select-none"
                    :class="{ 'mb-3 pb-2 border-bottom-1 surface-border': isCalendarOpen }"
                    @click="isCalendarOpen = !isCalendarOpen"
                >
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-calendar text-primary text-xl"></i>
                        <div>
                            <h3 class="text-base font-bold m-0 text-900">Kalender Akademik</h3>
                            <p class="text-xs text-500 m-0">
                                {{ isCalendarOpen ? 'Klik untuk menyembunyikan' : 'Klik untuk menampilkan' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex align-items-center gap-2">
                        <Tag value="Read-Only" severity="secondary" class="text-xs" />
                        <i :class="isCalendarOpen ? 'pi pi-chevron-up text-500' : 'pi pi-chevron-down text-500'"></i>
                    </div>
                </div>

                <div v-if="isCalendarOpen">
                    <!-- Mini Legend -->
                    <div class="flex flex-wrap gap-2 mb-3 text-xs">
                        <span class="flex align-items-center gap-1">
                            <span class="w-0-5rem h-0-5rem border-round bg-red-500" style="display:inline-block; width:8px; height:8px;"></span> Non-KBM
                        </span>
                        <span class="flex align-items-center gap-1">
                            <span class="w-0-5rem h-0-5rem border-round bg-green-500" style="display:inline-block; width:8px; height:8px;"></span> Kegiatan
                        </span>
                        <span class="flex align-items-center gap-1">
                            <span class="w-0-5rem h-0-5rem border-round bg-orange-500" style="display:inline-block; width:8px; height:8px;"></span> Ujian
                        </span>
                    </div>

                    <!-- Calendar View Container -->
                    <div class="mobile-calendar-container">
                        <FullCalendar :options="mobileCalendarOptions" />
                    </div>
                </div>
            </div>

            <!-- MODAL DETAIL EVENT KALENDER -->
            <Dialog 
                v-model:visible="displayCalendarDialog" 
                header="Detail Agenda Kalender" 
                modal 
                style="width: 90vw; max-width: 450px;"
                :dismissableMask="true"
            >
                <div v-if="selectedCalendarEvent" class="py-2">
                    <div class="mb-3">
                        <Tag 
                            :severity="selectedCalendarEvent.is_holiday ? 'danger' : (selectedCalendarEvent.type === 'exam' ? 'warning' : 'success')" 
                            class="px-3 py-1 text-xs uppercase font-bold"
                        >
                            {{ selectedCalendarEvent.is_holiday ? 'LIBUR / NON-KBM' : 'ADA KBM' }}
                        </Tag>
                    </div>

                    <h4 class="text-lg font-bold text-900 m-0 mb-3">
                        {{ selectedCalendarEvent.title }}
                    </h4>

                    <div class="surface-100 p-3 border-round-lg mb-3">
                        <div class="mb-2">
                            <span class="text-500 text-xs block font-semibold uppercase">Jenis Agenda:</span>
                            <span class="font-bold text-700 text-sm">{{ typeLabels[selectedCalendarEvent.type] || selectedCalendarEvent.type }}</span>
                        </div>

                        <div>
                            <span class="text-500 text-xs block font-semibold uppercase">Tanggal:</span>
                            <span v-if="selectedCalendarEvent.start_date === selectedCalendarEvent.end_date" class="font-bold text-blue-700 text-sm">
                                {{ formatDateIndo(selectedCalendarEvent.start_date) }}
                            </span>
                            <span v-else class="font-bold text-blue-700 text-sm">
                                {{ formatDateIndo(selectedCalendarEvent.start_date) }} — {{ formatDateIndo(selectedCalendarEvent.end_date) }}
                            </span>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button label="Tutup" severity="secondary" size="small" @click="displayCalendarDialog = false" />
                </template>
            </Dialog>

            <div class="flex justify-content-between align-items-center mb-3">
                <h3 class="text-lg font-bold m-0 text-900">Riwayat Terakhir</h3>
                <Button label="Semua" text size="small" @click="router.get(route('guru.agenda.index'))" />
            </div>
            
            <div class="flex flex-column gap-2 mb-5">
                <div 
                    v-for="agenda in recentAgendas" 
                    :key="agenda.id" 
                    class="flex align-items-center gap-3 p-3 surface-card border-round shadow-1 active:surface-50"
                    @click="router.get(route('guru.agenda.show', agenda.id))"
                >
                    <div class="bg-blue-50 p-2 border-round">
                        <i class="pi pi-book text-blue-500"></i>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-bold text-900">{{ agenda.classroom?.name }}</div>
                        <div class="text-xs text-500">
                            {{ formatDate(agenda.date) }} · {{ agenda.hadir_count }} Siswa Hadir
                        </div>
                    </div>
                    <i class="pi pi-chevron-right text-300"></i>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';

// FullCalendar
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

const props = defineProps({
    is_walikelas: Boolean,
    is_extracurricular_teacher: Boolean,
    wali_kelas_summary: Object,
    teacher_info: Object,
    stats: Object,
    activeAgenda: Object,
    recentAgendas: Array,
    unread_facility_reports: Number,
    guru_permissions: Array, // ['manage-discipline', 'manage-permits', ...]
    events: Array,
    disposisi_pending: Array,
});

const stripHtml = (html) => {
    if (!html) return '-';
    let formatted = html
        .replace(/<li>/gi, ' • ')
        .replace(/<\/li>/gi, ' ')
        .replace(/<\/p>/gi, ' ')
        .replace(/<br\s*[\/]?>/gi, ' ')
        .replace(/<[^>]+>/g, '');
    try {
        const doc = new DOMParser().parseFromString(formatted, 'text/html');
        const text = doc.body.textContent || doc.body.innerText || '';
        return text.replace(/\s+/g, ' ').trim() || '-';
    } catch (e) {
        return formatted.replace(/\s+/g, ' ').trim() || '-';
    }
};

// Helper: cek apakah guru memiliki permission tertentu
const hasPermission = (perm) => {
    if (!props.guru_permissions) return false;
    return props.guru_permissions.includes(perm);
};

const page = usePage();
const user = computed(() => page.props.auth.user);

const showStudentModal = ref(false);
const selectedStatusKey = ref(null);
const selectedStatusTitle = ref('');
const showTHModal = ref(false);

const openStatusDialog = (statusKey, title) => {
    selectedStatusKey.value = statusKey;
    selectedStatusTitle.value = title;
    showStudentModal.value = true;
};

const openTHDialog = () => {
    showTHModal.value = true;
};

const filteredWaliStudents = computed(() => {
    if (!props.wali_kelas_summary?.students || !selectedStatusKey.value) return [];
    return props.wali_kelas_summary.students.filter(s => s.status === selectedStatusKey.value);
});

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', { 
        day: 'numeric', 
        month: 'short' 
    });
}

// 📅 KALENDER AKADEMIK MOBILE LOGIC
const isCalendarOpen = ref(false);
const displayCalendarDialog = ref(false);
const selectedCalendarEvent = ref(null);

const addOneDay = (dateStr) => {
  if (!dateStr) return null;
  const d = new Date(dateStr);
  d.setDate(d.getDate() + 1);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
};

const formatDateIndo = (dateStr) => {
  if (!dateStr) return '-';
  const parts = dateStr.split('-');
  if (parts.length === 3) {
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    return d.toLocaleDateString('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });
  }
  return dateStr;
};

const handleCalendarEventClick = (info) => {
  const ext = info.event.extendedProps;
  selectedCalendarEvent.value = {
    id: info.event.id,
    title: ext.originalTitle || info.event.title,
    type: ext.type,
    is_holiday: ext.is_holiday,
    start_date: ext.rawStartDate,
    end_date: ext.rawEndDate,
  };
  displayCalendarDialog.value = true;
};

const typeLabels = {
  holiday: 'Libur / Non-KBM',
  event: 'Kegiatan Sekolah (Ada KBM)',
  exam: 'Ujian / Asesmen (Ada KBM)',
  info: 'Informasi / Pengumuman',
};

const eventColors = {
  holiday: '#ef4444',
  event: '#10b981',
  exam: '#f97316',
  info: '#3b82f6',
};

const mobileCalendarOptions = computed(() => ({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  height: 'auto',
  locale: 'id',
  eventDisplay: 'block',
  headerToolbar: {
    left: 'prev,next',
    center: 'title',
    right: 'today'
  },
  buttonText: {
    today: 'Hari Ini',
  },
  eventClick: handleCalendarEventClick,
  events: (props.events || []).map(e => {
    const isNoKbm = Boolean(e.is_holiday || e.type === 'holiday');
    const badgeText = isNoKbm ? ' [Non-KBM]' : ' [KBM]';
    return {
      id: e.id,
      title: `${e.title}${badgeText}`,
      start: e.start_date,
      end: e.end_date !== e.start_date ? addOneDay(e.end_date) : undefined,
      allDay: true,
      backgroundColor: isNoKbm ? '#ef4444' : (eventColors[e.type] ?? '#10b981'),
      borderColor: 'transparent',
      textColor: '#ffffff',
      extendedProps: {
        type: e.type,
        is_holiday: isNoKbm,
        originalTitle: e.title,
        rawStartDate: e.start_date,
        rawEndDate: e.end_date,
      }
    };
  }),
}));
</script>

<style scoped>
:deep(.mobile-calendar-container .fc) {
    font-size: 0.75rem !important;
}

:deep(.mobile-calendar-container .fc-toolbar-title) {
    font-size: 0.95rem !important;
    font-weight: 700 !important;
}

:deep(.mobile-calendar-container .fc-button) {
    padding: 0.2rem 0.4rem !important;
    font-size: 0.75rem !important;
}

:deep(.mobile-calendar-container .fc-event) {
    white-space: normal !important;
    word-break: break-word !important;
    cursor: pointer !important;
    padding: 2px 4px !important;
    margin-bottom: 2px !important;
    border-radius: 4px !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
}

:deep(.mobile-calendar-container .fc-event-title) {
    white-space: normal !important;
    word-break: break-word !important;
    font-weight: 600 !important;
    font-size: 0.68rem !important;
    line-height: 1.2 !important;
    display: block !important;
}

:deep(.mobile-calendar-container .fc-daygrid-day-frame) {
    min-height: 48px !important;
}
</style>