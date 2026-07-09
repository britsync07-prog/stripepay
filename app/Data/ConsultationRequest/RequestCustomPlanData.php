<?php

namespace App\Data\ConsultationRequest;

use Spatie\LaravelData\Data;

/**
 * A client-submitted request for a tailored (custom) plan. Captures what they
 * need; an admin reviews it, sets a price, and sends back a payment link.
 */
class RequestCustomPlanData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $company,
        public ?string $country,
        public string $message,
        public ?string $budget,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'message' => ['required', 'string', 'max:5000'],
            'budget' => ['nullable', 'string', 'max:100'],
        ];
    }
}
