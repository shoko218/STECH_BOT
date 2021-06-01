<?php

namespace Tests\Feature\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use App\Http\Controllers\MeetingController;
use JoliCode\Slack\Exception\SlackErrorResponse;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\InvalidArgumentException;

require_once 'vendor/autoload.php';

class MeetingTest extends TestCase
{
    public function setUp() :void
    {
        parent::setUp();
    }

    public function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * MeetingController@AskToHoldMeetingの正常処理テスト
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testSuccessAskToHoldMeeting()
    {
        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->with(\Mockery::on(function ($array) {
                $value_isset = isset($array['channel']) && isset($array['text']) && isset($array['blocks']);
                return $value_isset;
            }))
            ->andReturn(true);

        $client_factory_mock = Mockery::mock('alias:'. JoliCode\Slack\ClientFactory::class);
        $client_factory_mock->shouldReceive('create')
                        ->with('dummy token')
                        ->andReturn(new JoliCode\Slack\Api\Client);
    
        $slack_client_mock = JoliCode\Slack\ClientFactory::create('dummy token');
        
        $meeting = new MeetingController($slack_client_mock);
        $meeting->AskToHoldMeeting();
    }

    /**
     * 例外を発生させるMeetingController::classを返す
     * 例外(Slack Error Response)を起こすのはSlackApiClient
     * 
     * @return object
     */
    public function createMeetingControllerCausesError ()
    {
        $api_error_mock = Mockery::mock('overload:'. JoliCode\Slack\Api\Client::class);
        
        $api_error_mock->shouldReceive('chatPostMessage')
            ->andThrow(new SlackErrorResponse('dummy exception: chatPostMessage'))
            ->getMock();
        $api_error_mock->shouldReceive('chatScheduleMessage')
            ->andThrow(new SlackErrorResponse('dummy exception: chatScheduleMessage'))
            ->getMock();

        $client_factory_mock = Mockery::mock('alias:'. JoliCode\Slack\ClientFactory::class);
        $client_factory_mock->shouldReceive('create')
                        ->with('dummy token')
                        ->andReturn(new JoliCode\Slack\Api\Client);
        
        $slack_client_error_mock = JoliCode\Slack\ClientFactory::create('dummy token');

        return new MeetingController($slack_client_error_mock);
    }

    /**
     * MeetingController@AskToHoldMeetingの例外処理テスト
     * 
     * api接続の失敗時に例外処理となる
     * catch内で$e->getMessageをechoさせているので、その出力が期待通りになっているかをテストします
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorAskToHoldMeeting()
    {
        $this->createMeetingControllerCausesError()->askToHoldMeeting();
        $this->expectOutputString('Slack returned error code "dummy exception: chatPostMessage"');
    }

    /**
     * MeetingController@scheduleMeetingsの正常処理テスト
     *
     * @param string $first_meeting_day 来週の月曜日の予定日時(UNIXTIME形式)：ここでは2021/5/17
     * @param string $second_meeting_day 来週の木曜日の予定日時(UNIXTIME形式)：ここでは2021/5/20
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @dataProvider meetingToScheduleProvider
     */
    public function testSuccessScheduleMeetings($meeting_value, $monday, $thursday)
    {       
        $slack_api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);

        $slack_api_mock->shouldReceive('chatPostMessage')
            ->with(\Mockery::on(function ($array) {
                $value_isset = isset($array['channel']) && isset($array['text']);
                return $value_isset;
            }))
            ->andReturn(true);

        $slack_api_mock->shouldReceive('chatScheduleMessage')
            ->with(\Mockery::on (function ($array) {
                $value_isset = isset($array['channel']) && isset($array['post_at']) && isset($array['blocks']);
                return $value_isset;
            }))
            ->andReturn(true);

        $client_factory_mock = Mockery::mock('alias:'. JoliCode\Slack\ClientFactory::class);
        $client_factory_mock->shouldReceive('create')
                        ->with('dummy token')
                        ->andReturn(new JoliCode\Slack\Api\Client);
                                    
        $slack_client_mock = JoliCode\Slack\ClientFactory::create('dummy token');
        $meeting = new MeetingController($slack_client_mock);

