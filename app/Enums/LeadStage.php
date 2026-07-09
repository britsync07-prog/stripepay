<?php

namespace App\Enums;

/**
 * The CRM pipeline stage of a lead (an inbound custom-plan request or a
 * manually-added prospect). Stages are listed in board order; Won and Lost are
 * terminal. Won is reached automatically when the lead's payment is recorded.
 */
enum LeadStage: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Quoted = 'quoted';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Quoted => 'Quoted',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::New => 'outline',
            self::Contacted => 'secondary',
            self::Quoted => 'warning',
            self::Won => 'success',
            self::Lost => 'destructive',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Won || $this === self::Lost;
    }

    /** Linear position in the pipeline (New→Contacted→Quoted→Won). Lost sits outside it. */
    public function rank(): int
    {
        return match ($this) {
            self::New => 1,
            self::Contacted => 2,
            self::Quoted => 3,
            self::Won => 4,
            self::Lost => 99,
        };
    }

    /**
     * Whether activity may auto-advance this lead to $target: only ever forward,
     * and never out of a terminal stage (Won/Lost are left exactly as they are).
     */
    public function canAutoAdvanceTo(self $target): bool
    {
        return ! $this->isTerminal() && $target->rank() > $this->rank();
    }

    /**
     * @return array<int, array{value: string, label: string, tone: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->label(), 'tone' => $s->badge()],
            self::cases(),
        );
    }
}
