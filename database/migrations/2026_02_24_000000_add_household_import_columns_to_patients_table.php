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
        Schema::table('patients', function (Blueprint $table) {
            $table->string('birth_order')->nullable()->after('birth_date');
            $table->string('blood_type')->nullable();
            $table->boolean('indigent')->nullable()->default(false);
            $table->boolean('pwd')->nullable()->default(false);
            $table->boolean('renter')->nullable()->default(false);
            $table->boolean('solo_parent')->nullable()->default(false);
            $table->boolean('senior_citizen')->nullable()->default(false);
            $table->string('household_no')->nullable();
            $table->string('precinct_no')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'birth_order',
                'blood_type',
                'indigent',
                'pwd',
                'renter',
                'solo_parent',
                'senior_citizen',
                'household_no',
                'precinct_no',
            ]);
        });
    }
};
