<?php

namespace App\Concerns;

use Illuminate\Support\Str;

trait HasUuidKey
{
    protected static function bootHasUuidKey(): void
    {
        static::creating(function ($model) {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
