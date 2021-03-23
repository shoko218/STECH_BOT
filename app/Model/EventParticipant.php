<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    protected $fillable = [
        'event_id','slack_user_id'
    ];

    public function event(){
        return $this->belongsTo('App\Model\Event');
    }
}
