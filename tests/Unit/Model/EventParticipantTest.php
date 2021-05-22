<?php

namespace Tests\Unit\Model;

use App\Model\Event;
use App\Model\EventParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventParticipantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 作成できるかどうかを確認
     */
    public function testMakeable()
    {
        EventParticipant::query()->delete();
        $eloquent = app(EventParticipant::class);
        $this->assertEmpty($eloquent->get());
        factory(EventParticipant::class)->create();
        $this->assertNotEmpty($eloquent->get());
    }

    /**
     * Eventとのリレーションが成立しているかを確認
     */
    public function testProductBelongsToGenre()
    {
        $event = factory(Event::class)->create();
        $event_participant = factory(EventParticipant::class)->create([
            'event_id' => $event->id,
        ]);
        $this->assertNotEmpty($event_participant->event);
    }
}
