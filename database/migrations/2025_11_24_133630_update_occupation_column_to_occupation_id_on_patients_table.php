<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            // Remove the old string column
            $table->dropColumn('occupation');
        });

        Schema::table('patients', function (Blueprint $table) {
            // Add the new FK column
            $table->foreignId('occupation_id')
                ->nullable()
                ->constrained('occupations')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('patients', function (Blueprint $table) {
            // Drop the foreign key + column
            $table->dropForeign(['occupation_id']);
            $table->dropColumn('occupation_id');
        });

        Schema::table('patients', function (Blueprint $table) {
            // Restore original string column
            $table->string('occupation')->nullable();
        });
    }
};