<?php

namespace App\Services;

use App\Models\ReferenceNumber;

class GenerateReferenceNumber
{
    /**
     * Generate a unique reference number.
     *
     * @return string
     */
    public function generateReferenceNumber(string $type)
    {
        $prefixes = [
            'sale' => 'SAL',
            'purchase' => 'PUR',
        ];

        $prefix = $prefixes[$type] ?? strtoupper($type);
        $today = now()->toDateString(); // e.g., '2025-01-05'

        // Fetch or create the reference record for this type and date
        $reference = ReferenceNumber::firstOrCreate(['type' => $type, 'date' => $today], ['current_number' => 0, 'prefix' => $prefix]);

        // Increment the current number
        $reference->current_number++;
        $reference->save();

        // Return the formatted reference number
        return $reference->prefix . '-' . $reference->current_number; // e.g., "SAL-1" or "PUR-1"
    }
}
