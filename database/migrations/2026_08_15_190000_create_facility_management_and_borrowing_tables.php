<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Master Ruangan
        Schema::create('facility_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // contoh: LAB-KOMP-1, AULA-UTAMA
            $table->string('name'); // contoh: Lab Komputer 1
            $table->integer('capacity')->default(0);
            $table->string('location')->nullable(); // contoh: Gedung B Lantai 2
            $table->json('facilities')->nullable(); // contoh: ["AC", "Proyektor", "Sound System", "WiFi"]
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->enum('status', ['available', 'maintenance', 'inactive'])->default('available');
            $table->boolean('is_reservable')->default(true);
            $table->timestamps();

            $table->index('status');
        });

        // 2. Tabel Master Aset & Peralatan
        Schema::create('facility_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique(); // contoh: AST-LAP-001
            $table->string('name'); // contoh: Laptop ASUS Core i7 #1
            $table->string('category')->default('Umum'); // Elektronik, Multimedia, Olahraga, Laboratorium, Musik, Umum
            $table->string('brand_model')->nullable(); // contoh: ASUS Vivobook 14
            $table->string('serial_number')->nullable();
            $table->string('qr_code_token')->unique(); // Token unik untuk scan QR code
            $table->enum('condition', ['good', 'minor_damage', 'heavy_damage'])->default('good');
            $table->enum('status', ['available', 'borrowed', 'maintenance', 'lost', 'disposed'])->default('available');
            $table->string('location')->nullable(); // Posisi simpan: Ruang Sarpras Lemari A
            $table->string('image')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'condition']);
            $table->index('category');
        });

        // 3. Tabel Blackout / Jadwal Khusus Sekolah
        Schema::create('facility_blackouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id')->nullable(); // null jika berlaku untuk semua ruang
            $table->string('name'); // contoh: Ujian Akhir Semester / Rapat Pleno
            $table->text('reason')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('facility_rooms')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['start_time', 'end_time']);
        });

        // 4. Tabel Reservasi / Peminjaman
        Schema::create('facility_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_code')->unique(); // contoh: SAR-202608-0001
            $table->unsignedBigInteger('user_id'); // FK users peminjam
            $table->unsignedBigInteger('extracurricular_id')->nullable(); // jika permohonan atas nama eskul
            $table->unsignedBigInteger('room_id')->nullable(); // FK facility_rooms (jika pinjam ruang)
            $table->enum('type', ['room_only', 'asset_only', 'both'])->default('both');

            $table->string('title'); // Nama Kegiatan
            $table->text('purpose'); // Tujuan / Keperluan
            $table->string('proposal_file_path')->nullable(); // PDF Proposal/Surat izin
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('participant_count')->nullable();

            // Approval Tahap 1 (Pembina Eskul / Waka)
            $table->boolean('requires_coach_approval')->default(false);
            $table->unsignedBigInteger('coach_user_id')->nullable();
            $table->enum('stage1_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('stage1_by')->nullable();
            $table->text('stage1_notes')->nullable();
            $table->dateTime('stage1_at')->nullable();

            // Approval Tahap 2 (Petugas Sarpras)
            $table->enum('stage2_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('stage2_by')->nullable();
            $table->text('stage2_notes')->nullable();
            $table->dateTime('stage2_at')->nullable();

            // Status Keseluruhan
            $table->enum('status', [
                'draft',
                'pending_coach',
                'pending_sarpras',
                'approved',
                'in_use',
                'completed',
                'rejected',
                'cancelled',
                'incident'
            ])->default('pending_sarpras');

            // Serah Terima (Handover / Pengambilan)
            $table->unsignedBigInteger('handover_by')->nullable();
            $table->dateTime('handover_at')->nullable();
            $table->text('handover_notes')->nullable();

            // Pengembalian
            $table->unsignedBigInteger('return_by')->nullable();
            $table->dateTime('return_at')->nullable();
            $table->text('return_notes')->nullable();
            $table->enum('return_condition_summary', ['good', 'damaged', 'lost'])->nullable();

            $table->string('qr_token')->unique(); // Token untuk verifikasi barcode E-Permit
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('facility_rooms')->onDelete('set null');
            $table->foreign('extracurricular_id')->references('id')->on('extracurriculars')->onDelete('set null');
            $table->foreign('coach_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('stage1_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('stage2_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('handover_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('return_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['status', 'start_time', 'end_time']);
            $table->index('user_id');
        });

        // 5. Tabel Pivot Aset Terpinjam
        Schema::create('facility_reservation_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservation_id');
            $table->unsignedBigInteger('asset_id');
            $table->enum('checkout_condition', ['good', 'minor_damage', 'heavy_damage'])->default('good');
            $table->enum('return_condition', ['good', 'minor_damage', 'heavy_damage', 'lost'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('reservation_id')->references('id')->on('facility_reservations')->onDelete('cascade');
            $table->foreign('asset_id')->references('id')->on('facility_assets')->onDelete('cascade');
            $table->index(['reservation_id', 'asset_id']);
        });

        // 6. Tabel Berita Acara Kerusakan / Kehilangan (Damage Report)
        Schema::create('facility_damage_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_code')->unique(); // contoh: DMG-202608-0001
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('reported_by'); // FK users (petugas pencatat)
            $table->enum('damage_type', ['damaged', 'lost']);
            $table->text('description'); // Penjelasan kerusakan / kronologi
            $table->json('evidence_photos')->nullable();
            $table->text('action_plan')->nullable(); // Tindak lanjut (perbaikan / ganti rugi)
            $table->decimal('compensation_fee', 12, 2)->default(0);
            $table->boolean('is_compensated')->default(false);
            $table->enum('status', ['open', 'in_review', 'resolved'])->default('open');
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('reservation_id')->references('id')->on('facility_reservations')->onDelete('set null');
            $table->foreign('asset_id')->references('id')->on('facility_assets')->onDelete('set null');
            $table->foreign('room_id')->references('id')->on('facility_rooms')->onDelete('set null');
            $table->foreign('reported_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_damage_reports');
        Schema::dropIfExists('facility_reservation_assets');
        Schema::dropIfExists('facility_reservations');
        Schema::dropIfExists('facility_blackouts');
        Schema::dropIfExists('facility_assets');
        Schema::dropIfExists('facility_rooms');
    }
};
