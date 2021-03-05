<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name','description','event_datetime','notice_datetime','url'
    ];
}
