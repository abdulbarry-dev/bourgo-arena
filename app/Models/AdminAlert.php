<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAlert extends Model
{
    protected $fillable = [
        'terminal_id',
        'member_id',
        'alert_type',
        'description',
        'count',
        'is_dismissed',
    ];

    protected $casts = [
        'is_dismissed' => 'boolean',
        'count' => 'integer',
    ];

    public function terminal()
    {
        return $this->belongsTo(HikvisionTerminal::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
