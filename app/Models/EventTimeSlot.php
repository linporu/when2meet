<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventTimeSlot extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function participantAvailabilities()
    {
        return $this->hasMany(ParticipantAvailability::class, 'event_id', 'event_id')
            ->where('date', $this->date)
            ->where('start_time', '>=', $this->start_time)
            ->where('end_time', '<=', $this->end_time);
    }
}
