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
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('barangay_id')->nullable()->constrained('barangays')->nullOnDelete();
            $table->string('facility_name');
            $table->text('facility_address')->nullable();
            $table->text('reason_for_referral');
            $table->string('urgency')->default('routine');
            $table->text('referring_provider_notes')->nullable();
            $table->string('status')->default('pending');
            $table->dateTime('date_referred');
            $table->dateTime('date_completed')->nullable();
            $table->string('receiving_provider_name')->nullable();
            $table->text('receiving_provider_notes')->nullable();
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