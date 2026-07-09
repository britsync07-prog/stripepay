<?php

namespace App\Enums;

enum ContactChannel: string
{
    case Note = 'note';
    case Phone = 'phone';
    case Video = 'video';
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Phone => 'Phone call',
            self::Video => 'Video call',
            self::Email => 'Email',
        };
    }

    public function isCall(): bool
    {
        return $this === self::Phone || $this === self::Video;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
