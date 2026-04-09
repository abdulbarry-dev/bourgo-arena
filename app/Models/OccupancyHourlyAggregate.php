<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OccupancyHourlyAggregate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hour' => 'integer',
            'entries_count' => 'integer',
            'exits_count' => 'integer',
            'avg_occupancy' => 'integer',
        ];
    }
}
