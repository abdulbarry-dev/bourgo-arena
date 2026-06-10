<?php

namespace App\Models;

use Database\Factories\Shared\Auth\OtpCodeFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected static function newFactory(): Factory
    {
        return OtpCodeFactory::new();
    }

    protected $fillable = [
        'identifier',
        'code',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'code' => 'hashed',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->used_at && ! $this->isExpired();
    }

    /** @use HasFactory<OtpCodeFactory> */
    use HasFactory;
}
