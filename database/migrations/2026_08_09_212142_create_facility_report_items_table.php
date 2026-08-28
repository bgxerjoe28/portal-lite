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
        Schema::create('facility_report_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_report_id');
            $table->enum('type', ['sarana', 'prasarana']);
            $table->string('item_name');
            $table->string('room_name')->nullable();
            $table->enum('severity', ['ringan', 'sedang', 'berat']);
            $table->string('recommendation')->nullable();
            $table->enum('status', ['dilaporkan', 'diperbaiki', 'selesai'])->default('dilaporkan');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('facility_report_id')->references('id')->on('facility_reports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_report_items');
    }
};
