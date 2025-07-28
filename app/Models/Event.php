<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $hash
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventTimeSlot> $timeSlots
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventParticipant> $participants
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ParticipantAvailability> $participantAvailabilities
 */
class Event extends Model
{
    use HasFactory;

    private const HASH_LENGTH = 8;

    protected $fillable = [
        'name',
        'hash',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->hash)) {
                $event->hash = static::generateUniqueHash();
            }
        });
    }

    public static function generateUniqueHash(): string
    {
        do {
            $hash = Str::random(self::HASH_LENGTH);
        } while (static::where('hash', $hash)->exists());

        return $hash;
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(EventTimeSlot::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function participantAvailabilities(): HasMany
    {
        return $this->hasMany(ParticipantAvailability::class);
    }

    public function getRouteKeyName()
    {
        return 'hash';
    }
}
