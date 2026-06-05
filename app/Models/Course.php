<?php

namespace App\Models;

use Database\Factories\Dashboard\Catalog\CourseFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected static function newFactory(): Factory
    {
        return CourseFactory::new();
    }

    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'service_id',
        'name',
        'description',
        'images',
        'category',
        'image_url',
        'status',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'images' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function hasOfferings(): bool
    {
        return $this->sessions()->exists();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSession::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }
}
