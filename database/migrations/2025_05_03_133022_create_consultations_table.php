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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('date');
            
            // Consultation Details
            $table->text('address');
            $table->boolean('disability')->default(false);
            $table->boolean('philhealth')->default(false);
            $table->boolean('member_of_4ps')->default(false);
            $table->boolean('nhts_member')->default(false);
            $table->boolean('birth_plan')->default(false);
            $table->string('type')->nullable();
            
            // Mother Information
            $table->string('mother_first_name');
            $table->string('mother_last_name');
            $table->string('mother_middle_name')->nullable();
            
            // Child Information
            $table->decimal('child_weight', 5, 2)->nullable();
            $table->unsignedTinyInteger('child_order')->nullable();
            $table->boolean('mother_status')->nullable();
            $table->boolean('hepa_b')->nullable();
            $table->boolean('nbs')->nullable();
            $table->boolean('prenatal_dates')->default(false);
            
            // Immunization Dates
            $table->date('bcg_date')->nullable();
            $table->date('prenatal_date')->nullable();
            $table->date('polio_date')->nullable();
            $table->date('ipv_date')->nullable();
            $table->date('pcv_date')->nullable();
            $table->date('measles_date')->nullable();
            $table->date('mmr_date')->nullable();
            
            // Disabilities and Notes
            $table->json('disabilities')->nullable();
            $table->string('other_diseases')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};