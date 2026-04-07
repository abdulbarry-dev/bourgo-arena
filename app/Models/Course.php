<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'instructor',
        'description',
        'color',
    ];

    public function sessions()
    {
        return $this->hasMany(CourseSession::class);
    }
}
