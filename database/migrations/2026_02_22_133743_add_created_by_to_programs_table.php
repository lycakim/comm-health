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
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('coordinator')->constrained('users')->nullOnDelete();
        });

        // Backfill: use coordinator as created_by for existing records where coordinator is set
        \DB::statement('UPDATE programs SET created_by = coordinator WHERE created_by IS NULL AND coordinator IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
    }
};
