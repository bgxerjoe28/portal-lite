# Portal Lite - CBT & Penugasan

Aplikasi Computer Based Test (CBT) dan Manajemen Penugasan Sekolah berbasis Laravel dan Vue.js.

## Fitur Utama

- **Computer Based Test (CBT)**:
  - Manajemen Bank Soal dengan dukungan import langsung dokumen Microsoft Word (.docx).
  - Berbagai tipe soal: Pilihan Ganda, Pilihan Ganda Kompleks (Checklist), Menjodohkan (Matching), dan Uraian.
  - Manajemen Ruangan & Pengaturan Denah Tempat Duduk via import Excel.
  - Sistem Proktoring Ujian Real-time (Live Monitor, Suspensi/Peringatan, Blokir, Token Dinamis).
  - Skoring Otomatis (Auto-grading) & Analisis Butir Soal (IRT / CTT).
- **Modul Penugasan Siswa**:
  - Pembuatan dan distribusi tugas terstruktur oleh guru.
  - Pengumpulan tugas online oleh siswa (dokumen/media/teks).
  - Penilaian fleksibel berbasis rubrik atau nilai langsung.
- **Manajemen Data Akademik Pendukung**:
  - Manajemen Siswa, Guru, dan Kelas (Rombongan Belajar) terintegrasi.

## Stack Teknologi

