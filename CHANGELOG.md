# Changelog

## [2.16.4] - 2026-08-27
### Fixed
- **Pencegahan Duplikasi Jawaban Siswa & Penanganan Race Condition CBT (`cbt_student_answers`)**:
  - **Database Unique Constraint**: Menambahkan constraint unik PostgreSQL `cbt_student_answers_exam_question_unique` pada pasangan kolom `(cbt_student_exam_id, cbt_question_id)` (`2026_08_27_140000_add_unique_index_to_cbt_student_answers_table.php`) untuk menjamin di level basis data bahwa satu siswa dalam satu sesi ujian hanya memiliki tepat 1 baris jawaban per nomor soal.
  - **Deduplikasi Cerdas Otomatis pada Migrasi**: Skrip migrasi secara aman mengonsolidasikan data duplikat lawas sebelum indeks dibuat—mempertahankan jawaban terisi (`selected_answer`), nilai skor/poin tertinggi (`points_earned`), dan menghapus baris duplikat yang lebih lama.
  - **Resilient Exception Handling pada Autosave Jawaban (`StudentCbtController::saveAnswer`)**: Menangani `UniqueConstraintViolationException` dan `QueryException` pada method autosave. Jika terjadi balapan request simultan dari klik cepat siswa, request kedua otomatis fallback melakukan pembaruan (update) tanpa memicu *HTTP Error 500* atau pesan error koneksi di layar siswa.
  - **Akurasi Perhitungan Jumlah Terjawab di Monitoring & Hasil Ujian**:
    - Menambahkan `->unique('cbt_question_id')->count()` pada `CbtExamController::results()` dan `CbtProctorController::getStatus()` agar rekapitulasi jumlah soal terjawab siswa selalu tepat dan tidak pernah melebihi total butir soal ujian.

