<?php

namespace Tests\Feature\Controller;

use DateTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;
use App\Model\Event;
use InvalidArgumentException;
use JoliCode\Slack\Exception\SlackErrorResponse;

class EventTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    public function setUp() :void
    {
        parent::setUp();

        $chat_post_message_post_response_200_mock = Mockery::mock('overload:JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200');
        $chat_post_message_post_response_200_mock->shouldReceive('getTs')
            ->andReturn($this->faker->randomNumber);

        $slack_api_client_mock = Mockery::mock('overload:JoliCode\Slack\Api\Client');
        $slack_api_client_mock->shouldReceive('chatPostMessage')
            ->with(Mockery::on(function ($arg) {
                return ($arg['channel'] !== null && ((array_key_exists('blocks', $arg) && $arg['blocks'] !== null) || (array_key_exists('text', $arg) && $arg['text'] !== null)));
            }))
            ->andReturn(app()->make('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200'))
            ->shouldReceive('chatPostMessage')
            ->with(Mockery::on(function ($arg) {
                return !($arg['channel'] !== null && ((array_key_exists('blocks', $arg) && $arg['blocks'] !== null) || (array_key_exists('text', $arg) && $arg['text'] !== null)));
            }))
            ->andReturn('error')
            ->shouldReceive('chatDelete')
            ->with(Mockery::on(function ($arg) {
                return ($arg['channel'] !== null && $arg['ts'] !== null);
            }))
            ->andReturn(app()->make('JoliCode\Slack\Api\Model\ChatDeletePostResponse200'))
            ->shouldReceive('chatDelete')
            ->with(Mockery::on(function ($arg) {
                return !($arg['channel'] !== null && $arg['ts'] !== null);
            }))
            ->andReturn('error')
            ->shouldReceive('chatUpdate')
            ->with(Mockery::on(function ($arg) {
                return ($arg['channel'] !== null && $arg['ts'] !== null && $arg['blocks'] !== null);
            }))
            ->andReturn(app()->make('JoliCode\Slack\Api\Model\ChatUpdatePostResponse200'))
            ->shouldReceive('chatUpdate')
            ->with(Mockery::on(function ($arg) {
                return !($arg['channel'] !== null && $arg['ts'] !== null && $arg['blocks'] !== null);
            }))
            ->andReturn('error')
            ->shouldReceive('create')
            ->andReturn(app()->make('JoliCode\Slack\Api\Client'));
    }

    /**
     * EventController@showCreateEventModalの正常処理テスト
     * イベント作成フォームが表示できるかどうか
     */
    public function testSuccessShowCreateEventModal()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getCreateEventModalConstitution')
            ->andReturn(['key' => 'value']);

        $guzzle_http_client_mock = Mockery::mock('overload:GuzzleHttp\Client');
        $guzzle_http_client_mock->shouldReceive('request')
            ->withArgs(function ($method, $url, $options) {
                return ($options['headers']['Authorization']  !==  'Bearer ' && $options['json']['view'] !== null);
            })
            ->andReturn('ok')
            ->shouldReceive('request')
            ->withArgs(function ($method, $url, $options) {
                return !($options['headers']['Authorization']  !==  'Bearer ' && $options['json']['view'] !== null);
            })
            ->andReturn('error');

        $trigger_id = $this->faker->userName;

        $request = new Request();
        $request->merge([
            'trigger_id' => $trigger_id,
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->showCreateEventModal($request);

        $this->assertStringContainsString('ok', $response);
    }

    /**
     * EventController@showCreateEventModalの例外処理テスト
     * イベント作成フォーム表示時に例外が発生した場合、例外処理を無事に行えるかどうか
     */
    public function testErrorShowCreateEventModalIfExpectionOccurs()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getCreateEventModalConstitution')
            ->andReturn(['key' => 'value']);
        $guzzle_http_client_mock = Mockery::mock('overload:GuzzleHttp\Client');
        $error = 'GuzzleHttp returned error code';
        $guzzle_http_client_mock->shouldReceive('request')
            ->andThrow(new InvalidArgumentException($error));

        $trigger_id = $this->faker->userName;

        $request = new Request();
        $request->merge([
            'trigger_id' => $trigger_id,
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->showCreateEventModal($request);

        $this->assertStringContainsString(false, $response);
    }

    /**
     * EventController@createEventの正常処理テスト
     * イベントを登録できるかどうか
     */
    public function testSuccessCreateEvent()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getCreatedEventMessageBlockConstitution')
            ->andReturn(['key' => 'value']);

        $tmp_event_datetime = $this->faker->dateTimeBetween('+2 week', '+4 week');
        $tmp_notice_datetime = $this->faker->dateTimeBetween('tomorrow', '+13 day');

        $event_datetime = new DateTime($tmp_event_datetime->format('Y-m-d H:'.($this->faker->numberBetween(0, 3) * 15).':00'));
        $notice_datetime = new DateTime($tmp_notice_datetime->format('Y-m-d H:'.($this->faker->numberBetween(0, 3) * 15).':00'));

        $name = $this->faker->realText(20, 2);
        $description = $this->faker->realText(200, 2);
        $url = $this->faker->url();

        $payload =[
            'view' => [
                'state' => [
                    'values' => [
                        'name' => [
                            'name' => [
                                'value' => $name
                            ]
                        ],
                        'description' => [
                            'description' => [
                                'value' => $description
                            ]
                        ],
                        'url' => [
                            'url' => [
                                'value' => $url
                            ]
                        ],
                        'event_date' => [
                            'event_date' => [
                                'selected_date' => $event_datetime->format('Y-m-d')
                            ]
                        ],
                        'event_time' => [
                            'event_hour' => [
                                'selected_option' => [
                                    'value' => $event_datetime->format('H'),
                                ]
                            ],
                            'event_minute' => [
                                'selected_option' => [
                                    'value' => $event_datetime->format('i'),
                                ]
                            ]
                        ],
                        'notice_date' => [
                            'notice_date' => [
                                'selected_date' => $notice_datetime->format('Y-m-d')
                            ]
                        ],
                        'notice_time' => [
                            'notice_hour' => [
                                'selected_option' => [
                                    'value' => $notice_datetime->format('H')
                                ]
                            ],
                            'notice_minute' => [
                                'selected_option' => [
                                    'value' => $notice_datetime->format('i'),
                                ]
                            ]
                        ],

                    ]
                ]
            ],
            'user' => [
                'id' => $this->faker->userName
            ]
        ];

        $response = app()->make('App\Http\Controllers\EventController')->createEvent($payload);

        $this->assertDatabaseHas('events', [
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'event_datetime' => $event_datetime,
            'notice_datetime' => $notice_datetime
        ]);

        $this->assertInstanceOf('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response);
    }

    /**
     * EventController@createEventのエラー処理テスト
     * イベント登録時、お知らせ日時が現在時刻以前だった場合にバリデーション処理できるかどうか
     */
    public function testErrorCreateEventIfNoticeDatetimeIsThePast()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getCreatedEventMessageBlockConstitution')
            ->andReturn(['key' => 'value']);

        $tmp_datetime = $this->faker->dateTimeBetween('+2 week', '+4 week');

        $event_datetime = new DateTime($tmp_datetime->format('Y-m-d H:'.($this->faker->numberBetween(0, 3) * 15).':00'));
        $notice_datetime = new DateTime();
        $notice_datetime = $notice_datetime->modify('-1 minute');

        $name = $this->faker->realText(20, 2);
        $description = $this->faker->realText(200, 2);
        $url = $this->faker->url();

        $payload =[
            'view' => [
                'state' => [
                    'values' => [
                        'name' => [
                            'name' => [
                                'value' => $name
                            ]
                        ],
                        'description' => [
                            'description' => [
                                'value' => $description
                            ]
                        ],
                        'url' => [
                            'url' => [
                                'value' => $url
                            ]
                        ],
                        'event_date' => [
                            'event_date' => [
                                'selected_date' => $event_datetime->format('Y-m-d')
                            ]
                        ],
                        'event_time' => [
                            'event_hour' => [
                                'selected_option' => [
                                    'value' => $event_datetime->format('H'),
                                ]
                            ],
                            'event_minute' => [
                                'selected_option' => [
                                    'value' => $event_datetime->format('i'),
                                ]
                            ]
                        ],
                        'notice_date' => [
                            'notice_date' => [
                                'selected_date' => $notice_datetime->format('Y-m-d')
                            ]
                        ],
                        'notice_time' => [
                            'notice_hour' => [
                                'selected_option' => [
                                    'value' => $notice_datetime->format('H')
                                ]
                            ],
                            'notice_minute' => [
                                'selected_option' => [
                                    'value' => $notice_datetime->format('i'),
                                ]
                            ]
                        ],

                    ]
                ]
            ],
            'user' => [
                'id' => $this->faker->userName
            ]
        ];

        $response = app()->make('App\Http\Controllers\EventController')->createEvent($payload);

        $this->assertDatabaseMissing('events', [
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'event_datetime' => $event_datetime,
            'notice_datetime' => $notice_datetime
        ]);

        $result = $response->getData();

        $this->assertStringContainsString('現在時刻以降の日時を入力してください。', $result->errors->notice_date);
        $this->assertStringContainsString('errors', $result->response_action);
    }

    /**
     * EventController@createEventのエラー処理テスト
     * イベント登録時、イベント日時が現在時刻以前だった場合にバリデーション処理できるかどうか
     */
    public function testErrorCreateEventIfEventDatetimeIsThePast()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getCreatedEventMessageBlockConstitution')
            ->andReturn(['key' => 'value']);

        $tmp_event_datetime = $this->faker->dateTimeBetween('-1 month', '-1 day');
        $tmp_notice_datetime = $this->faker->dateTimeBetween('tomorrow', '+1 month');

        $event_datetime = new DateTime($tmp_event_datetime->format('Y-m-d H:'.($this->faker->numberBetween(0, 3) * 15).':00'));
        $notice_datetime = new DateTime($tmp_notice_datetime->format('Y-m-d H:'.($this->faker->numberBetween(0, 3) * 15).':00'));

        $name = $this->faker->realText(20, 2);
        $description = $this->faker->realText(200, 2);
        $url = $this->faker->url();

        $payload =[
            'view' => [
                'state' => [
                    'values' => [
                        'name' => [
                            'name' => [
                                'value' => $name
                            ]
                        ],
                        'description' => [
                            'description' => [
                                'value' => $description
                            ]
                        ],
                        'url' => [
                            'url' => [
                                'value' => $url
                            ]
                        ],
                        'event_date' => [
                            'event_date' => [
                                'selected_date' => $event_datetime->format('Y-m-d')
                            ]
                        ],
                        'event_time' => [
                            'event_hour' => [
                                'selected_option' => [
                                    'value' => $event_datetime->format('H'),
                                ]
                            ],
                            'event_minute' => [
                                'selected_option' => [
                                    'value' => $event_datetime->format('i'),
                                ]
                            ]
                        ],
                        'notice_date' => [
                            'notice_date' => [
                                'selected_date' => $notice_datetime->format('Y-m-d')
                            ]
                        ],
                        'notice_time' => [
                            'notice_hour' => [
                                'selected_option' => [
                                    'value' => $notice_datetime->format('H')
                                ]
                            ],
                            'notice_minute' => [
                                'selected_option' => [
                                    'value' => $notice_datetime->format('i'),
                                ]
                            ]
                        ],

                    ]
                ]
            ],
            'user' => [
                'id' => $this->faker->userName
            ]
        ];

        $response = app()->make('App\Http\Controllers\EventController')->createEvent($payload);

        $this->assertDatabaseMissing('events', [
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'event_datetime' => $event_datetime,
            'notice_datetime' => $notice_datetime
        ]);

        $result = $response->getData();

        $this->assertStringContainsString('現在時刻以降の日時を入力してください。', $result->errors->event_date);
        $this->assertStringContainsString('errors', $result->response_action);
    }

    /**
     * EventController@createEventのエラー処理テスト
     * イベント登録時、イベント日時がお知らせ日時以前だった場合にバリデーション処理できるかどうか
     */
    public function testErrorCreateEventIfEventDateIsBeforeNoticeDate()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getCreatedEventMessageBlockConstitution')
            ->andReturn(['key' => 'value']);

        $tmp_event_datetime = $this->faker->dateTimeBetween('tomorrow', '+13 day');
        $tmp_notice_datetime = $this->faker->dateTimeBetween('+2 week', '+4 week');

        $event_datetime = new DateTime($tmp_event_datetime->format('Y-m-d H:'.($this->faker->numberBetween(0, 3) * 15).':00'));
        $notice_datetime = new DateTime($tmp_notice_datetime->format('Y-m-d H:'.($this->faker->numberBetween(0, 3) * 15).':00'));

        $name = $this->faker->realText(20, 2);
        $description = $this->faker->realText(200, 2);
        $url = $this->faker->url();

        $payload =[
            'view' => [
                'state' => [
                    'values' => [
                        'name' => [
                            'name' => [
                                'value' => $name
                            ]
                        ],
                        'description' => [
                            'description' => [
                                'value' => $description
                            ]
                        ],
                        'url' => [
                            'url' => [
                                'value' => $url
                            ]
                        ],
                        'event_date' => [
                            'event_date' => [
                                'selected_date' => $event_datetime->format('Y-m-d')
                            ]
                        ],
                        'event_time' => [
                            'event_hour' => [
                                'selected_option' => [
                                    'value' => $event_datetime->format('H'),
                                ]
                            ],
                            'event_minute' => [
                                'selected_option' => [
                                    'value' => $event_datetime->format('i'),
                                ]
                            ]
                        ],
                        'notice_date' => [
                            'notice_date' => [
                                'selected_date' => $notice_datetime->format('Y-m-d')
                            ]
                        ],
                        'notice_time' => [
                            'notice_hour' => [
                                'selected_option' => [
                                    'value' => $notice_datetime->format('H')
                                ]
                            ],
                            'notice_minute' => [
                                'selected_option' => [
                                    'value' => $notice_datetime->format('i'),
                                ]
                            ]
                        ],

                    ]
                ]
            ],
            'user' => [
                'id' => $this->faker->userName
            ]
        ];

        $response = app()->make('App\Http\Controllers\EventController')->createEvent($payload);

        $this->assertDatabaseMissing('events', [
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'event_datetime' => $event_datetime,
            'notice_datetime' => $notice_datetime
        ]);

        $result = $response->getData();

        $this->assertStringContainsString('お知らせする日時はイベントの日時より前に設定してください。', $result->errors->notice_date);
        $this->assertStringContainsString('errors', $result->response_action);
    }

    /**
     * EventController@createEventのエラー処理テスト
     * イベント登録時、イベントURLが無効なURLだった場合にバリデーション処理できるかどうか
     */
    public function testErrorCreateEventIfEventURLIsInvalid()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getCreatedEventMessageBlockConstitution')
            ->andReturn(['key' => 'value']);

        $tmp_event_datetime = $this->faker->dateTimeBetween('+2 week', '+4 week');
        $tmp_notice_datetime = $this->faker->dateTimeBetween('tomorrow', '+13 day');

        $event_datetime = new DateTime($tmp_event_datetime->format('Y-m-d H:'.($this->faker->numberBetween(0, 3) * 15).':00'));
        $notice_datetime = new DateTime($tmp_notice_datetime->format('Y-m-d H:'.($this->faker->numberBetween(0, 3) * 15).':00'));

        $name = $this->faker->realText(20, 2);
        $description = $this->faker->realText(200, 2);
        $url = $this->faker->userName();

        $payload =[
            'view' => [
                'state' => [
                    'values' => [
                        'name' => [
                            'name' => [
                                'value' => $name
                            ]
                        ],
                        'description' => [
                            'description' => [
                                'value' => $description
                            ]
                        ],
                        'url' => [
                            'url' => [
                                'value' => $url
                            ]
                        ],
                        'event_date' => [
                            'event_date' => [
                                'selected_date' => $event_datetime->format('Y-m-d')
                            ]
                        ],
                        'event_time' => [
                            'event_hour' => [
                                'selected_option' => [
                                    'value' => $event_datetime->format('H'),
                                ]
                            ],
                            'event_minute' => [
                                'selected_option' => [
                                    'value' => $event_datetime->format('i'),
                                ]
                            ]
                        ],
                        'notice_date' => [
                            'notice_date' => [
                                'selected_date' => $notice_datetime->format('Y-m-d')
                            ]
                        ],
                        'notice_time' => [
                            'notice_hour' => [
                                'selected_option' => [
                                    'value' => $notice_datetime->format('H')
                                ]
                            ],
                            'notice_minute' => [
                                'selected_option' => [
                                    'value' => $notice_datetime->format('i'),
                                ]
                            ]
                        ],

                    ]
                ]
            ],
            'user' => [
                'id' => $this->faker->userName
            ]
        ];

        $response = app()->make('App\Http\Controllers\EventController')->createEvent($payload);

        $this->assertDatabaseMissing('events', [
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'event_datetime' => $event_datetime,
            'notice_datetime' => $notice_datetime
        ]);

        $result = $response->getData();

        $this->assertStringContainsString('有効なURLを入力してください。', $result->errors->url);
        $this->assertStringContainsString('errors', $result->response_action);
    }

    /**
     * EventController@createEventの例外処理テスト
     * イベント登録時に例外が発生した場合、例外処理を無事に行えるかどうか
     */
    public function testErrorCreateEventIfExpectionOccurs()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getCreatedEventMessageBlockConstitution')
            ->andReturn(['key' => 'value']);

        $payload = [
            'user' => [
                'id' => $this->faker->userName
            ]
        ];

        $response = app()->make('App\Http\Controllers\EventController')->createEvent($payload);

        $this->assertStringContainsString(false, $response);
    }


    /**
     * EventController@deleteEventの正常処理テスト
     * お知らせもリマインドもしていない時にイベントを削除できるかどうか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessDeleteEvent()
    {
        $event = factory(Event::class)->create();

        $this->assertDatabaseHas('events', [
            'name' => $event->name,
            'description' => $event->description,
            'url' => $event->url,
            'event_datetime' => $event->event_datetime,
            'notice_datetime' => $event->notice_datetime
        ]);

        $payload = [
            'actions' => [
                [
                    'value' => $event->id
                ]
            ],
            'user' =>[
                'id' => $this->faker->userName
            ]
        ];

        $response = app()->make('App\Http\Controllers\EventController')->deleteEvent($payload);

        $this->assertDatabaseMissing('events', [
            'name' => $event->name,
            'description' => $event->description,
            'url' => $event->url,
            'event_datetime' => $event->event_datetime,
            'notice_datetime' => $event->notice_datetime
        ]);

        $this->assertInstanceOf('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response['post_msg']);
    }

    /**
     * EventController@deleteEventの正常処理テスト
     * 既にお知らせしている時にイベントを削除できるかどうか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessDeleteEventIfAlreadyNoticed()
    {
        $event = factory(Event::class)->create();
        $notice_ts = $this->faker->randomNumber;
        $event->notice_ts = $notice_ts;
        $event->save();

        $this->assertDatabaseHas('events', [
            'name' => $event->name,
            'description' => $event->description,
            'url' => $event->url,
            'event_datetime' => $event->event_datetime,
            'notice_datetime' => $event->notice_datetime,
            'notice_ts' => $notice_ts
        ]);

        $payload = [
            'actions' => [
                [
                    'value' => $event->id
                ]
            ],
            'user' =>[
                'id' => $this->faker->userName
            ]
        ];

        $response = app()->make('App\Http\Controllers\EventController')->deleteEvent($payload);

        $this->assertDatabaseMissing('events', [
            'name' => $event->name,
            'description' => $event->description,
            'url' => $event->url,
            'event_datetime' => $event->event_datetime,
            'notice_datetime' => $event->notice_datetime,
            'notice_ts' => $notice_ts
        ]);

        $this->assertInstanceOf('JoliCode\Slack\Api\Model\ChatDeletePostResponse200', $response['delete_notice_post']);
        $this->assertInstanceOf('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response['post_msg']);
    }

    /**
     * EventController@deleteEventの正常処理テスト
     * 既にリマインドしている時にイベントを削除できるかどうか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessDeleteEventIfAlreadyReminded()
    {
        $event = factory(Event::class)->create();
        $remind_ts = $this->faker->randomNumber;
        $event->remind_ts = $remind_ts;
        $event->save();

        $this->assertDatabaseHas('events', [
            'name' => $event->name,
            'description' => $event->description,
            'url' => $event->url,
            'event_datetime' => $event->event_datetime,
            'notice_datetime' => $event->notice_datetime,
            'remind_ts' => $remind_ts
        ]);

        $payload = [
            'actions' => [
                [
                    'value' => $event->id
                ]
            ],
            'user' =>[
                'id' => $this->faker->userName
            ]
        ];

        $response = app()->make('App\Http\Controllers\EventController')->deleteEvent($payload);

        $this->assertDatabaseMissing('events', [
            'name' => $event->name,
            'description' => $event->description,
            'url' => $event->url,
            'event_datetime' => $event->event_datetime,
            'notice_datetime' => $event->notice_datetime,
            'remind_ts' => $remind_ts
        ]);

        $this->assertInstanceOf('JoliCode\Slack\Api\Model\ChatDeletePostResponse200', $response['delete_remind_post']);
        $this->assertInstanceOf('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response['post_msg']);
    }

    /**
     * EventController@deleteEventの例外処理テスト
     * イベント削除時に例外が発生した場合、例外処理を無事に行えるかどうか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorDeleteEventIfExpectionOccurs()
    {
        $payload = [
            'user' =>[
                'id' => $this->faker->userName
            ]
        ];

        $response = app()->make('App\Http\Controllers\EventController')->deleteEvent($payload);

        $this->assertStringContainsString(false, $response);
    }

    /**
     * EventController@showEventsの正常処理テスト
     * 開催予定のイベントがある場合にイベントを表示できるかどうか
     */
    public function testSuccessShowEventIfEventIs()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getShowEventBlockConstitution')
            ->andReturn(['key' => 'value'])
            ->shouldReceive('getShowHeaderBlockConstitution')
            ->andReturn(['key' => 'value']);

        $count = $this->faker->numberBetween(1, 20);
        factory(Event::class, $count)->create();

        $request = new Request();
        $request->merge([
            'user_id' => $this->faker->userName,
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->showEvents($request);

        $this->assertArrayHasKey('header', $response);
        $this->assertArrayHasKey('contents', $response);

        $this->assertInstanceOf('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response['header']);
        $this->assertCount($count, $response['contents']);
        $this->assertContainsOnly('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response['contents']);
    }

    /**
     * EventController@showEventsの正常処理テスト
     * 開催予定のイベントがない場合にメッセージを表示できるかどうか
     */
    public function testSuccessShowEventIfEventIsNot()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getShowEventBlockConstitution')
            ->andReturn(['key' => 'value'])
            ->shouldReceive('getShowHeaderBlockConstitution')
            ->andReturn(['key' => 'value']);

        $request = new Request();
        $request->merge([
            'user_id' => $this->faker->userName,
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->showEvents($request);

        $this->assertArrayHasKey('header', $response);
        $this->assertArrayHasKey('contents', $response);

        $this->assertInstanceOf('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response['header']);
        $this->assertCount(1, $response['contents']);
        $this->assertContainsOnly('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response['contents']);
    }

    /**
     * EventController@showEventsの例外処理テスト
     * イベント表示時に例外が発生した場合、例外処理を無事に行えるかどうか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorShowEventIfExpectionOccurs()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $error = 'Event payload controller returned error code';
        $event_payload_mock->shouldReceive('getShowHeaderBlockConstitution')
            ->andThrow(new InvalidArgumentException($error))
            ->shouldReceive('getShowEventBlockConstitution')
            ->andThrow(new InvalidArgumentException($error));

        $request = new Request();
        $request->merge([
            'user_id' => $this->faker->userName,
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->showEvents($request);

        $this->assertStringContainsString(false, $response);
    }

    /**
     * EventController@noticeEventの正常処理テスト
     * 知らせるべきイベントを知らせられるかどうか
     */
    public function testSuccessNoticeEvent()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getNoticeEventBlocks')
            ->andReturn(['key' => 'value']);

        $should_noticed_count = $this->faker->numberBetween(1, 20);
        $should_not_noticed_count = $this->faker->numberBetween(1, 20);
        $noticed_count = $this->faker->numberBetween(1, 20);

        factory(Event::class, $should_noticed_count)->create([
            'notice_datetime' => $this->faker->dateTimeBetween('-2 week', '-1 minute'),
        ]);
        factory(Event::class, $should_not_noticed_count)->create([
            'notice_datetime' => $this->faker->dateTimeBetween('+2 minute', '+2 week'),
        ]);
        factory(Event::class, $noticed_count)->create([
            'notice_datetime' => $this->faker->dateTimeBetween('-2 week', '-1 minute'),
            'notice_ts' => $this->faker->randomNumber
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->noticeEvent();

        $this->assertCount($should_noticed_count, $response);
        $this->assertContainsOnly('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response);
    }

    /**
     * EventController@noticeEventの正常処理テスト
     * 知らせるべきイベントがない時、無事に処理が終了するかどうか
     */
    public function testSuccessNoticeEventIfEventIsNot()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getNoticeEventBlocks')
            ->andReturn(['key' => 'value']);

        $should_not_noticed_count = $this->faker->numberBetween(1, 20);
        $noticed_count = $this->faker->numberBetween(1, 20);

        factory(Event::class, $should_not_noticed_count)->create([
            'notice_datetime' => $this->faker->dateTimeBetween('+2 minute', '+2 week'),
        ]);
        factory(Event::class, $noticed_count)->create([
            'notice_datetime' => $this->faker->dateTimeBetween('-2 week', '-1 minute'),
            'notice_ts' => $this->faker->randomNumber
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->noticeEvent();

        $this->assertEmpty($response);
    }

    /**
     * EventController@noticeEventの例外処理テスト
     * イベントお知らせ時に例外が発生した場合、例外処理を無事に行えるかどうか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorNoticeEventIfExpectionOccurs()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $error = 'Event payload controller returned error code';
        $event_payload_mock->shouldReceive('getNoticeEventBlocks')
            ->andThrow(new InvalidArgumentException($error));

        $should_noticed_count = $this->faker->numberBetween(1, 20);

        factory(Event::class, $should_noticed_count)->create([
            'notice_datetime' => $this->faker->dateTimeBetween('-2 week', '-1 minute'),
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->noticeEvent();

        $this->assertStringContainsString(false, $response);
    }

    /**
     * EventController@remindEventの正常処理テスト
     * 今日開催予定のイベントをリマインドできるかどうか
     */
    public function testSuccessRemindEvent()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getRemindEventBlocks')
            ->andReturn(['key' => 'value']);

        $should_reminded_count = $this->faker->numberBetween(1, 20);
        $should_not_reminded_count = $this->faker->numberBetween(1, 20);
        $reminded_count = $this->faker->numberBetween(1, 20);

        factory(Event::class, $should_reminded_count)->create([
            'event_datetime' => $this->faker->dateTimeBetween('today', 'today'),
        ]);
        factory(Event::class, $should_not_reminded_count)->create([
            'event_datetime' => $this->faker->dateTimeBetween('tomorrow', '+2 week'),
        ]);
        factory(Event::class, $reminded_count)->create([
            'event_datetime' => $this->faker->dateTimeBetween('today', 'today'),
            'remind_ts' => $this->faker->randomNumber
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->remindEvent();

        $this->assertCount($should_reminded_count, $response);
        $this->assertContainsOnly('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response);
    }

    /**
     * EventController@remindEventの正常処理テスト
     * 今日開催予定のイベントがない時、無事に処理が終了するかどうか
     */
    public function testSuccessRemindEventIfEventIsNot()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getRemindEventBlocks')
            ->andReturn(['key' => 'value']);

        $should_not_reminded_count = $this->faker->numberBetween(1, 20);
        $reminded_count = $this->faker->numberBetween(1, 20);

        factory(Event::class, $should_not_reminded_count)->create([
            'event_datetime' => $this->faker->dateTimeBetween('tomorrow', '+2 week'),
        ]);
        factory(Event::class, $reminded_count)->create([
            'event_datetime' => $this->faker->dateTimeBetween('today', 'today'),
            'remind_ts' => $this->faker->randomNumber
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->remindEvent();

        $this->assertEmpty($response);
    }

    /**
     * EventController@remindEventの例外処理テスト
     * 今日開催予定のイベントをリマインドする時に例外が発生した場合、例外処理を無事に行えるかどうか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorRemindEventIfExpectionOccurs()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $error = 'Event payload controller returned error code';
        $event_payload_mock->shouldReceive('getRemindEventBlocks')
            ->andThrow(new InvalidArgumentException($error));

        $should_reminded_count = $this->faker->numberBetween(1, 20);

        factory(Event::class, $should_reminded_count)->create([
            'event_datetime' => $this->faker->dateTimeBetween('today', 'today'),
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->remindEvent();

        $this->assertStringContainsString(false, $response);
    }

    /**
     * EventController@shareEventUrlの正常処理テスト
     * 15分後に始まるイベントのURLを共有できるかどうか
     */

    public function testSuccessShareEventUrl()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getShareEventUrlBlocks')
            ->andReturn(['key' => 'value']);

        $should_shared_url_count = $this->faker->numberBetween(1, 20);
        $should_not_shared_url_count = $this->faker->numberBetween(1, 20);

        $now = new DateTime();

        factory(Event::class, $should_shared_url_count)->create([
            'event_datetime' => $now->modify('+15 minute')->format('Y-m-d H:i:00'),
        ]);
        factory(Event::class, $should_not_shared_url_count)->create([
            'event_datetime' => $this->faker->dateTimeBetween('tomorrow', '+2 week'),
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->shareEventUrl();

        $this->assertCount($should_shared_url_count, $response);
        $this->assertContainsOnly('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response);
    }

    /**
     * EventController@shareEventUrlの正常処理テスト
     * 15分後に始まるイベントがない時、無事に処理が終了するかどうか
     */

    public function testSuccessShareEventUrlIfEventIsNot()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getShareEventUrlBlocks')
            ->andReturn(['key' => 'value']);

        $should_not_shared_url_count = $this->faker->numberBetween(1, 20);

        $now = new DateTime();

        factory(Event::class, $should_not_shared_url_count)->create([
            'event_datetime' => $this->faker->dateTimeBetween('tomorrow', '+2 week'),
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->shareEventUrl();

        $this->assertEmpty($response);
    }

    /**
     * EventController@shareEventUrlの例外処理テスト
     * 15分後に始まるイベントのURLを共有する時に例外が発生した場合、例外処理を無事に行えるかどうか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */

    public function testErrorShareEventUrlIfExpectionOccurs()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $error = 'Event payload controller returned error code';
        $event_payload_mock->shouldReceive('getShareEventUrlBlocks')
            ->andThrow(new InvalidArgumentException($error));

        $should_shared_url_count = $this->faker->numberBetween(1, 20);

        $now = new DateTime();

        factory(Event::class, $should_shared_url_count)->create([
            'event_datetime' => $now->modify('+15 minute')->format('Y-m-d H:i:00'),
        ]);
        $response = app()->make('App\Http\Controllers\EventController')->shareEventUrl();

        $this->assertStringContainsString(false, $response);
    }

    /**
     * EventController@updateEventPostsの正常処理テスト
     * 既にお知らせしている場合に、イベントに関する投稿の参加者情報を更新できるかどうか
     */

    public function testSuccessUpdateEventPostsIfEventIsAlreadyNoticed()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getNoticeEventBlocks')
            ->andReturn(['key' => 'value']);

        $event = factory(Event::class)->create([
            'notice_ts' => $this->faker->randomNumber
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->updateEventPosts($event);

        $this->assertInstanceOf('JoliCode\Slack\Api\Model\ChatUpdatePostResponse200', $response['notice']);
    }

    /**
     * EventController@updateEventPostsの正常処理テスト
     * 既にリマインドしている場合に、イベントに関する投稿の参加者情報を更新できるかどうか
     */

    public function testSuccessUpdateEventPostsIfEventIsAlreadyReminded()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $event_payload_mock->shouldReceive('getRemindEventBlocks')
            ->andReturn(['key' => 'value']);

        $event = factory(Event::class)->create([
            'remind_ts' => $this->faker->randomNumber
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->updateEventPosts($event);

        $this->assertInstanceOf('JoliCode\Slack\Api\Model\ChatUpdatePostResponse200', $response['remind']);
    }

    /**
     * EventController@updateEventPostsの例外処理テスト
     * イベントに関する投稿の参加者情報更新時に例外が発生した場合、例外処理を無事に行えるかどうか
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */

    public function testErrorUpdateEventPostsIfExpectionOccurs()
    {
        $event_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\EventPayloadController');
        $error = 'Event payload controller returned error code';
        $event_payload_mock->shouldReceive('getNoticeEventBlocks')
            ->andThrow(new InvalidArgumentException($error))
            ->shouldReceive('getRemindEventBlocks')
            ->andThrow(new InvalidArgumentException($error));

        $event = factory(Event::class)->create([
            'notice_ts' => $this->faker->randomNumber,
            'remind_ts' => $this->faker->randomNumber
        ]);

        $response = app()->make('App\Http\Controllers\EventController')->updateEventPosts($event);

        $this->assertStringContainsString(false, $response);
    }
}
