<?php

namespace App\Enums;

enum ConsultationWorkStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In progress',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->label()],
            self::cases(),
        );
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function progressSteps(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->label()],
            [self::New, self::InProgress, self::Resolved, self::Closed],
        );
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Resolved, self::Closed, self::Cancelled],
            self::Resolved => [self::Closed, self::InProgress, self::Cancelled],
            self::Closed => [self::InProgress],
            self::Cancelled => [self::InProgress],
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
