<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventParticipant extends Model
{
    use HasFactory;

    protected $table = 'event_participants';

    public $timestamps = false;

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
        'created_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function availableDatetimes()
    {
        return $this->hasMany(AvailableDatetime::class, 'participant_id');
    }

    public function checkPassword($password): bool
    {
        if (empty($this->password)) {
            return true;
        }
        
        return password_verify($password, $this->password);
    }
}