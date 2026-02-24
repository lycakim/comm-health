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
            'Losartan 50 mg' => 'Losartan 50 mg',
            'Losartan 100 mg' => 'Losartan 100 mg',
            'Amlodipine 50 mg' => 'Amlodipine 50 mg',
            'Amlodipine 10 mg' => 'Amlodipine 10 mg',
            'Metoprolol 50 mg' => 'Metoprolol 50 mg',
            'Metoprolol 100 mg' => 'Metoprolol 100 mg',
            'Gliclazide 30 mg' => 'Gliclazide 30 mg',
            'Gliclazide 80 mg' => 'Gliclazide 80 mg',
            'Metformin 500 mg' => 'Metformin 500 mg',
            'Simvastation 20 mg' => 'Simvastation 20 mg',
            'Insulin' => 'Insulin',
        ];
    }

    public static function getPatientRelationships()
    {
        return [
            'Household-Head' => 'Household Head',
            'Spouse' => 'Spouse',
            'Child' => 'Child',
            'Co-Wife' => 'Co-wife',
            'Son-in-Law' => 'Son-in-law',
            'Daughter-in-Law' => 'Daughter-in-law',
            'Grandparent' => 'Grandparent',
            'Grandchild' => 'Grandchild',
            // 'Other' => 'Other',
        ];
    }

    public static function getPatientStatus()
    {
        return [
            'Single' => 'Single',
            'Married' => 'Married',
            'Widowed' => 'Widowed',
            'Separated' => 'Separated',
            'Live-in' => 'Live-in',
        ];
    }

    public static function getPatientEducationalAttainment()
    {
        return [
            'None' => 'None',
            'Elementary Level' => 'Elementary Level',
            'Elementary Graduate' => 'Elementary Graduate',
            'Highschool Level' => 'Highschool Level',
            'Highschool Graduate' => 'Highschool Graduate',
            'College Level' => 'College Level',
            'College Graduate' => 'College Graduate',
            'Post Graduate' => 'Post Graduate',
        ];
    }

    public static function getPatientOccupation()
    {
        return [
            'Government Employee' => 'Government Employee',
            'Private Employee' => 'Private Employee',
            'Self-employed' => 'Self-employed',
            'Retired' => 'Retired',
            'Unemployed' => 'Unemployed',
            'Farmer' => 'Farmer',
            'Fisherman' => 'Fisherman',
            'Laborer (Construction)' => 'Laborer (Construction)',
            'Carpenter' => 'Carpenter',
            'Banana Peeler' => 'Banana Peeler',
            'Vendor' => 'Vendor',
            'Driver' => 'Driver',
            'Housekeeper' => 'Housekeeper',
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

    public static function getFamilyMonthlyIncome()
    {
        return [
            '₱1000 to ₱4000' => '₱1000 to ₱4000',
            '₱5000 to ₱9000' => '₱5000 to ₱9000',
            '₱10000 to ₱14000' => '₱10000 to ₱14000',
            '₱15000 to ₱19000' => '₱15000 to ₱19000',
            '₱20000 to ₱24000' => '₱20000 to ₱24000',
            '₱25000 to ₱29000' => '₱25000 to ₱29000',
            'Other' => 'Other',
        ];
    }

    public static function getPatientHouseTypes()
    {
        return [
            'Barong Barong' => 'Barong-Barong',
            'Nipa/Bamboo' => 'Nipa/Bamboo',
            'Wooden House' => 'Wooden House',
            'Semi-Concrete House' => 'Semi-Concrete House',
            'Concrete House' => 'Concrete House',
        ];
    }
}