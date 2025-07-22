<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'participant_id',
        'date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function participant()
    {
        return $this->belongsTo(EventParticipant::class, 'participant_id');
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForTimeRange($query, $startTime, $endTime)
    {
        return $query->where('start_time', '>=', $startTime)
            ->where('end_time', '<=', $endTime);
    }

    public function isOverlapping(ParticipantAvailability $other): bool
    {
        return $this->date->eq($other->date) &&
               $this->start_time < $other->end_time &&
               $this->end_time > $other->start_time;
    }
}
