<?php

namespace Tests\Unit\Model;

use App\Model\Event;
use App\Model\EventParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 作成できるかどうかを確認
     */
    public function testMakeable()
    {
        Event::query()->delete();
        $eloquent = app(Event::class);
        $this->assertEmpty($eloquent->get());
        factory(Event::class)->create();
        $this->assertNotEmpty($eloquent->get());
    }

    /**
     * EventParicipantとのリレーションが成立しているかを確認
     */
    public function testEventHasManyEventParticipants()
    {
        $count = 10;
        Event::query()->delete();
        $event = factory(Event::class)->create();
        factory(EventParticipant::class, $count)->create([
            'event_id' => $event->id,
        ]);
        $this->assertEquals($count, count($event->refresh()->eventParticipants));
    }
}
