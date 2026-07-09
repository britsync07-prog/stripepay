<?php

namespace App\Data\Notification;

use Illuminate\Notifications\DatabaseNotification;
use Spatie\LaravelData\Data;

/**
 * Frontend shape for a database notification — the single source of truth for
 * how a notification is presented, shared by the global Inertia prop and the
 * admin/portal notification pages.
 */
class NotificationData extends Data
{
    public function __construct(
        public string $id,
        public ?string $type,
        public string $title,
        public string $message,
        public ?string $url,
        public bool $read,
        public ?string $time,
    ) {}

    public static function fromModel(DatabaseNotification $notification): self
    {
        return new self(
            id: $notification->id,
            type: $notification->data['type'] ?? null,
            title: $notification->data['title'] ?? 'Notification',
            message: $notification->data['message'] ?? '',
            url: $notification->data['url'] ?? null,
            read: $notification->read_at !== null,
            time: $notification->created_at?->diffForHumans(),
        );
    }
}
