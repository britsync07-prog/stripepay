<?php

namespace App\Data\User;

use App\Models\ConsultationRequest;
use App\Models\User;
use App\Services\SettingsService;
use Spatie\LaravelData\Data;

class ClientData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public int $consultations_count,
        public string $spent,
        public ?string $joined,
        public ?string $company,
        public ?string $country,
        public ?string $phone,
    ) {}

    public static function fromModel(User $user): self
    {
        $contact = $user->relationLoaded('contact') ? $user->contact : $user->contact()->first();

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            consultations_count: (int) ($user->consultation_requests_count ?? 0),
            spent: ConsultationRequest::formatMoneyMinor((int) ($user->paid_amount ?? 0), SettingsService::currencyCode()),
            joined: $user->created_at?->toFormattedDayDateString(),
            company: $contact?->company,
            country: $contact?->country,
            phone: $contact?->phone,
        );
    }
}
