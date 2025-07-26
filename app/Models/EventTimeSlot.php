<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $event_id
 * @property \Illuminate\Support\Carbon $date
 * @property string $start_time
 * @property string $end_time
 * @property-read \App\Models\Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ParticipantAvailability> $participantAvailabilities
 */
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
