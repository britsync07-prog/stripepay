<?php

namespace App\Models;

use App\Concerns\FormatsMoney;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use FormatsMoney;

    protected $fillable = [
        'consultation_request_id',
        'gateway',
        'reference',
        'amount',
        'fee',
        'net',
        'currency',
        'status',
        'paid_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'fee' => 'integer',
            'net' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function consultationRequest(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class);
    }
}
