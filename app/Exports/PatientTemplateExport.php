<?php

namespace App\Exports;

use App\Services\PatientImportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientTemplateExport
{
    /**
     * Return a streamed download response for the template (vanilla PHP CSV, no package).
     */
    public static function download(string $filename): StreamedResponse
    {
        $headers = PatientImportService::getTemplateHeaders();
        $sampleRow = [
            'Juan',
            'Manuel',
            'Dela Cruz',
            'Jr.',
            '1990-01-15',
            'male',
            'Single',
            '09123456789',
            'Sample Purok',
            '',
            'Farmer',
            '120/80',
            '90',
            '170',
            '70',
            'Manila',
            'College Graduate',
        ];

        $response = new StreamedResponse(function () use ($headers, $sampleRow) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, $headers);
            fputcsv($out, $sampleRow);
            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
