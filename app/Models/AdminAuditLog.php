<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    protected $fillable = ['admin_id', 'event_id', 'action'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id')->withTrashed();
    }
}
