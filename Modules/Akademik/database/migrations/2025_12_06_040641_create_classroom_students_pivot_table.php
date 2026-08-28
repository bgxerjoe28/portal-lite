<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Hapus kolom classroom_id di students (karena sudah tidak relevan)
        if (Schema::hasColumn('students', 'classroom_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('classroom_id');
            });
        }

        // 2. Buat Tabel Pivot (Riwayat Kelas)
        Schema::create('classroom_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            
            // Kolom Status (Opsional: Aktif, Pindah, Keluar di tengah tahun)
            $table->enum('status', ['aktif', 'pindah', 'keluar', 'lulus'])->default('aktif');
            
            $table->timestamps();

            // CONSTRAINT PENTING:
            // Satu siswa hanya boleh ada di SATU kelas pada TAHUN AJARAN yang sama.
            $table->unique(['academic_year_id', 'student_id'], 'unique_student_per_year');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_students');
        
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('classroom_id')->nullable();
        });
    }
};