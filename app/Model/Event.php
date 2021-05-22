<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name','description','event_datetime','notice_datetime','url'
    ];

    protected $dates = ['event_datetime','notice_datetime'];

    public function eventParticipants(){
        return $this->hasMany('App\Model\EventParticipant');
    }
}
