<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_objectives_tp', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cp_id');

            $table->string('kode_tp')->unique();
            $table->string('nomor_tp');
            $table->text('rumusan_tp');

            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
            $table->unique(['cp_id', 'nomor_tp']);
            $table->foreign('cp_id')
                ->references('id')
                ->on('learning_outcomes_cp')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_objectives_tp');
    }
};
