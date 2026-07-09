<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Currency extends Model
{
    protected $fillable = ['code', 'name', 'symbol', 'sort_order'];

    /**
     * @return Collection<int, array{value: string, label: string, symbol: string}>
     */
    public static function options(): Collection
    {
        return self::orderBy('sort_order')
            ->orderBy('code')
            ->get(['code', 'name', 'symbol'])
            ->map(fn (self $c) => [
                'value' => $c->code,
                'label' => "{$c->code} — {$c->name} ({$c->symbol})",
                'symbol' => $c->symbol,
            ])
            ->values();
    }
}
