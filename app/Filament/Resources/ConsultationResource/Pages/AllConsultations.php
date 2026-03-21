<?php

namespace App\Filament\Resources\ConsultationResource\Pages;

use App\Models\Barangay;
use App\Models\Consultation;
use Filament\Resources\Pages\Page;
use App\Filament\Resources\ConsultationResource;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class AllConsultations extends Page
{
    use HasDashboardBreadcrumb;

    protected static string $resource = ConsultationResource::class;

    protected static string $view = 'filament.resources.consultation-resource.pages.all-consultations';

    public $barangays;

    public $consultations;

    public function mount()
    {
        $this->barangays = Barangay::withCount(['consultations', 'patients'])->get();
        $this->consultations = Consultation::count();
    }
}