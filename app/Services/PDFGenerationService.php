<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Program;
use App\Models\Referral;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
        // Eager load the user relationship to avoid N+1 queries
        $referral->load('user');
        
        // Build data array for PDF template
        $referralDate = $referral->date_referred ?? $referral->created_at ?? now();
        $data = [
            'referred_to' => $referral->referred_to ?? 'CARMEN MHO',
            'referred_address' => 'ISING CARMEN',
            'date' => $referralDate->format('M d, Y'),
            'time' => $referralDate->format('H:i A'),
            'chief_complaints' => $referral->chief_complaint ?? $consultation->chief_complaint ?? 'N/A',
            'medical_history' => $consultation->notes ?? 'N/A',
            'referral_by' => $referral->user->name ?? '',
            'license_no' => '-',
            // Recipient information (from referral if available)
            'recipient_name' => $referral->receiving_provider_name ?? '',
            'recipient_age' => '',
            'recipient_sex' => '',
            'recipient_date' => $referral->date_completed ? $referral->date_completed->format('M d, Y') : '',
            'recipient_diagnosis' => '',
            'recipient_medical_history' => '',
            'recommendation' => $referral->receiving_provider_notes ?? '',
            'recipient_signature' => '',
            'recipient_hospital' => '',
            'recipient_contact' => '',
            // Acknowledgement
            'ack_patient' => $patient->first_name . ' ' . $patient->last_name,
            'ack_hospital' => $referral->referred_to ?? 'CARMEN MHO',
            'ack_date' => $referralDate->format('M d, Y'),
        ];
        
        $html = view('pdf.referral-form', [
            'referral' => $referral,
            'patient' => $patient,
            'consultation' => $consultation,
            'data' => $data,
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
        $barangayName = $barangay ? $barangay->name : 'All Barangays';
        $province = config('app.province', 'DAVAO DEL NORTE');
        $municipality = config('app.municipality', 'CARMEN');
        $dateTime = now()->format('F d, Y h:i A');

        $html = view('pdf.patient-list', [
            'patients' => $patients,
            'title' => $title,
            'barangay' => $barangay,
            'barangayName' => $barangayName,
            'province' => $province,
            'municipality' => $municipality,
            'date' => now()->format('F d, Y'),
            'dateTime' => $dateTime,
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

    /**
     * Generate PDF for consultation list reports
     */
    public function generateConsultationListPdf($consultations, string $title, $barangay = null): \Barryvdh\DomPDF\PDF
    {
        $barangayName = $barangay ? $barangay->name : 'All Barangays';
        $province = config('app.province', 'DAVAO DEL NORTE');
        $municipality = config('app.municipality', 'CARMEN');
        $dateTime = now()->format('F d, Y h:i A');

        $html = view('pdf.consultation-list', [
            'consultations' => $consultations,
            'title' => $title,
            'barangay' => $barangay,
            'barangayName' => $barangayName,
            'province' => $province,
            'municipality' => $municipality,
            'date' => now()->format('F d, Y'),
            'dateTime' => $dateTime,
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    /**
     * Generate PDF for program list reports
     */
    public function generateProgramListPdf($programs, string $title, $barangay = null): \Barryvdh\DomPDF\PDF
    {
        $barangayName = $barangay ? $barangay->name : 'All Barangays';
        $province = config('app.province', 'DAVAO DEL NORTE');
        $municipality = config('app.municipality', 'CARMEN');
        $dateTime = now()->format('F d, Y h:i A');

        $html = view('pdf.program-list', [
            'programs' => $programs,
            'title' => $title,
            'barangay' => $barangay,
            'barangayName' => $barangayName,
            'province' => $province,
            'municipality' => $municipality,
            'date' => now()->format('F d, Y'),
            'dateTime' => $dateTime,
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    /**
     * Generate PDF for referral list reports
     */
    public function generateReferralListPdf($referrals, string $title, $barangay = null): \Barryvdh\DomPDF\PDF
    {
        $barangayName = $barangay ? $barangay->name : 'All Barangays';
        $province = config('app.province', 'DAVAO DEL NORTE');
        $municipality = config('app.municipality', 'CARMEN');
        $dateTime = now()->format('F d, Y h:i A');

        $html = view('pdf.referral-list', [
            'referrals' => $referrals,
            'title' => $title,
            'reportTitle' => $title,
            'barangay' => $barangay,
            'barangayName' => $barangayName,
            'province' => $province,
            'municipality' => $municipality,
            'date' => now()->format('F d, Y'),
            'dateTime' => $dateTime,
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    /**
     * Generate PDF for report data (used by Reports page)
     */
    public function generateReportDataPdf(array $reportData, string $reportType, $barangay = null): \Barryvdh\DomPDF\PDF
    {
        $templateMap = [
            'patient-profiling' => 'pdf.reports.patient-profiling',
            'resident-profiling' => 'pdf.reports.patient-profiling',
            'maternal-child' => 'pdf.reports.maternal-child',
            'senior-citizens' => 'pdf.reports.senior-citizens',
            'family-planning' => 'pdf.reports.family-planning',
            'morbidity-mortality' => 'pdf.reports.morbidity-mortality',
            'family-profile-consolidation' => 'pdf.reports.family-profile-consolidation',
        ];

        $template = $templateMap[$reportType] ?? 'pdf.reports.patient-profiling';

        // Get report title
        $reportTitles = [
            'patient-profiling' => 'Patient Profiling Report',
            'resident-profiling' => 'Patient Profiling Report',
            'maternal-child' => 'Maternal and Child Report',
            'senior-citizens' => 'Senior Citizens Health Status Report',
            'family-planning' => 'Family Planning Usage Report',
            'morbidity-mortality' => 'Morbidity and Mortality Report',
            'family-profile-consolidation' => 'Family Profile Consolidation',
        ];

        $reportTitle = $reportTitles[$reportType] ?? ucwords(str_replace('-', ' ', $reportType)) . ' Report';
        $barangayName = $barangay ? $barangay->name : 'All Barangays';
        $dateTime = now()->format('F d, Y h:i A');

        $viewData = [
            'date' => now()->format('F d, Y'),
            'dateTime' => $dateTime,
            'province' => config('app.province', 'DAVAO DEL NORTE'),
            'municipality' => config('app.municipality', 'CARMEN'),
            'barangayName' => $barangayName,
            'reportTitle' => $reportTitle,
        ];

        if ($reportType === 'family-profile-consolidation') {
            $viewData = array_merge($viewData, [
                'summary' => $reportData['summary'] ?? [],
                'ageGroupsLeft' => $reportData['ageGroupsLeft'] ?? [],
                'ageGroupsRight' => $reportData['ageGroupsRight'] ?? [],
                'totalLeft' => $reportData['totalLeft'] ?? ['male' => 0, 'female' => 0, 'total' => 0],
                'totalRight' => $reportData['totalRight'] ?? ['male' => 0, 'female' => 0, 'total' => 0],
                'totalRecords' => ($reportData['summary']['totalPopulation'] ?? 0),
            ]);
        } else {
            $viewData = array_merge($viewData, [
                'headers' => $reportData['headers'] ?? [],
                'rows' => $reportData['rows'] ?? [],
                'totalRecords' => count($reportData['rows'] ?? []),
            ]);
        }

        $html = view($template, $viewData)->render();

        return Pdf::loadHTML($html)
            ->setPaper('legal', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    /**
     * Save report to storage and return file metadata
     *
     * @param string $content File content (PDF output or CSV content)
     * @param string $reportType Report type identifier
     * @param string $format File format ('pdf' or 'csv')
     * @param string $filename Original filename
     * @return array File metadata including path, filename, and size
     */
    public function saveReportToStorage(string $content, string $reportType, string $format, string $filename): array
    {
        // Create directory structure: reports/{report_type}/{year}/{month}/
        $year = now()->format('Y');
        $month = now()->format('m');
        $directory = "reports/{$reportType}/{$year}/{$month}";

        // Ensure directory exists
        Storage::disk('public')->makeDirectory($directory);

        // Store the file
        $filePath = "{$directory}/{$filename}";
        Storage::disk('public')->put($filePath, $content);

        // Get file size
        $fileSize = Storage::disk('public')->size($filePath);

        return [
            'file_path' => $filePath,
            'file_name' => $filename,
            'file_size' => $fileSize,
        ];
    }
}