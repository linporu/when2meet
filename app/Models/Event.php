<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public function eventDatetimes()
    {
        return $this->hasMany(EventTimeSlot::class);
    }

    public function participants()
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function availableDatetimes()
    {
        return $this->hasMany(ParticipantAvailability::class);
    }

    public function getRouteKeyName()
    {
        return 'hash';
    }
}
