<?php

namespace App\Models;

use App\Concerns\HasReference;
use App\Concerns\HasUuidKey;
use App\Enums\MilestoneStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model implements HasMedia
{
    use HasReference, HasUuidKey, InteractsWithMedia;

    public const REFERENCE_PREFIX = 'PRJ';

    public const REFERENCE_PADDING = 4;

    protected $fillable = [
        'uuid',
        'reference',
        'consultation_request_id',
        'user_id',
        'expert_id',
        'title',
        'status',
        'completion_percentage',
        'health',
        'health_summary',
        'health_flags',
        'health_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'completion_percentage' => 'integer',
            'health' => ProjectHealth::class,
            'health_flags' => 'array',
            'health_checked_at' => 'datetime',
        ];
    }

    public function recomputeCompletion(): void
    {
        $total = $this->milestones()->count();
        $done = $this->milestones()->where('status', MilestoneStatus::Completed->value)->count();

        $attributes = [
            'completion_percentage' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];

        if ($total > 0 && $done === $total) {
            $attributes['status'] = ProjectStatus::Completed;
        } elseif ($this->status === ProjectStatus::Completed) {
            $attributes['status'] = ProjectStatus::Active;
        }

        $this->update($attributes);
    }

    public function consultationRequest(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('sort_order');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')->useDisk('local');
    }
}
