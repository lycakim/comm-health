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
        // Add indexes to chats table for better query performance
        Schema::table('chats', function (Blueprint $table) {
            $table->index('receiver_id');
            $table->index('read_at');
            $table->index(['receiver_id', 'read_at']); // Composite index for unread counts query
        });

        // Add indexes to patients table for filtering and statistics
        Schema::table('patients', function (Blueprint $table) {
            $table->index('barangay_id');
            $table->index('category_id');
            $table->index('sex');
            $table->index('created_at');
            $table->index(['barangay_id', 'created_at']); // Composite for filtered stats
            $table->index(['sex', 'created_at']); // Composite for gender-based charts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropIndex(['chats_receiver_id_index']);
            $table->dropIndex(['chats_read_at_index']);
            $table->dropIndex(['chats_receiver_id_read_at_index']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['patients_barangay_id_index']);
            $table->dropIndex(['patients_category_id_index']);
            $table->dropIndex(['patients_sex_index']);
            $table->dropIndex(['patients_created_at_index']);
            $table->dropIndex(['patients_barangay_id_created_at_index']);
            $table->dropIndex(['patients_sex_created_at_index']);
        });
    }
};
