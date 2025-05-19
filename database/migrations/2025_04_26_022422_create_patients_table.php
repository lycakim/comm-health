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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('relationship_to_head_of_family');
            $table->string('relationship_to_head_of_family_other')->nullable();
            $table->text('place_of_birth');
            $table->date('birth_date');
            $table->string('age')->nullable();
            $table->string('sex');
            $table->string('civil_status');
            $table->string('educational_attainment');
            $table->string('occupation');

            // Women of Reproductive Age
            $table->boolean('pregnant')->default(false);
            $table->integer('weeks_pregnant')->nullable();
            $table->integer('months_pregnant')->nullable();
            $table->string('child_health_status')->nullable();
            $table->json('current_family_planning_method')->nullable();
            $table->string('family_monthly_income')->nullable();

            // Religion/Indigenous People
            $table->boolean('ip')->default(false);
            $table->string('ip_type')->nullable();

            // Housing Facilities
            $table->integer('no_of_house')->default(1);
            $table->boolean('with_fence')->default(false);
            $table->string('house_type')->nullable();

            // Medical History
            $table->string('blood_pressure')->nullable();
            $table->decimal('sugar_level', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->boolean('trained_for_first_aid')->default(false);
            $table->decimal('bmi', 8, 2)->nullable();
            $table->string('bmi_category')->nullable();

            // Health Statuses (Health Conditions)
            $table->json('health_statuses')->nullable();

            // Health Statuses (Medications)
            $table->json('medication_maintenance')->nullable();

            // Water Supply Sources
            $table->json('water_supply_sources')->nullable();

            // Toilet Types
            $table->json('toilet_types')->nullable();

            // Drainage and Disposal
            $table->json('drainage_disposals')->nullable();

            // Livestock
            $table->json('livestock')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};