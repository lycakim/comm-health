<?php

namespace App\Services;

/**
 * Simplifies technical error messages into plain, elderly-friendly language
 * for bulk resident/patient import.
 */
class ImportErrorSimplifier
{
    /**
     * Convert a technical error message into a simple, actionable one.
     */
    public static function simplify(string $message, ?int $row = null): string
    {
        $msg = trim($message);
        $prefix = $row ? "Row {$row}: " : '';

        // Remove "Validation failed: " prefix to parse the rest
        $msg = preg_replace('/^Validation failed:\s*/i', '', $msg);

        // Required fields
        if (preg_match('/first[\s_]*name.*required/i', $msg)) {
            return $prefix . 'Please enter the first name.';
        }
        if (preg_match('/middle[\s_]*name.*required/i', $msg)) {
            return $prefix . 'Please enter the middle name.';
        }
        if (preg_match('/last[\s_]*name.*required/i', $msg)) {
            return $prefix . 'Please enter the last name.';
        }
        if (preg_match('/birth[\s_]*date.*required/i', $msg)) {
            return $prefix . 'Please enter the birth date.';
        }
        if (preg_match('/sex.*required/i', $msg) || preg_match('/gender.*required/i', $msg)) {
            return $prefix . 'Please enter Male or Female for gender.';
        }
        if (preg_match('/civil[\s_]*status.*required/i', $msg)) {
            return $prefix . 'Please enter the civil status (e.g., Single, Married).';
        }

        // Birth date format
        if (preg_match('/birth[\s_]*date.*(valid|format)/i', $msg) || preg_match('/invalid birth date/i', $msg)) {
            return $prefix . 'Please check the birth date. Use Year-Month-Day (e.g., 1990-01-15).';
        }
        if (preg_match('/before_or_equal|cannot be in the future/i', $msg)) {
            return $prefix . 'Birth date cannot be in the future.';
        }

        // Sex/gender values
        if (preg_match('/sex.*(male|female)/i', $msg) && preg_match('/must be|invalid/i', $msg)) {
            return $prefix . 'Please enter Male or Female for gender.';
        }

        // Name format (regex validation)
        if (preg_match('/first[\s_]*name.*format|regex/i', $msg)) {
            return $prefix . 'First name: use only letters, spaces, and hyphens.';
        }
        if (preg_match('/middle[\s_]*name.*format|regex/i', $msg)) {
            return $prefix . 'Middle name: use only letters, spaces, and hyphens.';
        }
        if (preg_match('/last[\s_]*name.*format|regex/i', $msg)) {
            return $prefix . 'Last name: use only letters, spaces, and hyphens.';
        }
        if (preg_match('/civil[\s_]*status.*format|regex/i', $msg)) {
            return $prefix . 'Civil status: use only letters, spaces, and hyphens.';
        }

        // Duplicate / already exists
        if (preg_match('/already exists|duplicate|same name/i', $msg)) {
            return $prefix . 'This resident is already in the list.';
        }

        // Barangay / account setup
        if (preg_match('/barangay|assigned|assignment/i', $msg)) {
            return $prefix . 'Your account needs to be set up. Please contact your administrator.';
        }

        // File errors
        if (preg_match('/file not found|could not open/i', $msg)) {
            return 'The file could not be opened. Please try again.';
        }
        if (preg_match('/unsupported file|\.csv|\.xlsx/i', $msg)) {
            return 'Please use an Excel (.xlsx) or CSV file.';
        }

        // Numeric fields
        if (preg_match('/sugar[\s_]*level|height|weight.*(numeric|number)/i', $msg)) {
            return $prefix . 'Please enter a valid number.';
        }

        // Contact number
        if (preg_match('/contact.*(format|invalid|number)/i', $msg)) {
            return $prefix . 'Please check the contact number. Use format: 09XXXXXXXXX.';
        }

        // SQL / database errors (hide technical details)
        if (preg_match('/SQLSTATE|Integrity constraint|Duplicate entry/i', $msg)) {
            return $prefix . 'This resident may already exist. Please check your list.';
        }

        // Generic validation
        if (preg_match('/validation|invalid|must be/i', $msg)) {
            return $prefix . 'Please check the information and fix any mistakes.';
        }

        // Unknown - keep it short and helpful
        return $prefix . 'Please check the information and try again.';
    }

    /**
     * Simplify error array for display (used when mapping allErrors).
     */
    public static function simplifyForDisplay(array $error): array
    {
        $row = $error['row'] ?? null;
        $message = $error['message'] ?? 'Unknown error';

        return array_merge($error, [
            'message' => self::simplify($message, $row),
        ]);
    }
}
