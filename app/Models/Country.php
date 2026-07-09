<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    public $timestamps = false;

    protected $fillable = ['code', 'name', 'dial_code', 'emoji'];

    /**
     * Country options for select inputs, ordered by name.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return static::orderBy('name')
            ->get(['code', 'name'])
            ->map(fn (self $c) => ['value' => $c->code, 'label' => $c->name])
            ->all();
    }

    /**
     * Resolve an ISO code to the country name, falling back to the raw code.
     * The full code→name map is loaded once per request so list pages mapping
     * many rows don't fire a query per row.
     */
    public static function nameFor(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $names = once(fn (): array => static::pluck('name', 'code')->all());

        return $names[$code] ?? $code;
    }
}
