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
        Schema::create('student_permits', function (Blueprint $table) {
            $table->id();
            $table->date('date'); // Tanggal izin
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('permit_type', 2); // S (Sakit), I (Izin), D (Dispen), A (Alfa)
            $table->text('reason')->nullable(); // Keterangan tambahan (misal: Sakit Tipus)
            $table->foreignId('teacher_id')->constrained(); // Guru/Piket yang mencatat
            $table->timestamps();
            $table->softDeletes();
            
            // Pastikan satu siswa hanya punya satu permit per hari
            $table->unique(['date', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_permits');
    }
};
