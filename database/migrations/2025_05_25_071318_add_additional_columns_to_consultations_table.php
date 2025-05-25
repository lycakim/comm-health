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
        Schema::table('consultations', function (Blueprint $table) {
            // Maternal Information for Husband/Partner
            $table->string('husband_first_name')->nullable()->after('mother_middle_name');
            $table->string('husband_last_name')->nullable()->after('husband_first_name');
            $table->string('husband_middle_name')->nullable()->after('husband_last_name');
            $table->string('husband_contact_no')->nullable()->after('husband_middle_name');
            $table->string('husband_occupation')->nullable()->after('husband_contact_no');
            $table->boolean('husband_philhealth')->nullable()->after('husband_occupation');
            $table->boolean('husband_member_of_4ps')->nullable()->after('husband_philhealth');
            $table->boolean('husband_nhts_member')->nullable()->after('husband_member_of_4ps');
            
            // Maternal Information for Mother
            $table->date('mother_lmp_date')->nullable()->after('husband_nhts_member');
            $table->string('delivery_address')->nullable()->after('mother_lmp_date');
            $table->date('mother_edc_date')->nullable()->after('delivery_address');
            $table->boolean('mother_and_child_book')->nullable()->after('mother_edc_date');
            $table->unsignedTinyInteger('number_of_pregnancies')->nullable()->after('mother_and_child_book');
            $table->unsignedTinyInteger('successful_deliveries')->nullable()->after('number_of_pregnancies');
            $table->unsignedTinyInteger('pregnancy_losses')->nullable()->after('successful_deliveries');
            
            // GPA (Laboratory) Information
            $table->string('laboratory_exam')->nullable()->after('pregnancy_losses');
            $table->string('first_hgb')->nullable()->after('laboratory_exam');
            $table->string('blood_type')->nullable()->after('first_hgb');
            $table->string('iron_imms')->nullable()->after('blood_type');
            $table->string('iodized_salt')->nullable()->after('iron_imms');
            $table->string('second_hgb')->nullable()->after('iodized_salt');
            $table->text('ua')->nullable()->after('second_hgb');
            
            // Laboratory/Immunization Dates
            $table->date('imm_received_dates')->nullable()->after('ua');
            $table->date('tt1_date')->nullable()->after('imm_received_dates');
            $table->date('tt2_date')->nullable()->after('tt1_date');
            $table->date('tt3_date')->nullable()->after('tt2_date');
            $table->date('tt4_date')->nullable()->after('tt3_date');
            $table->date('tt5_date')->nullable()->after('tt4_date');
            $table->date('tt_imm')->nullable()->after('tt5_date');
            
            // Other Maternal Labs
            $table->string('blood_pressure')->nullable()->after('tt_imm');
            $table->decimal('weight', 5, 2)->nullable()->after('blood_pressure');
            $table->decimal('height', 5, 2)->nullable()->after('weight');
            $table->decimal('fundal_height', 5, 2)->nullable()->after('height');
            $table->decimal('fetal_hydronephrosis', 5, 2)->nullable()->after('fundal_height');
            $table->decimal('age_of_gestation', 5, 2)->nullable()->after('fetal_hydronephrosis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['purok_id']);
            
            // Drop all added columns
            $table->dropColumn([
                'purok_id',
                'husband_first_name',
                'husband_last_name',
                'husband_middle_name',
                'husband_contact_no',
                'husband_occupation',
                'husband_philhealth',
                'husband_member_of_4ps',
                'husband_nhts_member',
                'mother_lmp_date',
                'delivery_address',
                'mother_edc_date',
                'mother_and_child_book',
                'number_of_pregnancies',
                'successful_deliveries',
                'pregnancy_losses',
                'laboratory_exam',
                'first_hgb',
                'blood_type',
                'iron_imms',
                'iodized_salt',
                'second_hgb',
                'ua',
                'imm_received_dates',
                'tt1_date',
                'tt2_date',
                'tt3_date',
                'tt4_date',
                'tt5_date',
                'tt_imm',
                'blood_pressure',
                'weight',
                'height',
                'fundal_height',
                'fetal_hydronephrosis',
                'age_of_gestation',
            ]);
        });
    }
};