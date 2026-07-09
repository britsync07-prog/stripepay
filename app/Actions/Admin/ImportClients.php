<?php

namespace App\Actions\Admin;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Bulk-import clients from a CSV/XLSX. The header row names the columns
 * (name + email required; company/country/phone optional). Each row upserts a
 * client via {@see UpsertClient} (reused by email, unverified — see that action),
 * and the admin gets a created/updated/skipped summary. Capped to guard the DB.
 */
class ImportClients
{
    use AsAction;

    private const MAX_ROWS = 2000;

    public function asController(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        $rows = IOFactory::load($request->file('file')->getRealPath())
            ->getActiveSheet()
            ->toArray(null, true, false, false);

        if (count($rows) < 2) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'The file has no data rows.']);

            return back();
        }

        // Resolve columns from the header row (case-insensitive).
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);
        $col = fn (string $key) => array_search($key, $header, true);
        [$iEmail, $iName] = [$col('email'), $col('name')];

        if ($iEmail === false || $iName === false) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'The file needs at least "name" and "email" columns.']);

            return back();
        }

        [$iCompany, $iCountry, $iPhone] = [$col('company'), $col('country'), $col('phone')];
        $cell = fn (array $row, int|false $i) => $i !== false ? trim((string) ($row[$i] ?? '')) : null;

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $capped = false;

        foreach (array_slice($rows, 1) as $n => $row) {
            if ($n >= self::MAX_ROWS) {
                $capped = true;
                break;
            }

            $email = trim((string) ($row[$iEmail] ?? ''));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;

                continue;
            }

            $result = UpsertClient::run([
                'email' => $email,
                'name' => (string) $cell($row, $iName),
                'company' => $cell($row, $iCompany),
                'country' => $iCountry !== false ? substr((string) $cell($row, $iCountry), 0, 2) : null,
                'phone' => $cell($row, $iPhone),
            ]);

            $result['created'] ? $created++ : $updated++;
        }

        activity('consultation')
            ->causedBy($request->user())
            ->withProperties(['type' => 'clients_imported', 'created' => $created, 'updated' => $updated, 'skipped' => $skipped])
            ->log('Clients imported');

        $message = "Import complete — {$created} added, {$updated} updated"
            .($skipped ? ", {$skipped} skipped" : '')
            .($capped ? '. Only the first '.self::MAX_ROWS.' rows were processed' : '').'.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
