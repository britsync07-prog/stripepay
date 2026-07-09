<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Expert extends Model
{
    protected $fillable = [
        'name',
        'email',
        'specialization',
        'country',
        'is_active',
        'preferred_payout_method',
        'payout_notes',
        'stripe_account_id',
        'stripe_payouts_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'stripe_payouts_enabled' => 'boolean',
        ];
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Active experts as `{value, label}` options for a select.
     *
     * @return Collection<int, array{value: string, label: string}>
     */
    public static function activeOptions(): Collection
    {
        return self::active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (self $expert) => [
                'value' => (string) $expert->id,
                'label' => $expert->name,
            ])
            ->values();
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function consultationRequests(): HasMany
    {
        return $this->hasMany(ConsultationRequest::class);
    }
}
