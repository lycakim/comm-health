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
        Schema::table('patients', function (Blueprint $table) {
            // First, convert existing string age values to integers
            // Handle non-numeric values by converting to 0 or null
            DB::statement('UPDATE patients SET age = CASE 
                WHEN age IS NULL OR age = "" THEN NULL
                WHEN age REGEXP "^[0-9]+$" THEN CAST(age AS UNSIGNED)
                ELSE 0
            END');
            
            // Change column type to integer
            $table->integer('age')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('age')->nullable()->change();
        });
    }
};
