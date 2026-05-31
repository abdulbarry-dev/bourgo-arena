<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationStateLog extends Model
{
    protected $table = 'reservation_state_logs';

    protected $fillable = [
        'reservationable_type',
        'reservationable_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
    ];

    public function reservationable()
    {
        return $this->morphTo();
    }
}
