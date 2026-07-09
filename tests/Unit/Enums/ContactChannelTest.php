<?php

namespace Tests\Unit\Enums;

use App\Enums\ContactChannel;
use Tests\TestCase;

class ContactChannelTest extends TestCase
{
    public function test_only_phone_and_video_count_as_calls(): void
    {
        $this->assertTrue(ContactChannel::Phone->isCall());
        $this->assertTrue(ContactChannel::Video->isCall());

        $this->assertFalse(ContactChannel::Note->isCall());
        $this->assertFalse(ContactChannel::Email->isCall());
    }

    public function test_labels_are_human_friendly(): void
    {
        $this->assertSame('Note', ContactChannel::Note->label());
        $this->assertSame('Phone call', ContactChannel::Phone->label());
        $this->assertSame('Video call', ContactChannel::Video->label());
        $this->assertSame('Email', ContactChannel::Email->label());
    }

    public function test_options_covers_every_case(): void
    {
        $options = ContactChannel::options();

        $this->assertCount(count(ContactChannel::cases()), $options);
        $this->assertSame(
            ['note', 'phone', 'video', 'email'],
            array_column($options, 'value'),
        );
    }
}
