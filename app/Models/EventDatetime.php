<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventDatetime extends Model
{
    use HasFactory;

    protected $table = 'event_time_slots';

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function availableDatetimes()
    {
        return $this->hasMany(AvailableDatetime::class, 'event_id', 'event_id')
            ->where('date', $this->date)
            ->where('start_time', '>=', $this->start_time)
            ->where('end_time', '<=', $this->end_time);
    }
}
