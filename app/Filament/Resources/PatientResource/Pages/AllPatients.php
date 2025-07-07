<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Models\Patient;
use App\Filament\Resources\PatientResource;
use App\Models\Barangay;
use Filament\Resources\Pages\Page;

class AllPatients extends Page
{
    protected static string $resource = PatientResource::class;

    protected static string $view = 'filament.resources.patient-resource.pages.all-patients';

    public $barangays;

    public $patients;

    public function mount()
    {
        $this->barangays = Barangay::withCount('patients')->get();
        $this->patients = Patient::count();
    }
}