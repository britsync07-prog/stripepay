<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planning = 'planning';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planning',
            self::Active => 'Active',
            self::OnHold => 'On hold',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::Active => 'secondary',
            self::Cancelled => 'destructive',
            self::Planning, self::OnHold => 'outline',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->label()],
            self::cases(),
        );
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Planning => [self::Active, self::Cancelled],
            self::Active => [self::OnHold, self::Completed, self::Cancelled],
            self::OnHold => [self::Active, self::Cancelled],
            self::Completed => [self::Active],
            self::Cancelled => [self::Active],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function transitionOptions(): array
    {
        return collect([$this, ...$this->allowedTransitions()])
            ->unique(fn (self $s) => $s->value)
            ->map(fn (self $s) => ['value' => $s->value, 'label' => $s->label()])
            ->values()
            ->all();
    }
}
