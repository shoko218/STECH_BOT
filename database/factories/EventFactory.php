<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model\Event;
use Faker\Generator as Faker;

$factory->define(Event::class, function (Faker $faker) {
    $event_datetime = $this->faker->dateTimeBetween('+2 week', '+4 week');
    $notice_datetime = $this->faker->dateTimeBetween('tomorrow', '+13 day');

    return [
        'name' => $faker->realText(20, 2),
        'description' => $faker->realText(200, 2),
        'url' => $faker->url(),
        'event_datetime' => $event_datetime->format('Y-m-d H:'.sprintf('%02d', ($this->faker->numberBetween(0, 3) * 15)).':00'),
        'notice_datetime' => $notice_datetime->modify('-1 week')->format('Y-m-d H:'.sprintf('%02d', ($this->faker->numberBetween(0, 3) * 15)).':00'),
    ];
});