        $meeting->scheduleMeetings([$meeting_value, $monday, $thursday]);
    }

    /**
     * MeetingTest@testSuccessScheduleMeetingsで使用する
     * 押されたボタンのvalueと月曜日・木曜日(5/17,5/20)のUNIXTIMEデータプロバイダー
     */
    public function  meetingToScheduleProvider()
    {
        $monday = 1621213200;
        $thursday = 1621472400;

        return [
            'hold both meetings' => ['both_meetings', $monday, $thursday],
            'hold only monday meeting' => ['first_meeting', $monday, $thursday],
            'hold only thursday meeting' => ['second_meeting', $monday, $thursday],
            'not hold both meetings' => ['not_both_meetings', $monday, $thursday],
            'invalid value' => ['another_value', $monday, $thursday]
        ];
    }

    /**
     * MeetingController@scheduleMeetingsの例外処理テスト
     * 
     * api接続の失敗時に例外処理となる
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorScheduleMeetings()
    {
        $this->createMeetingControllerCausesError()->scheduleMeetings(['both_meetings', 1621213200, 1621472400]);
        $this->expectOutputString('Slack returned error code "dummy exception: chatScheduleMessage"');
    }

    /**
     * slackでスケジュール登録されたメッセージのデータ関連
     * 
     * @return array
     */
    public function getScheduledMessageData ()
    {
        return [
            'response_mock' => [
                "ok"=>true,
                "scheduled_messages"=>[
                    ["id"=>"Q021SQ9LKK4", "channel_id"=>"C01MGFXJSD8", "post_at"=>1621213200,],
                    ["id"=>"Q022CAGCS3A", "channel_id"=>"C01MGFXJSD8", "post_at"=>1621472400,]
                ],
                "response_metadata"=>["next_cursor"=>""]
            ]
        ];
    }

    /**
     * MeetingController@getScheduleMeetingListの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessGetScheduledMeetingList()
    {
        $scheduled_data = $this->getScheduledMessageData();
        $response_mock = json_encode($scheduled_data['response_mock']);
        $expected_scheduled_meetings = $scheduled_data['response_mock']['scheduled_messages'];

        $handler = new MockHandler([
            new Response(200, [], $response_mock),
            new Response(200, [], $response_mock)
        ]);
        $handler_stack = HandlerStack::create($handler);
        $guzzle_mock = new Client(['handler' => $handler_stack]);

        $meeting = new MeetingController(null, $guzzle_mock);

        $this->assertEquals($expected_scheduled_meetings, $meeting->getScheduledMeetingList());
        
        return $expected_scheduled_meetings;
    }

    /**
     * MeetingController@getScheduleMeetingListの例外処理テスト
     * 
     * 引数エラーのときに例外をキャッチする
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorGetScheduledMeetingList()
    {
        $handler = new MockHandler([
            new InvalidArgumentException('dummy guzzle exception'),
            new InvalidArgumentException('dummy guzzle exception')
        ]);
        $handler_stack = HandlerStack::create($handler);
        $guzzle_mock = new Client(['handler' => $handler_stack]);

        $meeting = new MeetingController(null, $guzzle_mock);
        $meeting->getScheduledMeetingList();
        
        $this->expectOutputString('dummy guzzle exception');
    }

    /**
     * MeetingController@deleteOverlappedMeetingの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @depends testSuccessGetScheduledMeetingList
     * @dataProvider deletedMeetingDataProvider
     */
    public function testSuccessDeleteOverlappedMeeting($deleted_meetings, $monday, $thursday, $scheduled_meetings)
    {
        $slack_api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $slack_api_mock->shouldReceive('chatDeleteScheduledMessage')
            ->with(\Mockery::on (function ($array) {
                $value_isset = isset($array['channel']) && isset($array['scheduled_message_id']);
                return $value_isset;
            }))
            ->andReturn(true);

        $client_factory_mock = Mockery::mock('alias:'. JoliCode\Slack\ClientFactory::class);
        $client_factory_mock->shouldReceive('create')
                        ->with('dummy token')
                        ->andReturn(new JoliCode\Slack\Api\Client);
                                    
        $slack_client_mock = JoliCode\Slack\ClientFactory::create('dummy token');

        $meeting_controller_mock = Mockery::mock('App\Http\Controllers\MeetingController[getScheduledMeetingList]', [$slack_client_mock, null]);
        $meeting_controller_mock->shouldReceive('getScheduledMeetingList')
                ->andReturn($scheduled_meetings);
                

        $this->assertEquals($deleted_meetings, $meeting_controller_mock->deleteOverlappedMeeting([$monday, $thursday]));
    }

    /**
     * MeetingTest@testSuccessDeleteOverlappedMeetingで使うデータ
     */
    public function deletedMeetingDataProvider()
    {
        $deleted_both_meeting = [
            ["id"=>"Q021SQ9LKK4", "channel_id"=>"C01MGFXJSD8", "post_at"=>1621213200,],
            ["id"=>"Q022CAGCS3A", "channel_id"=>"C01MGFXJSD8", "post_at"=>1621472400,]
        ]; 
        $deleted_monday_meeting= [["id"=>"Q021SQ9LKK4", "channel_id"=>"C01MGFXJSD8", "post_at"=>1621213200,]];
        $deleted_thursday_meeting = [["id"=>"Q022CAGCS3A", "channel_id"=>"C01MGFXJSD8", "post_at"=>1621472400,]];
        $deleted_no_meeting = [];

        $monday = 1621213200;
        $thursday = 1621472400;

        return [
            'delete both meetings' => [$deleted_both_meeting, $monday, $thursday],
            'delete monday meeting' => [$deleted_monday_meeting, $monday, 0000000000],
            'delete thursday meeting' => [$deleted_thursday_meeting, 0000000000, $thursday],
            'delete no meeting' => [$deleted_no_meeting,0000000000, 0000000000]
        ];
    }

    /**
     * MeetingController@deleteOverlappedMeetingの例外処理テスト
     * 
     * $deleted=[]で配列の数が0になり例外にならないため、
     * メソッド内にあるgetScheduledMeetinglistがエラーを引き起こした場合を想定しています
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @depends testSuccessGetScheduledMeetingList
     */
    public function testErrorDeleteOverlappedMeeting()
    {
        $meeting_controller_mock = Mockery::mock('App\Http\Controllers\MeetingController[getScheduledMeetingList]', [null, null]);
        $meeting_controller_mock->shouldReceive('getScheduledMeetingList')
            ->andThrow(new InvalidArgumentException('dummy guzzle exception'));

        $meeting_controller_mock->deleteOverlappedMeeting([0000000000, 0000000000]);
        $this->expectOutputString('dummy guzzle exception');
    }


    /**
     * notifyMeetingSettingCompletion()のテストに必要なMeetingcontrollerのモックを提供する
     * 予めモックされたSlackClientも含まれており、
     * 
     * @param array $next_meetings 次週ミーティングに関する値を返す配列
     * @param bool $returned_bool MeetingController@scheduleMeetingsを実行した結果返される値(true|false)
     * @return object
     */
    public function provideMeetingControllerMocks($next_meetings, $returned_bool)
    {
        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->with(\Mockery::on(function ($array) {
                $value_isset = isset($array['channel']) && isset($array['text']);
                return $value_isset;
            }))
            ->andReturn(true);

        $client_factory_mock = Mockery::mock('alias:'. JoliCode\Slack\ClientFactory::class);
        $client_factory_mock->shouldReceive('create')
                        ->with('dummy token')
                        ->andReturn(new JoliCode\Slack\Api\Client);
    
        $slack_client_mock = JoliCode\Slack\ClientFactory::create('dummy token');

        $meeting_controller_mock = Mockery::mock(
            'App\Http\Controllers\MeetingController[deleteOverlappedMeeting, scheduleMeetings, getScheduledMeetingList]', 
            [$slack_client_mock, null]
        );
        
        $meeting_controller_mock->shouldReceive('deleteOverlappedMeeting')
                ->with(\Mockery::on (function ($array_about_next_meeting_days) {
                    $next_monday = $array_about_next_meeting_days[0];
                    $next_thursday = $array_about_next_meeting_days[1];

                    $this->assertIsInt($next_monday);
                    $this->assertIsInt($next_thursday);
                    
                    $arguments_isset = isset($next_monday) && isset($next_thursday);
                    return $arguments_isset;
                }))
                ->andReturn(true);

        $meeting_controller_mock->shouldReceive('scheduleMeetings')
                ->with(\Mockery::on (function ($array_about_next_meeting) {
                    $value = $array_about_next_meeting[0];
                    $next_monday = $array_about_next_meeting[1];
                    $next_thursday = $array_about_next_meeting[2];

                    $this->assertIsInt($next_monday);
                    $this->assertIsInt($next_thursday);
                    
                    $arguments_isset = isset($value) && isset($next_monday) && isset($next_thursday);
                    return $arguments_isset;
                }))
                // true/falseでその後の挙動が異なる
                ->andReturn($returned_bool);

        $meeting_controller_mock->shouldReceive('getScheduledMeetingList')
                ->andReturn($next_meetings);

        return $meeting_controller_mock;
    }

    /**
     * MeetingController@notifyMeetingSettingCompletionの正常処理テスト1
     * 
     * 
     * 月曜日・木曜日両日、月曜日のみ、木曜日のみに開催する場合を想定
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @dataProvider nextMeetingDataProvider
     */
    public function testSuccessNotifyMeetingSettingCompletion_ExistsMeeting ($expected_next_meeting_date_list, $next_meetings, $dummy_payload)
    {
        $meeting_controller_mock = $this->provideMeetingControllerMocks($next_meetings, true);
        
        $this->assertEquals(
            $expected_next_meeting_date_list, 
            $meeting_controller_mock->notifyMeetingSettingsCompletion($dummy_payload)
        );
    }

    /**
     * testSuccessNotifyMeetingSettingCompletion_ExistsMeetingのためのデータプロバイダー
     */
    public function nextMeetingDataProvider ()
    {
        $both_meetings = [
            ["id"=>"Q021SQ9LKK4", "channel_id"=>"C01MGFXJSD8", "post_at"=>1621213200,],
            ["id"=>"Q022CAGCS3A", "channel_id"=>"C01MGFXJSD8", "post_at"=>1621472400,]
        ];
        $monday_meeting = [["id"=>"Q021SQ9LKK4", "channel_id"=>"C01MGFXJSD8", "post_at"=>1621213200,]];
        $thursday_meeting = [["id"=>"Q022CAGCS3A", "channel_id"=>"C01MGFXJSD8", "post_at"=>1621472400,]];

        $dummy_both_meetings_payload = [
            "type" => "block_actions",
            "actions" => [["block_id" => "confirm_meeting","value" => "both_meetings"]]
        ];
        $dummy_monday_meeting_payload = [
            "type" => "block_actions",
            "actions" => [["block_id" => "confirm_meeting","value" => "first_meeting"]]
        ];
        $dummy_thursday_meeting_payload = [
            "type" => "block_actions",
            "actions" => [["block_id" => "confirm_meeting","value" => "second_meeting"]]
        ];
        
        return [
            [['2021年05月17日', '2021年05月20日'], $both_meetings, $dummy_both_meetings_payload],
            [['2021年05月17日'], $monday_meeting, $dummy_monday_meeting_payload],
            [['2021年05月20日'], $thursday_meeting, $dummy_thursday_meeting_payload]
        ];
    }

    /**
     * MeetingController@notifyMeetingSettingCompletionの正常処理テスト2
     * 
     * 両日ともミーティングを開催しない場合
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessNotifyMeetingSettingCompletion_NoMeeting ()
    {
        $next_meetings = [];
        $dummy_payload = [
                "type" => "block_actions",
                "actions" => [[
                    "action_id" => "meeting_option3",
                    "block_id" => "confirm_meeting",
                    "value" => "not_both_meetings"
                ]]
        ];

        $meeting_controller_mock = $this->provideMeetingControllerMocks($next_meetings, false);
        $meeting_controller_mock->notifyMeetingSettingsCompletion($dummy_payload);
    }

    /**
     * MeetingController@notifyMeetingSettingCompletionの例外処理テスト
     * 
     * chatScheduleMessageが失敗した場合を想定しています
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorNotifyMeetingSettingcompletion_ExistsMeeting ()
    {
        $dummy_payload = [
            "type" => "block_actions",
            "actions" => [["block_id" => "confirm_meeting","value" => "both_meetings"]]
        ];

        $this->createMeetingControllerCausesError()->notifyMeetingSettingsCompletion($dummy_payload);
        $this->expectOutputString('Slack returned error code "dummy exception: chatScheduleMessage"');
    }
}