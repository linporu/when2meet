<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $password
 * @property int $event_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ParticipantAvailability> $participantAvailabilities
 */
class EventParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'password',
        'event_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function participantAvailabilities()
    {
        return $this->hasMany(ParticipantAvailability::class, 'participant_id');
    }

    public function checkPassword($password): bool
    {
        if (empty($this->password)) {
            return true;
        }

        return password_verify($password, $this->password);
    }
}
