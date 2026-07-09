<?php

namespace Tests\Feature;

use App\Actions\Admin\SendLeadFollowUpDigest;
use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Notifications\LeadFollowUpsDueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadFollowUpDigestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('client');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function lead(array $overrides = []): ConsultationRequest
    {
        return ConsultationRequest::create(array_merge([
            'plan_id' => null,
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
            'amount' => 0,
            'currency' => 'USD',
            'status' => 'pending',
            'lead_stage' => LeadStage::New,
            'lead_source' => 'manual',
        ], $overrides));
    }

    public function test_digest_notifies_admins_with_overdue_and_due_today_counts(): void
    {
        Notification::fake();

        $this->lead(['next_follow_up_at' => now()->subDay()]);
        $this->lead(['next_follow_up_at' => now()]);
        $this->lead(['next_follow_up_at' => now()->addDays(3)]);
        $this->lead(['next_follow_up_at' => now()->subDay(), 'lead_stage' => LeadStage::Won]);

        $result = SendLeadFollowUpDigest::run();

        $this->assertSame(1, $result['overdue']);
        $this->assertSame(1, $result['dueToday']);
        $this->assertSame(1, $result['notified']);

        Notification::assertSentTo(
            $this->admin,
            LeadFollowUpsDueNotification::class,
            fn (LeadFollowUpsDueNotification $n) => $n->overdue === 1 && $n->dueToday === 1,
        );
    }

    public function test_digest_sends_nothing_when_no_follow_ups_are_due(): void
    {
        Notification::fake();

        $this->lead(['next_follow_up_at' => now()->addDays(5)]);
        $this->lead(['next_follow_up_at' => null]);

        $result = SendLeadFollowUpDigest::run();

        $this->assertSame(0, $result['notified']);
        Notification::assertNothingSent();
    }

    public function test_agenda_page_groups_leads_into_overdue_today_and_upcoming(): void
    {
        $this->lead(['name' => 'Overdue Olly', 'next_follow_up_at' => now()->subDays(2)]);
        $this->lead(['name' => 'Today Tom', 'next_follow_up_at' => now()]);
        $this->lead(['name' => 'Upcoming Uma', 'next_follow_up_at' => now()->addDays(4)]);
        $this->lead(['next_follow_up_at' => null]);
        $this->lead(['next_follow_up_at' => now()->subDay(), 'lead_stage' => LeadStage::Lost]);

        $this->actingAs($this->admin)
            ->get(route('admin.follow-ups'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/follow-ups')
                ->where('stats.overdue', 1)
                ->where('stats.today', 1)
                ->where('stats.upcoming', 1)
                ->has('overdue', 1)
                ->has('today', 1)
                ->has('upcoming', 1)
                ->where('overdue.0.name', 'Overdue Olly')
            );
    }

    public function test_calendar_grid_is_built_server_side_for_the_requested_month(): void
    {
        $this->lead(['name' => 'Cell Carl', 'next_follow_up_at' => '2026-07-15']);

        $monthStart = Carbon::parse('2026-07-01')->startOfMonth();
        $gridStart = $monthStart->copy()->subDays($monthStart->dayOfWeek);
        $offset = (int) $gridStart->diffInDays(Carbon::parse('2026-07-15'));
        $week = intdiv($offset, 7);
        $weekday = $offset % 7;

        $this->actingAs($this->admin)
            ->get(route('admin.follow-ups', ['month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/follow-ups')
                ->where('calendar.month', 'July 2026')
                ->where('calendar.prev', '2026-06')
                ->where('calendar.next', '2026-08')
                ->has('calendar.weeks', 6)
                ->has('calendar.weeks.0', 7)
                ->where("calendar.weeks.{$week}.{$weekday}.iso", '2026-07-15')
                ->where("calendar.weeks.{$week}.{$weekday}.inMonth", true)
                ->has("calendar.weeks.{$week}.{$weekday}.leads", 1)
                ->where("calendar.weeks.{$week}.{$weekday}.leads.0.name", 'Cell Carl')
            );
    }

    public function test_invalid_month_param_falls_back_to_the_current_month(): void
    {
        Carbon::setTestNow('2026-07-09 10:00:00');

        $this->actingAs($this->admin)
            ->get(route('admin.follow-ups', ['month' => '2026-13']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('calendar.month', 'July 2026')
            );

        Carbon::setTestNow();
    }
}