- [Laravel](https://laravel.com) - Framework Backend PHP.
- [Vue.js 3](https://vuejs.org) - Framework Frontend (Composition API).
- [Inertia.js](https://inertiajs.com) - Pendekatan *Modern Monolith* tanpa API eksternal.
- [PrimeVue](https://primevue.org) - UI Component Library.
- [PostgreSQL](https://www.postgresql.org/) - Relational Database Management System.

## Versi Saat Ini

- **Version**: 2.16.4
- **Channel**: Stable
- **Released**: 2026-08-27

### Sorotan Pembaruan (v2.16.4)
- **Integritas Jawaban Siswa CBT & Penanganan Race Condition (`cbt_student_answers`)**:
  - Penambahan PostgreSQL *Unique Constraint* `(cbt_student_exam_id, cbt_question_id)` untuk mencegah terciptanya jawaban ganda per nomor soal di level basis data.
  - Script migrasi otomatis membersihkan data duplikat lama secara cerdas tanpa menghilangkan data esai atau skor siswa.
  - Exception handling cerdas pada autosave jawaban backend (`StudentCbtController::saveAnswer`) untuk mencegah pesan gagal / HTTP 500 saat siswa klik cepat atau koneksi balapan.
  - Perhitungan jumlah terjawab akurat pada layar monitoring pengawas ujian (`CbtProctorController`) dan rekapitulasi hasil ujian (`CbtExamController`).

### Sorotan Pembaruan (v2.16.3)
- **Penyempurnaan Laporan Jurnal Kelas & Jurnal Mengajar Guru (`/teacher/reports/classroom` & `/teacher/reports/personal`)**:
  - Kolom Presensi Lengkap 6 Kategori: `Jml`, `H (Hadir)`, `S (Sakit)`, `I (Izin)`, `D (Dispensasi)`, dan `A (Alpa)` terintegrasi otomatis dengan surat izin siswa di `/permits`.
  - Kolom Siswa Tidak Hadir format bersih (*Clean & Compact*): Menampilkan nama dan kode status `Nama Siswa (Status)` tanpa tautan URL/bukti panjang.
  - Dokumen cetak PDF resmi dikonfigurasi dalam format **A4 Portrait** siap cetak dan arsip.
  - Tampilan Web Desktop dan Mobile Jurnal Kelas dilengkapi badge perincian `H/S/I/D/A` dan daftar siswa yang tidak hadir.
- **Penyempurnaan CLI Backup & Restore Remote (`backup:restore`)**:
  - Dukungan opsi `--bucket=` untuk override nama bucket S3/MinIO dan *real-time progress bar streaming download*.
- **Perbaikan Ikon UI Dashboard**:
  - Memperbaiki class ikon pintasan *Rekap Siswa* menjadi `pi-list-check`.

### Sorotan Pembaruan (v2.16.2)
- **Fitur Rekap Presensi Siswa per Mata Pelajaran Guru (`/teacher/attendance-recap`)**:
  - Halaman web matriks presensi siswa per mata pelajaran & kelas yang diampu guru pada tahun ajaran aktif.
  - Dua opsi filter: Filter Bulanan (Bulan & Tahun) dan Filter Rentang Tanggal Bebas (*Datepicker*).
  - Cetak Laporan PDF Resmi (A4 Landscape) lengkap dengan Kop Surat Sekolah, metadata mapel & kelas, legenda status, dan tanda tangan guru mata pelajaran ber-NIP.
  - Export data ke format Excel (.xlsx).
  - Integrasi tab menu "Rekap Presensi" di jajaran menu atas guru dan tombol aksi cepat "Rekap Siswa" di Dashboard Guru.
- **Fitur Sinkronisasi Izin Siswa dari Google Sheets (2 Arah) (`/permits`)**:
  - **Tarik Data di Portal**: Tombol *"Tarik Google Sheet"* di menu Pusat Izin Siswa (`/permits`) untuk membaca data izin langsung dari Google Sheet publik dengan pencocokan nama & kelas cerdas, pemisahan izin rentang tanggal ke hari aktif KBM, dan tautan bukti Google Drive.
  - **Otomatisasi 2 Arah Google Apps Script**: Script Apps Script bawaan di Google Sheets yang dapat mengirim data baru ke API Portal (`/api/permits/sync-sheet`) dan secara otomatis mencentang kolom *"Cek Input Portal"* menjadi `TRUE` setelah data tersimpan di database portal.

### Sorotan Pembaruan (v2.16.1)
- **Fitur Import Data Ruangan dengan Template Excel (`/admin/sarpras/rooms`)**:
  - Dukungan import master data ruangan secara massal via Excel (.xlsx/.xls/.csv) dengan tombol unduh template resmi (`FacilityRoomTemplateExport`).
  - Pemrosesan otomatis dengan `FacilityRoomImport` (updateOrCreate berbasis kode ruangan unik, normalisasi status, parsing fasilitas JSON array, dan proteksi transaksi database atomik).
- **Fitur Import Data Aset & Peralatan dengan Template Excel (`/admin/sarpras/assets`)**:
  - Dukungan import data inventaris aset dan alat secara massal via Excel (.xlsx/.xls/.csv) dengan tombol unduh template resmi (`FacilityAssetTemplateExport`).
  - Pemrosesan otomatis dengan `FacilityAssetImport` (updateOrCreate berbasis kode aset unik, normalisasi kondisi & status, dan auto-generate QR Code token untuk aset baru).

### Sorotan Pembaruan (v2.16.0)
- **Fitur Status Post & Unpost Penilaian Guru (`/penilaian/grades`)**:
  - Kolom status interaktif *Posted / Unposted (Draf)* pada daftar penilaian guru untuk menyeimbangkan duplikasi nilai (auto-sync CBT vs entri manual).
  - Penilaian unposted otomatis dikecualikan dari perhitungan rata-rata komponen dan Nilai Akhir (NA) pada rekapitulasi nilai guru.
  - Penilaian unposted disembunyikan sepenuhnya dari portal nilai siswa.
- **Koreksi & Pembaruan Kunci Jawaban CBT (Zero Data Loss)**:
  - Edit kunci jawaban & bobot per butir soal secara manual langsung dari antarmuka web bank soal (`/cbt/bank/{id}/questions`).
  - Fitur Import Revisi Kunci Jawaban (Excel Saja) dengan 5 lapis pengaman yang memperbarui kunci tanpa menghapus ID soal maupun riwayat jawaban peserta.
  - Tombol unduh template resmi Excel kunci CBT langsung dari modal import.
- **Bundel Lengkap Cetak Laporan PDF 3-in-1 (CBT)**:
  - Penyatuan 3 dokumen laporan ujian (Laporan Jawaban, Matriks Dikotomi, dan Daftar Nilai + Tanda Tangan) dalam 1 kali cetak terstruktur.
  - Penyeragaman header tombol menjadi `SplitButton` biru (*severity="info"*) pada halaman Hasil Ujian (`/cbt/exams/{id}/results`) dan Analisis Bank Soal (`/cbt/bank/{id}/analytics`).
- **Integrasi Penilaian Ulang (Re-Grading) & Analisis CBT**:
  - Tombol *"Hitung Ulang Nilai & Analisis"* otomatis mengeksekusi penilaian ulang (*re-grade*) ke seluruh peserta yang telah selesai sebelum menghitung metrik CTT & IRT.

### Sorotan Pembaruan (v2.15.5)
- **Konfigurasi Provinsi pada Profil Sekolah (`/admin/settings`)**:
  - Penambahan input field **Provinsi** (`school_province`) berdampingan dengan Kota/Kabupaten di menu Pengaturan Situs Admin.
  - Perbaikan inisialisasi state form `useForm` untuk parameter `school_province`, `kop_pemprov`, dan `kop_dinas`.
- **Standarisasi Urutan Alamat pada Kop Surat & PDF Laporan**:
  - Penyeragaman format baris alamat resmi pada Kop Surat Terpusat, Dokumen PDF Surat Keluar Resmi, Laporan Ekstrakurikuler, dan Preview Kop Surat menjadi: `Alamat` $\rightarrow$ `Kota/Kabupaten` $\rightarrow$ `Provinsi` $\rightarrow$ `Kode Pos`.

### Sorotan Pembaruan (v2.15.4)
- **Penyempurnaan Manajemen Status & Filter Jadwal Ujian CBT (`/cbt/exams`)**:
  - Komponen `ToggleSwitch` interaktif langsung pada kolom Status tabel Jadwal Ujian untuk aktivasi/deaktivasi 1 klik.
  - Tab/tombol filter status (*Semua*, *Ujian Aktif*, *Ujian Non-Aktif*) lengkap dengan penghitung (*counter*) jumlah jadwal ujian.
  - Endpoint `PATCH /cbt/exams/{id}/toggle-active`.
- **Auto-Sync Nilai Ujian CBT ke Buku Nilai Siswa (`StudentGrade` / Modul Penilaian)**:
  - Integrasi otomatis pembuatan *Grading Item* per kelas peserta dan sinkronisasi skor siswa yang telah selesai ujian ke modul Penilaian saat Kategori Nilai dipilih pada jadwal ujian.
  - Nilai CBT langsung muncul di portal nilai siswa (`/grades`) dan buku nilai guru.
  - Sinkronisasi otomatis real-time saat siswa menyelesaikan ujian di masa mendatang.
- **Restriksi Otorisasi & Hak Akses Menu Jadwal Pengawas (`/cbt/proctor-schedules`)**:
  - Penerapan Role-Based Access Control (RBAC) pada Jadwal Pengawas: Admin memiliki hak kelola penuh (CRUD), sedangkan Guru hanya dapat melihat penugasan pengawasannya sendiri.
  - Penanda visual khusus (*Sesi Ujian Mandiri 07:00 - 15:00*) untuk membedakan sesi pengawasan reguler dengan sesi otomatis Ujian Mandiri.

### Sorotan Pembaruan (v2.15.3)
- **Refactoring Filesystem Dinamis & Standarisasi Object Storage (MinIO / S3)**:
  - Mengeliminasi seluruh pemanggilan disk statis (`'public'` & `'s3'`) di seluruh controller (Kesiswaan, Akademik, Sarpras, Daftar Ulang, Auth, Setting) dan menggantinya dengan `config('filesystems.default')` agar file langsung terunggah ke MinIO S3 secara dinamis.
  - Memperbaiki issue region S3 pada controller Catatan Layanan BK (`/bk/service-records`).
- **Command Artisan Sinkronisasi Storage Lokal ke S3 (`storage:sync-public-to-s3`)**:
  - Command CLI berkecepatan tinggi dengan cache index S3 in-memory untuk memigrasikan file dari disk lokal ke Object Storage (MinIO/S3) tanpa duplikasi file.
  - Berhasil menyinkronkan 19.953 file ke MinIO staging dengan konfigurasi *Public Read Bucket Policy*.

### Sorotan Pembaruan (v2.15.2)
- **Pembedaan Status Selesai Mandiri vs Selesai dari Sistem pada CBT**:
  - Kolom `submit_type` pada tabel `cbt_student_exams` untuk mendeteksi sumber penyelesaian ujian: *Selesai Mandiri (Siswa)*, *Selesai Sistem (Waktu Habis)*, *Selesai Sistem (Pengawas)*, *Selesai Sistem (Dipaksa Guru)*, dan *Selesai Sistem (Pelanggaran / Ban)*.
  - Penyesuaian visual tag, badge, dan keterangan status di halaman Hasil Ujian (`Results.vue`) dan halaman Monitor Pengawas (`Monitor.vue`).
- **Fitur Penyelesaian Massal Siswa Aktif pada Hasil Ujian CBT (`/cbt/exams/{id}/results`)**:
  - Tombol aksi *"Selesaikan Semua Siswa Aktif"* dengan grading otomatis dan pembaruan instan metrik analitik Classical Test Theory (CTT).
- **Alternatif Tampilan Tabel & Opsi Pagination Dinamis pada Ruang Pengawas CBT (`/cbt/proctor/exam-room/{id}`)**:
  - Switcher mode antara Denah Kotak (Card Grid) dan Tabel Peserta (Table View) dengan pencarian instan dan penyimpanan preferensi lokal.
  - Dropdown baris per halaman (**10 - 50 - 100**) pada tabel monitor pengawas, tabel presensi peserta, dan tabel hasil ujian.
- **Helper & Composable Standarisasi Paginasi Terpusat (`resources/js/Utils/pagination.js`)**:
  - Utility global `$pagination({ label: 'nama' })` dan composable `usePagination()` untuk standarisasi instan opsi ukuran halaman (10, 50, 100) dan template di seluruh tabel sistem.

### Sorotan Pembaruan (v2.15.1)
- **Hotfix Modul CBT - Pemantauan Ruang Ujian & Denah Bangku Pengawas (`/cbt/proctor/exam-room/{id}`)**:
  - Perbaikan error SQL query pada endpoint status proktor akibat filter kolom `cbt_session_id` yang tidak ada pada tabel `cbt_room_students`.
  - Memulihkan fungsi pemantauan real-time denah 36 kursi, statistik peserta ujian, dan tabel presensi peserta pada halaman pengawas CBT.

### Sorotan Pembaruan (v2.15.0)
- **Alur Simpan sebagai Draf pada Penugasan Siswa (`/student/assignments/{id}`)**:
  - Siswa kini dapat menyimpan progres jawaban (Pilihan Ganda, Uraian, dan Lampiran URL Google Drive) sebagai **Draf** kapan saja tanpa harus langsung mengumpulkan.
  - Banner informatif status draf yang menampilkan waktu penyimpanan terakhir dan pengisian otomatis seluruh jawaban draf saat siswa membuka kembali halaman tugas.
  - Lencana status *"Draf Tersimpan"* dan tombol aksi *"Lanjutkan Pengerjaan"* pada daftar tugas siswa (`/student/assignments`).
  - Pengetatan proteksi prasyarat tugas: draf tidak dihitung sebagai penyelesaian tugas prasyarat (*prerequisite*) hingga dikumpulkan secara resmi.
- **Fitur Paksa Kumpulkan Draf oleh Guru (`/teacher/assignments/{id}/submissions`)**:
  - Guru dapat memaksa pengumpulan draf jawaban siswa (baik secara individual per siswa via *"Paksa Kumpulkan"* maupun massal via *"Paksa Kumpulkan Semua Draf"*), terutama setelah batas waktu pengumpulan (*due date*) berakhir.
  - Sistem otomatis mengevaluasi jawaban draf (penilaian otomatis pilihan ganda & keyword similarity essay), mencatat waktu submit, mengunci akses edit, dan menyinkronkan nilai ke modul Penilaian (`StudentGrade`).

### Sorotan Pembaruan (v2.14.0 & v2.14.1)
- **Manajemen Disposisi & E-Office Terpadu**:
  - Alur disposisi terpusat dari Kepala Sekolah / Admin ke Guru, Pegawai, atau Siswa dengan RichTextEditor dan fitur *Auto Fan-Out* per kelompok unit kerja (Kurikulum, Kesiswaan, Sarpras, Humas, BK, TU, Wali Kelas).
  - Isolasi pelaporan tindak lanjut mandiri per penerima disposisi dan dukungan upload multi-file lampiran (SPPD, Struk Bensin/Tol, Surat Tugas, Foto Kegiatan, Dokumen Pendukung) yang terhubung langsung ke storage S3 MinIO.
  - Dialog Review Eksekutif untuk Kepala Sekolah & Admin dengan *In-App File Previewer* untuk berkas PDF dan Gambar.
  - Tombol pintasan **Surat** pada *Bottom Navigation Bar* (`MobileLayout`) untuk Guru (`/surat/kalender`) dan Kepala Sekolah (`/surat/dashboard`).
- **Standardisasi & Pembersihan Access Control Persuratan**:
  - Penyeragaman permission modul surat ke standar `access-surat` dan `manage-surat`, serta pembersihan permission lama dari `/admin/access-control`.
  - Pembatasan arsip surat masuk dan surat keluar khusus staf/pimpinan, serta filter personal pada Kalender Persuratan Guru.

### Sorotan Pembaruan (v2.13.0)
- **Peminjaman Fasilitas & Aset Guru (`/teacher/sarpras-reservations`)**: Modul mandiri untuk peminjaman ruangan dan peralatan pembelajaran bagi guru dengan pengecekan bentrok jadwal realtime dan persetujuan langsung dari Tim Sarpras.
- **Rekap Nilai Siswa (`/student/grades`)**: Halaman rekapitulasi nilai rapor interaktif bagi siswa lengkap dengan status KKM, breakdown komponen nilai (TP, STS, SAS), dan penambahan menu "Rekap Nilai" di navigasi bawah siswa.
- **Chain Top Navigation Menu**: Navigasi tab terpadu untuk modul guru (`TeacherTabMenu.vue`) dan modul penilaian guru (`PenilaianTabMenu.vue`).
- **Pengetatan Regulasi Peminjaman Sarpras Siswa**: Wajib terdaftar di ekstrakulikuler aktif dan mewakili eskul untuk seluruh peminjaman sarpras, serta standardisasi `SiswaLayout` pada halaman reservasi siswa.

### Sorotan Pembaruan (v2.12.0)
- **Modul E-Office & Digital Signature (Tanda Tangan Elektronik / TTE)**:
  - **TTE Kriptografi & Token Anti-Manipulasi**: Penandatanganan surat elektronik berstandar kriptografi (UUID + SHA-256 integrity hash) yang menyematkan spesimen QR Code verifikasi pada lembar dokumen PDF.
  - **Halaman Verifikasi Publik (`/surat/verifikasi/{token}`)**: Akses verifikasi dokumen secara instan tanpa login via pemindaian kamera HP, menampilkan rincian validitas dokumen resmi, identitas penandatangan, waktu penandatanganan, dan tombol unduh PDF resmi.
  - **Approval Workflow & Paraf Berjenjang**: Pengelolaan siklus hidup surat kedinasan (Draft $\rightarrow$ Verifikasi KTU/Waka $\rightarrow$ TTE Kepala Sekolah) lengkap dengan fitur catatan revisi dan audit trail.
  - **Otorisasi PIN TTE 6-Digit**: Pengamanan ganda akun penandatangan (Kepala Sekolah/Plt) dengan PIN keamanan 6-digit saat membubuhkan TTE.
  - **PDF Engine Kedinasan & Integrasi Logo `/admin/settings`**: Otomatisasi generate PDF resmi dengan Kop Surat dinamis, integrasi logo sekolah dan logo pemda dari menu pengaturan admin, serta tata letak baku kedinasan.

### Sorotan Pembaruan (v2.11.0)
- **Modul Manajemen Sarpras & Branding QR Code**: Sistem peminjaman fasilitas dan inventaris aset fisik dengan dukungan upload multi-foto, galeri thumbnail cover, kategori dinamis (combobox editable), dan QR code tagging otomatis berstandar *High Error Correction (`ecc=H`)* dengan sematan logo resmi sekolah di bagian tengah QR.
- **Standardisasi Modal & Migrasi Global PrimeVue v4**: Menyelaraskan seluruh modal dialog di modul Sarpras dengan template standar Admin serta menuntaskan migrasi wrapper markup `p-input-icon-left` legacy ke komponen PrimeVue v4 `IconField` dan `InputIcon` di seluruh modul proyek.

### Sorotan Pembaruan (v2.10.8)
- **Format Gelar Otomatis & Pembersihan Data Ganda**: Sistem kini otomatis merangkai dan menampilkan gelar akademik guru secara rapi dan seragam di semua tampilan. Dilengkapi kemampuan (Regex) untuk mendeteksi dan membuang duplikasi gelar jika guru terlanjur menuliskan gelarnya di dalam kolom nama asli, memastikan hasil cetak PDF (Kop, Tanda Tangan, dll) tetap proporsional tanpa nama/gelar ganda.

### Sorotan Pembaruan (v2.10.7)
- **Pembersihan Otomatis Jadwal Pengawas Mandiri & Perbaikan Status CBT**: Memperbaiki isu manajemen proctor schedule yang tertinggal saat ujian mandiri dihapus atau diubah dengan sistem pembersihan otomatis, serta perlindungan status aktif ujian (tidak ter-reset saat ada update data jadwal).
- **Artisan Command Khusus**: Menyertakan `php artisan cbt:clean-orphaned-proctor-schedules` untuk operasi bersih-bersih server dengan aman.

### Sorotan Pembaruan (v2.10.6)
- **Ekspor PDF & Optimalisasi Modul Sarpras**: Menambahkan fitur cetak rekapitulasi data kerusakan sarana & prasarana ke dalam format PDF yang rapi beserta keterangan verifikator. Fitur filter Ruang/Gedung kini disempurnakan dengan susunan abjad dan *search-box* untuk pencarian instan. Modul penampil foto bukti kerusakan (Popup) juga telah di-upgrade menggunakan *Interactive Photo Viewer* dengan kemampuan bawaan untuk zoom in, zoom out, dan rotasi.

### Sorotan Pembaruan (v2.10.5)
- **Kelola Laporan Sarana & Prasarana**: Menambahkan fungsionalitas pendataan daftar rincian barang (jenis, nama, ruangan, tingkat kerusakan, dan status perbaikan) langsung di dalam laporan sarana & prasarana menggunakan fitur baris dinamis (*dynamic rows*) pada popup *modal*. Menyertakan laman Rekapitulasi Daftar Barang di `/admin/facility-reports/recap` dengan tabel yang dipisah antara Nama Barang & Ruangan, fitur saring (*filter*) nama ruang/gedung anti-redundan otomatis, hingga pengaturan edit atau hapus item lengkap dengan dialog konfirmasi demi mengantisipasi ketidaksengajaan operasi.

### Sorotan Pembaruan (v2.10.4)
- **Pengaturan & Laporan PDF (Kop Surat Terpusat)**: Menambahkan _blade component_ terpusat (`kop-surat.blade.php`) untuk menggantikan kode berulang pada Kop Surat di berbagai laporan PDF (seperti laporan Bulanan dan laporan Sesi). Menyesuaikan gaya spasi dengan _white-space: nowrap_ agar nama instansi tidak terpotong ke baris baru, menjamin konsistensi tata letak kop surat di semua laporan sekolah.

### Sorotan Pembaruan (v2.10.3)
- **Modul Presensi Siswa (Integrasi S3 MinIO & Laravel Proxy Stream Route)**: Menambahkan proxy stream controller (`GET /presensi/image/{filename}`) dan accessor dinamis pada model `Attendance` untuk bypass error `403 AccessDenied` dari bucket MinIO private. Dilengkapi dengan perbaikan *cURL SSL Verify error 60*, failover dual-layer otomatis ke storage lokal jika MinIO offline, proteksi fallback pada background upload job, serta pembaruan antarmuka Vue Log Presensi & Rekap Bulanan Siswa.

### Sorotan Pembaruan (v2.10.2)
- **Modul Ekstrakurikuler (Guru Pembina)**: Menambahkan antarmuka bagi Guru Pembina untuk dapat memantau dan melihat daftar lengkap siswa yang terdaftar di kegiatan ekstrakurikuler yang dibinanya secara langsung.

### Sorotan Pembaruan (v2.10.1)
- **Modul CBT & Admin Diagnostik (PDF Report & Real-time Server Diagnostics)**: Menambahkan ekspor laporan PDF `REPORT_DATA_JAWABAN.pdf` pada tingkat Bank Soal dan Sesi Ujian, pengurutan siswa A-Z, format gender strict (`L`/`P`), header 1 baris tanpa wrap, decoupling pembukaan halaman GET (<50ms), serta diagnostik real-time service Python Uvicorn & MinIO S3 di `/admin/backups` dengan proteksi token `X-CBT-Secret`.

### Sorotan Pembaruan (v2.10.0)
- **Modul CBT (Level-Up Scoring: Decoupled CTT & Python IRT Microservice)**: Memisahkan secara total komputasi nilai CTT (Classical Test Theory) dan IRT (Item Response Theory). IRT diaktifkan otomatis **hanya jika jumlah peserta $N \ge 100$** untuk menjamin konvergensi parameter. Seluruh komputasi IRT didelegasikan secara asinkron ke Microservice Python terpisah (`cbt-irt-service`) via Redis Queue / Async Webhook tanpa membebani rendering view atau controller Laravel.

### Sorotan Patch Pembaruan (v2.9.14)
- **Modul CBT & Proktoring (Pengecualian Fullscreen Apple)**: Menambahkan pengecualian (*bypass*) fitur mode layar penuh (Fullscreen) otomatis khusus bagi pengguna perangkat Apple (iOS/macOS dan Safari) pada laman ujian CBT untuk mencegah terjadinya deteksi kecurangan palsu yang diakibatkan oleh kurang sempurnanya dukungan API Fullscreen bawaan Apple, sedangkan aturan pencegahan tab-switching tetap bekerja normal.

### Sorotan Patch Pembaruan (v2.9.13)
- **Modul CBT & Proktoring (Audit Logging, Re-entry, Pembukaan Serentak, & Proteksi Ban)**: Mengintegrasikan sistem audit log lengkap pada seluruh aksi CBT (`ActivityLogger`), perbaikan zona waktu ISO 8601, penambahan fitur pembukaan kembali sesi ruangan secara serentak untuk seluruh siswa di ruangan, proteksi ketat bagi siswa terban Strike 4+ (wajib ujian ulang total), pengecualian siswa tersuspensi dari penutupan massal, perlindungan akses hasil ujian berbasis pemilik (`404 Not Found`), serta perombakan antarmuka lembar ujian siswa dengan tombol interaktif 3D tactile.

### Sorotan Patch Pembaruan (v2.9.12)
- **Pemilihan 2 Mata Pelajaran TKA Sekaligus**: Memperbarui sistem pemilihan mata pelajaran TKA bagi siswa Kelas XII agar mendukung pemilihan 2 mata pelajaran sekaligus pada modal peringatan (`SiswaLayout.vue`), dengan validasi otomatis mapel berbeda, penguncian pilihan yang sudah tersimpan, serta pembaruan middleware dan controller (`TkaStudentController`) untuk kuota 2 mata pelajaran.

### Sorotan Patch Pembaruan (v2.9.11)
- **Rekap Pilihan TKA Siswa per Kelas & Perbaikan Nama Siswa**: Menambahkan fitur Rekap Daftar Siswa TKA per Kelas XII (`/akademik/tka-recap`) yang menampilkan statistik siswa, mata pelajaran yang dipilih, dan penanda `-` bagi siswa yang belum memilih. Memperbaiki pula bug nama siswa kosong di tabel TKA melalui penambahan *accessor* pada model `Student` dan penyempurnaan antarmuka Vue.

### Sorotan Patch Pembaruan (v2.9.10)
- **Perbaikan Relasi Kelas Siswa & Filter Status**: Menambahkan method relasi `classroom()` pada model `Student` (`Modules/Akademik/app/Models/Student.php`) untuk mengatasi error `RelationNotFoundException: Call to undefined relationship [classroom]` saat pemanggilan data kelas siswa, serta memperbaiki bug filter status pada method `classInYear()`.

### Sorotan Patch Pembaruan (v2.9.9)
- **Import & Export Massal NIS/NISN Siswa**: Menambahkan fungsionalitas untuk melakukan import dan export data NIS/NISN siswa secara massal melalui file Excel (Bulk Update) pada halaman Manajemen Siswa (`/admin/akademik/students`), mempermudah pembaruan data nomor induk siswa.

### Sorotan Patch Pembaruan (v2.9.8)
- **Penyempurnaan Modul Surat**: Memperbaiki layout dan standarisasi UI pada berbagai modal surat, menambahkan shortcut (Quick Access) pada dashboard surat untuk mempermudah akses role pegawai, serta memperbaiki *route model binding* dan seeder *permission* agar sinkron dengan hierarki sistem.

### Sorotan Patch Pembaruan (v2.9.7)
- **Fix Cloudflare 524 Timeout — Decoupled Upload & Presigned URL**: Menyelesaikan dua titik timeout kritis di server produksi:
  - *Presensi Siswa*: Upload foto ke MinIO kini berjalan **di background via Laravel Queue Job** (`UploadAttendanceImageJob`). Data presensi (clock_in, status, GPS) langsung masuk DB dan response balik ke siswa dalam < 1 detik — tidak lagi memblokir request. Job otomatis retry 3× jika MinIO tidak stabil.
  - *Download Backup*: Method download backup kini men-generate **MinIO Presigned URL** (berlaku 15 menit) dan me-redirect browser langsung ke MinIO — file ZIP tidak lagi di-stream melewati Cloudflare.

### Sorotan Patch Pembaruan (v2.9.6)
- **Standarisasi Terminologi**: Menyeragamkan penggunaan istilah di seluruh sistem (menggunakan "Presensi" dari "Kehadiran", "Terlambat" dari "Telat", "Tanpa Keterangan (A)" dari "Alfa", dan "Tidak Hadir (TH)" dari "Absen Mapel").

### Sorotan Patch Pembaruan (v2.9.5)
- **Manajemen Backup**: Mengembalikan konfigurasi penyimpanan default backup dari S3 ke local disk untuk efisiensi waktu proses, dan menambahkan tombol manual **Sync to S3 Minio** untuk memindahkan arsip backup ke storage cloud secara fleksibel.

### Sorotan Patch Pembaruan (v2.9.4)
- **Modul Surat Menyurat & Layanan BK**: Membangun modul baru Surat Masuk, Surat Keluar, Disposisi Multi-Tujuan, serta laporan Tindak Lanjut dan integrasi antarmuka Layanan Bimbingan Konseling (BK).

### Sorotan Patch Pembaruan (v2.9.3)
- **Pengelolaan Storage & Impor CBT**: Penyempurnaan `WordQuestionParser` & `CbtImageController` untuk MinIO/S3 storage serta integrasi panel diagnostik backup database.

### Sorotan Patch Pembaruan (v2.9.2)
- **Antarmuka & Presensi Modul TKA**: Penambahan halaman Vue, controller, dan fitur presensi siswa untuk modul Tes Kompetensi Akademik (TKA).

### Sorotan Patch Pembaruan (v2.9.1)
- **Perbaikan Copy-Paste Penugasan Siswa**: Memperbaiki isu di mana siswa tidak dapat melakukan *copy-paste* jawaban ke dalam formulir pengumpulan tugas, dengan memberikan atribut `user-select` eksplisit dan bypass *event listener* pada form.

### Sorotan Rilis Pembaruan (v2.9.0)
- **Modul Tes Kompetensi Akademik (TKA)**: Membangun infrastruktur backend awal untuk fitur pilihan Mapel TKA khusus kelas XII yang alurnya terintegrasi mirip ekstrakurikuler. Menyiapkan skema database `tka_subjects`, `tka_students`, `tka_sessions`, dan `tka_attendances` beserta *seeder* otomatis untuk 12 mapel baku (Matematika Lanjut, Fisika, dll) yang langsung terikat pada Tahun Ajaran Aktif.

### Sorotan Rilis Pembaruan (v2.8.7)
- **Pembersihan Otomatis File Gambar Soal saat Hapus / Kosongkan Bank Soal (CBT)**: Memperbaiki kebocoran penyimpanan (*orphaned files*) — sebelumnya file gambar di MinIO/S3 dan local storage tidak pernah terhapus saat guru mengosongkan atau menghapus bank soal. Kini `CbtBankController` menghapus semua file terkait menggunakan strategi dua lapis: **(1)** scan `question_text` HTML untuk ekstrak nama file dari URL proxy dan hapus dari disk aktif, **(2)** hapus file fisik lokal berdasarkan pola nama `cbt_bank_{id}_*` sebagai safety net.

### Sorotan Rilis Pembaruan (v2.8.6)
- **Perbaikan Gambar Soal CBT Tidak Tampil — Error 403 dari MinIO**: Mengganti pendekatan URL langsung ke MinIO (yang 403 pada bucket private) dengan **Laravel Proxy Route** (`GET /cbt/questions/image/{filename}` → `CbtImageController`). Controller baru ini mem-streaming gambar dari storage backend aktif (MinIO, S3, atau lokal) melalui Laravel, sehingga tidak memerlukan konfigurasi bucket policy/ACL di sisi MinIO. Dilengkapi fallback otomatis ke disk lokal untuk soal lama, sanitasi filename (cegah path traversal), dan header browser cache 1 hari.

### Sorotan Rilis Pembaruan (v2.8.5)
- **Perbaikan Error Import Soal CBT (`SQLSTATE[22P05]` – PostgreSQL SQL_ASCII)**: Mendiagnosis dan memperbaiki kegagalan impor soal Word yang mengandung karakter Unicode seperti en-dash (`–`). Akar masalah adalah Laravel's built-in `'array'` cast yang menghasilkan escape sequence `\u2013` di JSON — format ini ditolak oleh PostgreSQL `SQL_ASCII`. Solusinya: membuat custom Eloquent cast `App\Casts\JsonUnescapedUnicode` yang menyimpan JSON dengan karakter UTF-8 literal (`JSON_UNESCAPED_UNICODE`). Cast baru ini diterapkan pada 7 model: `CbtQuestion`, `CbtStudentExam`, `CbtStudentAnswer`, `CbtProctorSchedule`, `AssignmentQuestion`, `LearningOutcomeCP`, dan `AcademicYear`.
- **Perbaikan IDE Errors & Warnings (5 file)**: Menyelesaikan 2 error dan 9 warning static analysis: `Auth::id()` di `StudentSpEvaluationService`, import `DB` facade di migration `discipline_levels`, PHPDoc `@var` pada `StudentAssignmentController` & `TeacherAssignmentController`, serta 7 perbaikan `WordQuestionParser` (DOMElement type hints, undefined variable guard, CSS class, FilesystemAdapter cast untuk `url()`).

### Sorotan Rilis Pembaruan (v2.8.4)
- **Integrasi Storage MinIO (S3) & Panel Diagnostik Cloud Storage**: Penyesuaian penuh penyimpanan gambar soal CBT (Word import) dan file backup sistem agar tersimpan langsung di MinIO Object Storage ketika `FILESYSTEM_DISK=s3`. Dilengkapi dengan panel diagnostik & tes kesehatan real-time di halaman Admin Backup untuk mengecek kredensial `.env` serta operasi Write/Read/Delete secara live.

### Sorotan Rilis Pembaruan (v2.8.3)
- **Mobile UI Optimization Modul Kesiswaan**: Peningkatan tata letak dan penyederhanaan antarmuka pengguna pada halaman Aturan SP, Master Sanksi Edukatif, dan Catatan Pelanggaran Siswa agar jauh lebih rapi dan fungsional saat diakses melalui *smartphone*. Termasuk penambahan fitur penyaring (*filter*) tingkat pelanggaran dan penggabungan pendaftaran seeder database.

### Sorotan Rilis Pembaruan (v2.8.2)
- **Changelog Staging pada Halaman Login**: Menambahkan tombol & modal dialog changelog otomatis pada halaman login yang hanya tampil di mode staging (`is_staging === true`).

### Sorotan Rilis Pembaruan (v2.8.1)
- **Draf Cetak SP Dinamis dari Pengaturan System**: Cetak SP kini 100% tersambung otomatis dengan Pengaturan Sekolah (Nama Sekolah, Alamat, Kontak, Logo), Pengaturan Kepala Sekolah (Nama & NIP), Wali Kelas siswa, serta Guru BK pembina.

### Sorotan Rilis Pembaruan (v2.8.0)
- **Draf Cetak Resmi Surat Peringatan (SP)**: Menambahkan tombol cetak draf resmi SP (SP-1, SP-2, SP-3) berformat A4 lengkap dengan Kop Surat Sekolah, Nomor Surat Resmi, Identitas Siswa, Tabel Rincian Pelanggaran & Sanksi Edukatif, serta 4 Kolom Tanda Tangan Resmi (Orang Tua/Wali, Guru BK, Wali Kelas, dan Kepala Sekolah).

### Sorotan Rilis Pembaruan (v2.7.3)
- **Penyederhanaan Navigasi Kedisiplinan**: Menghapus menu lama yang redundan (*Penindakan / SP* dan *Usulan SP*) pada `DisciplineNav.vue` sehingga navigasi kini jauh lebih bersih, ringkas, dan bebas dari kebingungan tab ganda.

### Sorotan Rilis Pembaruan (v2.7.2)
- **Pembaruan Modal Detail Siswa (Pelanggaran & Sanksi Edukatif)**: Mengubah popup modal saat nama siswa diklik dari tampilan saldo poin lama menjadi tampilan **Rincian Pelanggaran & Sanksi Edukatif Siswa** (Status SP Aktif, Rekap Total Pelanggaran per Tingkat, dan Riwayat Lengkap Sanksi Edukatif).

### Sorotan Rilis Pembaruan (v2.7.1)
- **Master Sanksi Edukatif Terstandar & Pemilihan Dropdown**: Membuat tabel master `educational_sanctions` dan halaman pengelolaan master sanksi (`EducationalSanctions/Index.vue`). Form pencatatan pelanggaran kini menggunakan Select Dropdown terstandar untuk memilih sanksi edukatif, menjamin keseragaman data pencatatan sanksi di seluruh sekolah.

### Sorotan Rilis Pembaruan (v2.6.4)
- **Deteksi & Pengelompokan Jawaban Duplikat (Indikasi Plagiasi)**: Halaman penilaian guru (`/teacher/assignments/{id}/submissions`) sekarang secara otomatis memindai jawaban uraian (essay) dan URL lampiran siswa secara real-time. Jawaban yang sama persis dikelompokkan (Grup 1, Grup 2, dst) untuk melacak kubu contek-menyontek. Status grup dipaparkan langsung di tabel utama menggunakan Tag merah yang kontras (`text-white` di atas `bg-red-900`) dan disorot di modal penilaian detail.
- **Perbaikan Namespace AttendanceReportExport**: Menyelesaikan error class not found dengan merelokasi berkas eksport laporan absensi kesiswaan ke namespace `Modules\Kesiswaan\app\Exports\` sesuai PSR-4.

### Sorotan Rilis Pembaruan (v2.6.3)
- **Dukungan Multi-Pembina Ekstrakulikuler**: Satu kegiatan ekstrakulikuler kini mendukung penunjukan lebih dari satu Guru Pembina (relasi *many-to-many*). Form penunjukan guru di modul admin dilengkapi komponen `MultiSelect` ber-chip, dan impor/ekspor Excel mendukung multi-NIP yang dipisahkan tanda koma.
- **Akses Cepat & Manajemen Anggota Ekstrakulikuler**: Menambahkan *shortcut* **Ekstrakulikuler** di Dashboard Guru secara dinamis bagi guru pembina aktif. Laman kelola anggota menampilkan kelas siswa dengan jelas dan hanya memunculkan siswa bertatus aktif.
- **Penyempurnaan Antarmuka Presensi Siswa**: Halaman Ekstrakulikuler Siswa kini ditata dengan urutan komponen yang intuitif dan dilengkapi fitur *1 Blok Penuh Collapsible* yang otomatis melipat menjadi satu baris hijau ringkas apabila siswa sudah melakukan presensi pada hari tersebut.

### Sorotan Rilis Pembaruan (v2.6.0)
- **Modul Ekstrakulikuler Terintegrasi (`Modules/Kesiswaan`)**: Membangun modul manajemen ekstrakurikuler lengkap per tahun ajaran (`academic_year_id`) dengan fitur penunjukan Guru Pembina, **"Salin Tahun Ajaran"** dari periode sebelumnya, dan **Import Excel** daftar ekstra maupun anggota siswa berdasarkan NIS/NISN.
- **Presensi Ekstra berbasis Token Waktu Terbatas & Live Monitoring**: Guru Pembina dapat membuat sesi presensi menggunakan **Token 6 karakter (contoh: `EK829X`)** dengan durasi waktu terbatas yang dapat diperpanjang (+15 menit). Dilengkapi panel pemantauan real-time **Daftar Hadir vs. Daftar Tidak Hadir** dan kapabilitas edit status kehadiran secara manual (`Hadir`, `Izin`, `Sakit`, `Alpha`).
- **Pendaftaran Mandiri & Peringatan Wajib Ekstra (Kelas X & XI)**: Siswa dapat mendaftar mandiri pada kegiatan yang dibuka dan melakukan presensi menggunakan token. Bagi siswa Kelas X dan XI yang belum memilih minimal 1 kegiatan, sistem secara otomatis menampilkan modal/pop-up peringatan interaktif saat mengakses portal SMA.

### Sorotan Patch Pembaruan (v2.5.6)
- **Safety Dump, Sanitasi SQL, & Rollback Otomatis Restore Database**: Meningkatkan keamanan pemulihan database (`BackupController`). Sistem kini otomatis membersihkan file SQL dump dari perintah `SET ROLE` / `OWNER TO` agar kompatibel dengan akun database non-superuser di staging/production. Sebelum database dikosongkan (`db:wipe`), sistem membuat *Safety Dump* dan melakukan **Rollback Otomatis** jika eksekusi restore via `psql` mengalami kegagalan.
- **Proteksi Keamanan URL Penugasan Siswa**: Memperkuat sistem kontrol akses tugas siswa (`StudentAssignmentController`) dengan validasi otomatis kepemilikan kelas, kelompok agama, dan penyelesaian tugas prasyarat. Mencegah siswa berpindah atau membuka soal dari kelas lain melalui manipulasi parameter URL.
- **Perbaikan Izin Edit Tugas Pasca-Deadline**: Siswa yang telah dibukakan akses edit oleh guru (`is_editable = true`) kini diizinkan memperbarui dan mengumpulkan kembali jawaban tugas mereka meskipun waktu pengumpulan (`due_at`) telah berakhir, dilengkapi banner notifikasi interaktif.
- **Tampilan Daftar Tugas Collapsible**: Meringkas antarmuka daftar penugasan siswa (`Penugasan/Siswa/Index.vue`) agar tidak memenuhi layar saat terdapat banyak tugas dengan deskripsi panjang, dilengkapi tombol *toggle* instruksi.
- **Tantangan Kata Kunci (Keyword Challenge) pada Restore Database**: Menambahkan konfirmasi pengaman dengan kata kunci (`FORCE_RESTORE`) untuk melewati pengecekan perbedaan versi migrasi database (*manifest*) saat restore backup jika diperlukan oleh administrator.

### Sorotan Patch Pembaruan (v2.5.5)
- **Tombol & Halaman Absen Mapel Kepsek**: Menambahkan tombol dan halaman khusus `Daftar Siswa Absen Mapel` di Dashboard Kepala Sekolah (`/alfa-students`).
- **Kartu TH (Tidak Hadir Mapel) Dashboard Wali Kelas**: Menambahkan kartu **TH (TDK HADIR)** pada blok Rekap Kelas di Dashboard Guru/Wali Kelas.
- **Kartu Presensi Lengkap di Rekap Kelas**: Menambahkan kartu status `D`, `TCO`, `TCI`, dan `FM` pada dasbor Wali Kelas.
- **Freeze/Sticky Header Tabel Laporan Presensi**: Menambahkan atribut `scrollable scrollHeight="600px"` pada komponen `DataTable` di seluruh tab Laporan Presensi Kesiswaan.

### Sorotan Patch Pembaruan (v2.5.4)
- **Pengecekan Versi Otomatis pada Backup & Restore**: Mengintegrasikan injeksi `manifest.json` pada file ZIP cadangan untuk mencatat versi struktur tabel (*database migration*) dan aplikasi secara otomatis. Sistem kini dilengkapi *validation gate* yang memblokir proses pemulihan (*restore*) jika versi tidak cocok, mencegah aplikasi *crash* akibat struktur data yang usang.
- **Pengamanan Password Arsip**: Arsip *backup* yang dihasilkan kini diwajibkan untuk dienkripsi dengan kata sandi khusus (via `.env` variabel `BACKUP_ARCHIVE_PASSWORD`).
- **Penyatuan Laporan UI Mobile**: Melakukan perombakan *Mobile-First Design* pada laporan Jurnal Kelas dan Jurnal Mengajar Guru. Tampilan kini lebih mulus disajikan dalam satu format responsif untuk semua perangkat, lengkap dengan filter dinamis dan akses tombol **Cetak PDF**.

### Sorotan Pembaruan Sebelumnya (v2.5.3)
- **Patch PostgreSQL Check Constraint Presensi (`attendances_status_check`)**: Memperbaiki error `SQLSTATE[23514]: Check violation: 7` saat memperbarui atau membuat presensi siswa dengan status `TCI` (*Tidak Presensi Masuk*), `TCO`, `PA`, `T-PA`, `FM`, dan `D` dengan memperluas daftar status valid pada tabel `attendances` (17 status valid).
- **Patch Optimasi Build Vite & Code Splitting**: Menerapkan konfigurasi *code splitting* (`manualChunks`) untuk memisahkan berkas pustaka eksternal PrimeVue dan Vue, sehingga mengatasi peringatan *chunk size limit* (>500 kB) dan mempercepat caching browser.

### Sorotan Pembaruan Sebelumnya (v2.5.2)
- **Sistem Log Aktivitas & Audit Trail**: Penambahan fitur rekam jejak (*audit trail*) otomatis dengan tabel `activity_logs` (append-only) untuk memantau aktivitas autentikasi dan perubahan data krusial beserta perbandingan `old_values` vs `new_values`. Dilengkapi halaman Admin `/admin/activity-logs`.
- **Status Aktif / Maintenance Mode Situs**: Penambahan tab baru "Status & Akses Situs" pada halaman `/admin/settings` untuk mengaktifkan atau menonaktifkan situs. Mode nonaktif (Maintenance Mode) melarang login/akses bagi pengguna non-admin dengan notifikasi informatif.
- **Deteksi Server Staging & Tautan Production**: Lingkungan uji coba (*staging*) dideteksi secara otomatis dan menampilkan banner khusus bersertai tombol/link langsung ke server utama (*Production* - `portal.sman16smg.sch.id`).
- **Resolusi Otomatis Tugas Prasyarat Multi-Kelas**: Pembuatan tugas berprasyarat secara massal untuk banyak kelas sekaligus kini otomatis mendeteksi dan menautkan ID tugas prasyarat yang sepadan untuk masing-masing kelas target.
- **Model Penilaian Kinerja Ceklist & UI Rekapitulasi Baru**: Guru kini dapat menggunakan instrumen soal "Ceklist" dengan Toggle Switch untuk menilai komponen praktek yang bersifat Tercapai/Belum. Kolom tabel rekap kinerja juga kini dilengkapi *sticky header/column* dan peringatan *collapsible* untuk pengalaman pengguna yang jauh lebih nyaman.
- **Visual & UI Fixes**: Memperbaiki kontras font jawaban siswa yang sebelumnya tak terbaca dan memberikan informasi error form (UI validation summary) dengan rapi.

### Sorotan Pembaruan Sebelumnya (v2.5.0)
- **Mesin Jet Laravel Octane (FrankenPHP)**: Mengimplementasikan sistem *high-performance* menggunakan Laravel Octane (FrankenPHP) khusus untuk mempercepat rute pengerjaan soal CBT Siswa, memecah beban (*Reverse Proxy Selective*) antara PHP-FPM standar dan server Octane.
- **Dasbor Pemantauan Laravel Pulse**: Dilengkapi Dasbor Metrik Server bawaan (*Laravel Pulse*) untuk mengamati beban CPU, RAM, serta kueri terlambat secara *real-time* langsung dari antarmuka Web Admin (akses URL `/pulse` diproteksi khusus Admin).

### Sorotan Pembaruan Sebelumnya (v2.4.1)
- **Token Ujian Mandiri & Anti-Cheat Cerdas**: Siswa bisa langsung ujian mandiri tanpa token, namun wajib menggunakan token izin proktor jika sistem mengeluarkan mereka akibat pelanggaran fokus 3 kali (Fokus Terputus).
- **Perbaikan UI Anti-Cheat**: Tampilan peringatan pelanggaran ujian kini responsif dan di-hardcode dengan warna yang kontras agar tulisan tidak menyatu dengan background gelap.
- **Perbaikan Waktu Jadwal Ujian (GMT ke Lokal)**: Menyelaraskan pembacaan zona waktu jadwal ujian pada tabel agar tidak bergeser otomatis.

### Sorotan Pembaruan Sebelumnya (v2.4.0)
- **Fitur Tugas Prasyarat (Assignment Prerequisites)**: Guru dapat menetapkan tugas prasyarat yang akan mengunci tugas siswa secara otomatis sampai tugas syarat diselesaikan.
- **Modus Lihat Saja (Read-Only) Tugas Kinerja Siswa**: Tugas tipe Penilaian Kinerja kini tidak meminta input file, melainkan hanya menampilkan petunjuk dan indikator penilaian secara read-only beserta hasil catatan guru.

### Sorotan Pembaruan Sebelumnya (v2.3.0)
- **Modul Penilaian Kinerja / Praktik Guru**: Dukungan pengujian performa siswa dengan indikator nilai dinamis (Slider + Input Angka), rekapitulasi penilaian aktif oleh guru, serta tombol instant publish/tarik nilai.
- **Penyaringan Otomatis Agama Guru & Siswa**: Label mapel agama otomatis memperlihatkan jenis agama (contoh: "Pendidikan Agama (Islam)"), dan daftar murid disaring secara presisi sesuai agama dari guru pengampu.
- **Proteksi Histori Agenda & Laporan Jurnal**: Fitur Reset Jadwal Guru dijamin 100% tidak menghapus riwayat jurnal mengajar (materi & absensi) berkat pelepasan ID slot jam dan kueri *LEFT JOIN* pada laporan personal.
- **Lencana Bobot & Skor Poin Siswa**: Lembar soal & pengerjaan tugas siswa (`/student/assignments/{id}`) kini menampilkan lencana bobot max poin serta perolehan nilai per butir soal.

### Sorotan Pembaruan Historis (v2.2.0)
- **Sistem Poin Tata Tertib & Merit Siswa**: Implementasi penuh pemotongan poin otomatis per pelanggaran, rekomendasi SP presisi berdasar saldo poin terkini, fitur pemulihan poin dari Sanksi Edukatif, serta pencatatan Merit / Penghargaan siswa berprestasi.
- **Transparansi Poin Siswa**: Menu "Poin Saya" di akun siswa dan modal riwayat mutasi poin interaktif di akun Guru/Admin saat nama siswa di-klik.

### Sorotan Pembaruan Sebelumnya (v2.0.0)
- **Modul Tata Tertib Siswa**: Implementasi penuh sistem pengelolaan pelanggaran tata tertib — Katalog Aturan per pasal, Catatan Pelanggaran dengan upload foto & GPS otomatis, Penindakan SP1/SP2/SP3, dan Laporan Statistika.
- **Navigasi Chain**: Komponen `DisciplineNav.vue` menghubungkan semua halaman tata tertib (Katalog → Catatan → Penindakan/SP → Laporan) dengan tab navigasi horizontal berindikator halaman aktif.
- **Menu & Aksi Cepat Guru Dinamis**: Menu sidebar dan tombol aksi cepat pada dashboard guru kini muncul otomatis sesuai permission masing-masing guru — tidak ada lagi menu yang tidak relevan tampil.

### Sorotan Pembaruan Sebelumnya (v1.9.9)
- **Rekap Presensi Harian & Tahunan**: Evaluasi otomatis jam presensi harian per kelas dan tabel rekapitulasi sekolah bulanan (12 bulan).
## Panduan Penggunaan Fitur Baru (v2.16.2)

### 1. Rekap Presensi Siswa per Mata Pelajaran Guru (`/teacher/attendance-recap`)
Fitur ini digunakan oleh Guru Pengampu untuk memantau, merekapitulasi, dan mencetak matriks kehadiran siswa pada mata pelajaran yang diampunya:
1. **Akses Menu**: Buka menu **"Rekap Presensi"** di bilah atas guru (Top Tab Menu) atau klik tombol **"Rekap Siswa"** di Aksi Cepat Dashboard Guru.
2. **Memilih Jadwal & Kelas**: Pilih rombel/mata pelajaran yang diampu dari dropdown pilihan jadwal.
3. **Filter Periode**:
   - **Mode Bulanan**: Pilih Bulan dan Tahun untuk melihat presensi bulanan.
   - **Mode Rentang Tanggal**: Aktifkan mode rentang tanggal dan pilih Tanggal Mulai s.d. Tanggal Selesai.
4. **Cetak PDF**: Klik tombol **"Cetak PDF"** untuk mengunduh dokumen resmi A4 Landscape yang dilengkapi Kop Surat Resmi Sekolah, rincian per pertemuan (*H, S, I, A, TH*), persentase kehadiran, dan lembar tanda tangan guru ber-NIP.
5. **Export Excel**: Klik tombol **"Export Excel"** untuk mengunduh berkas `.xlsx`.

---

### 2. Pusat Izin Siswa & Sinkronisasi Google Sheets (2 Arah) (`/permits`)
Memungkinkan Admin, Guru BK, Petugas Piket, dan Guru untuk mencatat izin siswa atau menyinkronkan data langsung dari Google Spreadsheet secara otomatis:

#### A. Tarik Data dari Web Portal (Pull Sync)
1. Buka menu **Pusat Izin Siswa** (`/permits`).
2. Klik tombol hijau **"Tarik Google Sheet"** di kanan atas.
3. Masukkan / periksa URL Google Spreadsheet Anda (pastikan hak akses share *Anyone with the link can view*).
4. Centang opsi *"Hanya proses baris yang kolom 'Cek Input Portal' bernilai FALSE"* agar tidak memproses ulang data lama.
5. Klik **"Mulai Tarik Data"**. Portal akan mencocokkan nama siswa, kelas, dan rentang tanggal secara otomatis.

#### B. Otomatisasi 2 Arah Google Apps Script di Google Sheets (Push Sync)
Agar pengisian di Google Sheets dapat langsung dikirim ke portal dan kolom **"Cek Input Portal"** otomatis tercentang `TRUE`:
1. Buka Google Spreadsheet Anda.
2. Buka menu **Ekstensi (*Extensions*) > Apps Script**.
3. Tempel (*paste*) script berikut:
```javascript
const PORTAL_SYNC_URL = "https://portal.sman16smg.sch.id/api/permits/sync-sheet";

function onOpen() {
  const ui = SpreadsheetApp.getUi();
  ui.createMenu('🚀 Portal SMA')
    .addItem('📥 Kirim Izin Baru ke Portal', 'syncUncheckedRows')
    .addItem('🔄 Sinkronkan Seluruh Data', 'syncAllRows')
    .addToUi();
}

function syncUncheckedRows() { syncToPortal(true); }
function syncAllRows() { syncToPortal(false); }

function syncToPortal(onlyUnchecked) {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  const data = sheet.getDataRange().getValues();
  if (data.length < 2) return SpreadsheetApp.getUi().alert('Data spreadsheet kosong.');

  const headers = data[0].map(h => String(h).toLowerCase().trim());
  const colCekPortalIdx = headers.indexOf('cek input portal');
  if (colCekPortalIdx === -1) return SpreadsheetApp.getUi().alert('Kolom "Cek Input Portal" tidak ditemukan di baris header.');

  const payload = { sheet_url: SpreadsheetApp.getActiveSpreadsheet().getUrl(), only_unchecked: onlyUnchecked };
  const options = { method: 'post', contentType: 'application/json', payload: JSON.stringify(payload), muteHttpExceptions: true };

  try {
    const response = UrlFetchApp.fetch(PORTAL_SYNC_URL, options);
    const result = JSON.parse(response.getContentText());
    if (result.status === 'success') {
      (result.data.synced_row_numbers || []).forEach(rowNum => {
        sheet.getRange(rowNum, colCekPortalIdx + 1).setValue(true);
      });
      let msg = '✅ ' + result.data.imported_count + ' data izin berhasil masuk ke Portal SMA.';
      if (result.data.skipped_count > 0) msg += '\n(' + result.data.skipped_count + ' data dilewati karena sudah bernilai TRUE).';
      if (result.data.unmatched_rows?.length > 0) {
        msg += '\n\n⚠️ ' + result.data.unmatched_rows.length + ' baris gagal dicocokkan (periksa nama/tanggal).';
      }
      SpreadsheetApp.getUi().alert(msg);
    } else {
      SpreadsheetApp.getUi().alert('❌ Gagal: ' + (result.message || 'Terjadi kesalahan'));
    }
  } catch (err) {
    SpreadsheetApp.getUi().alert('❌ Error koneksi: ' + err.toString());
  }
}
```
4. Simpan script (Ctrl+S) lalu refresh Google Sheet Anda.
5. Gunakan menu bilah atas **`🚀 Portal SMA > 📥 Kirim Izin Baru ke Portal`** kapan saja untuk sinkronisasi instan!

---

## Instalasi & Deployment

Pastikan telah menginstal dependensi *backend* dan *frontend*:
```bash
composer install
npm install
npm run build
php artisan migrate
```

## CI/CD Pipeline

Proyek ini menggunakan **GitHub Actions** untuk deployment otomatis:

| Event | Branch | Target |
|-------|--------|--------|
| `git push` (otomatis) | `ujicoba` | `uji-portal.sman16smg.sch.id` |
| Tombol GitHub (manual) | `main` | `portal.sman16smg.sch.id` |

- **Staging**: setiap push ke branch `ujicoba` → auto-deploy ke server ujicoba.
- **Production**: buka GitHub → Actions → *"🏭 Deploy ke Production"* → Run workflow → ketik `DEPLOY`.

## Lisensi

Aplikasi ini merupakan produk sistem manajemen tertutup (Closed Source) yang diperuntukkan bagi instansi pendidikan terkait. Tidak untuk didistribusikan secara publik tanpa izin.
