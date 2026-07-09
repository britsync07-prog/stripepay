<?php

namespace App\Concerns;

trait HasReference
{
    protected static function bootHasReference(): void
    {
        static::created(function ($model) {
            if (! $model->reference) {
                $model->reference = sprintf(
                    '%s-%s-%0'.$model::REFERENCE_PADDING.'d',
                    $model::REFERENCE_PREFIX,
                    $model->created_at?->year ?? date('Y'),
                    $model->getKey(),
                );
                $model->saveQuietly();
            }
        });
    }
}
