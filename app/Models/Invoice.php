<?php

namespace App\Models;

use App\Concerns\FormatsMoney;
use App\Concerns\HasReference;
use App\Concerns\HasUuidKey;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use FormatsMoney, HasReference, HasUuidKey;

    public const REFERENCE_PREFIX = 'INV';

    public const REFERENCE_PADDING = 4;

    protected $fillable = [
        'uuid',
        'reference',
        'user_id',
        'consultation_request_id',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'currency',
        'notes',
        'issued_at',
        'paid_at',
    ];

    protected $attributes = [
        'status' => InvoiceStatus::Paid->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'subtotal' => 'integer',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'integer',
            'total' => 'integer',
            'issued_at' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function recalculateTotals(): void
    {
        $total = (int) $this->items()->sum('amount');
        $rate = (float) $this->tax_rate;

        if ($rate <= 0) {
            $this->update([
                'subtotal' => $total,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $total,
            ]);

            return;
        }

        $subtotal = (int) round($total / (1 + $rate / 100));

        $this->update([
            'subtotal' => $subtotal,
            'tax_rate' => $rate,
            'tax_amount' => $total - $subtotal,
            'total' => $total,
        ]);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function consultationRequest(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }
}
