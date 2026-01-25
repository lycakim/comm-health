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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('generated_by')->nullable();
            $table->string('location')->nullable();
            $table->string('frequency')->nullable();
            
            // New fields for generated reports
            $table->string('report_type')->nullable(); // e.g., 'patient-profiling', 'maternal-child'
            $table->string('format')->default('pdf'); // 'pdf' or 'csv'
            $table->string('file_path')->nullable(); // Storage path
            $table->string('file_name')->nullable(); // Original filename
            $table->unsignedBigInteger('file_size')->nullable(); // File size in bytes
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('barangay_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};