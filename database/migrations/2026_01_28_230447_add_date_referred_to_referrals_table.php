<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dateTime('date_referred')->nullable()->after('user_id');
        });
        
        // Set existing records to use created_at as date_referred
        DB::statement('UPDATE referrals SET date_referred = created_at WHERE date_referred IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn('date_referred');
        });
    }
};
