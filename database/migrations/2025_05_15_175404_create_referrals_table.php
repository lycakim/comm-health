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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('urgency'); // Emergency, Ambulatory, Medico-Legal
            $table->string('referred_to');
            $table->string('status')->default('pending'); // pending, accepted, completed, cancelled
            $table->boolean('surgical_operation')->default(false);
            $table->string('procedure')->nullable();
            $table->boolean('drug_allergy')->default(false);
            $table->string('drug_allergy_notes')->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('blood_pressure')->nullable();
            $table->integer('rr')->nullable(); // Respiratory Rate
            $table->string('referral_reason'); // Hospital Capability, Lack of Specialists, Financial Constraint, Other
            $table->text('reason_for_referral_other')->nullable();
            $table->text('chief_complaint')->nullable();
            $table->text('action_taken')->nullable();
            $table->text('impression')->nullable();
            $table->text('hpi_notes')->nullable(); // History of Present Illness Notes
            $table->string('receiving_provider_name')->nullable();
            $table->timestamp('date_completed')->nullable();
            $table->text('receiving_provider_notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};