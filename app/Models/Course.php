<?php

namespace App\Models;

use Database\Factories\Dashboard\Catalog\CourseFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'name',
        'instructor',
        'description',
        'category',
        'icon',
        'image_url',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSession::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }
}
