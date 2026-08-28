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
        Schema::table('students', function (Blueprint $table) {
            // Rename columns to prevent duplicates
            $table->renameColumn('nama_ayah', 'father_name');
            $table->renameColumn('nama_ibu', 'mother_name');
            
            // Add missing details from registration schema
            $table->string('nik')->nullable();
            $table->string('no_kk')->nullable();
            $table->string('akta_no')->nullable();
            $table->string('citizenship')->default('WNI');
            $table->string('special_needs')->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('dusun')->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('residence_type')->nullable();
            $table->string('transportation')->nullable();
            $table->integer('child_order')->nullable();
            
            $table->boolean('father_deceased')->default(false);
            $table->string('father_nik')->nullable();
            $table->integer('father_birth_year')->nullable();
            $table->string('father_education')->nullable();
            $table->string('father_job')->nullable();
            $table->string('father_income')->nullable();
            $table->string('father_special_needs')->nullable();
            $table->string('father_phone')->nullable();
            
            $table->boolean('mother_deceased')->default(false);
            $table->string('mother_nik')->nullable();
            $table->integer('mother_birth_year')->nullable();
            $table->string('mother_education')->nullable();
            $table->string('mother_job')->nullable();
            $table->string('mother_income')->nullable();
            $table->string('mother_special_needs')->nullable();
            $table->string('mother_phone')->nullable();
            
            $table->string('guardian_name')->nullable();
            $table->string('guardian_nik')->nullable();
            $table->integer('guardian_birth_year')->nullable();
            $table->string('guardian_education')->nullable();
            $table->string('guardian_job')->nullable();
            $table->string('guardian_income')->nullable();
            $table->string('guardian_phone')->nullable();
            
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('head_circumference')->nullable();
            $table->double('distance_to_school_km')->nullable();
            $table->integer('travel_time_minutes')->nullable();
            $table->integer('sibling_count')->nullable();
            $table->string('periodik_phone')->nullable();
            
            $table->string('prev_school_type')->nullable();
            $table->string('prev_school_status')->nullable();
            $table->string('prev_school_name')->nullable();

            $table->string('file_kk')->nullable();
            $table->string('file_akta')->nullable();
            $table->string('file_ijazah')->nullable();
            $table->string('file_foto')->nullable();
            $table->string('file_other')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('father_name', 'nama_ayah');
            $table->renameColumn('mother_name', 'nama_ibu');

            $table->dropColumn([
                'nik', 'no_kk', 'akta_no', 'citizenship', 'special_needs',
                'rt', 'rw', 'dusun', 'kelurahan', 'kecamatan', 'postal_code',
                'latitude', 'longitude', 'residence_type', 'transportation', 'child_order',
                'father_deceased', 'father_nik', 'father_birth_year', 'father_education',
                'father_job', 'father_income', 'father_special_needs', 'father_phone',
                'mother_deceased', 'mother_nik', 'mother_birth_year', 'mother_education',
                'mother_job', 'mother_income', 'mother_special_needs', 'mother_phone',
                'guardian_name', 'guardian_nik', 'guardian_birth_year', 'guardian_education',
                'guardian_job', 'guardian_income', 'guardian_phone',
                'height', 'weight', 'head_circumference', 'distance_to_school_km',
                'travel_time_minutes', 'sibling_count', 'periodik_phone',
                'prev_school_type', 'prev_school_status', 'prev_school_name',
                'file_kk', 'file_akta', 'file_ijazah', 'file_foto', 'file_other'
            ]);
        });
    }
};
