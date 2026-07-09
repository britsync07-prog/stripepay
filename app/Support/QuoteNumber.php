<?php

namespace App\Support;

use App\Models\ConsultationRequest;

/**
 * Issues sequential quotation document numbers in the form `BDA-2026-001`
 * (prefix · issue year · zero-padded per-year sequence). The sequence restarts
 * each year. The number is only ever assigned once (when a quotation is first
 * built) so an existing quote keeps its number even if it is re-priced.
 */
final class QuoteNumber
{
    public const PREFIX = 'BDA';

    public const PADDING = 3;

    /** The next available number for the given year (defaults to the current year). */
    public static function next(?int $year = null): string
    {
        $year ??= (int) date('Y');
        $prefix = self::PREFIX.'-'.$year.'-';

        // Highest sequence issued this year so far; the suffix is fixed-width so
        // a plain string MAX orders correctly.
        $last = ConsultationRequest::query()
            ->where('quote_number', 'like', $prefix.'%')
            ->max('quote_number');

        $seq = $last ? ((int) substr($last, strlen($prefix)) + 1) : 1;

        return $prefix.str_pad((string) $seq, self::PADDING, '0', STR_PAD_LEFT);
    }
}
