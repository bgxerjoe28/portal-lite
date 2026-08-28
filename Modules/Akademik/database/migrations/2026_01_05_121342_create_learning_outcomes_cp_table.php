<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_outcomes_cp', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('subjects_id');
            $table->enum('fase', ['E', 'F']);

            $table->string('judul_cp');
            $table->text('rumusan_cp');
            $table->json('kata_kunci')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->index(['subjects_id', 'fase']);
            $table->unique(['subjects_id', 'fase', 'judul_cp']);
            $table->foreign('subjects_id')
                ->references('id')
                ->on('subjects')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_outcomes_cp');
    }
};
