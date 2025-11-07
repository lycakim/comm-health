<?php

namespace App\Services;

use App\Models\Location;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PDFGenerationService
{
    /**
     * Generate PDF report based on type and location
     *
     * @param string $reportType
     * @param int $barangayId
     * @return \Barryvdh\DomPDF\PDF
     * @throws \Exception
     */
    public function generateReport(string $reportType, int $barangayId, int $purokId): \Barryvdh\DomPDF\PDF
    {
        $barangay = $this->getBarangayWithRelations($barangayId);
        $purok = $this->getPurokWithRelations($purokId);

        if (!$barangay) {
            throw new \Exception('Barangay not found');
        }

        if (!$purok) {
            throw new \Exception('Purok not found');
        }

        $reportData = $this->getReportData($reportType, $barangayId, $purokId);

        return $this->generatePdf($reportType, $reportData, $barangay, $purok);
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
    private function getBarangayWithRelations(int $barangayId): ?Location
    {
        return Location::with([
            'parent',              // city/municipality
            'parent.parent'        // province
        ])
        ->where('type', 'barangay')
        ->find($barangayId);
    }
    
    /**
     * Get purok location (with hierarchy)
     */
    private function getPurokWithRelations(int $purokId): ?Location
    {
        return Location::with([
            'parent',              // barangay
            'parent.parent',        // city/municipality
            'parent.parent.parent' // province
        ])
        ->where('type', 'purok')
        ->find($purokId);
    }

    /**
     * Get report data based on report type
     */
    private function getReportData(string $reportType, int $barangayId, int $purokId): mixed
    {
        return match ($reportType) {
            '2022_family_household_profile_report' => $this->getFamilyHouseholdData($barangayId),
            'masterlist_of_person_with_disability' => $this->getPwdData($barangayId),
            'non_communicable_disease_masterlist_senior_citizen_pwds' => $this->getNcdData($barangayId),
            'expanded_program_immunization' => $this->getEpiData($barangayId),
            'immunization' => $this->getImmunizationData($barangayId),
            default => throw new \Exception("Invalid report type: {$reportType}")
        };
    }

    /**
     * Generate PDF based on report type
     */
    private function generatePdf(string $reportType, mixed $reportData, Location $barangay, Location $purok): \Barryvdh\DomPDF\PDF
    {
        return match ($reportType) {
            '2022_family_household_profile_report' => $this->generateFamilyHouseholdPdf($reportData, $barangay, $purok),
            'masterlist_of_person_with_disability' => $this->generatePwdPdf($reportData, $barangay, $purok),
            'non_communicable_disease_masterlist_senior_citizen_pwds' => $this->generateNcdPdf($reportData, $barangay, $purok),
            'expanded_program_immunization' => $this->generateEpiPdf($reportData, $barangay),
            'immunization' => $this->generateImmunizationPdf($reportData, $barangay),
            default => throw new \Exception("Invalid report type for PDF generation: {$reportType}")
        };
    }

    /* ==============================
       SAMPLE DATA RETRIEVAL METHODS
       ============================== */

    private function getFamilyHouseholdData(int $barangayId)
    {
        // Replace with actual query once models are ready
        return collect();
    }

    private function getPwdData(int $barangayId)
    {
        return collect();
    }

    private function getNcdData(int $barangayId)
    {
        return collect();
    }

    private function getEpiData(int $barangayId)
    {
        return collect();
    }

    private function getImmunizationData(int $barangayId)
    {
        return collect();
    }

    /* ==============================
       PDF GENERATION METHODS
       ============================== */

    private function generateFamilyHouseholdPdf($households, Location $barangay, Location $purok): \Barryvdh\DomPDF\PDF
    {
        logger($barangay);
        $html = view('pdf.family-household-profile', [
            'households' => $households,
            'barangay' => $barangay,
            'purok' => $purok,
            'city' => $barangay->parent?->name,
            'province' => $barangay->parent?->parent?->name,
            'date' => now()->format('F d, Y'),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    private function generatePwdPdf($persons, Location $barangay, Location $purok): \Barryvdh\DomPDF\PDF
    {
        $html = view('pdf.masterlist-of-pwd', [
            'records' => $persons,
            'barangay' => $barangay,
            'purok' => $purok,
            'city' => $barangay->parent?->name,
            'province' => $barangay->parent?->parent?->name,
            'date' => now()->format('F d, Y'),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    private function generateNcdPdf($persons, Location $barangay, Location $purok): \Barryvdh\DomPDF\PDF
    {
        $html = view('pdf.nc-disease-masterlist', [
            'persons' => $persons,
            'purok' => $purok,
            'barangay' => $barangay,
            'city' => $barangay->parent?->name,
            'province' => $barangay->parent?->parent?->name,
            'date' => now()->format('F d, Y'),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    private function generateEpiPdf($persons, Location $barangay): \Barryvdh\DomPDF\PDF
    {
        $html = view('pdf.expanded-program-immunization', [
            'persons' => $persons,
            'barangay' => $barangay,
            'city' => $barangay->parent?->name,
            'province' => $barangay->parent?->parent?->name,
            'date' => now()->format('F d, Y'),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    private function generateImmunizationPdf($immunizations, Location $barangay): \Barryvdh\DomPDF\PDF
    {
        $html = view('pdf.immunization', [
            'immunizations' => $immunizations,
            'barangay' => $barangay,
            'city' => $barangay->parent?->name,
            'province' => $barangay->parent?->parent?->name,
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
    public function logReportGeneration(string $reportType, int $barangayId, bool $success): void
    {
        $status = $success ? 'success' : 'failed';

        Log::info("PDF Report Generation [{$status}]", [
            'report_type' => $reportType,
            'barangay_id' => $barangayId,
            'generated_at' => now()->toDateTimeString(),
            'user_id' => Auth::id(),
        ]);
    }
}