## [2.16.3] - 2026-08-25
### Added
- **Penyempurnaan Laporan Jurnal Kelas Wali Kelas & Jurnal Mengajar Guru (`/teacher/reports/classroom` & `/teacher/reports/personal`)**:
  - **Perincian Kolom Presensi Lengkap (Jml, H, S, I, D, A)**:
    - Mengganti format lama (hanya Hadir dan Absen) menjadi 6 kolom presensi detail: *Jumlah Siswa (`Jml`), Hadir (`H`), Sakit (`S`), Izin (`I`), Dispensasi (`D`), dan Alpa (`A`)*.
    - Mengintegrasikan *Dynamic Student Permit Overlay* dari modul `/permits` sehingga izin resmi yang dimasukkan belakangan (*backdated*) otomatis ter-resolve pada rekapitulasi agenda KBM.
  - **Kolom Siswa Tidak Hadir (*Clean & Compact*)**:
    - Menampilkan daftar nama siswa yang tidak masuk beserta kode status ketidakhadirannya secara rapi dan bersih (contoh: `Budi Santoso (D)`, `Siti Nurhaliza (S)`, `Ahmad Pratama (A)`).
    - Menghilangkan tampilan tautan URL/bukti panjang agar dokumen cetak tetap rapi dan profesional.
    - Menampilkan keterangan `- (Nihil / Hadir Semua)` apabila seluruh siswa hadir di kelas.
  - **Cetak Laporan PDF Resmi A4 Portrait**:
    - Menyesuaikan orientasi kertas dokumen cetak PDF Jurnal Kelas (`agenda_classroom.blade.php`) dan Jurnal Mengajar Guru (`agenda_personal.blade.php`) menjadi **A4 Portrait** dengan proporsi kolom dan tipografi yang presisi.
  - **Tampilan Web & Mobile Jurnal Kelas**:
    - Memperbarui halaman web desktop ([`Classroom.vue`](file:///var/www/html/portal-sma/resources/js/Pages/Report/Agenda/Classroom.vue)) dan mobile ([`Mobile/Classroom.vue`](file:///var/www/html/portal-sma/resources/js/Pages/Report/Agenda/Mobile/Classroom.vue)) dengan badge perincian `H/S/I/D/A` dan daftar siswa yang tidak hadir.

- **Penyempurnaan Backup & Restore CLI (`php artisan backup:restore`)**:
  - Menambahkan opsi `--bucket=` untuk override nama bucket penyimpanan S3/MinIO secara dinamis lintas environment (Staging/Devel <-> Produksi).
  - Mengintegrasikan download streaming native AWS S3Client dengan *real-time progress bar* (kecepatan unduh, persentase %, MB terunduh, dan estimasi waktu).

### Fixed
- **Perbaikan Ikon Menu Rekap Siswa di Dashboard Guru**:
  - Memperbaiki class ikon dari `pi-user-check` menjadi `pi-list-check` resmi PrimeIcons pada Dashboard Guru Desktop, Mobile, dan komponen Tab Menu.

## [2.16.2] - 2026-08-25
### Added
- **Fitur Rekap Presensi Siswa per Mata Pelajaran Guru (`/teacher/attendance-recap`)**:
  - Menyediakan halaman matriks rekapitulasi kehadiran siswa khusus per mata pelajaran dan kelas yang diampu guru pada tahun ajaran aktif.
  - Mendukung dua mode filter periode:
    1. **Mode Bulanan**: Pilihan bulan (Januari s.d. Desember) dan tahun.
    2. **Mode Rentang Tanggal**: Pilihan bebas tanggal mulai dan tanggal selesai (*Datepicker*).
  - Menampilkan ringkasan statistik kelas (Total Siswa, Total Pertemuan KBM, dan Rata-rata Persentase Kehadiran).
  - Tabel matriks presensi interaktif dengan kolom data siswa (*No, Nama, NISN*), kolom tanggal pertemuan agenda KBM (*P1, P2, dst.*) beserta badge status kehadiran (*H, S, I, A, T, D*), serta kolom rekapitulasi (*Hadir, Tidak Hadir (S/I/A), Total Pertemuan, % Kehadiran*).
  - Kolom *No* dan *Nama Siswa* dibuat *sticky* pada tabel horizontal scrollable untuk kenyamanan navigasi guru di layar kecil/ponsel.
  - Baris footer rekapitulasi menghitung total siswa yang Hadir dan Tidak Hadir pada setiap sesi pertemuan KBM.
  - **Cetak Laporan PDF Resmi A4 Landscape** (`attendance_recap.blade.php`):
    - Dilengkapi Kop Surat Resmi Sekolah (Logo Pemda, Logo Sekolah, Nama Pemprov, Dinas Pendidikan, Alamat Lengkap & Kontak dari pengaturan sistem).
    - Memuat metadata kelas, mata pelajaran, nama guru pengampu, cakupan periode, legenda singkatan status (*H, S, I, A, TH, Jml*), titimangsa kota, dan lembar tanda tangan Guru Mata Pelajaran lengkap dengan NIP.
  - **Export Laporan Excel (.xlsx)** (`GuruAttendanceRecapExport.php` & `attendance_recap_excel.blade.php`):
    - File spreadsheet terstruktur siap cetak dan siap olah.
  - Menambahkan rute `guru.attendance-recap.index`, `guru.attendance-recap.pdf`, dan `guru.attendance-recap.excel` pada `routes/teacher.php`.
  - Menambahkan tab menu **"Rekap Presensi"** di `TeacherTabMenu.vue` dan pintasan cepat **"Rekap Siswa"** pada Dashboard Guru (`DashboardGuru.vue` & `Mobile/DashboardGuru.vue`).

- **Fitur Sinkronisasi Izin Siswa dari Google Sheets (2 Arah) (`/permits`)**:
  - Menyediakan solusi integrasi dua arah untuk membaca dan mengimpor data izin siswa yang dicatat oleh guru/petugas piket melalui Google Spreadsheet ([Spreadsheet Izin Siswa](https://docs.google.com/spreadsheets/d/1St-UOseLrUUg6MIUUS64b3CMynAtaK0BvxxbftfzXrM)).
  - Mengimplementasikan service backend `GoogleSheetPermitSyncService`:
    - Mengonversi link Google Spreadsheet publik menjadi export format CSV secara otomatis.
    - Mencocokkan data siswa secara cerdas (*case-insensitive & verifikasi kelas*) di tahun ajaran aktif.
    - Mendukung berbagai format penulisan tanggal bahasa Indonesia (*03-Agu-2026, 27-Jul-2026, dsb.*).
    - Normalisasi jenis izin (*Sakit (`S`), Izin (`I`), Dispensasi (`D`), Alpa (`A`), Terlambat (`T`)*).
    - Memecah izin rentang multi-hari (misal 03-Agu s.d. 17-Agu) ke setiap hari efektif sekolah secara otomatis.
    - Menyimpan tautan berkas bukti surat/Google Drive ke kolom keterangan portal.
  - **Antarmuka Web Tarik Data di Portal (`/permits`)**:
    - Tombol hijau **"Tarik Google Sheet"** di bilah atas halaman Pusat Izin Siswa.
    - Modal dialog dengan field URL Google Sheet (tersimpan otomatis di pengaturan `google_sheet_permit_url`), checkbox filter hanya memproses baris yang belum diinput (`Cek Input Portal = FALSE`), dan tombol eksekusi *"Mulai Tarik Data"*.
    - Menampilkan dialog ringkasan hasil tarik data (*jumlah berhasil, jumlah dilewati, dan daftar baris yang gagal dicocokkan*).
    - Menambahkan rute `POST /permits/sync-google-sheet` pada `Modules/Akademik/routes/web.php`.
  - **API Webhook & Otomatisasi Google Apps Script (GAS)**:
    - Endpoint Webhook publik tanpa CSRF `POST /api/permits/sync-sheet` untuk menerima trigger sinkronisasi langsung dari Google Apps Script.
    - Menyediakan script Google Apps Script siap pakai pada modal dialog portal:
      - Menambahkan menu kustom **"🚀 Portal SMA"** di bilah atas spreadsheet.
      - Menu **"📥 Kirim Izin Baru ke Portal"** mengirim baris baru dan secara otomatis mengubah nilai kolom **"Cek Input Portal"** menjadi **`TRUE`** (centang) pada spreadsheet secara real-time.

- **Dukungan Direct CDN Offloading & Edge Caching Media CBT**:
  - Menambahkan konfigurasi `asset_url` (`ASSET_URL`) pada `config/app.php` untuk memfasilitasi pendistribusian aset statis Vite (JS, CSS, Fonts) via subdomain CDN (misal `https://cdn.sman16smg.sch.id`).
  - Menambahkan dukungan opsi `CBT_DIRECT_CDN_URL` pada `WordQuestionParser` dan `CbtImageController` agar file gambar soal dari S3/Cloudhost disajikan langsung via domain CDN publik.
  - Mengoptimalkan header respons gambar soal CBT dengan directive `s-maxage=2592000` (30 hari Edge CDN Caching) dan `immutable` untuk mencapai 0% beban server origin pada saat ujian massal.

### Fixed
- **Perbaikan Rute Kosongkan Soal CBT (`cbt.bank.clear`)**:
  - Mendaftarkan rute `POST /cbt/bank/{id}/clear` yang sebelumnya tertinggal pada `Modules/Cbt/routes/web.php` dan meregenerasi rute Ziggy (`ziggy.js`) untuk mengatasi kendala error *Ziggy route not found* saat guru mengosongkan bank soal.

## [2.16.1] - 2026-08-22
### Added
- **Fitur Import Data Ruangan dengan Template Excel (`/admin/sarpras/rooms`)**:
  - Menyediakan fitur import master data ruangan sarpras secara massal berbasis file Excel (`.xlsx`/`.xls`/`.csv`).
  - Menyediakan tombol download template resmi Excel (`FacilityRoomTemplateExport`) yang dilengkapi styling header dan contoh data isian: `kode_ruangan`, `nama_ruangan`, `kapasitas`, `lokasi`, `fasilitas` (multi-item dipisahkan koma), `deskripsi`, `status` (`available`, `maintenance`, `inactive`), dan `dapat_dipinjam` (`1`/`0`).
  - Mengimplementasikan `FacilityRoomImport` dengan proteksi transaksi database atomik, normalisasi status, parsing fasilitas ke JSON array, dan otomatisasi `updateOrCreate` berdasarkan `kode_ruangan`.
  - Menambahkan endpoint `GET /admin/sarpras/rooms/template` dan `POST /admin/sarpras/rooms/import` pada `AdminFacilityRoomController` dan `routes/admin.php`.
  - Menyediakan antarmuka modal dialog import, panduan pengisian interaktif, dan feedback notifikasi toast di `Rooms/Index.vue`.

- **Fitur Import Data Aset & Peralatan dengan Template Excel (`/admin/sarpras/assets`)**:
  - Menyediakan fitur import data inventaris aset dan peralatan sarpras secara massal berbasis file Excel (`.xlsx`/`.xls`/`.csv`).
  - Menyediakan tombol download template resmi Excel (`FacilityAssetTemplateExport`) dengan kolom: `kode_aset`, `nama_aset`, `kategori`, `merk_tipe`, `nomor_seri`, `kondisi` (`good`, `minor_damage`, `heavy_damage`), `status` (`available`, `borrowed`, `maintenance`, `lost`, `disposed`), `lokasi_simpan`, dan `catatan`.
  - Mengimplementasikan `FacilityAssetImport` dengan fitur normalisasi kondisi & status, auto-generate token QR Code unik (`AST-...`) untuk aset baru, serta pembaruan data otomatis jika kode aset sudah terdaftar.
  - Menambahkan endpoint `GET /admin/sarpras/assets/template` dan `POST /admin/sarpras/assets/import` pada `AdminFacilityAssetController` dan `routes/admin.php`.
  - Menyediakan tombol "Import Excel", modal instruksi pengisian, dan dialog unggah file di `Assets/Index.vue`.

## [2.16.0] - 2026-08-21
### Added
- **Fitur Status Post & Unpost Penilaian Guru (`/penilaian/grades`)**:
  - Menambahkan kolom status **Posted / Unposted (Draf)** pada tabel daftar penilaian guru sebagai penyeimbang nilai apabila terjadi duplikasi nilai (misal dari auto-sync CBT dan entri manual guru).
  - Menambahkan aksi cepat toggle Post/Unpost berbasis dialog konfirmasi dan feedback toast langsung di tabel riwayat penilaian.
  - Penilaian yang di-unpost secara otomatis **dikecualikan dari perhitungan rata-rata komponen dan Nilai Akhir (NA)** pada matriks rekap nilai guru (`/penilaian/grades/recap`).
  - Penilaian yang di-unpost **disembunyikan sepenuhnya dari portal nilai siswa** (`/penilaian/student/grades`) sehingga tidak memengaruhi nilai maupun IPK siswa.
  - Menambahkan migrasi `is_posted` pada tabel `grading_items`, endpoint `POST /penilaian/grades/{id}/toggle-post`, serta penanda visual khusus `[UNPOST]` pada header tabel rekap dan file ekspor Excel.

- **Fitur Edit Kunci Jawaban & Bobot Per Butir Soal (Bank Soal CBT)**:
  - Menyediakan modal dialog interaktif pada halaman detail bank soal (`/cbt/bank/{id}/questions`) untuk mengoreksi kunci jawaban (Pilihan Ganda, Isian Singkat, Benar/Salah, Uraian) dan bobot skor butir soal secara langsung tanpa perlu upload ulang file Word.
  - Menambahkan endpoint `PUT /cbt/bank/{bank_id}/questions/{question_id}/key`.

- **Fitur Import Revisi Kunci Jawaban via Excel Saja (Zero Data Loss)**:
  - Menyediakan fitur import revisi kunci jawaban berbasis Excel (`.xlsx`/`.xls`) di halaman bank soal yang hanya memperbarui kolom kunci jawaban, bobot, grouping, dan lock posisi berdasarkan nomor urut baris soal tanpa menghapus ID soal maupun data soal di database.
  - Menjamin keamanan 100% data riwayat pengerjaan siswa (`cbt_student_answers`) saat kunci diperbaiki setelah ujian selesai dikerjakan.
  - Dilengkapi 5 lapis pengaman (*RBAC ownership authorization, validasi format & MIME file, proteksi isi kosong, transaksi database atomik DB::transaction, dan zero data loss matching guard*).
  - Menyediakan tombol unduh langsung template kunci resmi Excel di dalam modal import revisi kunci.
  - Menambahkan endpoint `POST /cbt/bank/{id}/import-keys`.

- **Penyatuan Bundel Cetak Laporan Lengkap PDF 3-in-1 (CBT)**:
  - Menggabungkan 3 dokumen laporan ujian (Laporan Data Jawaban Siswa, Matriks Jawaban Dikotomi 1/0, dan Daftar Nilai Siswa + Lembar Tanda Tangan Guru & Kepala Sekolah) ke dalam 1 template cetak terstruktur `report_full_bundle.blade.php`.
  - Menyederhanakan baris tombol cetak di halaman Hasil Ujian (`/cbt/exams/{id}/results`) dan Analisis Bank Soal (`/cbt/bank/{id}/analytics`) menjadi komponen `SplitButton` warna biru (*severity="info"*) dengan dropdown menu untuk opsi cetak satuan.
  - Menambahkan endpoint baru `GET /cbt/exams/{id}/export-full-report-pdf` dan `GET /cbt/bank/{id}/export-full-report-pdf`.

### Changed
- **Integrasi Penilaian Ulang (Re-Grading) ke dalam Rekalkulasi Analisis CBT**:
  - Mengintegrasikan pipeline penilaian ulang (`CbtGradingService::gradeExam`) untuk seluruh peserta ujian berstatus `submitted` / `completed` sebelum sistem menghitung metrik Classical Test Theory (CTT) dan Item Response Theory (IRT).
  - Nilai siswa pada tabel `cbt_student_exams`, buku nilai guru (`StudentGrade`), serta analitik butir soal otomatis tersinkronisasi kembali saat guru menekan tombol *"Hitung Ulang Nilai & Analisis"*.

## [2.15.5] - 2026-08-20
### Added
- **Penambahan Field Provinsi pada Profil Sekolah Pengaturan Admin (`/admin/settings`)**:
  - Menambahkan input field baru **Provinsi** (`school_province`) berdampingan dengan Kota/Kabupaten pada Tab 1 (*Profil Sekolah*) di menu Pengaturan Situs Admin.
  - Menambahkan validasi request, pemetaan form state Inertia (`useForm`), serta mekanisme penyimpanan ke tabel database `settings`.

### Changed
- **Standarisasi Urutan Alamat pada Kop Surat & Template PDF Dokumen Resmi**:
  - Menyelaraskan urutan baris alamat resmi instansi sekolah di Kop Surat Terpusat (`components/kop-surat.blade.php`), PDF Surat Keluar Kedinasan (`surat_resmi_pdf.blade.php`), Laporan Ekstrakurikuler, serta komponen Preview Kop Surat di Pengaturan Admin menjadi:
    `Alamat` $\rightarrow$ `Kota/Kabupaten` $\rightarrow$ `Provinsi` $\rightarrow$ `Kode Pos`.
  - Mengintegrasikan pengambilan konfigurasi dinamis `school_province` pada seluruh controller persuratan dan pelaporan (`SuratKeluarController`, `PublicVerificationController`, `ExtracurricularReportController`).

### Fixed
- **Perbaikan Form State Binding Pengaturan Situs (`Admin/Setting/Index.vue`)**:
  - Memperbaiki issue di mana nilai konfigurasi `kop_pemprov` dan `kop_dinas` tidak muncul pada form saat halaman pengaturan pertama kali dimuat karena belum terdaftar pada inisialisasi `useForm`.

## [2.15.4] - 2026-08-20
### Added
- **Penyempurnaan Manajemen Status & Filter Jadwal Ujian CBT (`/cbt/exams`)**:
  - Menambahkan komponen `ToggleSwitch` interaktif langsung pada kolom **Status** di tabel Jadwal Ujian untuk memudahkan pengaktifan/penonaktifan ujian dengan 1 klik tanpa perlu membuka modal Edit.
  - Menambahkan tombol filter status di atas tabel (*Semua*, *Ujian Aktif*, *Ujian Non-Aktif*) lengkap dengan penghitung (*counter*) jumlah jadwal ujian secara real-time.
  - Menambahkan endpoint `PATCH /cbt/exams/{id}/toggle-active` pada `CbtExamController` dan `Modules/Cbt/routes/web.php`.

- **Otomatisasi Sinkronisasi Nilai CBT ke Buku Nilai Siswa (`StudentGrade` / Modul Penilaian)**:
  - Mengintegrasikan otomatisasi pembuatan *Grading Item* per kelas peserta dan sinkronisasi nilai (*StudentGrade*) saat Kategori Nilai (*Grading Component*) dipilih atau diubah pada jadwal ujian.
  - Sinkronisasi instan skor siswa (`cbt_student_exams`) yang telah menyelesaikan ujian (*status: submitted*) ke modul Penilaian sehingga langsung tampil pada portal nilai siswa (`/grades` / `/nilai`) dan buku nilai guru.
  - Auto-sync real-time pada `CbtGradingService` saat siswa menyelesaikan ujian di masa mendatang.

### Security & Access Control
- **Restriksi Otorisasi & Hak Akses Menu Jadwal Pengawas (`/cbt/proctor-schedules`)**:
  - Menerapkan Role-Based Access Control (RBAC) pada controller `CbtProctorScheduleController`:
    - Role **Admin**: Memiliki hak penuh untuk menambah (`store`), mengubah (`update`), dan menghapus (`destroy`) seluruh jadwal pengawas ujian.
    - Role **Guru**: Dibatasi hanya dapat melihat daftar jadwal pengawasan miliknya sendiri (`teacher_id`), serta menyembunyikan tombol "Tambah Jadwal" dan kolom "Aksi" (Edit & Hapus).
  - Menambahkan visual badge dan penanda khusus (*Sesi Ujian Mandiri 07:00 - 15:00*) untuk membedakan sesi pengawasan reguler dengan sesi yang di-generate otomatis oleh fitur Ujian Mandiri guru.

## [2.15.3] - 2026-08-20
### Fixed
- **Perbaikan Hardcoded S3 Disk Error pada Modul Layanan BK (`/bk/service-records`)**:
  - Memperbaiki error *"Missing region in S3 configuration"* pada `BkServiceRecordController` akibat pemanggilan disk statis `'s3'` yang tidak terdefinisi pada `config/filesystems.php`.
  - Mengubah pemanggilan penyimpanan dan penghapusan dokumen BK agar menggunakan `config('filesystems.default')` secara dinamis.

### Changed
- **Refactoring & Standarisasi Konfigurasi Filesystem Dinamis di Seluruh Modul**:
  - Mengeliminasi seluruh pemanggilan disk statis (`'public'` / `'s3'`) di berbagai controller dan jobs untuk memastikan semua upload file diarahkan ke Object Storage (MinIO / S3) sesuai pengaturan `.env` (`FILESYSTEM_DISK=s3_local`).
  - File-file yang diperbarui meliputi:
    - *App Core*: `AuthController.php` (Avatar profil), `SettingController.php` (Logo & Favicon), `FacilityReportController.php` (Laporan Sarpras), `TeacherFacilityReservationController.php` & `StudentFacilityReservationController.php` (Proposal Fasilitas), `StudentController.php` (Berkas Siswa), `AdminFacilityAssetController.php` & `AdminFacilityRoomController.php` (Foto Aset & Ruangan Sarpras).
    - *Modul Kesiswaan*: `StudentViolationController.php` (Bukti Pelanggaran), `StudentSanctionController.php` (Surat SP & Pernyataan), `StudentMeritController.php` (Bukti Prestasi), `ExtracurricularCoachController.php` (Dokumentasi Eskul), `CounselingServiceController.php` (Dokumentasi Bimbingan Konseling).
    - *Modul Akademik*: `GuruAgendaController.php` (Foto Selfie Agenda), `StudentProfileController.php` (Berkas Biodata Siswa).
    - *Modul Daftar Ulang*: `RegistrationController.php` & `AdminDaftarUlangController.php` (Berkas Pendaftaran Ulang & Reset Calon Siswa).
    - *Modul Surat & CBT*: `SuratKeluarController.php` (Dukungan logo Word template dari S3/lokal), `CbtIrtCallbackController.php` (Konfigurasi disk analytics).

### Added
- **Command Artisan Sinkronisasi Storage Lokal ke S3 (`php artisan storage:sync-public-to-s3`)**:
  - Menambahkan command CLI berperforma tinggi untuk memigrasikan seluruh berkas dari direktori lokal `storage/app/public` ke Object Storage (MinIO/S3).
  - Dilengkapi fitur pra-pemindaian daftar file S3 (in-memory hash index) untuk pemrosesan super cepat, pencegahan upload ulang file yang sudah ada, opsi `--folder=` untuk filter direktori spesifik, opsi `--dry-run` untuk simulasi, dan *progress bar* interaktif.
  - Berhasil menyinkronkan 19.953 file (agenda selfies, berkas daftar ulang, foto presensi, dokumen sarpras, avatar, dan soal CBT) ke bucket MinIO `portal-sma-staging` dengan penerapan *Public Read Bucket Policy*.

## [2.15.2] - 2026-08-19
### Added
- **Pembedaan Status Selesai Mandiri vs Selesai dari Sistem pada CBT**:
  - Menambahkan kolom `submit_type` pada tabel `cbt_student_exams` (`2026_08_19_140700_add_submit_type_to_cbt_student_exams_table.php`) untuk melacak secara presisi asal/penyebab selesainya pengerjaan ujian siswa (`student`, `system_timeout`, `system_proctor`, `system_teacher`, `system_cheat`).
  - Mengintegrasikan penyimpanan parameter `submit_type` pada seluruh alur pengerjaan CBT:
    - `student`: Siswa menekan sendiri tombol *"Kumpulkan / Selesai Ujian"* (`StudentCbtController::submitExam`).
    - `system_timeout`: Waktu durasi ujian siswa habis otomatis diselesaikan oleh sistem (`StudentCbtController::showExam`).
    - `system_proctor`: Pengawas ruangan menutup sesi ujian via tombol *"Selesaikan Sesi (Tutup)"* atau heartbeat saat jadwal berakhir (`CbtProctorController::endExam`).
    - `system_teacher`: Guru melakukan force submit baik per individu maupun massal pada halaman hasil ujian (`CbtExamController::forceSubmitStudentExam` / `forceSubmitAllStudentExams`).
    - `system_cheat`: Siswa diblokir permanen otomatis karena melanggar fokus 4 kali berturut-turut / Strike 4 (`StudentCbtController::cheatWarning`).
  - Menyesuaikan tampilan label, badge warna, dan keterangan status di halaman Hasil Ujian (`/cbt/exams/{id}/results`) serta halaman Monitor Pengawas (`/cbt/proctor/exam-room/{id}`):
    - 🟢 **Selesai Mandiri**: Siswa selesai secara normal.
    - 🔵 **Selesai Sistem (Waktu Habis)**: Selesai otomatis karena kehabisan waktu.
    - 🟣 **Selesai Sistem (Pengawas)**: Selesai ditutup oleh pengawas ruangan.
    - 🟠 **Selesai Sistem (Dipaksa Guru)**: Selesai dipaksa oleh guru di halaman hasil.
    - 🔴 **Selesai Sistem (Pelanggaran / Ban)**: Selesai dipaksa akibat pelanggaran integritas/fokus ujian.

- **Fitur Penyelesaian Massal Siswa Aktif pada Halaman Hasil CBT (`/cbt/exams/{id}/results`)**:
  - Menambahkan deteksi cerdas dan banner peringatan interaktif jika terdapat siswa yang masih berstatus `login` atau `started` padahal jadwal telah terlewati.
  - Menyediakan tombol aksi massal *"Selesaikan Semua Siswa Aktif"* dengan dialog konfirmasi yang otomatis melakukan grading serentak dan memicu rekalkulasi metrik analisis Classical Test Theory (CTT).

- **Alternatif Tampilan Tabel & Opsi Pagination Dinamis pada Ruang Pengawas CBT (`/cbt/proctor/exam-room/{id}`)**:
  - Menambahkan *View Switcher* interaktif untuk berpindah antara tampilan **Denah Kotak (Card Grid)** 36 bangku dan tampilan **Tabel Peserta (Table View)**.
  - Menyimpan preferensi mode tampilan pengawas di `localStorage` (`cbt_proctor_view_mode`).
  - Dilengkapi fitur pencarian instan (Nama, NISN, Kelas, Nomor Kursi) pada mode tabel.
  - Menambahkan pilihan dropdown baris per halaman dinamis (**10 - 50 - 100** baris) pada Tabel Monitor Pengawas, Tabel Presensi Peserta, dan Tabel Hasil Ujian.

- **Helper & Composable Standarisasi Paginasi Terpusat (`resources/js/Utils/pagination.js`)**:
  - Menyediakan utility `getPaginationProps()` dan composable `usePagination()` untuk standardisasi konfigurasi paginasi komponen `DataTable` (PrimeVue) di seluruh sistem Portal SMA.
  - Mendaftarkan helper global `$pagination` dan `$defaultRowsPerPage` pada `resources/js/app.js` sehingga dapat langsung digunakan di template mana pun via `v-bind="$pagination({ label: 'nama_entitas' })"`.
  - Menyeragamkan standar sistem: ukuran default 10 baris, opsi baris per halaman `[10, 50, 100]`, format tombol navigasi halaman, dan teks template laporan (*"Menampilkan {first} s.d {last} dari {totalRecords} [label]"*).

## [2.15.1] - 2026-08-19 (Hotfix Patch)
### Fixed
- **Bug CBT — Daftar Siswa & Denah Bangku Tidak Muncul pada Monitor Pengawas (`/cbt/proctor/exam-room/{id}`)**:
  - **Root Cause**: Terjadi error SQL (`SQLSTATE[42703]: Undefined column: 7 ERROR: column "cbt_session_id" does not exist`) saat pemanggilan endpoint status pengawas (`CbtProctorController::getStatus()`). Kolom `cbt_session_id` pada tabel `cbt_room_students` telah dihapus pada migrasi database terdahulu karena alokasi bangku bersifat per-ruangan, namun query controller masih memfilter `CbtRoomStudent::where('cbt_session_id', ...)`.
  - **Dampak**: Endpoint polling status mengembalikan respons HTTP 500 sehingga seluruh denah bangku dan tabel presensi peserta di halaman monitor pengawas (`Monitor.vue`) gagal dimuat (tampil kosong).
  - **Fix**: Menghapus filter `->where('cbt_session_id', ...)` pada query `CbtRoomStudent` di method `getStatus()`, `endExam()`, dan `restartRoomSession()` pada `CbtProctorController.php`, membersihkan relasi/fillable usang pada model `CbtRoomStudent` dan `CbtSession`, serta merapikan pemanggilan query di `StudentCbtController.php`.

## [2.15.0] - 2026-08-18
### Added
- **Alur Simpan sebagai Draf pada Penugasan Siswa (`/student/assignments/{id}`)**:
  - Siswa kini dapat menyimpan sementara jawaban penugasan mata pelajaran (Pilihan Ganda, Uraian, dan URL Google Drive) sebagai **Draf** melalui tombol *"Simpan sebagai Draf"* tanpa langsung menyerahkan jawaban ke guru dan tanpa melakukan scoring/posting nilai ke buku nilai.
  - Halaman penugasan siswa dilengkapi banner informatif status draf yang menampilkan waktu terakhir draf disimpan dan status pengerjaan yang belum diserahkan.
  - Form pengerjaan otomatis memuat kembali jawaban draf yang telah disimpan ketika siswa membuka kembali halaman tugas.
  - Pada daftar tugas siswa (`/student/assignments`), tugas yang berstatus draf ditandai dengan lencana *"Draf Tersimpan"* serta tombol aksi *"Lanjutkan Pengerjaan"*.
  - Tugas prasyarat (*prerequisite*) diproteksi ketat: draf pengerjaan tidak dihitung sebagai pemenuhan prasyarat sampai siswa mengumpulkannya secara resmi.
  - Migrasi database `2026_08_18_210000_add_draft_status_to_assignment_submissions_table.php` untuk mendukung status `'draft'` dan kolom `submitted_at` nullable.

- **Fitur Paksa Kumpulkan Draf oleh Guru (`/teacher/assignments/{id}/submissions`)**:
  - Guru dapat memaksa pengumpulan draf jawaban siswa (baik per individu siswa via *"Paksa Kumpulkan"* maupun massal via tombol *"Paksa Kumpulkan Semua Draf"*), terutama saat batas waktu pengumpulan (*due date*) telah terlewati.
  - Sistem otomatis mengevaluasi jawaban draf (penilaian otomatis pilihan ganda & keyword essay similarity), memperbarui status menjadi `'submitted'`, mencatat waktu submit, mengunci akses edit, dan menyinkronkan nilai ke buku nilai guru (`StudentGrade`).

## [2.14.3] - 2026-08-18 (Hotfix Patch)
### Fixed
- **Bug CBT — `autoSubmitExam()` tidak menghentikan heartbeat & interval saat waktu habis** (`ExamSession.vue`):
  - **Root Cause**: Fungsi `autoSubmitExam()` (dipanggil otomatis saat timer siswa habis) tidak meng-set `isRedirecting = true` dan tidak memanggil `clearInterval` pada `heartbeatInterval` maupun `timerInterval` sebelum memproses submit.
  - **Dampak**: Setelah waktu habis, heartbeat masih berjalan setiap 10 detik. Heartbeat mendeteksi status `submitted` → memicu overlay "Ujian Selesai Paksa" dan redirect ulang → potensi **double-redirect** atau Inertia navigation error di tengah proses submit.
  - **Fix**: `autoSubmitExam()` sekarang: (1) set `isRedirecting.value = true`, (2) `clearInterval(heartbeatInterval)`, (3) `clearInterval(timerInterval)` — semua sebelum memanggil `router.post`.
  - **Bonus fix**: `submitExam()` (submit manual oleh siswa) juga diperbaiki: set `isRedirecting = true` sebelum POST agar event `blur`/`visibilitychange` tidak menghitung pelanggaran fokus saat Inertia navigasi post-submit; `isRedirecting` di-reset kembali di `onFinish` jika submit gagal (validasi server), dan heartbeat/timer dihentikan di `onSuccess`.

- **Bug CBT — Pengecekan proctor schedule tidak dibatasi per sesi** (`StudentCbtController.php`, `CbtProctorController.php`):
  - **Root Cause**: Query `CbtProctorSchedule` di `heartbeat()` dan `verifyToken()` hanya memfilter by `cbt_room_id` tanpa `cbt_session_id`. Demikian pula `endExam()`, `restartRoomSession()`, dan `getStatus()` di `CbtProctorController` fetch `CbtRoomStudent` hanya by `cbt_room_id`.
  - **Dampak (Skenario)**: Jika satu ruangan dipakai untuk 2 sesi berbeda di hari yang sama (misal: Sesi Pagi & Sesi Siang), saat pengawas Sesi Pagi menutup sesinya → heartbeat siswa Sesi Siang (yang ujiannya masih berlangsung) mendeteksi `$hasActiveSchedule = false` → **auto-submit paksa ke semua siswa Sesi Siang yang sedang mengerjakan**. Juga berpotensi token dari sesi lain bisa dipakai, dan monitor proktor menampilkan siswa lintas sesi.
  - **Fix**: Semua query `CbtProctorSchedule` kini ditambahkan `->when($rs->cbt_session_id, fn($q,$sid)=>$q->where('cbt_session_id', $sid))` — backward-compatible jika `cbt_session_id` null. Semua query `CbtRoomStudent` di `CbtProctorController` ditambahkan `->where('cbt_session_id', $schedule->cbt_session_id)` agar scope terbatas ke sesi proktor yang aktif.
  - **Pattern wajib**: Setiap query `CbtProctorSchedule` yang melibatkan penempatan siswa harus selalu filter by `cbt_session_id` (dari `CbtRoomStudent`) untuk memastikan isolasi sesi.

- **Bug CBT — Soal & Opsi Teracak Ulang serta Timer Ter-reset saat Re-Entry/Verifikasi Ulang Token** (`StudentCbtController.php`):
  - **Root Cause**: Di fungsi `showExam()`, blok `if ($studentExam->status === 'login')` selalu mengenerate ulang susunan soal, mengacak ulang soal & opsi (`shuffle()`), dan menimpa `started_at = now()`, tanpa mengecek apakah siswa tersebut sudah memiliki urutan soal sebelumnya.
  - **Dampak**: Ketika siswa melanjutkan ujian yang pernah dimulai (misal setelah memasukkan token pengawas pasca Pelanggaran Fokus ke-3 / kick proktor, atau guru menekan tombol *Izinkan Masuk Kembali*), nomor soal bergeser total, opsi pilihan ganda tertukar posisi, dan siswa mendapat tambahan durasi waktu penuh dari awal.
  - **Fix**: Menambahkan pengecekan `$hasExistingOrder = !empty($studentExam->question_order) && is_array($studentExam->question_order)`. Jika siswa melanjutkan ujian, sistem HANYA mengubah status menjadi `'started'` dan mempertahankan `question_order`, `options_order`, serta `started_at` asli. Pengacakan soal & inisialisasi waktu hanya dieksekusi saat pertama kali mulai ujian.
  - **Kompatibilitas**: Perbaikan ini sepenuhnya selaras dengan fitur `allowReenterStudent()` dan `verifyToken()` tanpa mengganggu alur submit/heartbeat yang sudah ada.

- **Bug CBT — `handleWindowBlur` & `handleVisibilityChange` menghitung pelanggaran saat fullscreen overlay aktif** (`ExamSession.vue`):
  - **Root Cause**: `handleWindowBlur` dan `handleVisibilityChange` tidak mengecek `isFullscreenActive`. Ketika siswa belum/tidak dalam fullscreen (overlay biru "Wajib Layar Penuh" sedang tampil), kedua handler ini tetap memanggil `reportCheatWarning()` jika window kehilangan fokus — padahal siswa sudah terkunci dan tidak bisa melihat soal apapun.
  - **Dampak**: Siswa yang kehilangan fokus window saat overlay fullscreen tampil dihukum dengan pelanggaran (warning_count bertambah) secara tidak adil, meskipun mereka tidak bisa mengakses soal sama sekali.
  - **Fix**: Tambahkan guard `&& isFullscreenActive.value` pada kedua handler. Event hanya dihitung sebagai pelanggaran jika ujian memang sedang aktif (fullscreen berjalan).
  - **Catatan iOS/Mac**: `isAppleDevice` dideteksi di `onMounted`. Pada iOS/Mac, `isFullscreenActive` diset **selalu `true`** (`isAppleDevice.value || !!document.fullscreenElement`) karena Apple tidak mengizinkan Fullscreen API. Akibatnya, guard `isFullscreenActive.value` pada iOS selalu lolos → cheat detection tetap berjalan normal di perangkat Apple tanpa pengecualian khusus.
  - **Handler yang tidak diubah**: `handleFullscreenChange` — handler ini memang tugasnya mendeteksi saat user keluar dari fullscreen (event itu sendiri adalah pelanggaran), jadi tidak perlu guard tambahan.

- **Bug CBT — Siswa ujian non-independent tanpa room assignment lolos heartbeat tanpa pengawasan** (`StudentCbtController.php`):
  - **Root Cause**: Di fungsi `heartbeat()`, jika `$roomStudents->isEmpty()` (siswa tidak terdaftar di ruang ujian manapun), blok `if ($roomStudents->isNotEmpty())` dilewati begitu saja, lalu fungsi melanjutkan dan mengembalikan respons `ok`. Siswa tanpa room assignment bisa mengerjakan ujian non-independent (berpengawas) tanpa ada mekanisme pengecekan proktor.
  - **Dampak**: Celah keamanan — siswa dengan data room assignment yang hilang/korup, atau yang sengaja dimanipulasi, bisa menyelesaikan ujian berpengawas tanpa ada proktor yang memantau.
  - **Fix**: Tambahkan pengecekan eksplisit `if ($roomStudents->isEmpty())` untuk ujian non-independent. Jika kosong, heartbeat mengembalikan `status: 'ended'` dengan pesan informatif. Ini konsisten dengan `verifyToken()` yang sudah menolak siswa tanpa room assignment.
  - **Tidak mempengaruhi ujian mandiri** (`is_independent = true`): Blok ini sudah di dalam kondisi `!$exam->is_independent`, jadi Ujian Mandiri tetap bebas dari pengecekan ruangan.

- **Bug CBT — Potensi False Absent Kick saat Siswa Memiliki Riwayat Multi-Room/Sesi** (`StudentCbtController.php`):
  - **Root Cause**: Pada `heartbeat()`, loop `foreach ($roomStudents as $rs)` menandai `$isAbsent = true` jika ada jadwal aktif yang daftar `present_students`-nya tidak memuat ID siswa. Jika siswa memiliki lebih dari 1 penempatan (misal pindah ruangan atau sesi), jadwal ruangan/sesi lain yang aktif bisa salah memicu `$isAbsent = true` meskipun siswa tercatat hadir di ruangan/sesi yang semestinya.
  - **Dampak**: Siswa yang memiliki riwayat mutasi ruangan atau alokasi lintas sesi bisa ter-logout otomatis saat pengawas di ruangan lain menyimpan presensi.
  - **Fix**: Logika presensi diperbaiki dengan melacak `$hasValidPresence` dan `$hasAttendanceList`. Siswa HANYA dianggap absen (`logged_out`) jika seluruh jadwal aktif yang telah mengunci absensi tidak memuat ID siswa tersebut. Jika siswa hadir di setidaknya satu jadwal aktifnya, siswa tetap aman mengerjakan ujian. Penanganan tipe data integer vs string di array `present_students` juga distandarisasi menggunakan `array_map('strval')`.

- **Bug CBT — Validasi `must_complete_all` Menganggap Sub-Jawaban Kosong/Array Kosong sebagai Terisi** (`StudentCbtController.php`):
  - **Root Cause**: Pada `submitExam()`, validasi `must_complete_all` memeriksa `is_array($val) && count($val) > 0`. Pada soal isian dinamis / multi-input atau penjodohan dengan sub-elemen kosong (contoh: `['sub_1' => '', 'sub_2' => '']`), `count($val)` bernilai > 0 sehingga sistem menganggap soal sudah terisi penuh.
  - **Dampak**: Siswa dapat mengumpulkan ujian meskipun masih ada isian multi-input atau penjodohan yang belum diisi saat fitur wajib jawab semua soal diaktifkan.
  - **Fix**: Menggunakan `array_filter()` rekursif untuk memastikan bahwa hanya array dengan setidaknya satu sub-nilai bukan null dan bukan string kosong (`''`) yang dianggap telah terisi (`isAnswered = true`).

- **Bug CBT #5 — Triple-event race condition (`blur` + `visibilitychange` + `fullscreenchange`)**: **Sudah dimitigasi otomatis oleh fix Bug 3**. Setelah `handleFullscreenChange` set `isFullscreenActive = false`, kedua handler `handleWindowBlur` dan `handleVisibilityChange` kini mengecek `&& isFullscreenActive.value` → keduanya langsung diblokir, sehingga tidak ada double-firing apapun saat siswa keluar fullscreen.

## [2.14.2] - 2026-08-18 (Hotfix Patch)
### Fixed
- **Bug Tanggal Timezone UTC vs WIB — `StudentViolations/Index.vue`**:
  - Perbaikan bug tanggal pada form "Catat Pelanggaran" di `/teacher/kesiswaan/discipline/student-violations` yang menyebabkan tanggal yang disimpan ke database mundur 1 hari.
  - **Root Cause**: `createForm.date` menyimpan objek `Date` JavaScript. Ketika dikirim via Inertia tanpa konversi, objek tersebut di-serialize sebagai ISO string UTC (contoh: `2026-08-17T17:00:00.000Z`), sehingga backend menerima tanggal hari sebelumnya (shift timezone WIB = UTC+7).
  - **Fix**: Menerapkan **pattern standar project** — `form.transform((data) => ({ date: new Date(data.date).toLocaleDateString('en-CA') }))` sebelum `.post()` — yang mengonversi objek `Date` ke string `YYYY-MM-DD` berdasarkan **local timezone** (bukan UTC).
  - **Pattern standar ini sudah digunakan di**: `Merits/Index.vue`, `Sanctions/Index.vue`, `Sanctions/Recommendations.vue`, `Guru/Permits/Index.vue`, `Guru/Agenda/Create.vue`, dll. Jika ada halaman baru dengan `DatePicker` + Inertia form submit, **wajib** gunakan `form.transform()` + `toLocaleDateString('en-CA')` untuk konversi tanggal.

## [2.14.1] - 2026-08-18 (Hotfix Patch)
### Fixed
- **Modul CBT - Bug Force Submit & Logout Massal**:
  - Perbaikan logika penghitungan CTT Analytics Otomatis (Classical Test Theory) saat submit ujian agar tidak memaksa/melakukan *force grade* pada siswa lain yang masih aktif mengerjakan (hanya memproses data siswa berstatus 'submitted' dan 'completed').
  - Perbaikan mekanisme *heartbeat* CBT yang sebelumnya memutus otomatis (force logout) Ujian Mandiri karena tidak ditemukannya jadwal pengawas ruangan.
  - Pengecualian Ujian Mandiri saat pengawas menekan tombol "Tutup Sesi" di Ruang Ujian agar siswa yang mengerjakan Ujian Mandiri di ruangan/lab yang sama tidak ikut tertutup.

## [2.14.0] - 2026-08-17 (Minor Feature Release)
### Added
- **Manajemen Disposisi & E-Office Terpadu**:
  - **Hierarki & Pembuatan Disposisi Pimpinan**: Pembuatan instruksi disposisi surat masuk khusus Kepala Sekolah dan Administrator dengan RichTextEditor (WYSIWYG), pemilihan penerima bertingkat (Filter kategori Guru & BK, Staf TU/Pegawai, Siswa), serta fitur *Auto Fan-Out* berdasarkan Kelompok Unit Kerja sekolah (Kurikulum, Kesiswaan, Sarpras, Humas, BK, TU, Wali Kelas).
  - **Isolasi Laporan Tindak Lanjut Guru/Pegawai**: Penegakan aturan hak akses bahwa guru/pegawai hanya dapat menginput laporan pelaksanaan pada baris disposisi miliknya sendiri.
  - **Unggah Lampiran Berkas Multi-File**: Dukungan pengunggahan banyak berkas bukti tindak lanjut (SPPD, Surat Tugas, Struk Bensin/Tol, Foto Kegiatan, Dokumen Pendukung) yang terintegrasi dengan storage S3 MinIO.
  - **Modal Detail Eksekutif & In-App File Previewer**: Dialog tinjauan pelaksanaan tugas bagi Kepala Sekolah & Admin yang dilengkapi kemampuan *preview* langsung berkas PDF dan Gambar tanpa perlu mengunduh file.
  - **Navigasi Bawah (*Bottom Bar*) Modul Surat**: Penambahan tombol navigasi cepat **Surat** pada *bottom navigation bar* `MobileLayout` untuk role Guru (`/surat/kalender`) dan Kepala Sekolah (`/surat/dashboard`).
  - **Notifikasi Disposisi Guru**: Peringatan kartu aktif pada Dashboard Guru (`DashboardGuru.vue`) saat ada disposisi surat dari Kepala Sekolah yang memerlukan tindak lanjut.
### Fixed & Improved
- **Pembersihan & Standardisasi Access Control**:
  - Menyeragamkan seluruh permission persuratan ke standar Portal SMA (`access-surat` dan `manage-surat`) dan menghapus 14 permission atomik redundan (`surat.*.*`) dari database `/admin/access-control`.
  - Memperbarui `PermissionSuratSeeder` dan `MenuSuratSeeder`.
- **Proteksi & Keamanan Rute Persuratan**:
  - Pembatasan halaman arsip surat masuk (`/surat/masuk`), surat keluar (`/surat/keluar`), dan dashboard persuratan (`/surat/dashboard`) hanya untuk Pimpinan dan Staf TU (guru diarahkan otomatis ke dashboard masing-masing).
  - Detail surat masuk (`/surat/masuk/{id}`) diproteksi agar guru hanya dapat membuka surat yang resmi didisposisikan kepada dirinya.
- **Kalender Kegiatan Personal Guru**:
  - Filter kegiatan di `/surat/kalender` otomatis dibatasi hanya menampilkan agenda & surat milik guru yang login.
  - Perbaikan layout kartu kalender (*overflow/out-of-bounds fix*) agar rapi dan responsif di smartphone maupun desktop.
- **Streaming Berkas Storage MinIO**:
  - Penyediaan rute proxy streaming `/surat/tindak-lanjut/lampiran/{id}/file` dan `/surat/tindak-lanjut/{id}/sppd-file` untuk mengatasi pembatasan private bucket MinIO dan CORS pada *preview in-app*.
- **PostgreSQL Enum Constraint Fix**:
  - Pembaruan check constraint status surat masuk (`baru`, `didisposisi`, `didisposisikan`, `diproses`, `selesai`) dan tipe lampiran tindak lanjut pada database PostgreSQL.

## [2.13.0] - 2026-08-16 (Minor Feature Release)
### Added
- **Modul Peminjaman Fasilitas & Aset Guru (`/teacher/sarpras-reservations`)**:
  - Peminjaman mandiri oleh guru untuk fasilitas ruangan (Lab, Aula, Kelas, Lapangan) maupun peralatan/aset sekolah (Laptop, Proyektor, Sound System, Mic, dll.) untuk keperluan KBM, praktikum, atau acara sekolah.
  - Alur persetujuan terintegrasi langsung ke Petugas Sarpras tanpa memerlukan verifikasi pembina eskul.
  - Integrasi tombol pintasan *Pinjam Aset* di dasbor guru (desktop & mobile) dan menu sidebar resmi.
  - Fitur Cek Bentrok Jadwal Realtime (anti-collision check) sebelum pengajuan.
- **Modul Rekap Nilai Siswa (`/student/grades`)**:
  - Halaman rekapitulasi nilai rapor & asesmen siswa per mata pelajaran dengan accordion detail komponen (TP/Sumatif/STS/SAS), bobot nilai, KKM, dan status ketuntasan.
  - Kartu ringkasan performa akademik siswa (Total Mapel Tuntas, Rata-rata Nilai, Predikat).
  - Integrasi tombol "Rekap Nilai" pada bottom navigation bar siswa (`SiswaLayout`).
- **Chain Top Navigation Menu Guru & Modul Penilaian**:
  - `TeacherTabMenu.vue`: Tab bar atas yang saling terhubung untuk seluruh modul guru (Dashboard Kurikulum, Jadwal Mengajar, CP/TP, Agenda, dan Rekap Siswa TKA).
  - `PenilaianTabMenu.vue`: Tab bar atas terpadu untuk modul Bobot Komponen Nilai, Input Nilai, dan Rekap Nilai serta standardisasi layout tabel penilaian.
### Fixed & Improved
- **Regulasi & Proteksi Peminjaman Sarpras Siswa**:
  - Penegakan aturan wajib ekstrakulikuler: siswa hanya dapat meminjam sarpras (ruang dan/atau alat) jika terdaftar di eskul aktif sekolah dan mewakili kegiatan eskul dengan approval Pembina Eskul.
  - Penyesuaian layout antarmuka peminjaman sarpras siswa agar konsisten menggunakan `SiswaLayout` (memperbaiki navigasi menu bawah yang sebelumnya tampil sebagai menu guru).
  - Memperbaiki query relasi pembina eskul pada approval sarpras (`extracurricular_teachers`) untuk kompatibilitas database PostgreSQL.
  - Penonaktifan tampilan menu "Poin Saya" / Tata Tertib siswa pada bottom navigation.

## [2.12.0] - 2026-08-16 (Minor Feature Release)
### Added
- **Modul E-Office & Digital Signature (Tanda Tangan Elektronik / TTE)**:
  - **Cryptographic TTE & Token Verification**: Sistem penandatanganan elektronik surat dinas berbasis token kriptografi unik (`UUID` + `SHA-256 HMAC Hash`) untuk memastikan keaslian dan integritas dokumen tanpa risiko manipulasi.
  - **Halaman Verifikasi Publik (`/surat/verifikasi/{token}`)**: Siapapun (orang tua, dinas, pihak luar) dapat memindai QR Code pada surat tercetak atau PDF untuk memvalidasi keaslian surat, identitas penandatangan, timestamp TTE, dan mengunduh berkas aslinya secara *real-time* tanpa perlu login.
  - **Hierarki Persetujuan (Approval Workflow)**: Alur penerbitan surat berjenjang (Draft $\rightarrow$ Verifikasi/Paraf KTU/Waka $\rightarrow$ TTE Kepala Sekolah) dengan riwayat audit trail lengkap dan fitur pengembalian surat dengan catatan revisi.
  - **Keamanan Otorisasi PIN TTE 6-Digit**: Pengamanan ganda pembubuhan tanda tangan elektronik untuk Kepala Sekolah/pejabat yang berwenang sebelum TTE diterbitkan.
  - **Mesin Surat Resmi & Auto Stamping PDF**: Template PDF resmi standar kedinasan dengan Kop Surat dinamis, integrasi logo sekolah & logo pemda dari `/admin/settings`, stempel digital, penomoran otomatis, serta penyematan spesimen QR Code TTE.

## [2.11.0] - 2026-08-15 (Minor Feature Release)
### Added
- **Modul Manajemen Sarpras Terpadu**:
  - **Manajemen Ruangan & Multi-Foto**: Pengelolaan data fasilitas dan ruangan lengkap dengan dukungan multi-upload foto, galeri thumbnail preview dengan tanda foto utama (cover), status ketersediaan, kapasitas, fasilitas interaktif, dan kontrol peminjaman mandiri siswa.
  - **Manajemen Aset & Peralatan**: Pengelolaan inventaris aset fisik dengan kategori dinamis (combobox editable yang otomatis mempelajari entri baru), multi-foto, kondisi fisik, dan QR Token unik.
  - **Branding QR Code dengan Logo Sekolah**: Pembuatan QR Code otomatis berstandar *High Error Correction (`ecc=H`)* dengan logo resmi sekolah dari `/admin/settings` yang disematkan tepat di tengah QR code. Berlaku pada modal preview interaktif maupun pada modul cetak stiker label QR fisik (`/admin/sarpras/assets/print-qr`).
### Improved & Standardized
- **Standardisasi Modal Dialog**: Menyeragamkan desain antarmuka modal Tambah/Edit Ruangan dan Aset mengikuti standar modal Admin (Header berikon, container proporsional `90vw max-w-650px`, dan footer tombol aksi terpisah).
- **Migrasi Global PrimeVue v4 Markup**: Memperbarui seluruh pemanggilan elemen search/input icon di seluruh aplikasi (13+ file modul: Akademik, Kesiswaan, Kepegawaian, BK, Autentikasi, Surat, dll) dari class legacy `p-input-icon-left` menjadi standar komponen resmi PrimeVue v4 `<IconField><InputIcon/></IconField>`.

## [2.10.8] - 2026-08-11 (Patch)
### Added
- **Otomatisasi Penulisan Gelar Guru**: Sistem kini secara otomatis merangkai dan menampilkan gelar depan dan gelar belakang guru ke dalam nama lengkapnya (`full_name`) di seluruh tampilan aplikasi (dasbor siswa, jadwal, daftar hadir, CBT, dsb.). Pengecualian dilakukan khusus pada halaman manajemen/CRUD master data guru (`admin.teachers.*`) agar Admin tetap dapat mengedit nama asli tanpa tercampur format gelar.
### Fixed
- **Pembersihan Gelar Ganda & Template PDF**: Memperbaiki isu gelar ganda dengan menerapkan regular expression (Regex) cerdas yang otomatis mendeteksi dan menghapus gelar apabila admin/guru sudah terlanjur mengetiknya secara manual di dalam kolom *full_name*. Selain itu, menghapus penggabungan gelar yang sebelumnya di-hardcode pada file _blade template_ Jurnal Mengajar Pribadi (`agenda_personal.blade.php`) dan dropdown Kelas.

## [2.10.7] - 2026-08-11 (Patch)
### Fixed
- **Pembersihan Otomatis Jadwal Pengawas Mandiri**: Memperbaiki masalah jadwal pengawas ujian mandiri (proctor schedules) yang tertinggal (yatim) ketika Jadwal Ujian Mandiri dihapus, diubah tanggalnya, atau dinonaktifkan mode mandirinya. Kini sistem akan otomatis mendeteksi dan menghapus jadwal pengawas yang sudah tidak digunakan, serta menyediakan _Artisan Command_ `cbt:clean-orphaned-proctor-schedules` untuk membersihkan sisa data lama di server produksi.
- **Pencegahan Reset Status Pengawas**: Memperbaiki metode pembaruan (dari `updateOrCreate` menjadi `firstOrCreate`) agar tidak mereset status pengawas kembali menjadi *not_started* (belum dimulai) ketika ada pembaruan data ujian di tengah-tengah pelaksanaan ujian mandiri.

## [2.10.6] - 2026-08-10 (Patch)
### Added
- **Cetak PDF Laporan Sarana & Prasarana**: Menambahkan fitur cetak rekapitulasi data kerusakan sarana & prasarana ke dalam format PDF yang berisi kolom: No, Ruang/Gedung, Nama Barang, Level Kerusakan, serta Keterangan/Tindakan (dari catatan verifikator). Dilengkapi juga dengan format persetujuan tanda tangan Waka Sarpras dan Kepala Sekolah di bagian bawah laporan.
- **Filter Pencarian Teks & Urutan Abjad Ruang/Gedung**: Mengoptimalkan menu *dropdown* filter Ruang/Gedung pada halaman Rekapitulasi agar menyusun nama ruangan secara alfabetis dan dapat diketik (fitur pencarian instan).
- **Interactive Photo Viewer**: Menggantikan dialog bawaan foto bukti laporan kerusakan dengan komponen PrimeVue Image. Kini gambar dapat di-zoom in, zoom out, dan digeser (pan/drag) secara bawaan (*native*).
### Fixed
- **Navigasi Tombol Kelola Laporan**: Mengubah tombol pintasan perkakas/kunci inggris (Kelola Penanganan) di halaman daftar Laporan Sarana & Prasarana (`/admin/facility-reports`) agar terbuka otomatis di tab baru (menggunakan *link* native `target="_blank"`).

## [2.10.5] - 2026-08-09 (Patch)
### Added
- **Kelola Laporan Sarana & Prasarana**:
  - **Pencatatan Rincian Barang**: Menambahkan fitur pengisian rincian barang (jenis, nama barang, ruang/gedung, keparahan, saran) menggunakan popup modal *dynamic rows* yang akan otomatis muncul saat admin memverifikasi laporan kerusakan sarana prasarana.
  - **Rekapitulasi Sarana Prasarana**: Menambahkan laman baru `/admin/facility-reports/recap` yang dilengkapi Tab Menu navigasi, digunakan untuk melihat seluruh daftar sarana/prasarana yang pernah didaftarkan dari berbagai laporan.
  - **Filter Ruang/Gedung Anti-Redundan**: Menambahkan fitur filter dropdown berbasis nama ruang/gedung secara dinamis pada tabel Rekapitulasi agar operator mudah melacak jumlah kerusakan per ruangan dan mencegah pendataan berulang.
  - **Manajemen Status & Konfirmasi Aksi**: Menambahkan fitur Edit informasi dan pembaruan status perbaikan (*Dalam Perbaikan*, *Selesai*) untuk masing-masing barang, lengkap dengan dialog konfirmasi khusus untuk aksi ubah status dan penghapusan data.


## [2.10.4] - 2026-08-08 (Patch)
### Fixed & Improved
- **Pengaturan & Laporan PDF (Kop Surat Terpusat)**:
  - **Satu Baris Nama Sekolah (No-Wrap)**: Menyesuaikan aturan spasi CSS pada Kop Surat (`white-space: nowrap`) untuk menjamin nama sekolah panjang (contoh: "SEKOLAH MENENGAH ATAS NEGERI 16 SEMARANG") selalu tercetak rapi dalam satu baris, mencegah pemotongan paksa ke baris baru.
  - **Blade Component Terpusat (`kop-surat.blade.php`)**: Memusatkan seluruh skrip HTML dan styling CSS Kop Surat (gabungan logo Pemda di kiri dan logo Sekolah di kanan beserta teks berjenjang) ke dalam satu _blade component_ terpusat (`resources/views/components/kop-surat.blade.php`). Hal ini menghilangkan duplikasi kode berulang di berbagai file PDF (Laporan Sesi, Rekap Bulanan, dsb.) dan mempermudah perubahan tata letak di masa mendatang.

## [2.10.3] - 2026-08-08 (Patch)
### Fixed & Improved
- **Modul Presensi Siswa (Integrasi S3 MinIO & Laravel Proxy Stream Route)**:
  - **Dukungan Dynamic URL Accessor Foto Presensi**: Menambahkan Accessor `image_in_url` dan `image_out_url` pada model `Attendance` (`Modules/Kesiswaan/app/Models/Attendance.php`) untuk meresolusi URL foto presensi siswa (Check-in & Check-out) secara terpusat berdasarkan disk storage yang dikonfigurasi (`presensi_disk`).
  - **Laravel Proxy Stream Route (`/presensi/image/{filename}`)**: Membuat `AttendanceImageController` (`Modules/Kesiswaan/app/Http/Controllers/AttendanceImageController.php`) yang mem-proxy dan mem-stream foto presensi secara langsung dari storage backend aktif (MinIO S3 atau lokal) ke browser pengguna. Mengatasi error `403 AccessDenied` dari MinIO bucket bersifat *private* tanpa memerlukan perubahan bucket policy/ACL di server MinIO.
  - **Failover Dual-Layer & SSL Handshake Fix**: Menambahkan opsi `'http' => ['verify' => env('AWS_SSL_VERIFY', false)]` pada konfigurasi disk S3 di `config/filesystems.php` untuk mencegah kegagalan *cURL SSL certificate verify error 60* saat PHP Laravel berkomunikasi dengan endpoint MinIO S3. Mengimplementasikan pembacaan stream langsung (`readStream`) dan fallback otomatis ke storage lokal jika MinIO offline atau file masih tersimpan secara lokal.
  - **Proteksi Backup Job Upload**: Memperbarui `UploadAttendanceImageJob` agar secara otomatis menyimpan foto presensi ke storage lokal server jika upload ke S3 MinIO mengalami kegagalan berulang setelah 3 kali percobaan (*retry*), menjamin foto presensi siswa tidak akan pernah hilang.
  - **Pembaruan Antarmuka Vue (`Index.vue` & `MonthlyRecap.vue`)**: Memperbarui komponen Vue Log Presensi Admin (`Index.vue`) dan Rekap Bulanan Siswa (`MonthlyRecap.vue`) agar memanfaatkan `image_in_url` dan `image_out_url` secara dinamis, menghilangkan pengaitan URL lokal `/storage/presensi/` yang bersifat hardcoded.

## [2.10.2] - 2026-08-06 (Patch)
### Added
- **Modul Ekstrakurikuler (Guru Pembina)**: Menambahkan fitur dan antarmuka bagi Guru Pembina untuk melihat daftar lengkap siswa yang bergabung pada kegiatan ekstrakurikuler yang dibinanya. Menambahkan tabel daftar anggota beserta informasi Kelas dan Tanggal Bergabung.

## [2.10.1] - 2026-08-05 (Patch)
### Added & Improved
- **Modul CBT & Analisis Analitik (PDF Report & Performance Decoupling)**:
  - **PDF Report Data Jawaban Siswa (`REPORT_DATA_JAWABAN.pdf`)**: Mengimplementasikan ekspor cetak PDF laporan pemeriksaan jawaban siswa pada rute Bank Soal (`/cbt/bank/{id}/export-answers-pdf`) dan Sesi Ujian (`/cbt/exams/{id}/export-answers-pdf`). Menyajikan string kunci jawaban runtut, pengurutan siswa alfabetis (A-Z), format gender strict (`L`/`P`), 1-baris header data umum tanpa wrap, dan layout tanda tangan simetris.
  - **Decoupled Read Analytics**: Memastikan rute GET `/cbt/bank/{id}/analytics` hanya membaca data dari database terhitung (`cbt_ctt_exam_summaries`, `cbt_ctt_item_analyses`, `cbt_irt_item_parameters`) secara instan (<50ms) tanpa melakukan rekalkulasi ulang pada setiap request pembukaan halaman.
  - **Ringkasan Statistik Sekali di Akhir Tabel**: Memindahkan baris statistik ringkasan (JUMLAH, TERKECIL, TERBESAR, RATA-RATA, SIMPANGAN BAKU) keluar dari `tfoot` ke bagian akhir `tbody` dengan aturan `page-break-inside: avoid` agar hanya muncul tepat 1 kali di halaman terakhir laporan cetak.
- **Modul Admin Backup & Diagnostik Sistem Server**:
  - **Diagnostik Real-Time Uvicorn IRT Microservice**: Menambahkan card diagnostik status service Python Uvicorn (`IRT_MICROSERVICE_URL`) di `/admin/backups` beserta latency respon (ms) dan proteksi header token `X-CBT-Secret`.
  - **Diagnostik Storage Cloud (MinIO / S3)**: Menambahkan pengujian konektivitas real-time (Write, Read, Delete) ke storage MinIO / S3 pada halaman `/admin/backups`.
  - **Proteksi Keamanan API Inter-Service**: Mewajibkan validasi header token `X-CBT-Secret` dan pembatasan callback internal pada Microservice Python IRT (`main.py`) dan controller Laravel.

## [2.10.0] - 2026-08-05 (Minor)
### Added & Improved
- **Modul CBT (Decoupling Penilaian CTT & IRT Microservice)**:
  - **Decoupling Scoring Engine**: Memisahkan kalkulasi hasil ujian menjadi 2 jalur independen: CTT (Classical Test Theory) dan IRT (Item Response Theory - Rasch/1PL, 2PL, 3PL) secara *asynchronous* agar tidak membebani HTTP web worker dan database saat submit massal.
  - **Threshold Peserta IRT ($N \ge 100$)**: Menetapkan aturan ambang batas minimal peserta $N \ge 100$ agar estimasi parameter IRT ($\theta, a, b, c$) tetap reliabel dan konvergen secara psikometrik. Ujian dengan $N < 100$ secara otomatis hanya menggunakan analisis CTT.
  - **Integrasi Async Microservice Python**: Menghapus komputasi IRT dari alur *request-response* / view Laravel dan mengalihkannya ke Microservice Python terpisah (`cbt-irt-service`) via Redis Queue / Async Webhook (`POST /api/cbt/internal/irt-callback`).
  - **Skema Tabel Database Analisis Baru**: Menambahkan rancangan struktur tabel `cbt_analysis_jobs`, `cbt_ctt_student_results`, `cbt_ctt_item_analyses`, `cbt_ctt_exam_summaries`, `cbt_irt_item_parameters`, dan `cbt_irt_student_abilities`.

## [2.9.14] - 2026-08-05 (Patch)
### Fixed & Improved
- **Modul CBT (Computer Based Test & Proktoring)**:
  - **Pengecualian Wajib Fullscreen bagi Perangkat Apple**: Mengimplementasikan pengecualian mode layar penuh khusus bagi perangkat Apple (iPad, iPhone, iPod, Mac) pada laman pengerjaan ujian CBT siswa (`ExamSession.vue`). Hal ini dilakukan untuk menghindari sistem memblokir perangkat iOS/Safari yang tidak mendukung fitur `requestFullscreen` dengan sempurna. Pengecekan dilakukan secara komprehensif melalui perpaduan *User-Agent* dan *Platform*, namun *event* larangan berpindah tab (`visibilitychange` & `blur`) tetap diberlakukan dengan semestinya.

## [2.9.13] - 2026-08-04 (Patch)
### Fixed & Improved
- **Modul CBT (Computer Based Test & Proktoring)**:
  - **Pencegahan Duplicate Activity Log Saat Login**: Memperbarui trait `LogsActivity` (`app/Traits/LogsActivity.php`) untuk mengecualikan kolom `last_login_at` dan `last_login_ip` dari pencatatan log event `updated`. Login pengguna kini mencatat tepat 1 record log bersih ("Pengguna berhasil login ke portal") tanpa catatan ganda.
  - **Sistem Audit Logging CBT Terintegrasi**: Mengintegrasikan `ActivityLogger` pada seluruh aksi CBT & proktoring (`CBT_VERIFY_TOKEN`, `CBT_CHEAT_WARNING`, `CBT_SUBMIT`, `CBT_ALLOW_REENTER`, `CBT_ADMIN_REOPEN_EXAM`, `CBT_PROCTOR_GENERATE_TOKEN`, `CBT_PROCTOR_ATTENDANCE`, `CBT_PROCTOR_START`, `CBT_PROCTOR_END`, `CBT_PROCTOR_RESTART_ROOM`, `CBT_PROCTOR_LOGOUT_STUDENT`).
  - **Perbaikan Zona Waktu ISO 8601 & Heartbeat**: Memperbarui pengiriman tanggal/waktu pada controller dan halaman Vue CBT agar menggunakan ISO 8601 dengan offset zona waktu (`+07:00` WIB / Asia/Jakarta) sehingga terhindar dari ketidaksesuaian jam lokal vs UTC.
  - **Fitur Buka Sesi Ujian Ruangan Serentak**: Menambahkan tombol **"Buka Kembali Sesi Ruangan"** di header konsol monitor pengawas (`/cbt/proctor/exam-room/{id}`). Saat sesi ruangan ditutup/selesai, pengawas dapat membuka kembali sesi seluruh siswa di ruangan tersebut secara serentak dalam 1 klik tanpa harus klik 1-per-1. Jawaban siswa dan waktu `started_at` tidak ter-reset.
  - **Proteksi Siswa Ban (Strike 4+) & Akses Ujian Ulang**: Memastikan siswa yang terkena ban permanen (`warning_count >= 4` atau `is_blocked = true`) tidak dapat menggunakan fitur Buka Kembali / Reset Status Ujian. Tombol `pi-undo` disembunyikan untuk siswa ban, dan backend menolak reset status. Siswa yang terkena ban hanya bisa di-reset total (Ujian Ulang Dari Awal) oleh guru/admin jika diizinkan.
  - **Perlindungan Terpenuh Siswa Tersuspensi saat Sesi Ruangan Ditutup**: Memperbarui method `endExam` agar siswa yang sedang dalam masa kunci suspensi sementara (`blocked_until` di masa depan akibat Strike 1/2) diabaikan dari penutupan massal sehingga timer suspensi mereka tetap berjalan hingga selesai.
  - **Otorisasi Pemilik Ujian & Respons 404 pada URL Direct**: Memperbarui `CbtExamController` dan `CbtProctorController` dengan metode `checkExamOwnership`. Guru lain yang mencoba mengakses URL hasil ujian atau konsol proktoring milik guru lain secara manual langsung ditolak dengan respons **404 Not Found**.
  - **Desain Tombol 3D Tactile Lembar Ujian Siswa**: Memperbarui antarmuka `ExamSession.vue` dengan merombak kartu opsi jawaban (Pilihan Ganda & Checklist), badge huruf A/B/C/D, serta tombol angka Peta Soal menjadi tombol interaktif 3D dengan efek *card elevation*, *depth shadow*, *hover lift*, dan penanda status aktif yang kontras.
  - **Pengurutan Abjad & Indikator Progres Soal**: Mengurutkan daftar siswa di konsol pengawas secara alfabetis (A-Z) dan menambahkan indikator progres jumlah soal yang telah dijawab (`12/50` + progress bar) di bangku monitor pengawas dan hasil ujian guru.
  - **Perbaikan CSS Layout Kotak Bangku**: Menambahkan aturan `overflow: hidden`, `max-width: 100%`, dan `line-clamp: 2` pada nama siswa di `.seat-box` agar teks tidak pernah meluber (*out of bounds*).

## [2.9.12] - 2026-08-04 (Patch)
### Changed & Improved
- **Modul Akademik (Manajemen TKA - Pemilihan 2 Mata Pelajaran)**:
  - **Dukungan Pemilihan 2 Mapel TKA Sekaligus**: Memperbarui logika modal peringatan pemilihan mata pelajaran TKA untuk siswa Kelas XII pada antarmuka siswa (`SiswaLayout.vue`) agar menyediakan dua pilihan mata pelajaran (`Mata Pelajaran TKA Pertama` dan `Mata Pelajaran TKA Kedua`) sekaligus, menggantikan sistem satu pilihan mapel sebelumnya. Dilengkapi dengan validasi agar kedua mata pelajaran yang dipilih tidak boleh sama.
  - **Pencegahan Tumpang Tindih Modal di Halaman TKA**: Menambahkan kondisi agar modal peringatan wajib TKA tidak muncul pada URL `/student/tka` untuk mencegah modal menutupi halaman utama pengelolaan TKA siswa.
  - **Pembaruan Middleware Prop TKA**: Memperbarui middleware `HandleInertiaRequests` agar memeriksa status pemenuhan kuota **2 mata pelajaran** (`$tkaCount >= 2`). Prop `tka_warning` kini menyertakan informasi `chosen_count` dan `chosen_ids` untuk penyesuaian otomatis UI siswa (mengunci pilihan mapel pertama jika sudah terpilih sebelumnya).
  - **Pendaftaran Sekaligus di Controller**: Memperbarui metode `selfRegister` pada `TkaStudentController` agar mendukung penerimaan parameter array `tka_subject_ids` maupun parameter tunggal `tka_subject_id`, dengan pembatasan otomatis kuota maksimal 2 mata pelajaran per siswa.

## [2.9.11] - 2026-08-03 (Patch)
### Added
- **Modul Akademik (Manajemen TKA)**:
  - **Rekap Daftar Siswa TKA per Kelas**: Menambahkan fitur rekapitulasi pilihan mata pelajaran TKA siswa berdasarkan rombel/kelas XII (`/akademik/tka-recap`). Admin dapat memilih atau menyaring kelas XII, melihat statistik jumlah siswa yang sudah/belum memilih, serta melihat rincian mata pelajaran pilihan setiap siswa. Siswa yang belum memilih diberi penanda `-`.
  - **Tombol Akses Rekap TKA**: Menambahkan tombol **Rekap per Kelas** pada halaman utama Master TKA (`/akademik/tka-subjects`) dan halaman Kelola Siswa Mapel (`/akademik/tka-subjects/{id}/students`) untuk akses cepat ke halaman rekap.
  - **Migrasi Menu Rekap TKA**: Menambahkan migrasi menu sidebar admin untuk rute `/akademik/tka-recap`.

### Fixed
- **Modul Akademik (Vue TKA & Model Student)**:
  - **Perbaikan Nama Siswa Kosong di Modul TKA**: Memperbaiki masalah nama siswa tidak muncul di tabel pada halaman siswa mapel TKA dan presensi TKA dengan menambahkan *accessor* `getNameAttribute()` dan `'name'` ke properti `$appends` model `Student` (`Modules/Akademik/app/Models/Student.php`), serta memperbarui antarmuka Vue TKA agar mendukung format `student.full_name || student.name`.

## [2.9.10] - 2026-08-03 (Patch)
### Fixed
- **Modul Akademik (Model Student)**:
  - **Perbaikan RelationNotFoundException `classroom`**: Menambahkan method relasi alias `classroom()` pada model `Student` (`Modules/Akademik/app/Models/Student.php`) yang merujuk ke `currentClassroom()`. Hal ini memperbaiki fatal error `Call to undefined relationship [classroom] on model [Modules\Akademik\Models\Student]` saat melakukan *eager loading* (seperti `with('student.classroom')` pada modul TKA) serta saat pengaksesan properti `student.classroom.name` di antarmuka Vue.
  - **Perbaikan Bug Filter Status Kelas**: Memperbaiki kriteria filter status pada method `classInYear()` dari `'active'` menjadi `'aktif'` sesuai dengan nilai yang tersimpan dalam tabel pivot `classroom_students`.

## [2.9.9] - 2026-08-02 (Patch)
### Added
- **Modul Akademik**:
  - **Import & Export Massal NIS/NISN**: Menambahkan fitur `StudentBulkNisExport` dan `StudentBulkNisImport` pada halaman Manajemen Siswa. Fitur ini memungkinkan admin untuk mengunduh template Excel berisi daftar siswa dan mengunggah kembali file tersebut untuk memperbarui NIS & NISN secara massal (Bulk Update).

## [2.9.8] - 2026-08-02 (Patch)
### Fixed & Improved
- **Modul Surat**:
  - **Standarisasi UI & Layout Modals**: Memperbaiki layout pada halaman `SuratMasuk/Create`, `SuratKeluar/Create`, modal `JenisSurat`, modal `Kelompok`, serta modal `Buat Disposisi` dan `Tindak Lanjut`. Semua layout kini menggunakan standarisasi responsif yang sama dengan Layanan BK (flex-column, gap-2). Tombol 'Kembali' dihapus dari halaman Create.
  - **Dashboard Surat Shortcuts**: Menambahkan tautan/menu cepat (Quick Access) untuk Surat Masuk, Surat Keluar, Master Jenis, dan Menunggu Disposisi langsung dari Dashboard Surat untuk memudahkan user role Pegawai.
  - **Perbaikan Fatal Error Ziggy & Route Model Binding**: Memperbaiki error Ziggy *'masuk' parameter is required* saat transisi Vue dengan menambahkan fungsi eksplisit pemetaan parameter `->parameters(['masuk' => 'suratMasuk'])` di `routes/web.php` dan memperbaiki binding ID di file `Index.vue` & `Show.vue`.
  - **Pencegahan Error Undefined Status**: Menambahkan v-if rendering tag `status` pada tabel dan modal untuk mengatasi peringatan `TypeError: Cannot read properties of undefined (reading 'toUpperCase')`.
  - **Seeder Permission & Role Surat**: Mengubah struktur default seeder agar Role untuk modul surat menjadi sinkron dengan hierarki *portal-sma* yang sesungguhnya (`pegawai`, `kepsek`, `admin`, dsb).


## [2.9.7] - 2026-08-02 (Patch)
### Fixed
- **Cloudflare 524 Timeout pada Presensi Siswa & Download Backup**:
  - **Akar Masalah (Presensi)**: Upload foto presensi (base64 ~100–300 KB) ke MinIO dilakukan **sinkron** di dalam satu HTTP request. Di jam sibuk (ratusan siswa pukul 07:00 bersamaan), PHP workers penuh menunggu MinIO → antrian panjang → Cloudflare memutus koneksi dengan error 524.
  - **Akar Masalah (Backup Download)**: `$disk->download($path)` mem-*stream* file ZIP backup (50 MB – 2 GB+) melewati Laravel → Cloudflare → browser. Cloudflare tidak mentoleransi waktu transfer sebesar ini → error 524.
  - **Solusi (Presensi) — Decoupled Upload via Queue Job**:
    - Membuat Job baru [`UploadAttendanceImageJob`](Modules/Kesiswaan/app/Jobs/UploadAttendanceImageJob.php) yang berjalan di background queue (`database` driver).
    - Merefaktor `AttendanceService::storeImage()` menjadi `storeImageAsync()`: data presensi (`clock_in`, `status`, GPS) **langsung disimpan ke DB** tanpa menunggu upload MinIO. Foto ditulis ke file sementara lokal (`storage/app/presensi-temp/`), lalu Job di-dispatch ke queue.
    - Job mengupload foto ke MinIO dan mengupdate kolom `image_in` / `image_out` setelah selesai. Jika MinIO tidak stabil, Job **otomatis retry 3x** dengan jeda 10 detik.
    - Dampak: Response ke siswa balik dalam **< 1 detik** terlepas dari kondisi MinIO.
  - **Solusi (Backup Download) — MinIO Presigned URL**:
    - Method `BackupController::download()` kini men-detect disk driver. Untuk disk S3/MinIO, di-generate **Temporary Presigned URL** (expire 15 menit) dan browser langsung di-redirect ke URL MinIO tersebut — melewati Cloudflare sepenuhnya.
    - Fallback otomatis ke stream Laravel untuk disk `local`.
  - **File yang Diubah**:
    - `Modules/Kesiswaan/app/Jobs/UploadAttendanceImageJob.php` — **[BARU]** Queue Job upload foto presensi.
    - `Modules/Kesiswaan/app/Services/AttendanceService.php` — Refaktor upload menjadi async, tambah `storeImageAsync()`.
    - `app/Http/Controllers/Admin/BackupController.php` — Ganti stream dengan Presigned URL redirect.

## [2.9.6] - 2026-08-01 (Patch)
### Changed
- **Standarisasi Bahasa Presensi**: 
  - Mengubah terminologi "Kehadiran" menjadi "Presensi" secara komprehensif pada antarmuka, *flash messages*, dan menu sistem.
  - Menyeragamkan label "Telat" menjadi "Terlambat".
  - Memperjelas perbedaan tipe ketidakhadiran dengan mengubah label "Alfa" menjadi "Tanpa Keterangan (A)" dan "Absen Mapel" menjadi "Tidak Hadir (TH)".

## [2.9.5] - 2026-08-01 (Patch)
### Changed
- **Penyimpanan Backup**: Mengembalikan konfigurasi penyimpanan utama backup database dari S3 ke *local disk* bawaan server untuk mempercepat proses pencadangan (backup) dan pemulihan (restore).
- **Pengiriman Backup ke S3**: Menambahkan fitur tombol khusus *Sync to S3 Minio* pada halaman Manajemen Backup Admin (`/admin/backups`) agar file backup lokal dapat dikirim ke server penyimpanan S3 secara manual dan selektif.

## [2.9.4] - 2026-08-01 (Patch)
### Added
- **Modul Surat Menurat / Tata Usaha (Persiapan Surat-Menyurat)**:
  - Membangun antarmuka dan infrastruktur backend pengelolaan Surat Masuk, Surat Keluar, Disposisi Multi-Tujuan (Arsip, Pegawai, Kelompok), serta laporan Tindak Lanjut beserta pengunggahan lampiran SPPD dan Struk.
  - Integrasi Kalender Surat dan Master Data Jenis Surat & Kelompok Penerima Disposisi.
- **Modul Layanan Bimbingan Konseling (BK / Counseling Service)**:
  - Memperbarui antarmuka pengelolaan layanan konseling siswa (`Create.vue`, `Edit.vue`, `Show.vue`) dengan pengalaman pengguna (UX) yang disempurnakan serta penyesuaian skema database untuk histori konseling.

## [2.9.3] - 2026-08-01 (Patch)
### Fixed & Improved
- **Peningkatan Impor & Pengelolaan Bank Soal CBT**:
  - Mengoptimalkan `WordQuestionParser` dan `CbtImageController` dalam pengunggahan dan proxy gambar soal CBT ke MinIO/S3.
  - Menyempurnakan `BackupController` untuk fungsionalitas unduh, hapus, restore backup database, dan pelaporan tes koneksi storage cloud.

## [2.9.2] - 2026-07-31 (Patch)
### Added
- **Antarmuka & Fitur Presensi Modul TKA (Tes Kompetensi Akademik)**:
  - Mengimplementasikan antarmuka Vue untuk pendaftaran siswa, penjadwalan sesi TKA, dan pencatatan presensi siswa (`TkaStudentController`, `TkaSessionController`, `TkaAttendanceController`).
  - Menambahkan navigasi dan menu TKA pada `SiswaLayout.vue` serta pengiriman variabel status via middleware Inertia (`HandleInertiaRequests`).

## [2.9.1] - 2026-07-31 (Patch)
### Fixed
- **Perbaikan Isu Copy-Paste Penugasan Siswa**: Menambahkan penanganan eksplisit (`user-select: auto; -webkit-user-select: auto;`, `@paste.stop`, dll.) pada form isian jawaban penugasan siswa (`Show.vue`) guna memastikan browser mengizinkan *copy-paste* dan fitur context menu tidak terblokir oleh *event listener* global aplikasi CBT atau *style* komponen bawaan.

## [2.9.0] - 2026-07-31 (Minor)
### Added
- **Modul Tes Kompetensi Akademik (TKA)**: Membangun infrastruktur backend awal untuk fitur pilihan Mapel TKA khusus kelas XII yang alurnya terintegrasi mirip ekstrakurikuler. Termasuk:
  - `TkaSubject`: Master data mata pelajaran TKA.
  - `TkaStudent`: Pivot pemilihan/penugasan mapel TKA kepada siswa dengan pengikatan ketat khusus untuk Kelas XII.
  - `TkaSession`: Jadwal pertemuan / sesi absensi TKA.
  - `TkaAttendance`: Rekapitulasi absensi harian per sesi TKA.
- **Seeder Otomatis TKA**: Menambahkan seeder otomatis (`TkaSubjectSeeder`) untuk 12 mata pelajaran TKA baku (Matematika Tingkat Lanjut, Fisika, Biologi, Sejarah, dsb.) yang langsung terikat pada Tahun Ajaran yang sedang aktif.
## [2.8.7] - 2026-07-30 (Patch)
### Fixed
- **Orphaned Media Files pada Hapus / Kosongkan Bank Soal CBT**:
  - **Masalah**: Saat guru menekan tombol *"Kosongkan Soal"* (`POST /cbt/bank/{id}/clear`) atau *"Hapus Bank Soal"* (`DELETE /cbt/bank/{id}`), hanya record database yang dihapus. File gambar soal di MinIO/S3 maupun di local storage (`storage/app/public/cbt_questions/`) **tidak pernah dihapus** → terakumulasi selamanya sebagai *orphaned files*.
  - **Solusi**: Menambahkan method `deleteBankMedia(int $bankId)` pada `CbtBankController` yang dipanggil sebelum penghapusan record DB. Method ini menggunakan strategi **dua lapis**:
    - **Lapis 1 (Presisi)**: Memindai kolom `question_text` setiap soal, mengekstrak nama file dari URL proxy (`/cbt/questions/image/{filename}`), lalu menghapus file tersebut dari disk aktif (MinIO/S3) **dan** disk `public` lokal (fallback).
    - **Lapis 2 (Safety Net)**: Menghapus semua file fisik di folder `storage/app/public/cbt_questions/` yang cocok dengan pola `cbt_bank_{id}_*` menggunakan `glob()` — memastikan tidak ada file sisa meskipun soal tidak memiliki referensi gambar di HTML.
  - Kedua method `clearQuestions()` dan `destroy()` di `CbtBankController` kini memanggil `deleteBankMedia()` sebelum menghapus soal dari database.

## [2.8.6] - 2026-07-30 (Patch)
### Fixed
- **Gambar Soal CBT Tidak Tampil (Error 403) dari MinIO/S3**:
  - **Akar Masalah**: MinIO bucket secara default bersifat **private**. URL gambar yang di-generate langsung oleh `Storage::disk()->url()` menghasilkan link langsung ke endpoint MinIO tanpa credentials. Browser menerima `403 Forbidden` saat mencoba memuat gambar soal.
  - **Solusi — Proxy Route Laravel**: Membuat controller [`CbtImageController`](Modules/Cbt/app/Http/Controllers/CbtImageController.php) yang berperan sebagai proxy HTTP. Setiap request gambar soal kini melewati Laravel (`GET /cbt/questions/image/{filename}`) yang kemudian mem-*forward* konten file dari storage backend aktif (MinIO, S3, atau local) menggunakan `readStream()`.
  - **Keunggulan proxy dibanding alternatif lain**:
    - ✅ Tidak memerlukan konfigurasi bucket policy atau ACL di sisi server MinIO.
    - ✅ URL tidak pernah expired (berbeda dengan pre-signed `temporaryUrl()` yang disimpan permanen di database).
    - ✅ Kompatibel dengan semua backend storage (`local`, `public`, `s3`/MinIO) tanpa perubahan konfigurasi.
    - ✅ Fallback otomatis ke disk `public` lokal untuk soal lama yang diimpor sebelum migrasi ke MinIO.
    - ✅ Route dilindungi middleware `auth` — gambar soal hanya bisa diakses pengguna terautentikasi.
  - **Perubahan file**:
    - `Modules/Cbt/app/Http/Controllers/CbtImageController.php` — **[BARU]** controller proxy streaming gambar dengan sanitasi filename (cegah path traversal) dan header `Cache-Control: public, max-age=86400`.
    - `Modules/Cbt/routes/web.php` — Mendaftarkan route `GET cbt/questions/image/{filename}` bernama `cbt.questions.image`.
    - `Modules/Cbt/app/Services/WordQuestionParser.php` — Mengganti `$disk->url()` dengan `route('cbt.questions.image', ['filename' => $fileName])` saat men-generate HTML `<img>` soal. Upload gambar kini juga ditambahkan visibility `'public'` sebagai lapisan kompatibilitas tambahan.

## [2.8.5] - 2026-07-30 (Patch)
### Fixed
- **Error Import Soal CBT: `SQLSTATE[22P05] Untranslatable character` (PostgreSQL SQL_ASCII)**:
  - Mendiagnosis akar masalah: Laravel's built-in `'array'` cast menggunakan `json_encode()` tanpa flag `JSON_UNESCAPED_UNICODE`, menghasilkan escape sequence `\uXXXX` (contoh: `\u2013` untuk en-dash `–`) yang ditolak oleh PostgreSQL yang berjalan dengan encoding `SQL_ASCII`.
  - Membuat custom Eloquent cast baru [`App\Casts\JsonUnescapedUnicode`](app/Casts/JsonUnescapedUnicode.php) yang meng-encode array ke JSON menggunakan `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`, menyimpan karakter Unicode secara literal (UTF-8) alih-alih escape sequence.
  - Membuat [`Modules\Cbt\Casts\JsonUnescapedUnicode`](Modules/Cbt/app/Casts/JsonUnescapedUnicode.php) sebagai thin subclass yang meng-extend cast app-level agar import lama di model Cbt tetap valid tanpa duplikasi logika.
  - Menerapkan cast baru pada seluruh 7 model yang menyimpan kolom JSON berisi konten teks pengguna:
    - `CbtQuestion` → `options`, `correct_answer`
    - `CbtStudentExam` → `question_order`, `options_order`
    - `CbtStudentAnswer` → `selected_answer`
    - `CbtProctorSchedule` → `present_students`
    - `AssignmentQuestion` → `options`, `keywords`
    - `LearningOutcomeCP` → `kata_kunci`
    - `AcademicYear` → `school_days`
- **Perbaikan IDE Errors & Warnings (5 file)**:
  - **`StudentSpEvaluationService`** (Error): Menambahkan `use Illuminate\Support\Facades\Auth` dan mengganti `auth()->id()` dengan `Auth::id()` — menghilangkan error *"Undefined method 'id'"* pada IDE.
  - **`2026_07_29_199900_create_discipline_levels_table` Migration** (Error): Menambahkan `use Illuminate\Support\Facades\DB` yang hilang — menghilangkan error *"Undefined type 'DB'"*.
  - **`StudentAssignmentController`** (Warning): Menambahkan PHPDoc `@var AssignmentSubmission` sebelum dua pemanggilan `->update()` yang diwarning sebagai *"Call to unknown method: stdClass::update()"*.
  - **`TeacherAssignmentController`** (Warning): Menambahkan `@var Schedule $schedule` di dalam closure `each()` — menghilangkan warning *"Call to unknown method: stdClass::setRelation()"*.
  - **`WordQuestionParser`** (Error + 9 Warnings):
    - Menambahkan `@var \DOMElement` PHPDoc di dalam setiap loop `foreach` yang mengiterasi `DOMNodeList` — menghilangkan 6 warning *"Call to unknown method: DOMNameSpaceNode|DOMNode::getAttribute/getAttributeNS/getElementsByTagName()"*.
    - Mengubah `$questionHtml = trim($questionHtml)` menjadi `$questionHtml = isset($questionHtml) ? trim($questionHtml) : trim($questionHtmlRaw)` — menghilangkan warning *"Possible undefined variable '\$questionHtml'"*.
    - Mengganti class CSS `border-1` dengan `border` pada HTML textarea yang di-generate — menghilangkan warning CSS *"The class 'border-1' can be written as 'border'"*.
    - Menambahkan `@var \Illuminate\Filesystem\FilesystemAdapter $disk` sebelum `$disk->url()` — menghilangkan error *"Undefined method 'url'"* (method ada di FilesystemAdapter, bukan di Filesystem contract).

## [2.8.4] - 2026-07-30 (Patch)
### Added
- **Panel Diagnostik Storage Cloud (MinIO / S3) pada Admin Backup**:
  - Menambahkan antarmuka diagnostik & tes kesehatan koneksi MinIO/S3 real-time pada halaman `/admin/backups`.
  - Memeriksa secara otomatis 7 konfigurasi `.env` (`FILESYSTEM_DISK`, `BACKUP_DISKS`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT`).
  - Menyediakan pengujian operasi file real-time (Write, Read, Delete test) dengan informasi waktu respon (ms) serta tombol uji ulang instan tanpa reload halaman.

### Changed
- **Integrasi Storage Cloud MinIO (S3) & Secure Filename Hashing pada Gambar Soal CBT**:
  - Memperbarui `WordQuestionParser` agar saat impor soal Word (`.docx`), seluruh berkas gambar pada soal otomatis diunggah langsung ke penyimpanan MinIO S3 jika `FILESYSTEM_DISK=s3` di-set di lingkungan server (staging/production).
  - Mengimplementasikan pembuatan nama berkas gambar ter-hash 32-karakter unik (`cbt_bank_{bankId}_{hash}.{ext}`) untuk menjamin keamanan dari penebakan URL gambar soal oleh siswa dan mencegah konflik nama berkas.
- **Dukungan Remote Storage pada Sistem Backup**:
  - Memperbarui `config/backup.php` agar lokasi penyimpanan backup Spatie dapat dikonfigurasikan secara dinamis via `.env` (`BACKUP_DISKS=s3`).
  - Menyesuaikan `BackupController.php` pada alur Restore & Upload-Restore agar kompatibel dengan remote storage tanpa tergantung pada local filesystem path.
- **Optimasi Workflow Deployment Staging (`deploy-staging.yml`)**:
  - Menggeser posisi eksekusi `php artisan octane:reload` ke tahap akhir setelah pembuatan cache konfigurasi & route agar worker Octane di memori server langsung booting dengan konfigurasi terbaru.

## [2.8.3] - 2026-07-30 (Patch)
### Added
- **Seeder Modul Kesiswaan**: Menambahkan `SpRuleSeeder` dan `EducationalSanctionSeeder` ke dalam pemanggil seeder utama (`KesiswaanDatabaseSeeder`) agar dapat otomatis berjalan ketika inisialisasi database modul.
- **Filter Tabel Sanksi Edukatif**: Menambahkan *dropdown filter* berbasis tingkat (Ringan, Sedang, Berat) pada halaman Master Sanksi Edukatif (`/educational-sanctions`).

### Changed
- **Optimalisasi Antarmuka Mobile Kesiswaan (UI/UX)**:
  - **Aturan SP (`sp-rules`)**: Meringkas ukuran *card* ambang batas SP dan mengubah layout teks agar lebih proporsional di layar HP.
  - **Sanksi Edukatif (`educational-sanctions`)**: Menyederhanakan baris tabel dengan menggabungkan kolom tingkat dan nama sanksi, serta membuat teks keterangan sanksi tersembunyi (bisa dilipat / *collapsible*).
  - **Pelanggaran Siswa (`student-violations`)**:
    - Memperbaiki *bug* dropdown jenis pelanggaran pada modal input yang sebelumnya terpotong (*out of bounds*) di HP, kini dapat menyesuaikan layar (*text-wrap*).
    - Menyederhanakan desain kolom data; kolom "Tanggal & Waktu" dan "Nama Siswa" kini tergabung menjadi satu blok interaktif "Siswa & Waktu".
    - Mengubah tampilan kolom foto bukti menjadi lebih ringkas berikon mata (👁️), dan jenis pelanggaran dibuat *collapsible* agar tabel tidak terlalu lebar.

## [2.8.2] - 2026-07-29 (Patch)
### Added
- **Modal Dialog Changelog Staging pada Halaman Login**:
  - Menampilkan tombol **`📋 Changelog`** dan link footer **`📋 Lihat Catatan Rilis & Changelog (Staging)`** pada halaman Login (`Login.vue`) yang hanya aktif ketika lingkungan server berada dalam mode pengujian / staging (`is_staging === true`).
  - Mengonversi isi `CHANGELOG.md` secara otomatis menjadi dialog modal interaktif tanpa perlu update manual hardcoded.

## [2.8.1] - 2026-07-29 (Patch)
### Changed
- **Integrasi Otomatis Pengaturan Sekolah & Guru pada Cetak SP**:
  - Draf Cetak Surat Peringatan (`sp.print.blade.php`) kini secara otomatis mengambil data **Nama Sekolah, Alamat, Telepon, Email, Nama Kepala Sekolah & NIP** dari Pengaturan Sistem Database (`Setting::get()`).
  - Mengambil secara otomatis **Nama Wali Kelas** (dari relasi kelas siswa) dan **Nama Guru BK** (dari relasi penanganan konseling BK).

## [2.8.0] - 2026-07-29 (Minor)
### Added
- **Fitur Draf Cetak Resmi Surat Peringatan (SP-1 / SP-2 / SP-3)**:
  - **Blade View `sp.print`**: Menyediakan template cetak resmi A4 lengkap dengan Kop Surat Sekolah, Nomor Surat Resmi, Identitas Siswa, Tabel Rincian Pelanggaran & Sanksi Edukatif, Kalimat Pernyataan Peringatan Keras, serta 4 Kolom Tanda Tangan Resmi (Orang Tua/Wali, Guru BK, Wali Kelas, dan Kepala Sekolah/Stempel).
  - **Fitur Tombol Cetak / PDF**: Menambahkan tombol `Cetak Draf Resmi SP` (ikon printer) pada halaman `/teacher/kesiswaan/discipline/student-sps` yang secara otomatis membuka dokumen pratinjau cetak di tab baru dengan dukungan window print & simpan PDF.

## [2.7.3] - 2026-07-29 (Patch)
### Changed
- **Penyederhanaan & Konsolidasi Navigasi Modul Kedisiplinan**:
  - Menghapus tab navigasi lama yang redundan (*"Penindakan / SP"* dan *"Usulan SP"*) pada `DisciplineNav.vue`.
  - Mengonsolidasikan alur kerja menjadi alur 1-pintu yang jelas: **Katalog Aturan** > **Catatan Pelanggaran** > **Master Sanksi** > **Surat Peringatan (SP)** > **Antrean BK** > **Aturan SP** > **Merit** > **Laporan**.

## [2.7.2] - 2026-07-29 (Patch)
### Changed
- **Pembaruan Modal Detail Siswa (Rincian Pelanggaran & Sanksi Edukatif)**:
  - Mengganti modal riwayat saldo/poin lama saat nama siswa diklik dengan **Modal Rincian Pelanggaran & Sanksi Edukatif Siswa**.
  - Menyajikan informasi komprehensif: Status SP Aktif (SP1/SP2/SP3/Bebas SP), Rekapitulasi Total Pelanggaran per Tingkat (Ringan, Sedang, Berat), serta Daftar Riwayat Pelanggaran & Sanksi Edukatif beserta status penyelesaiannya (Selesai / Belum Selesai).

## [2.7.1] - 2026-07-29 (Patch)
### Added
- **Master Sanksi Edukatif Terstandar & Pemilihan Dropdown**:
  - **Tabel Master `educational_sanctions`**: Membuat tabel penyimpanan data master sanksi edukatif terstandar beserta rekomendasi tingkat pelanggarannya (Ringan, Sedang, Berat).
  - **Halaman Manajemen Master Sanksi**: Menyediakan antarmuka CRUD khusus (`EducationalSanctions/Index.vue`) bagi Admin/Guru Kesiswaan untuk mengelola daftar sanksi edukatif.
  - **Pemilihan Dropdown di Form Pelanggaran**: Mengubah input sanksi edukatif pada form pencatatan pelanggaran siswa dari pengetikan teks bebas menjadi **Select Dropdown** wajib pilih agar data sanksi yang diberikan selalu seragam, konsisten, dan terstruktur.

## [2.6.4] - 2026-07-29 (Patch)
### Added
- **Deteksi & Pengelompokan Jawaban Duplikat (Indikasi Plagiasi/Menyontek)**:
  - **Analisis Duplikasi Real-time**: Pada saat guru membuka halaman submissions (`/teacher/assignments/{id}/submissions`), sistem akan secara otomatis memindai seluruh jawaban uraian (essay) dan tautan URL siswa untuk mendeteksi kesamaan 100%.
  - **ID Kelompok Dinamis**: Jawaban yang terdeteksi sama persis akan otomatis dikelompokkan dengan penomoran unik (Grup 1, Grup 2, dst) untuk melacak kelompok siswa mana yang memiliki jawaban identik.
  - **Lencana di Tabel Utama**: Menambahkan label merah "Indikasi Duplikat (Grup X)" pada kolom status pengerjaan di tabel depan agar guru tidak perlu membuka detail siswa satu per satu.
  - **Visual Peringatan Detail Jawaban**: Menyediakan sorotan warna merah tua kontras (`bg-red-900` dengan font putih terang `text-white`) serta tag "Indikasi Duplikat (Menyontek Versi X)" di dalam modal detail penilaian untuk memberikan warning visual yang jelas.

### Fixed
- **Perbaikan Namespace AttendanceReportExport**: Memindahkan file `AttendanceReportExport.php` dari `Modules/Kesiswaan/Exports/` ke `Modules/Kesiswaan/app/Exports/` untuk menyelaraskan dengan aturan PSR-4 Module Laravel sehingga menghindari error class not found pada pembuatan laporan absensi ekstrakulikuler.

## [2.6.3] - 2026-07-29 (Patch)
### Added
- **Dukungan Multi-Pembina Ekstrakulikuler**: Mengubah relasi guru pembina pada Modul Ekstrakulikuler dari tunggal (`teacher_id`) menjadi relasi many-to-many (`extracurricular_teachers`), sehingga satu kegiatan ekstrakulikuler dapat memiliki lebih dari satu Guru Pembina.
  - **Manajemen UI MultiSelect**: Form tambah dan edit kegiatan ekstrakulikuler kini menggunakan komponen `MultiSelect` dari PrimeVue dengan tampilan *Chip* agar Admin dapat menunjuk beberapa guru pembina sekaligus.
  - **Import & Export Excel Multi-NIP**: Memperbarui template dan mesin pembaca Excel impor ekstrakulikuler agar mendukung penulisan beberapa NIP guru pembina sekaligus yang dipisahkan dengan tanda koma (Contoh: `19800101, 19800202`).
- **Akses Cepat Dashboard Guru Pembina**: Menambahkan tombol/ikon **Ekstrakulikuler** pada menu Aksi Cepat di Dashboard Guru secara dinamis untuk guru yang ditugaskan sebagai pembina pada tahun ajaran aktif.
- **Tampilan Kelas Siswa pada Manajemen Anggota Ekskul**: Menampilkan informasi **Kelas Siswa** baik pada dropdown pemilihan anggota maupun pada tabel daftar anggota aktif di modul admin dan guru.

### Changed
- **Penyempurnaan UI Presensi & Daftar Ekstrakulikuler Siswa**:
  - Mengatur ulang urutan komponen pada halaman Ekstrakulikuler Siswa menjadi: **Presensi Ekstrakulikuler** -> **Riwayat Presensi Saya** -> **Ekstrakulikuler Saya** -> **Daftar Ekstrakulikuler Tersedia**.
  - **1 Blok Penuh Collapsible & Auto-Collapse**: Blok "Presensi Ekstrakulikuler" dan "Daftar Ekstrakulikuler Tersedia" kini mendukung fitur lipat 1 blok penuh (*full-block collapsible*). Blok presensi otomatis melipat menjadi satu baris hijau ringkas apabila siswa terdeteksi sudah melakukan presensi pada hari tersebut atau tepat setelah berhasil menginput token.
  - Mengganti teks placeholder pada input token menjadi **"KODE HADIR"** dan mengganti pesan toast kesalahan presensi dengan **Modal Peringatan Dialog** dari PrimeVue.

### Fixed
- **Perbaikan SQL Error `teacher_id` Undefined**: Mengganti seluruh query penarikan data kegiatan ekstrakulikuler pada controller guru dan siswa agar menggunakan relasi `teachers()` pivot table.
- **Perbaikan Route Name Redirect & Peringatan Komponen Vue**: Memperbaiki nama route redirect pada `ExtracurricularCoachController` menjadi `guru.kesiswaan.extracurriculars.sessions` serta menambahkan impor komponen `Checkbox` pada `StudentIndex.vue` dan `Index.vue` untuk mengatasi *Vue warning*.

## [2.6.2] - 2026-07-28 (Patch)
### Fixed
- **Perbaikan Editor Tiptap**: Mengonfigurasi modul inti `StarterKit` untuk menonaktifkan *extension* `link` dan `underline` bawaan, guna menyelesaikan konflik nama ekstensi duplikat yang sebelumnya muncul di konsol saat membuat soal penugasan.
- **Kunci Akses Edit Jawaban Siswa**: Memperbaiki anomali pada form edit penugasan siswa, dengan memaksakan pembaruan kolom `is_editable` menjadi `false` sesaat setelah skor tersimpan, sehingga akses edit otomatis terkunci kembali segera setelah siswa menekan tombol 'Update & Kumpulkan Kembali'.

### Changed
- **Penyempurnaan Tampilan Daftar Tugas**: Merombak dan memaksimalkan antarmuka kartu penugasan menjadi super ringkas (*compact* dan *collapsible*) di daftar tugas Siswa maupun Guru (`Penugasan/Siswa/Index.vue` & `Penugasan/Guru/Index.vue`). Fitur ini secara dramatis menghemat ruang layar pada perangkat seluler, sehingga pengguna dapat melihat jumlah tugas yang jauh lebih banyak dalam satu kali gulir layar.

## [2.6.1] - 2026-07-28 (Patch)
### Fixed
- **Perbaikan IDE Warnings & Type Hints**: Menambahkan *type hints* secara eksplisit pada parameter *method* di `AttendanceReportService` dan `KepsekDashboardController` untuk mengatasi peringatan "*no type information available*".
- **Refaktorisasi Dasbor Guru**: Mengekstrak logika *query* laporan sarpras dan kalkulasi presensi wali kelas ke dalam *private helper methods* pada `GuruDashboardController` guna mengatasi batasan inferensi tipe kompleks pada IDE (peringatan "*utilizes too many types*") serta meminimalisir kesalahan pelaporan statis.

## [2.6.0] - 2026-07-28 (Minor)
### Added
- **Modul Ekstrakulikuler Terintegrasi (`Modules/Kesiswaan`)**: Membangun modul manajemen ekstrakurikuler lengkap yang mendukung pengelolaan data per tahun ajaran (`academic_year_id`).
  - **Manajemen Admin**: CRUD kegiatan ekstrakulikuler, penunjukan Guru Pembina, fitur **"Salin Tahun Ajaran"** untuk menyalin daftar ekstra dari tahun ajaran sebelumnya, serta **Import Excel** daftar kegiatan (otomatis memetakan NIP/Nama Guru Pembina) dan anggota siswa berdasarkan NIS/NISN.
  - **Dashboard & Presensi Guru Pembina**: Guru Pembina dapat mengelola presensi siswa berbasis **Token Waktu Terbatas (6 karakter alfabet-angka huruf besar, contoh: `EK829X`)** dengan opsi perpanjangan waktu (+15 menit). Dilengkapi pemantauan real-time **Daftar Hadir vs. Daftar Tidak Hadir** serta kemampuan merubah status kehadiran secara manual (`Hadir`, `Izin`, `Sakit`, `Alpha`).
  - **Pendaftaran Mandiri & Input Token Siswa**: Siswa dapat memilih dan mendaftar secara mandiri pada kegiatan ekstrakulikuler yang dibuka, melakukan presensi cepat dengan memasukkan token, serta memantau riwayat kehadiran ekstra.
  - **Peringatan Wajib Ekstrakulikuler untuk Siswa Kelas X & XI**: Menambahkan konfigurasi kewajiban ekstrakulikuler (`extracurricular_mandatory_grades`, default: `X,XI`) dan toggle notifikasi (`extracurricular_warning_modal_enabled`). Siswa Kelas X dan XI yang belum bergabung di minimal 1 kegiatan akan otomatis menerima notifikasi modal peringatan saat membuka portal SMA, dilengkapi tombol pintas untuk memilih kegiatan.

## [2.5.6] - 2026-07-28 (Patch)
### Added
- **Safety Dump & Rollback Otomatis Restore Database**: Meningkatkan keamanan pemulihan database di `BackupController`. Sebelum database dikosongkan (`db:wipe`), sistem kini secara otomatis membuat cadangan sementara (*Safety Dump*). Jika proses eksekusi restore via `psql` gagal di tengah jalan, sistem akan melakukan **Rollback Otomatis** dari *Safety Dump* tersebut sehingga database tidak akan berakhir dalam kondisi kosong atau rusak.
- **Sanitasi Path Traversal & Command Injection pada Backup**: Memperketat keamanan file backup dengan fungsi `basename()` dan sanitasi argumen shell (`escapeshellarg` / `escapeshellcmd`) pada seluruh eksekusi `pg_dump` dan `psql` di `BackupController`.
- **Tantangan Kata Kunci (Keyword Challenge) pada Force Restore Database**: Menambahkan input validasi konfirmasi pengaman dengan kata kunci tantangan (`FORCE_RESTORE`) pada modal dan proses pemulihan (*restore*) di `BackupController` & `Admin/Backup/Index.vue`. Admin dapat melewati pengecekan perbedaan versi migrasi database (*manifest*) hanya dengan mengonfirmasi kata kunci tersebut.
- **Proteksi Akses URL Penugasan Siswa**: Memvalidasi akses tugas secara ketat di `StudentAssignmentController` (`show` & `submit`). Sistem mencegah siswa berpindah atau mengakses tugas dari kelas lain, kelompok agama lain, atau tugas yang masih terkunci prasyarat hanya dengan mengganti ID tugas di URL browser.

### Changed
- **Tampilan Daftar Tugas Siswa yang Ringkas (Collapsible Card)**: Meringkas tampilan kartu penugasan di laman daftar tugas siswa (`Penugasan/Siswa/Index.vue`). Deskripsi tugas yang panjang secara default disembunyikan dalam 1 baris cuplikan agar daftar tugas tidak memenuhi layar, dilengkapi dengan tombol interaktif **"Lihat Petunjuk & Detail Tugas"** untuk membuka/menutup petunjuk lengkap dengan transisi halus.

### Fixed
- **Perbaikan Error Restore PostgreSQL (`SET ROLE "postgres"` / `OWNER TO`) di Server Staging/Production**: Menambahkan metode `sanitizeSqlDumpFile` pada `BackupController` dan konfigurasi `--no-owner --no-privileges` pada `pg_dump`. File SQL dump dari backup lama maupun baru kini otomatis dibersihkan dari perintah kepemilikan (`OWNER TO`) dan perubahan peran (`SET ROLE` / `GRANT`), sehingga proses restore tidak akan pernah gagal meskipun dijalankan oleh akun database non-superuser.
- **Perbaikan Izin Edit Tugas Pasca-Deadline**: Memperbaiki logika penguncian waktu dan status `is_editable` pada `StudentAssignmentController@submit` dan `Penugasan/Siswa/Show.vue`. Siswa yang telah dibukakan akses edit oleh guru (`is_editable = true`) kini diizinkan mengumpulkan kembali perbaikan jawaban tugas mereka meskipun melewati batas waktu pengumpulan (`due_at`). Dilengkapi banner notifikasi kuning/amber **"Akses Edit Jawaban Telah Dibuka Oleh Guru"** yang informatif.
- **Perbaikan Error 500 (`MissingAppKeyException`) di Production**: Mengatasi kesalahan `MissingAppKeyException` pada saat login admin di lingkungan production dengan pembaruan dan penguncian cache konfigurasi (`php artisan config:cache`).

## [2.5.5] - 2026-07-27 (Patch)
### Added
- **Tombol & Halaman Absen Mapel Kepsek**: Menambahkan tombol dan halaman khusus `Daftar Siswa Absen Mapel` di Dashboard Kepala Sekolah (`/alfa-students`) untuk memantau siswa yang alpa pada mata pelajaran tertentu di tanggal terpilih.
- **Kartu TH (Tidak Hadir Mapel) Dashboard Wali Kelas**: Menambahkan kartu **TH (TDK HADIR)** pada blok Rekap Kelas di Dashboard Guru/Wali Kelas (`DashboardGuru.vue`) yang menghitung jumlah siswa yang tidak hadir pada jam pelajaran (Alpa Mapel). Dilengkapi modal interaktif yang menampilkan nama siswa, NIS, mata pelajaran yang dibolos, jam ke berapa, dan nama guru pengampu.
- **Kartu Presensi Lengkap di Rekap Kelas**: Menambahkan kartu status `D` (Dispensasi), `TCO` (Tidak Check-Out), `TCI` (Tidak Check-In), dan `FM` (Force Majeure) pada dasbor Wali Kelas sehingga mencakup seluruh status presensi harian secara akurat tanpa ada siswa yang terlewat.
- **Freeze/Sticky Header Tabel Laporan Presensi**: Menambahkan atribut `scrollable scrollHeight="600px"` pada komponen `DataTable` di seluruh tab Laporan Presensi Kesiswaan (`/teacher/kesiswaan/reports/attendance`). Mengunci baris judul tabel (*header*) agar tetap di posisi atas (*sticky*) saat data siswa digulir (*scroll*).

### Fixed
- **Pencatatan Log Backup, Restore & Impersonate**: Mengintegrasikan `ActivityLogger` pada `BackupController` (aktivitas pembuat, penghapus, dan pemulihan *backup*) serta `ImpersonateController` untuk memastikan semua tindakan sensitif tercatat di *audit log*.
- **Modal Guru Dashboard Admin**: Memperbaiki nama kolom modal guru pada dashboard admin (`Dashboard.vue`) agar menampilkan data `kelas_jam` dengan benar.
- **Penanganan Prop Warning Vue DataTable**: Memperbaiki peringatan `Invalid prop type check failed for prop "value"` pada `Kesiswaan/Reports/Attendance/Index.vue` dengan menggunakan computed `listReportData` sehingga `DataTable` di tab non-aktif tidak lagi menerima *Object* sebagai data tabel.

## [2.5.4] - 2026-07-27 (Patch)
### Added
- **Pengecekan Versi (Manifest) pada Backup & Restore**: Mengamankan sistem pencadangan (*backup*) dengan menyuntikkan file `manifest.json` ke dalam arsip ZIP untuk mencatat versi migrasi *database* (dari `migrations`) dan versi aplikasi. Saat *restore* dilakukan, sistem kini akan memblokir (*abort*) proses jika *manifest* tidak ditemukan atau versi *database* tidak cocok, guna mencegah *crash* atau *error* fatal akibat perbedaan struktur data.
- **Kewajiban Password Backup**: Proses pembuatan *backup* kini mewajibkan adanya konfigurasi sandi/kunci di file `.env` (`BACKUP_ARCHIVE_PASSWORD`), melindungi file arsip ZIP dari pembobolan. File `.env.example` telah diperbarui dengan variabel tersebut.
- **Tombol Cetak PDF di Tampilan Mobile**: Menambahkan tombol akses cepat "Cetak Jurnal" (Ikon Printer) secara langsung di antarmuka laporan Jurnal Kelas dan Jurnal Mengajar Pribadi pada mode Mobile Guru.

### Changed
- **Penyatuan UI (Mobile-First) Laporan Guru**: Mengganti sistem tampilan terpisah (Desktop/Mobile) untuk halaman Laporan Jurnal Mengajar dan Jurnal Kelas (Guru). Kini, baik diakses lewat HP maupun PC, sistem konsisten akan menampilkan tata letak UI *Mobile-First* yang lebih ringkas dan interaktif. (Modifikasi pada `AgendaReportController.php` & refaktorisasi `Mobile/Classroom.vue` serta `Mobile/Personal.vue`).

### Fixed
- **Proteksi Error Impor psql (Restore)**: Mengatasi permasalahan sistem yang diam-diam mengabaikan *error* saat memulihkan database dari file `.sql`. Perintah `psql` di `BackupController` kini dilengkapi bendera `-v ON_ERROR_STOP=1`, yang secara instan akan menghentikan seluruh operasi jika terjadi kesalahan (misal tabel gagal terbuat), sehingga database tidak akan berada dalam status "setengah jadi" (parsial).

## [2.5.3] - 2026-07-27 (Patch)
### Fixed (Patch)
- **Patch PostgreSQL Check Constraint Presensi (`attendances_status_check`)**: Memperbaiki error `SQLSTATE[23514]: Check violation: 7` saat memperbarui atau membuat presensi siswa dengan status `TCI` (*Tidak Presensi Masuk*), `TCO`, `PA`, `T-PA`, `FM`, dan `D`. Menambahkan migrasi `2026_07_27_160000_fix_attendances_status_check_constraint` yang menghapus *constraint* lama yang kaku dan memperluas daftar status valid pada tabel `attendances` (total 17 status).
- **Patch Optimasi Build Vite & Code Splitting**: Mengatasi peringatan *chunk size limit* (>500 kB) pada saat eksekusi `npm run build` dengan menerapkan konfigurasi `manualChunks` di `vite.config.js`. Pustaka eksternal kini dipisah secara otomatis ke dalam `vendor-primevue.js` dan `vendor-vue.js` guna mempercepat caching browser.

## [2.5.2] - 2026-07-27
### Added
- **Sistem Log Aktivitas & Audit Trail**: Menambahkan fitur rekam jejak aktivitas pengguna (*Audit Trail*) secara lengkap dan *append-only* (tabel `activity_logs`). Mencatat otomatis setiap keberhasilan/kegagalan login, logout, dan modifikasi data krusial beserta perbandingan data sebelum dan sesudah (`old_values` vs `new_values`). Dilengkapi halaman dashboard admin `/admin/activity-logs` dengan fitur pencarian dan filter interaktif.
- **Pengaturan Status Aktif / Maintenance Mode Situs**: Menambahkan tab baru "Status & Akses Situs" pada Pengaturan Admin (`/admin/settings`). Admin dapat menonaktifkan situs menggunakan Toggle Switch, yang secara otomatis memblokir login/akses pengguna non-admin dan menampilkan banner peringatan pemeliharaan pada halaman login.
- **Deteksi Server Staging & Tautan ke Production**: Menambahkan pendeteksi otomatis lingkungan server uji coba (*staging*). Pada halaman `/admin/settings` dan `/login` di server staging, sistem kini menampilkan banner informasi dengan tombol/link langsung untuk membuka server utama (*Production* - `portal.sman16smg.sch.id`).
- **Resolusi Otomatis Tugas Prasyarat Multi-Kelas**: Mengimplementasikan algoritma pintar (`resolvePrerequisiteForClassroom`) pada pembuatan dan pembaruan tugas massal. Ketika guru memilih satu prasyarat dari kelas tertentu (misal Tugas 1 X-1) namun membuat tugas lanjutan untuk banyak kelas sekaligus (X-1, X-2, X-3), sistem otomatis mendeteksi dan mengaitkan prasyarat yang sepadan untuk masing-masing kelas target secara tepat.
- **Model Penilaian Kinerja Ceklist**: Guru kini dapat menambahkan jenis indikator soal "Ceklist" untuk tipe penugasan *Penilaian Kinerja / Praktik*. Fitur ini mempermudah input skor dengan menggunakan komponen Toggle Switch (Tercapai / Belum) saat merekap nilai.
- **Peringatan Kesalahan Validasi (UI Form)**: Menambahkan ringkasan *error* berbasis UI pada form pembuatan tugas untuk menginformasikan dengan jelas ketika ada validasi di sisi server yang gagal.

### Fixed
- **Desain Tabel Rekap Penilaian Kinerja**: Memperbaiki fungsi scroll (*overflow*) pada tabel Penilaian Kinerja (`/teacher/assignments/{id}/performance-grading`). Judul kolom dan baris pertama (Nama Siswa) kini "berhenti" (*sticky*) saat layar digulir, memudahkan guru untuk melihat nama siswa dan bobot kriteria.
- **Tampilan Peringatan Nilai Belum Publikasi**: Komponen *card* peringatan "Nilai Belum Dipublikasi" kini dapat ditutup/disembunyikan (collapsible) dengan tombol silang, memperluas area fokus pada tabel penilaian.
- **Visibilitas Teks Jawaban Siswa**: Memperbaiki bug desain UI pada laman penugasan di mana fon / tulisan teks jawaban siswa berwarna putih di atas warna *background* yang juga cerah.
- **Dependensi dan Build NPM**: Memperbaiki permasalahan pengunduhan dependensi PrimeVue (`ETARGET` error) dengan mengembalikan pustaka *themes* yang stabil dan menyelesaikan bug eksekusi `npm ci`.

## [2.5.0] - 2026-07-26
### Added
- **Integrasi Laravel Octane (FrankenPHP)**: Mengimplementasikan Laravel Octane dengan *server* FrankenPHP sebagai mesin pendongkrak performa (*high-performance*) khusus untuk rute pengerjaan ujian CBT Siswa (`/student/cbt`) dan status Octane (`/octane-status`). Membantu menangani lonjakan beban tinggi pada saat ujian berlangsung tanpa mengganggu server utama.
- **Rute Pengecekan Status Octane**: Menambahkan endpoint khusus `/octane-status` pada Nginx untuk memeriksa keberadaan/status berjalannya layanan FrankenPHP secara *real-time*.
- **Integrasi Laravel Pulse**: Mengintegrasikan `laravel/pulse` sebagai dasbor analitik pemantauan server visual (*monitoring*) untuk mengamati beban CPU, sisa RAM, kueri terlambat, dan status kapasitas antrean *worker* secara langsung dari dalam aplikasi.
- **Autorisasi Dasbor Pulse**: Menambahkan perlindungan Gate `viewPulse` pada `AppServiceProvider` yang membatasi akses URL dasbor pemantauan Pulse (`/pulse`) agar hanya bisa dibuka oleh pengguna dengan peran (*role*) `admin`.

## [2.4.1] - 2026-07-26
### Changed
- **Pembaruan Aturan Token Ujian Mandiri**: Siswa kini dapat masuk ke sesi "Ujian Mandiri" (Proktoring Cepat) secara langsung tanpa perlu memasukkan token pada percobaan pertama. Namun, jika siswa terdeteksi melakukan pelanggaran fokus 3 kali dan dikeluarkan (status `logged_out`), fitur masuk otomatis akan dinonaktifkan, sehingga siswa **wajib meminta token pengawas/guru** untuk dapat melanjutkan ujiannya.

### Fixed
- **Desain Layar Peringatan Ujian (Anti-Cheat)**: Memperbaiki tata letak (layout) dan warna pada kotak peringatan "Fokus Terputus" atau teguran *Anti-Cheat* pada halaman ujian siswa yang sebelumnya terpotong (clipped) pada perangkat berspesifikasi layar kecil. Memperbaiki tampilan warna teks pada peringatan menggunakan _inline styles_ yang pasti ter-_render_ untuk mencegah tulisan berwarna hitam menyatu dengan _background_ gelap.
- **Bug Fullscreen API (Permissions Check Failed)**: Menghapus eksekusi otomatis fungsi `requestFullscreen()` pasca-hukuman yang sering kali memicu pesan kesalahan (error) `Failed to execute 'requestFullscreen' on 'Element': API can only be initiated by a user gesture.`. Kini, siswa harus mengeklik tombol pengaktifan layar penuh secara interaktif demi keamanan.
- **Waktu GMT pada Jadwal Ujian**: Memperbaiki masalah perbedaan pembacaan zona waktu (Local Time vs GMT) yang menyebabkan jadwal ujian sering bergeser jika dilihat pada tabel manajemen ujian Proktor (`/cbt/exams`).

## [2.4.0] - 2026-07-26
### Added
- **Fitur Tugas Prasyarat (Assignment Prerequisites)**: Guru dapat menetapkan tugas prasyarat saat membuat/mengedit tugas (`/teacher/assignments/create` & `/edit`). Tugas yang mensyaratkan tugas lain akan terkunci secara otomatis bagi siswa sampai tugas prasyarat diselesaikan.
- **Tampilan Tugas Terkunci Siswa**: Menambahkan indikator visual lencana *Terkunci*, tombol disabled *Terkunci*, dan info tugas prasyarat pada halaman `/student/assignments`. Backend juga memproteksi akses langsung dengan alur pengalihan aman.
- **Tombol Navigasi 'Daftar Nilai'**: Menambahkan tombol akses cepat *Daftar Nilai* (`pi-table`) pada header bar halaman penugasan guru (`/teacher/assignments`).

### Changed
- **Modus Lihat Saja (Read-Only) Tugas Kinerja Siswa**: Tugas tipe *Penilaian Kinerja / Praktik* (`type = performance`) di halaman siswa (`/student/assignments/{id}`) kini hanya menampilkan petunjuk dan indikator penilaian secara read-only tanpa input pengumpulan, serta siap menampilkan nilai & catatan evaluasi setelah guru mempublikasikan nilai.
- **Tampilan Label Mapel Agama di Penilaian**: Nama mata pelajaran agama pada dropdown & header di halaman `/penilaian/grades` dan `/penilaian/grades/recap` otomatis menyertakan label agama pengampunya (contoh: "Pendidikan Agama (Agama Katolik)").

### Fixed
- **Hitungan & Filter Tab Tugas Siswa**: Memperbaiki kalkulasi tab *Belum Dikerjakan*, *Terkunci*, dan *Sudah Dikumpulkan* pada daftar tugas siswa yang sebelumnya keliru karena pengurangan manual `(assignments.length - pendingCount)`.
- **Tugas Agama Tercampur pada Rekap Nilai**: Memperbaiki query `GradingComponent` di `getRecapData()` agar menyaring komponen penilaian berdasarkan `teacher_id` pengampu jadwal kelas sehingga tugas agama Katolik/Islam tidak lagi bercampur di tabel rekap.
- **Deduplikasi Notifikasi Toast**: Menambahkan proteksi pencatatan riwayat pesan flash (`lastSuccessFlash` & `lastErrorFlash`) di `AppLayout.vue` dan menghapus panggilan manual `toast.add()` berlebih pada `PerformanceGrading.vue` sehingga notifikasi toast hanya muncul 1x secara bersih.

## [2.3.1] - 2026-07-25
### Changed
- **Standardisasi Kode Status Presensi**: Menyelaraskan kode status presensi harian siswa antara database (`AttendanceService`) dan frontend laporan Vue (`T-PA`, `TCI`, `TCO`).
- **Pembersihan Legenda Presensi KBM**: Menghapus legenda status Sakit/Izin/Dispen/Alpa pada modal presensi agenda KBM guru (hanya menyisakan Hadir / Tidak Hadir) karena presensi KBM hanya mencatat presensi aktual di kelas fisik, sedangkan status izin dikelola di modul izin.

### Fixed
- **Admin Presensi 403 Forbidden**: Memperbaiki masalah Error 403 pada saat Admin melakukan filter data di halaman `/admin/kesiswaan/attendance` (sebelumnya filter ter-hardcode melempar request ke route guru).

## [2.3.0] - 2026-07-25
### Added
- **Fitur Penilaian Kinerja / Praktik Guru**: Modul penugasan kini mendukung tipe *Penilaian Kinerja*. Guru dapat membuat indikator penilaian dengan skor maksimal per indikator, merekap nilai siswa secara interaktif menggunakan perpaduan **Slider + Direct Input Number**, dan mempublikasikan/menarik publikasi nilai secara langsung dari halaman rekap kinerja.
- **Tampilan Bobot & Poin Skor Per Soal Siswa**: Menambahkan lencana (*badge/tag*) *Bobot Max Poin* dan *Nilai Skor Diperoleh* pada halaman pengerjaan & lembar jawaban siswa (`/student/assignments/{id}`).

### Changed
- **Penyaringan Otomatis Mapel & Siswa Berdasarkan Agama Guru**: Penyesuaian cerdas untuk guru mata pelajaran agama (Islam, Katolik, Protestan, dll). Nama agama otomatis disematkan pada label mapel (contoh: "Pendidikan Agama (Islam)"), dan daftar murid pada halaman rekap kinerja/submisi otomatis tersaring sesuai agama dari guru yang bersangkutan.

### Fixed
- **Perbaikan Duplikasi Nama Agama pada Label Mapel**: Menggunakan kloning objek model `Subject` di memori dan proteksi `!str_contains` untuk mencegah string nama agama berulang (contoh sebelumnya: "Pendidikan Agama (Islam) (Islam)").
- **Proteksi Histori Agenda Mengajar saat Reset Jadwal**: Penyempurnaan fitur Reset Jadwal Guru (`/admin/monitoring/schedule-teacher`) dengan pelepasan `schedule_detail_id` otomatis sebelum menghapus slot jam, serta pengubahan kueri ke `LEFT JOIN` pada Laporan Agenda Mengajar (`/teacher/reports/personal`) sehingga 100% histori jurnal mengajar (materi & absensi) tetap aman dan tampil utuh.
- **Check Constraint Migration Type**: Memperbarui migrasi kolom `type` pada tabel `assignment_questions` untuk secara otomatis menghapus *Check Constraint* bawaan PostgreSQL (`assignment_questions_type_check`) sehingga mendukung tipe soal `performance_indicator`.

## [2.2.0] - 2026-07-24
### Added
- **Rich Text Editor Soal Penugasan**: Mengintegrasikan Tiptap (mirip Microsoft Word) pada form pembuatan soal. Guru kini dapat memformat teks (tebal, miring, warna), menyisipkan tabel, list, dan **mengunggah gambar soal**.
- **Upload Gambar Soal Terenkripsi**: Mengimplementasikan server-side upload untuk gambar soal dengan enkripsi nama file (SHA-256 Hash + UUID) agar gambar dijamin aman dari override dan database tidak bengkak karena Base64.
- **Tampilan HTML Soal**: Soal di halaman pengerjaan siswa dan di modal penilaian guru kini merender formatting HTML dari Rich Text secara rapi.

### Changed
- **Filter Dropdown Dinamis Penilaian**: Dropdown *Mata Pelajaran* dan *Kelas* di halaman `/penilaian/grades` kini saling memfilter (dependent dropdown) berdasarkan jadwal aktual, sehingga mencegah kesalahan input data oleh guru.
- **Nama Menu Dashboard**: Label "Tugas (PR)" di card dashboard siswa (versi web & mobile) diubah menjadi "Tugas Siswa" untuk lebih representatif.

## [2.1.0] - 2026-07-23
### Added
- **Proteksi Anti-Crawl (SEO/Privacy)**: Menambahkan konfigurasi `public/robots.txt` (`Disallow: /`) dan `<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">` pada `app.blade.php` untuk mencegah robot/crawler mesin pencari (Google, Bing, ChatGPT Bot, dll) mengindeks halaman internal portal sekolah.
- **Sistem Poin Tata Tertib & Merit Siswa**:
  - **Sistem Poin Akumulatif**: Saldo poin siswa awal (default 200 poin) yang berlaku seumur aktif di sekolah tanpa reset tahunan.
  - **Pemotong Poin Pelanggaran & Rekomendasi SP Otomatis**: Setiap pelanggaran memotong poin siswa berdasarkan `penalty_points` master aturan. Usulan SP (SP1 <125 poin, SP2 <80 poin, SP3 <20 poin, Pengembalian <=0 poin) terhitung otomatis secara presisi dari saldo poin terkini.
  - **Sanksi Edukatif & Pemulihan Poin**: Fitur penyelesaian Sanksi Edukatif pada tabel Catatan Pelanggaran yang mengembalikan/merestore sebagian/seluruh poin yang terpotong hingga batas maksimal (200 poin).
  - **Modul Merit Siswa (`/discipline/merits`)**: Sistem pencatatan prestasi dan poin bonus untuk siswa berprestasi dengan kategori merit dinamis dan unggah berkas sertifikat/bukti.
  - **Akses Poin Mandiri Siswa (`Poin Saya`)**: Halaman khusus siswa (`/student/poin-tata-tertib`) untuk melihat saldo poin utama, statistik mutasi, petunjuk ambang batas, dan log riwayat pergerakan poin secara transparan.
  - **Modal Detail Saldo & Riwayat Poin Siswa**: Klik pada nama siswa di tabel Catatan Pelanggaran untuk membuka modal *Pop-up* rincian saldo dan log mutasi poin *real-time*.
  - **Halaman Pengaturan Poin (`/discipline/settings`)**: Konfigurasi parameter poin awal, threshold SP1/SP2/SP3, dan batas maksimal recovery poin dari UI Admin.

### Changed
- **Visual & UI Modul Merit Siswa**: Redesign penuh komponen `Kesiswaan/Discipline/Merits/Index.vue` dan `Settings/Index.vue` agar konsisten dengan standar UI/UX modul Kesiswaan (PrimeVue `IconField`, `Calendar`, `Tag` emas, dan layout `surface-card`).
- **Nomor Surat & Unggah Berkas SP**: Form penerbitan SP pada modul rekomendasi kini mendukung input nomor surat opsional dan upload berkas PDF/foto SP.

## [2.0.0] - 2026-07-22
### Added
- **Modul Tata Tertib Siswa (Discipline Module)**: Implementasi penuh modul pengelolaan tata tertib dan pelanggaran siswa:
  - **Katalog Aturan**: Master data aturan tata tertib berdasarkan pasal (Ringan/Pasal 11, Sedang/Pasal 12, Berat/Pasal 13) dengan poin pelanggaran, rekomendasi sanksi edukatif, dan fitur pencarian/filter level.
  - **Catatan Pelanggaran Siswa**: Pencatatan pelanggaran per siswa dilengkapi upload bukti foto (opsional), deteksi koordinat GPS otomatis via HTML5 Geolocation API, link Google Maps langsung dari tabel, dan lightbox preview foto bukti.
  - **Penindakan / Surat Peringatan (SP)**: Pengelolaan SP1, SP2, SP3, Pemanggilan Orang Tua, dan Sanksi Edukatif dengan filter tahun ajaran dan kelas.
  - **Laporan & Statistika**: Rekapitulasi pelanggaran tahunan per kategori, grafik penindakan, dan tabel top pelanggar.
- **Navigasi Chain Tata Tertib**: Komponen `DisciplineNav.vue` yang muncul di bagian atas semua halaman modul tata tertib sebagai tab navigasi horizontal (Katalog → Catatan → Penindakan/SP → Laporan) dengan indikator tab aktif.
- **Kontrol Akses Modul (`/admin/access-control`)**: Permission `manage-discipline` terdaftar dan diintegrasikan ke sistem akses. Role `admin`, `guru bk`, dan `kepala sekolah` otomatis mendapat akses penuh; role `guru` hanya dapat akses jika ditambahkan secara manual.
- **Migration GPS Pelanggaran**: Menambahkan kolom `latitude` & `longitude` pada tabel `student_violations` untuk penyimpanan koordinat lokasi kejadian.

### Changed
- **Menu Sidebar Guru Dinamis**: `NewMenuService.php` diperluas menggunakan mapping `permission → url_pattern`. Menu tata tertib, perizinan, terlambat, dan kelola sarpras kini hanya tampil di sidebar jika guru memiliki permission yang sesuai. Role `admin`, `guru bk`, `kepala sekolah`, dan `staff` bypass semua filter (selalu melihat semua menu).
- **Aksi Cepat Dashboard Guru Dinamis**: Bagian "Aksi Cepat" di `DashboardGuru.vue` (mobile & desktop) kini dinamis berdasarkan permission guru yang login. Tombol baru ditambahkan untuk modul Tata Tertib, Perizinan, dan Terlambat yang hanya muncul sesuai hak akses masing-masing guru.
- **Tampilan Tombol Navigasi Tabel (`&laquo;` / `&raquo;`)**: Memperbaiki rendering entitas HTML mentah pada tombol navigasi pagination di semua tabel dengan menggunakan `<span v-html="link.label">` alih-alih binding string `:label`.

### Fixed
- **Error 403 Modul Tata Tertib Guru**: Middleware route group `teacher/kesiswaan` diperluas dengan permission `manage-discipline` agar guru yang sudah diberikan akses dapat membuka semua halaman tata tertib.
- **Ziggy Route Error**: Menambahkan `['as' => 'discipline']` pada resource route deklarasi di `web.php` sehingga Ziggy membuat nama rute `guru.kesiswaan.discipline.violations.index` secara konsisten.
- **Endpoint `/api/search-students` 404**: Membuat endpoint JSON khusus `discipline/search-students` yang terdaftar pada route group kesiswaan untuk pencarian siswa saat pencatatan pelanggaran.

## [1.9.9] - 2026-07-22
### Added
- **Rekap Presensi Harian Per Kelas (Matriks Evaluasi Otomatis)**: Implementasi kalkulasi matriks evaluasi status presensi otomatis harian per kelas berdasarkan perbandingan jam scan `clock_in` & `clock_out` terhadap toleransi jam sekolah. Menghasilkan kode status `H`, `T`, `PA`, `T-PA`, `TCI`, `TCO`, `A`, `S`, `I`, `D`, dan `FM` lengkap dengan widget ringkasan statistik dan tag warna visual.
- **Rekap Presensi Tahunan (Sekolah Bulanan)**: Memperbarui tampilan tab Tahunan di `Kesiswaan/Reports/Attendance/Index.vue` agar menampilkan tabel Kumulatif Rekapitulasi Sekolah Bulanan (12 Bulan: Juli–Juni) untuk Tahun Pelajaran terpilih, lengkap dengan kolom `Hari Efektif`, `Rata-Rata Kehadiran Sekolah (%)`, `Total Kasus Absensi Berat (>10% Alpa)`, dan baris footer ringkasan kumulatif.
- **Manajemen Jenis Layanan BK Dinamis**: Menambahkan dukungan penambahan jenis layanan konseling BK secara dinamis dari frontend (`+ Tambah Jenis`) beserta atribut `description` dan `sort_order`.

### Changed
- **Banner Impersonation Global**: Menampilkan banner merah **Mode Impersonation** secara konsisten di seluruh layout (`AppLayout.vue`, `SiswaLayout.vue`, `MobileLayout.vue`) baik di desktop maupun mobile.
- **Akses Rute Laporan Presensi**: Membuka akses rute `reports/attendance` untuk role **Admin**, **Guru**, **Guru BK**, dan **Kepala Sekolah**.

### Fixed
- **Error 419 Sesi Impersonate**: Memindahkan rute `users.impersonate.leave` dari grup `role:admin` ke grup `auth` umum di `routes/web.php` dan menambahkan `$request->session()->regenerateToken()` pada `ImpersonateController.php` agar proses kembali dari akun target non-admin ke admin berjalan aman tanpa error 419 / session mismatch.
- **Database Migration `is_force_majeure`**: Menambahkan migration `2026_07_22_202800_add_is_force_majeure_column_to_attendance_calendars_table.php` dengan pengecekan aman `Schema::hasColumn(...)`.
- **Display Pelapor Guru BK**: Memperbaiki tampilan nama pelapor Guru BK pada halaman detail konseling (`Show.vue`).

## [1.9.8] - 2026-07-20
### Fixed
- **Fitur Impersonate**: Perbaikan IDE error (Not enough arguments) pada controller impersonate (`ImpersonateController::leave`).

## [1.9.7] - 2026-07-20
### Added
- **Dashboard Kepala Sekolah – Monitoring Jadwal (Blok I)**: Menambahkan tabel jadwal pelajaran per kelas di atas tabel agenda. Saat kelas dipilih, tampil grid jadwal mingguan kelas tersebut dengan sel yang di-*span* sesuai durasi jam pelajaran (misal 3 JP = 3 kolom). Slot yang guruny sudah mengisi agenda diberi blok **hijau ✓**, slot yang belum diberi blok **merah ✗**. Tanpa pemilihan kelas, tampil tabel monitoring semua kelas (X1–XII6) × jam 1–10.
- **Dashboard Kepala Sekolah – Kolom "Jam Mengisi" (Blok II)**: Menambahkan kolom waktu submit agenda (`created_at`) pada tabel detail agenda guru, sehingga kepala sekolah dapat melihat jam berapa guru mengisi agenda (bukan jam edit).
- **Komponen `KepsekScheduleMatrix.vue`**: Komponen baru yang embeddable (tanpa AppLayout) untuk menampilkan matriks jadwal mingguan dengan indikator agenda terisi/belum, dukungan `colspan` otomatis per durasi JP, garis grid nyata, serta ringkasan harian (sudah diisi / belum / terjadwal).
- **Profil Pengguna**: Menu ganti profil kini tersedia untuk semua role (Guru, Guru BK, Pegawai, Kepala Sekolah), tidak hanya admin.
- **Foto Profil di Dashboard & Menu**: Foto profil pengguna ditampilkan pada ikon akun di pojok kanan atas layout.

### Changed
- **Layout Dashboard Kepala Sekolah**: Diubah susunan menjadi dua blok — Blok I (jadwal/monitoring) di atas, Blok II (detail agenda) di bawah. Menghapus komponen "Jadwal Pelajaran Per Kelas" terpisah yang membingungkan.
- **Filter Kelas Kepsek Dashboard**: Dropdown kelas kini hanya memuat kelas sesuai tahun ajaran aktif.

### Fixed
- **Error `Class "ScheduleDetail" not found`**: Menambahkan import namespace lengkap `ScheduleDetail` yang hilang di `KepsekDashboardController`.
- **Error `TypeError: Cannot read properties of undefined (reading 'total')`**: Memperbaiki dua `return Inertia::render()` duplikat di controller yang menyebabkan prop `attendanceStats` tidak terkirim ke Vue.
- **Error `Call to undefined method getColorBySubject()`**: Menambahkan method helper `getColorBySubject()` yang terlupakan di `KepsekDashboardController`.

## [1.9.6] - 2026-07-19
### Added
- **Modul Pelaporan Sarana & Prasarana**: Menambahkan sistem pelaporan kerusakan sarpras kelas/sekolah untuk Guru, Guru BK, Kepala Sekolah, dan Pegawai (`/facility-reports`). Mendukung koordinat GPS otomatis, opsi input kategori terpisah (Sarana vs Prasarana), multiple file upload, serta visualisasi timeline riwayat perbaikan.
- **Dashboard Quick Actions**: Menyediakan tombol Lapor Rusak dan Kelola Sarpras pada dashboard Guru, Guru BK, dan Kepala Sekolah lengkap dengan indikator notifikasi unread update.
- **Ekspor Excel Siswa**: Menambahkan tombol Eksport Excel di `/admin/students` yang secara dinamis mengikuti filter pencarian, kelas, agama, dan status keaktifan siswa.

### Changed
- **Tahun Ajaran Terkunci untuk Siswa**: Mengunci pilihan kelas di menu data siswa (`/admin/students`) agar hanya memuat kelas-kelas pada Tahun Ajaran Aktif.
- **Akses Kontrol Dinamis**: Memperbarui menu `/admin/access-control` agar mendeteksi secara dinamis role asli dari Spatie (Pegawai, Guru BK, Kepala Sekolah, Guru) alih-alih hardcode Guru & Staf, serta memperbaiki masalah pemotongan teks label pada pilihan dropdown.
- **Manajemen User**: Mengubah label tab filter di `/admin/users` dari "Guru & Staff" menjadi "Guru" karena Staf/Pegawai sudah dipisah tersendiri.

### Fixed
- **Bug Parsing Bank Soal (CBT)**: Memperbaiki pembacaan otomatis file Word di mana nomor soal 1 sebelumnya tidak terbaca akibat perbedaan penanda xml list auto-numbering.
- **Filter Tahun Ajaran di Penilaian**: Memperbaiki filter Tahun Ajaran di halaman nilai siswa (`/penilaian/grades`) agar terisi otomatis dengan tahun ajaran aktif secara default.

## [1.9.5] - 2026-07-19
### Changed
- **Pendaftaran Ulang**: Menyembunyikan opsi pendaftaran pada form login utama bila admin mengunci fitur daftar ulang, dan me-redirect pengguna kembali ke halaman utama apabila mencoba mengakses rute login pendaftaran ulang secara langsung.

### Fixed
- **Bug Tarik Data Siswa**: Memperbaiki referensi kolom data asal sekolah (`prev_school_name`, `prev_school_status`, `prev_school_type`) yang salah di-mapping (sebelumnya `asal_smp`) pada proses *bulk migrate* (Tarik Data Siswa), sehingga data asal sekolah calon murid kini berhasil tersimpan secara utuh ke dalam tabel siswa utama.

## [1.9.4] - 2026-07-17
### Added
- **Laporan Rekap Presensi Terpusat**: Menambahkan 5 mode rekapitulasi kehadiran (Harian, Mingguan, Bulanan, Semester, Tahunan) yang dapat diakses oleh Admin Kesiswaan, Guru BK, dan Wali Kelas. Mendukung ekspor Excel dan cetak PDF.
- **Label Status Presensi Guru**: Menambahkan label visual (Tag) `TERLAMBAT` dan `DISPENSASI` pada modal pembuatan dan halaman detail presensi mata pelajaran, untuk memberi informasi ekstra kepada guru mapel mengenai status kehadiran pagi siswa bersangkutan di gerbang sekolah.

### Changed
- **Sinkronisasi Presensi Mapel & Harian**: Mengunci (disable) tombol presensi guru mata pelajaran apabila siswa sudah tercatat Sakit (S), Izin (I), atau Alfa (A) di presensi utama harian. Khusus untuk status Dispensasi (D), presensi tidak dikunci (karena bisa jadi siswa hanya dispensasi pada jam tertentu) tetapi tetap mempertahankan log dispensasinya dalam database apabila guru mapel menandai absen atau hadir.

## [1.9.3] - 2026-07-15
### Added
- **Ganti Password via Profil**: Menambahkan menu *dropdown* pada profil pengguna di pojok kanan atas (layout Admin, Guru/Pegawai, dan Siswa) yang memuat opsi Ganti Password dan Logout secara terintegrasi.

### Fixed
- **Filter Siswa Mapel Agama**: Memperbaiki bug pada pembuatan agenda dan presensi di mana guru agama sebelumnya melihat seluruh siswa di kelas. Sistem kini secara spesifik memfilter siswa yang agamanya sesuai dengan `religion_id` pada jadwal mata pelajaran khusus agama.

## [1.9.2] - 2026-07-14
### Fixed
- **Bug Presensi (Guru)**: Memperbaiki masalah *loose comparison* pada backend (`GuruAgendaController.php`) yang menyebabkan status presensi siswa (seperti Sakit, Izin, Alfa) berubah semua menjadi Hadir saat guru menyimpan ulang form edit agenda.
- **Edit Presensi**: Memperbaiki mapping data presensi pada komponen Vue (`Edit.vue`) agar bisa membaca dan mempertahankan catatan status presensi secara akurat.

## [1.9.1] - 2026-07-11
### Added
- **Otomatisasi Presensi (Alpa Harian)**: Menambahkan *Artisan command* (`kesiswaan:seed-daily-attendance`) untuk mengisi status 'A' (Alpa) secara *default* bagi seluruh siswa aktif (yang memiliki kelas) pada pagi hari. Waktu eksekusi (_seed time_) dapat diatur via admin.
- **Pengaturan Presensi di Admin**: Menambahkan tab khusus "Presensi" pada Pengaturan Admin (`/admin/settings`) guna mengelola jam eksekusi otomatis Alpa dan mengontrol rentang jam buka/tutup presensi (*buffer time* check-in/out) via database tanpa hardcode.

### Changed
- **Pencabutan Kunci Perangkat (Device Lock)**: Menghapus logika penguncian ID perangkat dari *AttendanceService*, sehingga siswa kini diizinkan meminjam atau memakai HP teman/perangkat lain untuk melakukan presensi.
- **Tanggal Agenda Guru Otomatis**: Memperbarui halaman pembuatan agenda mengajar guru (`/teacher/agenda/create` baik desktop maupun mobile) agar tanggal KBM terkunci, menggunakan format lokal Indonesia (contoh: "11 Juli 2026"), dan otomatis memuat jadwal hari ini tanpa interaksi tambahan.

### Fixed
- **Bug Timezone Presensi (GMT vs Lokal)**: Memperbaiki inkonsistensi waktu pada presensi yang bergeser karena fungsi `Carbon::parse()` membaca string waktu (misal "07:00:00") sebagai UTC. Kini semua operasi diwajibkan menggunakan zona waktu aplikasi (`Asia/Jakarta`).
- **Desync Sequences PostgreSQL**: Menambahkan skrip/solusi perbaikan masalah *sequence* auto-increment di tabel utama (seperti jadwal dan kehadiran) yang sempat menyebabkan error `duplicate key value violates unique constraint`.

## [1.9.0] - 2026-07-11
### Changed
- **Penyelarasan Layout Menu Akademik**: Menyelaraskan bentuk layout halaman Guru (`/admin/teachers`), Siswa (`/admin/students`), Kelas (`/admin/classrooms`), Mata Pelajaran (`/admin/subjects`), Dashboard Kurikulum (`/admin/curriculum/dashboard`), Plotting Guru (`/admin/schedules`), dan Kalender Akademik (`/admin/calendar`) agar seragam dengan template layout `AcademicYear` (pemisahan Card Header dan Card Tabel/Konten).
- **Tampilan Mobile Penuh untuk Guru**: Menonaktifkan tampilan desktop penuh untuk role Guru. Apabila Guru mengakses sistem lewat browser desktop, layout otomatis dirender menggunakan `MobileLayout` secara penuh di layar (*full-width*) tanpa pembatasan frame/mockup smartphone, tetapi tetap menggunakan antarmuka mobile/smartphone.

## [1.8.9] - 2026-07-11
### Added
- **Generate NIS Siswa Berurutan (`/admin/students`)**: Menambahkan tombol *Generate NIS* di halaman Data Siswa Utama untuk meng-generate NIS bagi siswa yang belum memiliki NIS secara urut alfabetis nama berdasarkan NIS terbesar saat ini + 1.
- **Kontrol Penguncian Pendaftaran Ulang (`/admin/daftar-ulang`)**: Menambahkan panel kontrol akses pendaftaran ulang untuk admin untuk mengunci login dan mengunci edit data calon murid baru secara global/massal.
- **Pemisahan Filter Alumni (`/admin/users`)**: Menambahkan tab filter khusus untuk membedakan antara siswa aktif dan siswa alumni pada halaman Manajemen Pengguna (`/admin/users`), serta memperbaiki bug navigasi/rendering DataTable saat berpindah tab.
- **Layanan Lupa Email Siswa (`/login`)**: Menyediakan tautan *Lupa Email* di halaman login utama yang memungkinkan siswa mencari email login mereka menggunakan nomor NISN dan Tanggal Lahir.

## [1.8.8] - 2026-07-10
### Added
- **Kamera Live Selfie Presensi Guru**: Mengintegrasikan kamera langsung (live camera capture) di halaman pembuatan agenda mengajar (`/teacher/agenda/create`) menggunakan HTML5 MediaDevices API. Guru wajib mengambil foto selfie sebelum menyimpan agenda untuk memastikan kehadiran fisik secara valid.
- **Tujuan Pembelajaran (TP) Opsional**: Mengubah Tujuan Pembelajaran (TP) menjadi opsional (nullable) pada pembuatan agenda mengajar di desktop dan mobile.
- **Pembaruan Halaman Detail Agenda (Desktop)**: Menjadikan panel Tujuan Pembelajaran (TP) serta Materi/Catatan Mengajar pada halaman detail (`/teacher/agenda/{agenda}`) dapat langsung diedit di tempat (inline edit fields) dan terintegrasi penuh ke endpoint update agenda.
- **Dukungan Edit Mobile**: Halaman edit agenda mobile kini sepenuhnya mendukung status TP yang opsional tanpa menghalangi input materi mengajar.

## [1.8.7] - 2026-07-06
### Fixed
- **Restore Backup**: Mengosongkan database (`db:wipe`) secara otomatis sesaat sebelum proses *restore psql* dijalankan, guna menghindari error "constraint already exists" ketika me-restore dump SQL yang tidak memuat `DROP TABLE`.
- **Label Filter Daftar Ulang**: Memperbaiki kebingungan pada antarmuka (UI) kotak ringkasan "Sedang Mengisi" dengan mengubah labelnya menjadi "Sedang Mengisi / Upload" karena angka tersebut merepresentasikan gabungan status calon murid yang sedang mengisi draf maupun proses upload berkas.


## [1.8.6] - 2026-06-30
### Changed
- **Composer 2.10 (PHP 8.4)**: Memperbarui Composer di server ke versi 2.10 yang kompatibel penuh dengan PHP 8.4, menghilangkan *Deprecation Notice* `E_STRICT` dan parameter *nullable* yang muncul saat `composer install` pada versi Composer lama.

### Added
- **CI/CD Pipeline (GitHub Actions)**: Menambahkan pipeline *Continuous Deployment* otomatis:
  - **Staging Auto-Deploy**: Setiap `git push` ke branch `ujicoba` akan otomatis men-deploy ke `uji-portal.sman16smg.sch.id`.
  - **Production Manual Deploy**: Deploy ke `portal.sman16smg.sch.id` dilakukan secara manual melalui tombol *workflow_dispatch* di GitHub Actions dengan konfirmasi kata kunci `DEPLOY`.

## [1.8.5] - 2026-06-29
### Fixed
- **Restore Backup (Critical)**: Memperbaiki restore database yang tidak berpengaruh pada data. Root cause: (1) sanitasi karakter `:` pada nama file `.sql.gz` menyebabkan ekstensi berubah; (2) `psql` dipanggil tanpa path binary absolut sehingga tidak ditemukan oleh Windows service. Solusi: simpan ekstensi `.sql.gz` sebelum sanitasi, gunakan path absolut psql, tambahkan flag `--clean --if-exists`.
- **Deteksi Folder Uploads**: Memperbaiki deteksi subfolder `public/uploads/*` (misal `uploads/cbt_questions`) yang sebelumnya tidak ter-restore karena hanya mencocokkan `public/uploads` secara exact.
- **Notifikasi Restore**: Menambahkan ringkasan statistik pasca-restore pada notifikasi sukses (jumlah user, siswa, guru, soal CBT).

### Changed
- **Toast Error/Success**: Memperpanjang durasi toast untuk pesan restore (8 detik) dan error (7 detik) agar mudah dibaca.

## [1.8.4] - 2026-06-28
### Added
- **Dukungan PHP 8.4**: Memperbarui *constraints* versi PHP pada `composer.json` untuk mendukung secara penuh environment PHP 8.4.

### Fixed
- **Restore Backup**: Memperbaiki masalah pemulihan file fisik saat me-restore backup pada environment *non-interactive*.
- **Kompatibilitas OS**: Memperbaiki bug path directory pada Windows saat memproses direktori arsip hasil ekstraksi dari file backup.

## [1.8.3] - 2026-06-27
### Fixed
- **Sinkronisasi Waktu & Timezone KBM**: Menghapus duplikasi *watcher* tanggal agenda KBM pada desktop yang memicu *race condition* karena pengiriman object Date mentah yang terbaca UTC di server (mengakibatkan jadwal bergeser ke hari sebelumnya).
- **Parameter Tanggal Agenda Mobile**: Memastikan parameter tanggal agenda lokal terkirim saat me-load daftar siswa (`students`) di halaman pembuatan (*create*) dan pengubahan (*edit*) versi *mobile*, sehingga status izin siswa terambil secara akurat sesuai tanggal KBM dan tidak melakukan *fallback* ke tanggal hari ini.

## [1.8.2] - 2026-06-27
### Added
- **Preview Impor Calon Murid**: Menambahkan langkah pratinjau (*preview*) interaktif sebelum memproses impor data calon murid baru, memungkinkan admin meninjau 10 baris pertama data hasil parsing sebelum menyimpannya ke database.
- **Deteksi Delimiter Otomatis**: Mendeteksi pembatas (*delimiter*) file CSV secara dinamis (mendukung pemisah koma `,` maupun titik koma `;`).

### Fixed
- **Normalisasi Baris Terbungkus Kutipan**: Memperbaiki kegagalan impor pada baris CSV yang dibungkus dengan tanda kutip ganda luar (outer double quotes) dan tanda kutip ganda dalam yang lolos dari parser.
- **Penanganan Tanggal Lahir Kosong**: Menangani format tanggal lahir `0000-00-00` pada file impor dengan mengubahnya menjadi `NULL` secara otomatis agar kompatibel dengan validasi tipe data PostgreSQL.

## [1.8.1] - 2026-06-23
### Changed
- **Status Registrasi**: Mengarahkan calon murid yang berstatus `registered` (sudah submit form) langsung ke halaman bukti pendaftaran saat login, serta mematikan akses edit form.
- **Label Tombol Selesai**: Mengubah tulisan tombol pada halaman *Complete* menjadi "Keluar" bagi peserta yang status berkasnya telah diverifikasi (`verified` atau `migrated`).

## [1.8.0] - 2026-06-22
### Added
- **Modal Viewer Berkas**: Menambahkan fitur *Document Viewer* berwujud *modal* (*pop-up*) pada halaman Verifikasi Berkas (termasuk fitur *Zoom In/Out* dan Putar/Rotasi untuk gambar).
- **Tab Baru Detail Verifikasi**: Mengubah perilaku tombol *View* pada tabel calon murid di Dashboard Admin agar langsung membuka tab *browser* baru.

### Changed
- **Pembaruan Format Validasi Input Pendaftaran**: Melonggarkan batasan karakter (*regex*) khusus untuk kolom *Tempat Lahir* (agar dapat menggunakan tanda hubung dan koma) serta *Alamat Jalan* (menjadi teks bebas).

### Fixed
- **Validasi Koordinat Maps Pendaftaran**: Mencegah dan menambahkan peringatan *error* kepada calon murid jika titik koordinat GPS tidak digeser atau masih menggunakan titik koordinat bawaan (*default*) sistem.
- **Dinamisasi Halaman Bukti Pendaftaran**: Menghubungkan variabel nama dan alamat sekolah dari *Database Settings* untuk menghilangkan teks kaku *hardcode* "SMA Negeri Portal" pada cetak PDF pendaftaran ulang.

## [1.7.9] - 2026-06-22
### Added
- **Pengaturan Lokasi Dinamis via UI**: Memindahkan pengaturan Latitude, Longitude, dan Radius Presensi sekolah dari file `.env` ke halaman Pengaturan Situs lengkap dengan fitur map (peta).
- **Indikator Izin Login**: Menambahkan statistik jumlah calon murid yang diizinkan login (Izin Login) pada Dashboard Admin Daftar Ulang.
- **Kalkulasi Jarak Realtime**: Menambahkan kalkulasi jarak `Haversine` secara real-time pada halaman Detail & Verifikasi calon murid baru yang membandingkan titik kordinat input siswa dengan titik lokasi sekolah dari sistem.

## [1.7.8] - 2026-06-22
### Fixed
- **Validasi Karakter Tempat Lahir**: Memperbarui regex pada kolom Tempat Lahir agar karakter titik (`.`) dapat digunakan (misalnya untuk penulisan singkatan "Kab.").
- **Validasi Integer Panjang Karakter**: Memperbaiki logika validasi panjang digit (NISN, NIK, No. KK, Tahun Lahir) yang gagal ketika nilai dari database terbaca sebagai tipe `Number` atau integer.
- **Validasi Form Input Desimal**: Menambahkan izin masukan angka desimal (`step="any"`) di HTML untuk input Tinggi Badan, Berat Badan, dan Lingkar Kepala agar tidak ditolak oleh validasi form bawaan browser.

## [1.7.7] - 2026-06-22
### Improved
- **Sanitasi Koma untuk Desimal**: Menambahkan sanitasi form input pendaftaran ulang sehingga pengguna bisa menggunakan koma (`,`) maupun titik (`.`) saat mengisi kolom bilangan desimal seperti tinggi badan, berat badan, dan jarak tempat tinggal ke sekolah. Sistem secara otomatis menanganinya agar lolos validasi `numeric`.

## [1.7.6] - 2026-06-22
### Fixed
- **Tipe Data Tinggi & Berat Badan**: Mengubah tipe data kolom `height` dan `weight` pada tabel `new_students` dari `integer` menjadi `float` agar dapat menyimpan nilai desimal (contoh: 178.7).

### Changed
- **Konfigurasi Vite**: Pembaruan alamat IP host HMR di `vite.config.js` untuk environment lokal.

## [1.7.5] - 2026-06-21
### Added
- **Pembaruan Format Impor PPDB (Daftar Ulang)**: Penyesuaian layout impor Excel/CSV menjadi 17 kolom, mengabaikan kolom `No` serial dan `Status`, konversi otomatis gender `L`/`P`, serta perlindungan keunikan NISN/NIK.
- **Login Fleksibel via NISN**: Memungkinkan login calon murid menggunakan `no_pendaftaran` maupun `nisn` dengan kode login yang sah pada rute `/daftar-ulang/login`.
- **Konversi Jarak & Umur Otomatis**: Form registrasi otomatis mengonversi satuan jarak meter ke kilometer (misal `"125.12 meter"` menjadi `0.12512` km) dan menampilkan data readonly tanpa satuan ganda.

## [1.7.4] - 2026-06-21
### Added
- **Fitur Edit Akun (Manajemen Pengguna)**: Penambahan tombol aksi edit dan dialog modal edit (nama, email, role) pada halaman Manajemen Pengguna (`resources/js/Pages/Admin/Users/Index.vue`) agar Admin dapat memperbarui profil akun staf, pegawai, maupun admin.

## [1.7.3] - 2026-06-21
### Added
- **Dashboard Khusus Staf**: Pembuatan rute, controller, dan tampilan dashboard khusus untuk role staf (`pegawai`) guna memantau statistik Daftar Ulang (calon siswa baru) secara *real-time*.

### Fixed
- **Navigasi & Redireksi Fleksibel**: Perbaikan alur redirect pada login, root halaman `/`, dan klik logo pada layout utama agar secara otomatis mengarahkan ke dashboard masing-masing role (admin, guru, siswa, dan staf) tanpa menyebabkan error 403 atau 404.

## [1.7.2] - 2026-06-21
### Added
- **Dekopling Tahun Ajaran Modul Daftar Ulang**: Selector filter Tahun Ajaran kustom pada backend & frontend admin daftar ulang agar dapat mengelola data pendaftaran calon murid baru tanpa mengubah Tahun Ajaran aktif global sekolah.
- **Checklist Izin Login & Logger**: Switch `ToggleSwitch` (PrimeVue v4) untuk mengaktifkan/menonaktifkan izin login calon murid baru ke portal pendaftaran, lengkap dengan pencatatan otomatis nama pengguna pengizinkannya.
- **Konfirmasi Tantangan Kustom (Reset Challenge)**: Dialog konfirmasi khusus yang mewajibkan pengetikan teks tantangan (`RESET-DATA-[TAHUN-AJARAN]`) untuk menghindari tindakan reset total data secara tidak sengaja.

### Secure
- **Batasan Hak Akses (Admin Only)**: Menyembunyikan tombol-tombol sensitif (Impor, Ekspor, Reset) di frontend dan menambahkan perlindungan otorisasi backend (`hasRole('admin')`) pada method import, export, dan reset/destroy agar tidak dapat diakses atau ditembak langsung oleh role Guru & Staf.

## [1.7.1] - 2026-06-20
### Add
- **Sinkronisasi Modul ke Fitur**: Tombol sekali klik pada Access Control untuk otomatis mendaftarkan semua modul aktif (`Akademik`, `Cbt`, `DaftarUlang`, `Kesiswaan`, `Penilaian`) ke dalam tabel permissions Spatie (`access-{modul}` & `manage-{modul}`).
- **Multi-Select Penugasan Personil**: Dukungan penugasan banyak guru/staf sekaligus ke suatu hak akses menggunakan komponen MultiSelect (tampilan chip) di modal penugasan.

### Refactor
- **Responsive View Tunggal (Pusat Izin Siswa)**: Penggabungan halaman desktop dan mobile `Guru/Permits/Index.vue` menjadi satu file Vue tunggal yang responsif dinamis menggunakan layout wrapper dinamis (`AppLayout` & `MobileLayout`) berdasarkan lebar layar browser. Menghapus file view mobile terpisah dan menyederhanakan controller backend.

### Fix
- **Otorisasi Middleware Rute Modul**: Mengubah proteksi rute modul DaftarUlang, Kesiswaan, CBT, dan Akademik dari `role` menjadi `role_or_permission` agar user non-admin yang diberi hak akses spesifik tidak terblokir oleh error "User does not have the right roles".

## [1.7.0] - 2026-06-16
### Add
- **Arsitektur Ujian & Proktoring CBT**: Penambahan arsitektur *Room* (Ruangan), *Session* (Sesi), dan *Proctor Schedule* (Jadwal Pengawas) untuk fleksibilitas pengaturan ujian.
- **Live Monitor Pengawas**: Halaman *real-time* bagi pengawas untuk melihat status siswa (login, mengerjakan, selesai, suspensi, blokir) dan mengatur token ujian.
- **Atur Tempat Duduk CBT**: Fitur penempatan kursi siswa murni berdasarkan Ruangan secara tetap tanpa terikat sesi, disertai fitur Import *Seating* via Excel/CSV.
- **Total Poin Bank Soal**: Penambahan kolom kalkulasi Poin Maksimal yang didapat pada halaman manajemen Bank Soal.

### Fix
- **Perbaikan Render Tabel Soal Word**: Mengubah logika parser *auto-newline* (`nl2br`) menjadi pembacaan tag `<w:br>` bawaan Word agar struktur tabel yang di-import tidak berantakan/menyatu.
- **Perbaikan Soal Penjodohan Hilang**: Mengubah pendeteksi regex *shortcode* (seperti `[radio...]`) menjadi *case-insensitive* sehingga tidak lagi melewati soal yang tag-nya menggunakan huruf kecil.
- **Perbaikan Kalkulasi Poin**: Mengunci perhitungan poin soal majemuk murni dari *mapping* kunci Excel, mengatasi *bug* skor ganda (seperti 16 poin padahal seharusnya 8 poin).
- **Perbaikan Otorisasi Pembuat Soal**: Admin yang melakukan import soal tidak lagi dipaksa tercatat sebagai Guru pembuat bank soal tersebut.
- **Perbaikan Kompabilitas PostgreSQL**: Memperbaiki fungsi `FIELD()` MySQL yang menyebabkan *error* 500 saat pengurutan status peserta ujian di menu Proktoring.

## [1.23.1] - 2026-03-06
### Fix
- Perbaikan Fitur Cetak Agenda

## [1.23.0] - 2026-03-06
### Add
- Tambah Fitur Reset Password

## [1.22.0] - 2026-03-06
### Add
- Tambah Fitur Update data siswa by upload

## [1.21.0] - 2026-03-02
### Add
- Backup (fixed) dan Restore (nf)

### Fixed
- Perubahan foto dari mirorring 

## [1.20.3] – 2026-02-23
### Fix
- Perbaikan Presensi Siswa saat Checkout
- Perbaikan Edit Jadwal Guru
- Perbaikan Filter Presensi Siswa
- Perbaikan Fitur Ganti Password

## [1.20.2] – 2026-02-23
### Fix
- Perbaikan Edit Tipe Presensi

## [1.20.1] – 2026-02-11
### Fix
- Perbaikan Logic Tombol Pulang

## [1.20.0] – 2026-02-11
### Add
- Fitur Presensi Pulang

## [1.19.1] – 2026-02-09
### Add
- Fitur Cek History Presensi
- Fitur Kamera

### Fixed
- Jadwal Hari ini belum muncul
- Perbaikan Menus client siswa

## [1.18.0] – 2026-02-08
### Add
- Fitur Presensi Mandiri Siswa: Implementasi modul presensi yang memungkinkan siswa melakukan absen secara mandiri melalui perangkat mobile.
- Integrasi Leaflet Map: Penambahan peta interaktif pada laman siswa untuk visualisasi posisi siswa terhadap radius sekolah.
- Validasi Geofencing (Haversine): Logika penghitungan jarak antara koordinat siswa dan koordinat sekolah menggunakan rumus Haversine di sisi server untuk akurasi tinggi.
- Device Binding (Anti-Titip Absen): Penguncian identitas perangkat menggunakan User-Agent hashing untuk memastikan satu akun hanya bisa absen dari satu perangkat yang terdaftar.
- Konfigurasi Lokasi Dinamis: Pemindahan parameter koordinat sekolah dan radius toleransi ke dalam file .env dan config/app.php agar lebih mudah dikelola.

### Changed
- Optimasi AttendanceService: Refaktor logika check-in menggunakan DB::transaction untuk memastikan integritas data saat terjadi kegagalan validasi.
- Integrasi Service Akademik: Menghubungkan AttendanceService dengan AcademicYearService untuk otomatisasi penentuan tahun ajaran aktif pada tiap log presensi.
- UI/UX Dashboard Siswa: Penambahan jam real-time, indikator status GPS, dan kalkulasi jarak dinamis sebelum tombol absen diaktifkan.

### Fixed
- Bug Shadow Entry: Memperbaiki masalah tombol absen yang hilang/terkunci ketika validasi awal (jarak/perangkat) gagal namun baris data sudah terlanjur tercipta di database.
- Vite CORS & HMR: Perbaikan konfigurasi vite.config.js untuk mengizinkan akses pengujian dari perangkat mobile melalui alamat IP lokal.
- Issue Geolocation HTTP: Implementasi workaround menggunakan Chrome Flags untuk mengaktifkan API Lokasi pada lingkungan non-HTTPS selama masa pengembangan.

## [1.17.0] – 2026-02-01
### Add
- Rekap Kedisiplinan Siswa

## [1.16.4] – 2026-01-30
### Fixed
- Fix Bug Export Excel

## [1.16.3] – 2026-01-29
### Fixed
- Fix Bug Rekap Penilaian

## [1.16.2] – 2026-01-25
### Fixed
- Fix Bug error 500 saat penilaian 
- Fix Bug Too Many redirect
## [1.16.0] – 2026-01-25

### Added
- Fitur Rekapitulasi Nilai Matriks dengan Multi-level Header (Grouping).
- Sistem KKM (Passing Grade) dinamis yang diambil per Komponen Penilaian dari database.
- Fitur Export Rekap Nilai ke Excel menggunakan Maatwebsite Excel (Format Merged Cells & Conditional Coloring).
- Integrasi otomatis penentuan status kelulusan (Severity Tag) berdasarkan KKM.

### Fixed
- Sinkronisasi rute Inertia untuk Title Page dinamis via `@inertiaHead`.
- Fix bug "Maximum recursive updates" pada PrimeVue DataTable saat menggunakan ColumnGroup.
- Perbaikan filter otomatis Mata Pelajaran dan Kelas pada halaman Rekapitulasi.
- Optimasi Eager Loading pada relasi GradingComponent dan GradingItem.

## [1.15.2] – 2026-01-22
### Added
- Fitur Trash data di Permit
### Fixed
- Fix Nama Pemberi Izin
- Penyesuaian UI/UX di confirm

## [1.14.1] – 2026-01-22
### Fixed
- Guru Gagal Input Permit

## [1.14.0] – 2026-01-21
### Added
- Page Mobile untuk Late dan Permit

## [1.13.0] – 2026-01-20
### Added
- Modul Siswa Terlambat

## [1.12.0] – 2026-01-19
### Added
- Modul Izin / Permit 
- Fix Flow Desktop untuk Presensi dengan Izin

## [1.11.3] – 2026-01-18
### Minor Add
- Show Asteriks Password di halaman Login
### Fixed
- Bug Agenda KBM Mode Desktop (Perbaikan Tampilan Presensi)
- Bug CP Form Tambah tidak reset
- Bug Data Kelas Menjadi Ganda Saat Ganti Kelas di Agenda

## [1.11.0] – 2026-01-11
### Added
- Modul Reporting: Implementasi fitur cetak Jurnal Mengajar Bulanan dengan layout otomatis per tanggal.
- Fitur Wali Kelas (Classroom Report): Dashboard khusus wali kelas untuk memantau seluruh agenda guru yang masuk di kelas perwaliannya.
- Sistem Kalkulasi Presensi Otomatis: Penjumlahan otomatis jumlah siswa, hadir, dan absen menggunakan withCount di level database untuk performa tinggi.
- Selector Periode Dinamis: Penambahan filter Bulan dan Tahun pada halaman laporan dengan sinkronisasi label bulan bahasa Indonesia.
- Dashboard Integration: Penambahan shortcut menu cerdas di Dashboard yang mendeteksi status Wali Kelas secara otomatis (Conditional Rendering).

## [1.10.0] – 2026-01-08
### Added
- Fitur Filter Agenda: Filter riwayat berdasarkan Hari Ini, Minggu Ini, Bulan Ini, dan Semester Ini.
- Integritas Slot Jam: Implementasi schedule_detail_id untuk identifikasi unik sesi mengajar (mengatasi overlap pada jadwal di hari yang sama).
- Security Check: Pengetatan keamanan ownership data menggunakan teacher_id pada seluuh proses fetch dan store.

### Changed
- UI Presensi: Redesain dialog presensi menggunakan SelectButton (Toggle) untuk input yang lebih cepat dan efisien.
- Logika Simpan: Migrasi ke updateOrCreate untuk mencegah redundansi data agenda.
- Sorting Riwayat: Pengurutan otomatis berdasarkan Tanggal (Desc) dan Slot Jam Pelajaran (Asc).

### Fixed
- Timezone Bug: Perbaikan pengiriman tanggal dari DatePicker agar sesuai dengan waktu lokal (WIB).
- Locale Bug: Pemetaan manual nama hari Indonesia untuk akurasi query pada sistem operasi server yang berbeda.
- Null State Toggle: Perbaikan logika pada Show.vue agar status presensi tidak menjadi null saat di-klik ulang.

---

## [1.9.0] – 2026-01-06
### Added
- Modul Guru: Capaian Pembelajaran (CP) berbasis jadwal mengajar
- Modul Guru: Tujuan Pembelajaran (TP) berbasis CP
- Filter CP & TP otomatis berdasarkan Schedule (teacher ↔ subject)
- Import CP khusus mapel yang diampu guru
- Import TP berbasis CP (kode TP auto-generate server-side)

### Changed
- Akses Guru tidak lagi melihat CP/TP lintas mapel
- Relasi User → Teacher digunakan sebagai sumber identitas guru
- Validasi import CP/TP dikunci pada mapel & CP aktif

### Security
- Guru tidak dapat menghapus CP/TP secara permanen
- Akses URL manual CP/TP mapel lain menghasilkan 403
- Hardening backend pada semua endpoint guru

### Fixed
- Perbaikan mapping teacher_id vs user_id pada Schedule
- Sinkronisasi data CP/TP dengan jadwal mengajar aktif

---

## [1.7.0] - 2026-01-05
### Added
- Master Capaian Pembelajaran (CP) berbasis Modul Akademik
- Master Tujuan Pembelajaran (TP) terikat CP
- Auto-generate Kode TP (server-side, konsisten & aman)
- Import TP via Excel (kode TP dibuat otomatis oleh sistem)
- Template Excel Import TP
- Soft Delete & Arsip TP
- Toggle Arsip (Aktif / Arsip) di TP
- Navigasi CP → TP → Kembali ke CP

### Improved
- UX manajemen kurikulum (CP–TP) lebih kontekstual
- Validasi duplikasi TP per CP
- Struktur modul Akademik lebih rapi & konsisten

### Fixed
- Bug toggle arsip TP tidak kembali ke data aktif
- Error kode TP `undefined` pada preview

---

## [1.6.3] - 2026-01-04
### Added
- Dukungan mapel agama paralel per agama (Islam, Kristen, Katolik, dst)
- Penandaan mapel agama melalui field `is_religion` pada master subject
- Struktur jadwal mendukung banyak guru di jam yang sama untuk mapel agama
- Mekanisme versioning aplikasi berbasis file (`version.json`, `CHANGELOG.md`)

### Changed
- Refactor logic ScheduleController untuk mendukung mapel agama
- Refactor logic TeachingScheduleController agar bentrok guru tidak berlaku pada mapel agama
- Perubahan tampilan Manage Schedule (plotting guru) untuk mapel agama
- Filter kelas & jadwal berbasis tahun ajaran aktif
- Tampilan jadwal per kelas (byClass) mendukung banyak guru dalam slot yang sama

### Fixed
- Bug jadwal kelas lama masih muncul saat ganti tahun ajaran
- Error recursive update pada Manage.vue
- Bug jadwal agama hanya menampilkan data terakhir
- Bug import & plotting yang salah membaca tahun ajaran aktif
- Ketidaksinkronan data subject antara local dan server (PostgreSQL)

---

## [1.6.2] - 2026-01-04
### Fixed
- Perbaikan logging guru
- Perbaikan import siswa dan anggota kelas

---

## [1.6.1] - 2026-01-03
### Changed
- Refactor besar logic plotting guru dan mapel
- Penyaringan data kelas berdasarkan tahun ajaran aktif

---

## [1.5.2] - 2026-01-03
### Added
- User Management dengan role Pegawai

---

## [1.4.2] - 2026-01-03
### Added
- Modul Kalender Pendidikan (mode range & tag event)
- User Management dan Login Page

### Fixed
- Error codegen pada halaman Inertia Kalender
- Perapian tabel rekap TKA per mapel

---

## [1.4.1] - 2026-01-01
### Changed
- Refactor menu berbasis role
