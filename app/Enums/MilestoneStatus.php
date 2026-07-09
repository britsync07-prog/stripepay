<?php

namespace App\Enums;

enum MilestoneStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
        };
    }

    public function isComplete(): bool
    {
        return $this === self::Completed;
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
            self::Pending => [self::InProgress, self::Completed],
            self::InProgress => [self::Pending, self::Completed],
            self::Completed => [self::Pending, self::InProgress],
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
