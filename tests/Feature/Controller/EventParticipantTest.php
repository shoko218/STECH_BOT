<?php

namespace Tests\Feature\Controller;

use App\Model\Event;
use App\Model\EventParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class EventParticipantTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;


    public function setUp() :void
    {
        parent::setUp();
        $event_controller_mock = Mockery::mock('overload:App\Http\Controllers\EventController');
        $event_controller_mock->shouldReceive('updateEventPosts');
    }

    /**
     * EventParticipantController@createの正常処理テスト
     * イベントの参加登録ができるか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessCreate()
    {
        $event = factory(Event::class)->create();
        $slack_user_id = $this->faker->userName;

        $payload = [
            'message' => [
                'blocks' => [
                    4 => [
                        'elements' => [
                            0 => [
                                'value' => $event->id,
                            ]
                        ]
                    ]
                ]
            ],
            'user' => [
                'id' => $slack_user_id,
            ]
        ];

        $this->assertDatabaseMissing('event_participants', [
            'event_id' => $event->id,
            'slack_user_id' => $slack_user_id,
        ]);

        $response = app()->make('App\Http\Controllers\EventParticipantController')->create($payload);

        $this->assertStringContainsString(true, $response);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'slack_user_id' => $slack_user_id,
        ]);
    }

    /**
     * EventParticipantController@createの正常処理テスト
     * すでにイベントの参加登録をしている場合、無事に処理を終了できるか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessCreateIfAlreadyRegistered()
    {
        $event = factory(Event::class)->create();
        $slack_user_id = $this->faker->userName;

        $payload = [
            'message' => [
                'blocks' => [
                    4 => [
                        'elements' => [
                            0 => [
                                'value' => $event->id,
                            ]
                        ]
                    ]
                ]
            ],
            'user' => [
                'id' => $slack_user_id,
            ]
        ];

        factory(EventParticipant::class)->create([
            'event_id' => $event->id,
            'slack_user_id' => $slack_user_id,
        ]);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'slack_user_id' => $slack_user_id,
        ]);

        $response = app()->make('App\Http\Controllers\EventParticipantController')->create($payload);

        $this->assertStringContainsString(true, $response);
    }

    /**
     * EventParticipantController@createの例外処理テスト
     * イベントの参加登録時に例外が発生した場合、例外処理を無事に行えるかどうか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorCreateIfExpectionOccurs()
    {
        $payload = [
            'user' => [
                'id' => $this->faker->userName,
            ]
        ];
        $response = app()->make('App\Http\Controllers\EventParticipantController')->create($payload);

        $this->assertStringContainsString(false, $response);
    }

    /**
     * EventParticipantController@removeの正常処理テスト
     * イベントの参加者を削除できるか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessRemove()
    {
        $event = factory(Event::class)->create();
        $slack_user_id = $this->faker->userName;

        $payload = [
            'message' => [
                'blocks' => [
                    4 => [
                        'elements' => [
                            0 => [
                                'value' => $event->id,
                            ]
                        ]
                    ]
                ]
            ],
            'user' => [
                'id' => $slack_user_id,
            ]
        ];

        factory(EventParticipant::class)->create([
            'event_id' => $event->id,
            'slack_user_id' => $slack_user_id,
        ]);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'slack_user_id' => $slack_user_id,
        ]);

        $response = app()->make('App\Http\Controllers\EventParticipantController')->remove($payload);

        $this->assertStringContainsString(true, $response);

        $this->assertDatabaseMissing('event_participants', [
            'event_id' => $event->id,
            'slack_user_id' => $slack_user_id,
        ]);
    }

    /**
     * EventParticipantController@removeの正常処理テスト
     * 該当の参加登録がないとき、無事に処理を終了できるか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessRemoveIfDataIsNotExist()
    {
        $event = factory(Event::class)->create();
        $slack_user_id = $this->faker->userName;

        $payload = [
            'message' => [
                'blocks' => [
                    4 => [
                        'elements' => [
                            0 => [
                                'value' => $event->id,
                            ]
                        ]
                    ]
                ]
            ],
            'user' => [
                'id' => $slack_user_id,
            ]
        ];

        $event_participant = factory(EventParticipant::class)->create([
            'event_id' => $event->id,
            'slack_user_id' => $slack_user_id,
        ]);

        $event_participant->delete();

        $this->assertDatabaseMissing('event_participants', [
            'event_id' => $event->id,
            'slack_user_id' => $slack_user_id,
        ]);

        $response = app()->make('App\Http\Controllers\EventParticipantController')->remove($payload);

        $this->assertStringContainsString(true, $response);
    }

    /**
     * EventParticipantController@removeの例外処理テスト
     * イベントの参加者を削除する際に例外が発生した場合、例外処理を無事に行えるかどうか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorRemoveIfExpectionOccurs()
    {
        $payload = [
            'user' => [
                'id' => $this->faker->userName,
            ]
        ];

        $response = app()->make('App\Http\Controllers\EventParticipantController')->remove($payload);

        $this->assertStringContainsString(false, $response);
    }
}
