<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_attachments', function (Blueprint $table) {
            $table->id();

            // Relasi ke agenda
            $table->foreignId('teaching_agenda_id')
                ->constrained('teaching_agendas')
                ->cascadeOnDelete();

            // Jenis lampiran
            $table->enum('type', ['photo', 'audio']);

            // Lokasi file (storage/public/...)
            $table->string('file_path');

            // Nama asli (opsional, untuk info)
            $table->string('original_name')->nullable();

            // Metadata ringan (opsional, future-proof)
            $table->unsignedInteger('file_size')->nullable(); // bytes
            $table->string('mime_type')->nullable();

            $table->timestamps();

            // Index bantu query
            $table->index(['teaching_agenda_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_attachments');
    }
};