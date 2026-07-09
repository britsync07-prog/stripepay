<?php

namespace App\Models;

use App\Enums\MilestoneStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'sort_order',
        'due_date',
        'completed_at',
    ];

    protected $attributes = [
        'status' => MilestoneStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => MilestoneStatus::class,
            'sort_order' => 'integer',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
