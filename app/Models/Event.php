<?php

namespace App\Models;

use App\Events\EventCanceled;
use Database\Factories\Dashboard\Events\EventFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    protected static function newFactory(): Factory
    {
        return EventFactory::new();
    }

    /** @use HasFactory<EventFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_id', 'name', 'description', 'images', 'format', 'max_participants',
        'registration_deadline', 'start_date', 'end_date', 'requires_check_in',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'registration_deadline' => 'datetime',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'canceled_at' => 'datetime',
            'requires_check_in' => 'boolean',
            'images' => 'array',
        ];
    }

    public function getStatusAttribute(): string
    {
        if ($this->canceled_at) {
            return 'canceled';
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return 'completed';
        }

        if ($this->start_date && $this->start_date->isPast()) {
            return 'in_progress';
        }

        if ($this->registration_deadline && $this->registration_deadline->isFuture()) {
            return 'open';
        }

        return 'draft';
    }

    public function cancel(): void
    {
        if (! in_array($this->status, ['draft', 'open'])) {
            throw new \Exception('Only draft or open events can be canceled.');
        }

        $this->update(['canceled_at' => now()]);

        EventCanceled::dispatch($this);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('registration_deadline')
            ->where('registration_deadline', '>', now());
    }

    public function scopeOpen($query)
    {
        return $query->published()
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '>', now());
            });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(EventMatch::class);
    }
}
