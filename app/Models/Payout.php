<?php

namespace App\Models;

use App\Concerns\FormatsMoney;
use App\Enums\PayoutMethod;
use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Payout extends Model implements HasMedia
{
    use FormatsMoney, InteractsWithMedia;

    protected $fillable = [
        'expert_id',
        'amount',
        'currency',
        'method',
        'status',
        'reference',
        'idempotency_key',
        'note',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'method' => PayoutMethod::class,
            'status' => PayoutStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('proof')->singleFile()->useDisk('local');
    }
}
