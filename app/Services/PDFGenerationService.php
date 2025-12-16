<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Referral;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PDFGenerationService
{
    /**
     * Generate PDF report based on type and location
     *
     * @param string $reportType
     * @param int|null $barangayId
     * @param int|null $purokId
     * @return \Barryvdh\DomPDF\PDF
     * @throws \Exception
     */
    public function generateReport(string $reportType): \Barryvdh\DomPDF\PDF
    {
        // NCD report doesn't require location
        if ($reportType === 'non_communicable_disease_masterlist_senior_citizen_pwds') {
            $reportData = $this->getReportData($reportType, null, null);
            return $this->generatePdf($reportType, $reportData, null, null);
        }

        $reportData = $this->getReportData($reportType);

        return $this->generatePdf($reportType, $reportData);
    }

    /**
     * Get filename for the report
     */
    public function getReportFilename(string $reportType): string
    {
        $sanitized = str_replace(' ', '_', strtolower($reportType));
        return "{$sanitized}_" . now()->format('Y-m-d_His') . '.pdf';
    }

    /**
     * Get barangay location (with hierarchy)
     */
    // private function getBarangayWithRelations(int $barangayId): ?Location
    // {
    //     return Location::with([
    //         'parent',              // city/municipality
    //         'parent.parent'        // province
    //     ])
    //     ->where('type', 'barangay')
    //     ->find($barangayId);
    // }
    
    /**
     * Get purok location (with hierarchy)
     */
    // private function getPurokWithRelations(int $purokId): ?Location
    // {
    //     return Location::with([
    //         'parent',              // barangay
    //         'parent.parent',        // city/municipality
    //         'parent.parent.parent' // province
    //     ])
    //     ->where('type', 'purok')
    //     ->find($purokId);
    // }

    /**
     * Get report data based on report type
     */
    private function getReportData(string $reportType): mixed
    {
        return match ($reportType) {
            '2022_family_household_profile_report' => $this->getFamilyHouseholdData(),
            'masterlist_of_person_with_disability' => $this->getPwdData(),
            'non_communicable_disease_masterlist_senior_citizen_pwds' => $this->getNcdData(),
            'expanded_program_immunization' => $this->getEpiData(),
            'immunization' => $this->getImmunizationData(),
            default => throw new \Exception("Invalid report type: {$reportType}")
        };
    }

    /**
     * Generate PDF based on report type
     */
    private function generatePdf(string $reportType, mixed $reportData): \Barryvdh\DomPDF\PDF
    {
        return match ($reportType) {
            '2022_family_household_profile_report' => $this->generateFamilyHouseholdPdf($reportData),
            'masterlist_of_person_with_disability' => $this->generatePwdPdf($reportData),
            'non_communicable_disease_masterlist_senior_citizen_pwds' => $this->generateNcdPdf($reportData),
            'expanded_program_immunization' => $this->generateEpiPdf($reportData),
            'immunization' => $this->generateImmunizationPdf($reportData),
            default => throw new \Exception("Invalid report type for PDF generation: {$reportType}")
        };
    }

    /* ==============================
       SAMPLE DATA RETRIEVAL METHODS
       ============================== */

    private function getFamilyHouseholdData()
    {
        // Replace with actual query once models are ready
        return collect();
    }

    private function getPwdData()
    {
        return collect();
    }

    private function getNcdData()
    {
        // NCD data retrieval without location filter
        return collect();
    }

    private function getEpiData()
    {
        return collect();
    }

    private function getImmunizationData()
    {
        return collect();
    }

    /* ==============================
       PDF GENERATION METHODS
       ============================== */

    private function generateFamilyHouseholdPdf($households): \Barryvdh\DomPDF\PDF
    {
        $html = view('pdf.family-household-profile', [
            'households' => $households,
            'barangay' => null,
            'purok' => null,
            'city' => null,
            'province' => null,
            'date' => now()->format('F d, Y'),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    private function generatePwdPdf($persons): \Barryvdh\DomPDF\PDF
    {
        $html = view('pdf.masterlist-of-pwd', [
            'records' => $persons,
            'barangay' => null,
            'purok' => null,
            'city' => null,
            'province' => null,
            'date' => now()->format('F d, Y'),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    private function generateNcdPdf($persons): \Barryvdh\DomPDF\PDF
    {
        $html = view('pdf.nc-disease-masterlist', [
            'persons' => $persons,
            'barangay' => null,
            'purok' => null,
            'date' => now()->format('F d, Y'),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    private function generateEpiPdf($persons): \Barryvdh\DomPDF\PDF
    {
        $html = view('pdf.expanded-program-immunization', [
            'persons' => $persons,
            'barangay' => null,
            'city' => null,
            'province' => null,
            'date' => now()->format('F d, Y'),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    private function generateImmunizationPdf($immunizations): \Barryvdh\DomPDF\PDF
    {
        $html = view('pdf.immunization', [
            'immunizations' => $immunizations,
            'barangay' => null,
            'city' => null,
            'province' => null,
            'date' => now()->format('F d, Y'),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    /* ==============================
       LOGGING
       ============================== */
    public function logReportGeneration(string $reportType, bool $success): void
    {
        $status = $success ? 'success' : 'failed';

        Log::info("PDF Report Generation [{$status}]", [
            'report_type' => $reportType,
            'barangay_id' => null,
            'generated_at' => now()->toDateTimeString(),
            'user_id' => Auth::id(),
        ]);
    }

    public function generateReferralPdf(Referral $referral, Patient $patient, Consultation $consultation): \Barryvdh\DomPDF\PDF
    {
        $html = view('pdf.referral-form', [
            'referral' => $referral,
            'patient' => $patient,
            'consultation' => $consultation,
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    /**
     * Generate PDF for patient list reports
     */
    public function generatePatientListPdf($patients, string $title, $barangay = null): \Barryvdh\DomPDF\PDF
    {
        $html = view('pdf.patient-list', [
            'patients' => $patients,
            'title' => $title,
            'barangay' => $barangay,
            'date' => now()->format('F d, Y'),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    /**
     * Generate PDF for consultation reports
     */
    public function generateConsultationReportPdf(Consultation $consultation): \Barryvdh\DomPDF\PDF
    {
        $html = view('pdf.consultation-report', [
            'consultation' => $consultation,
            'date' => now()->format('F d, Y'),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }
}