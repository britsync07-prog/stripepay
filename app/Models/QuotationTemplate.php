<?php

namespace App\Models;

use App\Concerns\FormatsMoney;
use App\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A reusable quotation preset — a stored scope of services + line items an admin
 * can drop onto a new quotation. Line-item `unit_amount` is in minor units, like
 * every other money value in the app.
 */
class QuotationTemplate extends Model
{
    use FormatsMoney, HasUuidKey;

    protected $fillable = [
        'uuid',
        'name',
        'title',
        'scope_items',
        'line_items',
        'delivery_timeline',
        'valid_days',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'scope_items' => 'array',
            'line_items' => 'array',
            'valid_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Total in minor units, derived from the line items (never stored). */
    public function totalMinor(): int
    {
        return array_reduce(
            $this->line_items ?? [],
            fn (int $carry, array $item) => $carry + ((int) $item['unit_amount'] * (int) $item['quantity']),
            0,
        );
    }

    public function formattedTotal(): string
    {
        return self::formatMoneyMinorExact($this->totalMinor(), (string) $this->currency);
    }
}
