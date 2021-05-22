<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model\Event;
use App\Model\EventParticipant;
use Faker\Generator as Faker;

$factory->define(EventParticipant::class, function (Faker $faker) {
    $event = Event::inRandomOrder()->first();
    $slack_user_id = $faker->userName;
    if ($event === null || (EventParticipant::where('event_id', $event->id)->where('slack_user_id', $slack_user_id)->first()) === null) {
        $event = factory(Event::class)->create();
    }
    return [
        'event_id' => $event->id,
        'slack_user_id' => $slack_user_id
    ];
});
