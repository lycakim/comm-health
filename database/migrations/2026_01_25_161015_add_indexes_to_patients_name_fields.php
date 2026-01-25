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
            // Add individual indexes for faster LIKE queries
            $table->index('first_name', 'idx_patients_first_name');
            $table->index('last_name', 'idx_patients_last_name');
            $table->index('middle_name', 'idx_patients_middle_name');
            
            // Add composite index for common search patterns (first + last name)
            $table->index(['first_name', 'last_name'], 'idx_patients_first_last_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('idx_patients_first_name');
            $table->dropIndex('idx_patients_last_name');
            $table->dropIndex('idx_patients_middle_name');
            $table->dropIndex('idx_patients_first_last_name');
        });
    }
};
