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
        // 1. Tabel Laporan Sarana & Prasarana
        Schema::create('facility_reports', function (Blueprint $table) {
            $table->id();

            // Identitas laporan
            $table->string('title');
            $table->text('description');

            // Kategori: sarana atau prasarana
            $table->enum('type', ['sarana', 'prasarana']);

            // Item spesifik (contoh: lampu, lcd, tembok, plafond)
            $table->string('item_name'); // nama item kerusakan

            // Lokasi kejadian
            $table->string('location'); // misal: "Kelas XI-A", "Toilet Lantai 2"
            $table->unsignedBigInteger('classroom_id')->nullable(); // opsional: kelas terkait
            $table->double('latitude', 15, 8)->nullable();  // koordinat GPS otomatis
            $table->double('longitude', 15, 8)->nullable(); // koordinat GPS otomatis

            // Tingkat keparahan
            $table->enum('severity', ['ringan', 'sedang', 'berat'])->default('sedang');

            // Status penanganan
            $table->enum('status', ['pending', 'verified', 'in_progress', 'resolved', 'rejected'])
                  ->default('pending');

            // Siapa yang lapor
            $table->unsignedBigInteger('reported_by'); // FK → users.id

            // Bukti foto (multiple)
            $table->json('photos')->nullable();

            $table->timestamps();

            $table->foreign('reported_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('set null');

            $table->index(['status', 'type']);
            $table->index('reported_by');
        });

        // 2. Tabel Log Update / Perbaikan (bisa multiple update per laporan)
        Schema::create('facility_report_updates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('facility_report_id');
            $table->unsignedBigInteger('updated_by'); // FK → users.id

            $table->enum('new_status', ['pending', 'verified', 'in_progress', 'resolved', 'rejected']);
            $table->text('notes')->nullable(); // Catatan perbaikan

            // Apakah laporan ini sudah dibaca oleh pelapor (untuk notifikasi)
            $table->boolean('is_read_by_reporter')->default(false);

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('facility_report_id')->references('id')->on('facility_reports')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_report_updates');
        Schema::dropIfExists('facility_reports');
    }
};
