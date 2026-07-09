<?php

namespace App\Data\ConsultationRequest;

use Spatie\LaravelData\Data;

class ConsultationRequestData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public ?string $company,
        public ?string $country,
        public ?string $message,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'message' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
