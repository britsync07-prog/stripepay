<?php

namespace App\Actions\Admin;

use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Typeahead search over CRM contacts, so an admin adding a lead can pick an
 * existing person (prefilling their details) instead of re-typing them. Returns
 * JSON — consumed by the add-lead dialog's async search, not an Inertia page.
 */
class SearchContacts
{
    use AsAction;

    public function asController(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['contacts' => []]);
        }

        $contacts = Contact::query()
            ->where(fn ($query) => $query
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('company', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(10)
            ->get(['uuid', 'name', 'email', 'company', 'country']);

        return response()->json(['contacts' => $contacts]);
    }
}
