<?php

namespace Tests\Feature\Controllers;

use App\Model\Event;
use App\Model\EventParticipant;
use ErrorException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use InvalidArgumentException;
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
     * 正常に実行できるかどうか
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

        app()->make('App\Http\Controllers\EventParticipantController')->create($payload);
    }

    /**
     * EventParticipantController@executeCreateEventParticipantToDBの正常処理テスト
     * イベントの参加登録ができるか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessExecuteCreateEventParticipantToDB()
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

        app()->make('App\Http\Controllers\EventParticipantController')->executeCreateEventParticipantToDB($payload);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'slack_user_id' => $slack_user_id,
        ]);
    }

    /**
     * EventParticipantController@executeCreateEventParticipantToDBの正常処理テスト
     * すでにイベントの参加登録をしている場合、無事に処理を終了できるか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessExecuteCreateEventParticipantToDBIfAlreadyRegistered()
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

        app()->make('App\Http\Controllers\EventParticipantController')->executeCreateEventParticipantToDB($payload);
    }

    /**
     * EventParticipantController@executeCreateEventParticipantToDBの例外処理テスト
     * イベントの参加登録時に例外が発生した場合、例外を返せるか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorExecuteCreateEventParticipantToDBIfExpectionOccurs()
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined index: message');

        $payload = [];
        app()->make('App\Http\Controllers\EventParticipantController')->executeCreateEventParticipantToDB($payload);
    }

    /**
     * EventParticipantController@removeの正常処理テスト
     * 正常に実行できるか
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

        app()->make('App\Http\Controllers\EventParticipantController')->remove($payload);
    }

    /**
     * EventParticipantController@executeRemoveEventParticipantFromDBの正常処理テスト
     * イベントの参加者を削除できるか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessExecuteRemoveEventParticipantFromDB()
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

        app()->make('App\Http\Controllers\EventParticipantController')->executeRemoveEventParticipantFromDB($payload);

        $this->assertDatabaseMissing('event_participants', [
            'event_id' => $event->id,
            'slack_user_id' => $slack_user_id,
        ]);
    }

    /**
     * EventParticipantController@executeRemoveEventParticipantFromDBの正常処理テスト
     * 該当の参加登録がないとき、無事に処理を終了できるか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessExecuteRemoveEventParticipantFromDBIfDataIsNotExist()
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

        app()->make('App\Http\Controllers\EventParticipantController')->remove($payload);
    }

    /**
     * EventParticipantController@executeRemoveEventParticipantFromDBの例外処理テスト
     * イベントの参加者を削除する際に例外が発生した場合、例外処理を無事に行えるかどうか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorExecuteRemoveEventParticipantFromDBIfExpectionOccurs()
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined index: message');

        $payload = [];

        app()->make('App\Http\Controllers\EventParticipantController')->executeRemoveEventParticipantFromDB($payload);
    }
}
