<?php

namespace App\Services;

class PatientFormOptionsServices
{
    public static function getPatientHealthStatuses()
    {
        return [
            'Hypertension' => 'Hypertension',
            'Diabetes' => 'Diabetes',
            'Melitus' => 'Melitus',
            'Tuberculosis' => 'Tubercolosis',
            'Cancer' => 'Cancer',
            'Mental Illness' => 'Mental Illness',
            'Person with Disabilities' => 'Person with Disabilities',
            'Smoker' => 'Smoker',
            'Person who uses drugs' => 'Person who uses drugs',
        ];
    }

    public static function getPatientWaterSupplySources()
    {
        return [
            'Community Water System' => 'Community Water System',
            'Developed Spring' => 'Developed Spring',
            'Protected Well' => 'Protected Well',
            'Truck/Tanker Peddler' => 'Truck/Tanker Peddler',
            'Bottled Water' => 'Bottled Water',
            'Undeveloped Spring' => 'Undeveloped Spring',
            'Unprotected Well' => 'Unprotected Well',
            'Rain Water' => 'Rain Water',
            'River/Stream/Dam' => 'River/Stream/Dam',
        ];
    }

    public static function getPatientToiletTypes()
    {
        return [
            'Flush Toilet' => 'Flush Toilet',
            'Closed Plit Privy' => 'Closed Plit Privy',
            'Opened Plit Privy' => 'Opened Plit Privy',
            'Communal Toilet' => 'Communal Toilet',
            'Drop Overhung' => 'Drop Overhung',
            'Field/Body of Water' => 'Field/Body of Water',
            'No Toilet' => 'No Toilet',
        ];
    }

    public static function getPatientDrainageDisposals()
    {
        return [
            'With Blind Drainage' => 'With Blind Drainage',
            'Without Drainage' => 'Without Drainage',
            'Open Canal' => 'Open Canal',
            'Other' => 'Other',
        ];
    }

    public static function getPatientLivestock()
    {
        return [
            'None' => 'None',
            'Dog' => 'Dog',
            'Cat' => 'Cat',
            'Chicken' => 'Chicken',
            'Rooster' => 'Rooster',
            'Duck' => 'Duck',
            'Goat' => 'Goat',
            'Cow' => 'Cow',
            'Carabao' => 'Carabao',
            'Horse' => 'Horse',
            'Pig' => 'Pig',
            'Turkey' => 'Turkey',
            'Rabbit' => 'Rabbit',
            'Sheep' => 'Sheep',
            'Other' => 'Other',
        ];
    }

    public static function getMedicationMaintenance()
    {
        return [
            'Losartan 50 mg' => 'losartan_50_mg',
            'Losartan 100 mg' => 'losartan_100_mg',
            'Amlodipine 50 mg' => 'amlodipine_50_mg',
            'Amlodipine 10 mg' => 'amlodipine_10_mg',
            'Metoprolol 50 mg' => 'metoprolol_50_mg',
            'Metoprolol 100 mg' => 'metoprolol_100_mg',
            'Gliclazide 30 mg' => 'gliclazide_30_mg',
            'Gliclazide 80 mg' => 'gliclazide_80_mg',
            'Metformin 500 mg' => 'metformin_500_mg',
            'Simvastation 20 mg' => 'simvastation_20_mg',
            'Insulin' => 'insulin', 
        ];
    }

    public static function getPatientRelationships()
    {
        return [
            'spouse' => 'Spouse',
            'child' => 'Child',
            'co-wife' => 'Co-wife',
            'son-in-law' => 'Son-in-law',
            'daughter-in-law' => 'Daughter-in-law',
            'grandparent' => 'Grandparent',
            'grandchild' => 'Grandchild',
            'other' => 'Other',
        ];
    }

    public static function getPatientStatus()
    {
        return [
            'single' => 'Single',
            'married' => 'Married',
            'widowed' => 'Widowed',
            'separated' => 'Separated',
            'live-in' => 'Live-in',
        ];
    }

    public static function getPatientEducationalAttainment()
    {
        return [
            'none' => 'None',
            'elementary_level' => 'Elementary Level',
            'elementary_graduate' => 'Elementary Graduate',
            'highschool_level' => 'Highschool Level',
            'highschool_graduate' => 'Highschool Graduate',
            'college_level' => 'College Level',
            'college_graduate' => 'College Graduate',
            'post_graduate' => 'Post Graduate',
        ];
    }

    public static function getPatientOccupation()
    {
        return [
            'Government Employee' => 'government_employee',
            'Private Employee' => 'private_employed',
            'Self-employed' => 'self_employed',
            'Retired' => 'retired',
            'Unemployed' => 'unemployed',
            'Farmer' => 'farmer',
            'Fisherman' => 'fisherman',
            'Laborer (Construction)' => 'laborer_construction',
            'Carpenter' => 'carpenter',
            'Banana Peeler' => 'banana_peeler',
            'Vendor' => 'vendor',
            'Driver' => 'driver',
            'Housekeeper' => 'housekeeper',
            'None' => 'None',
        ];
    }

    public static function getFamilyPlanningMethods()
    {
        return [
            'Bilateral tubal ligation (BTL)' => 'Bilateral tubal ligation (BTL)', 
            'Vasectomy (VAS)' => 'Vasectomy (VAS)',
            'Pills (P)' => 'Pills (P)',
            'Condoms (C)' => 'Condoms (C)',
            'Injectables (INJ)' => 'Injectables (INJ)',
            'Intra-Utering Device (IUD)' => 'Intra-Utering Device (IUD)',
            'Standard Type Method (SDM)' => 'Standard Type Method (SDM)',
            'Basal Body Temp (BBT)' => 'Basal Body Temp (BBT)',
            'Sympto thermal method (STM)' => 'Sympto thermal method (STM)',
            'Lactational Method (LAM)' => 'Lactational Method (LAM)', 
            'Other' => 'Other',
            'None' => 'None',
        ];
    }

    public static function getChildHealthStatus()
    {
        return [
            'normal' => 'Normal',
            'underweight' => 'Underweight',
            'stunting' => 'Stunting',
            'waisting' => 'Waisting',
            'overweight' => 'Overweight',
        ];
    }

    public static function getFamilyMonthlyIncome()
    {
        return [
            '1k_to_4k' => '1000 to 4000',
            '5k_to_9k' => '5000 to 9000',
            '10k_to_14k' => '10000 to 14000',
            '15k_to_19k' => '15000 to 19000',
            '20k_to_24k' => '20000 to 24000',
            '25k_to_29k' => '25000 to 29000',
            'other' => 'Other',
        ];
    }

    public static function getPatientHouseTypes()
    {
        return [
            'barong_barong' => 'Barong-Barong',
            'nipa_bamboo' => 'Nipa/Bamboo',
            'wooden_house' => 'Wooden House',
            'semi_concrete_house' => 'Semi-Concrete House',
            'concrete_house' => 'Concrete House',
        ];
    }
}