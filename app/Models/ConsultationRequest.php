<?php

namespace App\Models;

use App\Concerns\FormatsMoney;
use App\Concerns\HasReference;
use App\Concerns\HasUuidKey;
use App\Enums\ConsultationStatus;
use App\Enums\ConsultationWorkStatus;
use App\Enums\LeadScoreBand;
use App\Enums\LeadStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ConsultationRequest extends Model implements HasMedia
{
    use FormatsMoney, HasReference, HasUuidKey, InteractsWithMedia;

    public const REFERENCE_PREFIX = 'CD';

    public const REFERENCE_PADDING = 6;

    protected $fillable = [
        'uuid',
        'reference',
        'quote_number',
        'quote_issued_at',
        'plan_id',
        'title',
        'user_id',
        'contact_id',
        'expert_id',
        'consultation_type_id',
        'name',
        'email',
        'phone',
        'company',
        'country',
        'message',
        'scope_items',
        'line_items',
        'delivery_timeline',
        'urgency',
        'budget_min',
        'budget_max',
        'budget_currency',
        'amount',
        'currency',
        'status',
        'work_status',
        'lead_stage',
        'lead_source',
        'next_follow_up_at',
        'lost_reason',
        'lead_score',
        'lead_score_summary',
        'lead_score_flags',
        'lead_scored_at',
        'checkout_gateway',
        'checkout_reference',
        'expires_at',
        'paid_at',
        'assigned_at',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConsultationStatus::class,
            'work_status' => ConsultationWorkStatus::class,
            'lead_stage' => LeadStage::class,
            'lead_score' => 'integer',
            'lead_score_flags' => 'array',
            'lead_scored_at' => 'datetime',
            'scope_items' => 'array',
            'line_items' => 'array',
            'quote_issued_at' => 'date',
            'next_follow_up_at' => 'datetime',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return $this->status === ConsultationStatus::Paid;
    }

    /** An admin-authored custom offer carries no plan. */
    public function isCustom(): bool
    {
        return $this->plan_id === null;
    }

    /** Tracked in the CRM pipeline (an inbound custom request or a manual prospect). */
    public function isLead(): bool
    {
        return $this->lead_stage !== null;
    }

    /**
     * A follow-up is overdue once its day has fully passed and the lead is still
     * open. A follow-up dated *today* is due, not overdue (the date is stored at
     * midnight, so we compare against the start of today).
     */
    public function isFollowUpOverdue(): bool
    {
        return $this->next_follow_up_at !== null
            && ! ($this->lead_stage?->isTerminal() ?? true)
            && $this->next_follow_up_at->lt(now()->startOfDay());
    }

    public function followUpBucket(): string
    {
        if ($this->isFollowUpOverdue()) {
            return 'overdue';
        }

        if ($this->next_follow_up_at !== null && $this->next_follow_up_at->lte(now()->endOfDay())) {
            return 'today';
        }

        return 'upcoming';
    }

    /** @param  Builder<self>  $query */
    public function scopeLeads(Builder $query): Builder
    {
        return $query->whereNotNull('lead_stage');
    }

    public function scopeOpenFollowUps(Builder $query): Builder
    {
        return $query->leads()
            ->whereNotIn('lead_stage', [LeadStage::Won->value, LeadStage::Lost->value])
            ->whereNotNull('next_follow_up_at');
    }

    /**
     * Standalone payment links: a priced custom request (no plan) that is *not*
     * a CRM lead. The admin sets only a price; the buyer fills their own details
     * at checkout. Kept out of the leads pipeline (`lead_stage` is null).
     *
     * @param  Builder<self>  $query
     */
    public function scopePaymentLinks(Builder $query): Builder
    {
        return $query->whereNull('plan_id')
            ->whereNull('lead_stage')
            ->where('amount', '>', 0);
    }

    /** A custom lead with no price set yet — an admin must price it first. */
    public function isUnpriced(): bool
    {
        return $this->isCustom() && (int) $this->amount <= 0;
    }

    /** A formal quotation has been issued (document number assigned). */
    public function isQuotation(): bool
    {
        return $this->quote_number !== null;
    }

    /** The public, shareable quotation document URL. */
    public function quoteUrl(): string
    {
        return route('consultation.quote', $this);
    }

    /**
     * The buyer-facing link we send: the quotation document if one was issued,
     * otherwise the bare checkout page.
     */
    public function offerUrl(): string
    {
        return $this->isQuotation() ? $this->quoteUrl() : $this->checkoutUrl();
    }

    /**
     * The quotation's validity, in whole days from issue to expiry — the
     * "Validity: 30 Days" line. Null until both dates are set.
     */
    public function validityDays(): ?int
    {
        if ($this->quote_issued_at === null || $this->expires_at === null) {
            return null;
        }

        return (int) $this->quote_issued_at->startOfDay()->diffInDays($this->expires_at->startOfDay());
    }

    /** A standalone payment link — priced, no plan, and not in the CRM pipeline. */
    public function isPaymentLink(): bool
    {
        return $this->isCustom() && $this->lead_stage === null && (int) $this->amount > 0;
    }

    /** A custom payment link past its pay-by date can no longer be paid. */
    public function isExpired(): bool
    {
        return $this->expires_at !== null
            && ! $this->isPaid()
            && $this->expires_at->isPast();
    }

    /** The public, gateway-agnostic checkout URL — this *is* the payment link. */
    public function checkoutUrl(): string
    {
        return route('consultation.checkout', $this);
    }

    /** Buyer-facing label: the custom title, or the plan name. */
    public function offerTitle(): string
    {
        return $this->title ?? $this->plan?->name ?? 'Consultation';
    }

    /** The full country name for the stored ISO code, falling back to the raw code. */
    public function countryName(): ?string
    {
        return Country::nameFor($this->country);
    }

    /** AI lead score bucketed into a priority band. `null` until scored. */
    public function scoreBand(): ?LeadScoreBand
    {
        return LeadScoreBand::fromScore($this->lead_score);
    }

    /** Which channels this prospect can be reached on — e.g. "email + phone". */
    public function contactSummary(): string
    {
        $channels = array_filter([
            $this->email ? 'email' : null,
            $this->phone ? 'phone' : null,
        ]);

        return $channels ? implode(' + ', $channels) : 'none';
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function consultationType(): BelongsTo
    {
        return $this->belongsTo(ConsultationType::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }
}
