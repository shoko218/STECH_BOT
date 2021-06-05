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
     * SlackClientFactoryのモックを作り、Clientオブジェクトを返す
     * 
     * @return object
     */
    public function provideSlackClientMock ()
    {
        $client_factory_mock = Mockery::mock('alias:'. JoliCode\Slack\ClientFactory::class);
        $client_factory_mock->shouldReceive('create')
                        ->with('dummy token')
                        ->andReturn(new JoliCode\Slack\Api\Client);
    
        return JoliCode\Slack\ClientFactory::create('dummy token');
    }

    /**
     * MeetingController@executeChatPostMessageOfConfirmationの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessExecuteChatPostMessageOfConfirmation ()
    {
        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->with(\Mockery::on(function ($array) {
                $value_isset = isset($array['channel']) && isset($array['text']) && isset($array['blocks']);
                return $value_isset;
            }))
            ->andReturn(true);

        $slack_client_mock = $this->provideSlackClientMock();
        
        $meeting_controller = new MeetingController($slack_client_mock);
        $meeting_controller->executeChatPostMessageOfConfirmation();
    }

    /**
     * Meetingcontroller@executeChatPostMessageOfConfirmationの例外処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorExecuteChatPostMessageOfConfirmation ()
    {
        $this->expectException(SlackErrorResponse::class);
        $this->expectExceptionMessage('Slack returned error code "dummy exception: chatPostMessage"');

        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->andThrow(new SlackErrorResponse('dummy exception: chatPostMessage'))
            ->getMock();

        $slack_client_mock = $this->provideSlackClientMock();
        $meeting_controller = new MeetingController($slack_client_mock);
        $meeting_controller->executeChatPostMessageOfConfirmation();
    }

    /**
     * MeetingController@AskToHoldMeetingの正常処理テスト
     *
     * 例外処理テストはtestErrorExecuteChatPostMessageOfConfirmationでカバー
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testSuccessAskToHoldMeeting()
    {
        $meeting_controller_mock = Mockery::mock('App\Http\Controllers\MeetingController[askToHoldMeeting]', [null, null]);
        $meeting_controller_mock->shouldReceive('askToHoldMeeting')
                ->andReturn(true);
        
        $meeting_controller_mock->askToHoldMeeting();
    }

    /**
     * MeetingController@executeChatScheduleMessageの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @dataProvider schedulingDayProvider
     */
    public function testSuccessExecuteChatScheduleMessage ($meeting_day, $meeting_day_name)
    {
        $slack_api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $slack_api_mock->shouldReceive('chatScheduleMessage')
            ->with(\Mockery::on (function ($array) {
                $value_isset = isset($array['channel']) && isset($array['text']) && isset($array['post_at']) && isset($array['blocks']);
                return $value_isset;
            }))
            ->andReturn(true);

        $slack_client_mock = $this->provideSlackClientMock();
        $meeting_controller = new MeetingController($slack_client_mock);

        $meeting_controller->executeChatScheduleMessage([$meeting_day, $meeting_day_name]);
    }

    /**
     * MeetingTest@testSuccessExecuteChatScheduleMessageで使用する
     * 月曜日・木曜日(5/17,5/20)のUNIXTIMEと文字列のデータプロバイダー
     */
    public function schedulingDayProvider ()
    {
        $monday_unixtime = 1621213200;
        $thursday_unixtime = 1621472400;
        $monday_string = '月曜日';
        $thursday_string = '木曜日';

        return [
            'schedule monday meeting' => [$monday_unixtime, $monday_string],
            'schedule thursday meeting' => [$thursday_unixtime, $thursday_string]
        ];
    }

    /**
     * MeetingController@executeChatScheduleMessageの例外処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorExecuteChatScheduleMessage ()
    {
        $this->expectException(SlackErrorResponse::class);
        $this->expectExceptionMessage('Slack returned error code "dummy exception: chatScheduleMessage"');

        $slack_api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $slack_api_mock->shouldReceive('chatScheduleMessage')
            ->andThrow(new SlackErrorResponse('dummy exception: chatScheduleMessage'))
            ->getMock();

        $slack_client_mock = $this->provideSlackClientMock();
        $meeting_controller = new MeetingController($slack_client_mock);

        $meeting_controller->executeChatScheduleMessage([1621213200, '月曜日']);
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
    public function testSuccessScheduleMeetings ($meeting_value, $monday, $thursday)
    {       
        $slack_api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $slack_api_mock->shouldReceive('chatPostMessage')
            ->with(\Mockery::on(function ($array) {
                $value_isset = isset($array['channel']) && isset($array['text']);
                return $value_isset;
            }))
            ->andReturn(true);

        $slack_client_mock = $this->provideSlackClientMock();

        $meeting_controller_mock = Mockery::mock(
            'App\Http\Controllers\MeetingController[executeChatScheduleMessage]', 
            [$slack_client_mock, null]
        );
        $meeting_controller_mock->shouldReceive('executeChatScheduleMessage')
            ->andReturn(true);

        $meeting_controller_mock->scheduleMeetings([$meeting_value, $monday, $thursday]);
    }

    /**
     * MeetingTest@testSuccessScheduleMeetingsで使用する
     * 押されたボタンのvalueと月曜日・木曜日(5/17,5/20)のUNIXTIMEデータプロバイダー
     */
    public function  meetingToScheduleProvider ()
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
     * MeetingController@executeChatScheduledMessagesListの正常処理テスト
     */
    public function testSuccessExecuteChatScheduledMessagesList ()
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

        $meeting_controller = new MeetingController(null, $guzzle_mock);

        $this->assertEquals($expected_scheduled_meetings, $meeting_controller->executeChatScheduledMessagesList());
        
        return $expected_scheduled_meetings;
    }

    /**
     * MeetingController@executeChatScheduledMessagesListの例外処理テスト
     */
    public function testErrorExecuteChatScheduledMessagesList ()
    {
        $this->expectException(InvalidArgumentException::class);

        $handler = new MockHandler([
            new InvalidArgumentException('dummy guzzle exception'),
            new InvalidArgumentException('dummy guzzle exception')
        ]);
        $handler_stack = HandlerStack::create($handler);
        $guzzle_mock = new Client(['handler' => $handler_stack]);

        $meeting_controller = new MeetingController(null, $guzzle_mock);
        $meeting_controller->executeChatScheduledMessagesList();
    }

    /**
     * MeetingController@getScheduleMeetingListの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @depends testSuccessExecuteChatScheduledMessagesList
     */
    public function testSuccessGetScheduledMeetingList ($expected_scheduled_meetings)
    {
        $meeting_controller_mock = Mockery::mock(
            'App\Http\Controllers\MeetingController[executeChatScheduledMessagesList]', 
            [null, null]
        );
        $meeting_controller_mock->shouldReceive('executeChatScheduledMessagesList')
            ->andReturn($expected_scheduled_meetings);

        $this->assertEquals($expected_scheduled_meetings, $meeting_controller_mock->getScheduledMeetingList());
    }

    /**
     * MeetingController@executeChatDeleteScheduledMessageの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessExecuteChatDeleteScheduledMessage ()
    {
        $slack_api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $slack_api_mock->shouldReceive('chatDeleteScheduledMessage')
            ->with(\Mockery::on (function ($array) {
                $value_isset = isset($array['channel']) && isset($array['scheduled_message_id']);
                return $value_isset;
            }))
            ->andReturn(true);

        $slack_client_mock = $this->provideSlackClientMock();
        $meeting_controller = new MeetingController($slack_client_mock, null);
        $scheduled_meeting_list = $this->getScheduledMessageData();

        foreach ($scheduled_meeting_list['response_mock']['scheduled_messages'] as $meeting) {
            $meeting_controller->executeChatDeleteScheduledMessage($meeting);
        }
    }

    /**
     * MeetingController@executeChatDeleteScheduledMessageの例外処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorExecuteChatDeleteScheduledMessage ()
    {
        $this->expectException(SlackErrorResponse::class);
        $this->expectExceptionMessage('Slack returned error code "dummy exception: chatScheduleMessage"');

        $slack_api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $slack_api_mock->shouldReceive('chatDeleteScheduledMessage')
            ->andThrow(new SlackErrorResponse('dummy exception: chatScheduleMessage'))
            ->getMock();

        $slack_client_mock = $this->provideSlackClientMock();
        $meeting_controller = new MeetingController($slack_client_mock, null);
        $scheduled_meeting_list = $this->getScheduledMessageData();

        foreach ($scheduled_meeting_list['response_mock']['scheduled_messages'] as $meeting) {
            $meeting_controller->executeChatDeleteScheduledMessage($meeting);
        }
    }

    /**
     * MeetingController@deleteOverlappedMeetingの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @depends testSuccessExecuteChatScheduledMessagesList
     * @dataProvider deletedMeetingDataProvider
     */
    public function testSuccessDeleteOverlappedMeeting ($deleted_meetings, $monday, $thursday, $scheduled_meetings)
    {
        $meeting_controller_mock = Mockery::mock(
            'App\Http\Controllers\MeetingController[getScheduledMeetingList, executeChatDeleteScheduledMessage]', 
            [null, null]
        );
        $meeting_controller_mock->shouldReceive('getScheduledMeetingList')
            ->andReturn($scheduled_meetings);
        $meeting_controller_mock->shouldReceive('executeChatDeleteScheduledMessage')
            ->andReturn(true);

        $this->assertEquals($deleted_meetings, $meeting_controller_mock->deleteOverlappedMeeting([$monday, $thursday]));
    }

    /**
     * MeetingTest@testSuccessDeleteOverlappedMeetingで使うデータプロバイダー
     */
    public function deletedMeetingDataProvider ()
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
            'delete no meeting' => [$deleted_no_meeting, 0000000000, 0000000000]
        ];
    }

    /**
     * MeetingController@executeChatPostMessageOfSchedulingResultの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessExecuteChatPostMessageOfSchedulingResult ()
    {
        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->with(\Mockery::on(function ($array) {
                $value_isset = isset($array['channel']) && isset($array['text']);
                return $value_isset;
            }))
            ->andReturn(true);

        $slack_client_mock = $this->provideSlackClientMock();
        $scheduled_data = $this->getScheduledMessageData();

        $meeting_controller_mock = Mockery::mock(
            'App\Http\Controllers\MeetingController[getScheduledMeetingList]', 
            [$slack_client_mock, null]
        );
        $meeting_controller_mock->shouldReceive('getScheduledMeetingList')
                ->andReturn($scheduled_data['response_mock']['scheduled_messages']);

        $meeting_controller_mock->executechatpostMessageOfSchedulingResult(true);
        $meeting_controller_mock->executechatpostMessageOfSchedulingResult(false);
    }

    /**
     * MeetingController@executeChatPostMessageOfSchedulingResultの例外処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorExecuteChatPostMessageOfSchedulingResult ()
    {
        $this->expectException(SlackErrorResponse::class);
        $this->expectExceptionMessage('Slack returned error code "dummy exception: chatPostMessage"');

        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->andThrow(new SlackErrorResponse('dummy exception: chatPostMessage'))
            ->getMock();

        $slack_client_mock = $this->provideSlackClientMock();
        $meeting_controller = new MeetingController($slack_client_mock, null);

        $meeting_controller->executeChatPostMessageOfSchedulingResult(true);
        $meeting_controller->executeChatPostMessageOfSchedulingResult(false);
    }

     /**
     * MeetingController@notifyMeetingSettingCompletionの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessNotifyMeetingSettingCompletion ()
    {
        $meeting_controller_mock = Mockery::mock(
            'App\Http\Controllers\MeetingController[deleteOverlappedMeeting, scheduleMeetings, executeChatPostMessageOfSchedulingResult]', 
            [null, null]
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
                ->andReturn(true);

        $meeting_controller_mock->shouldReceive('executeChatPostMessageOfSchedulingResult')
                ->with(\Mockery::on (function ($scheduling_result) {
                    $argument_inspection = isset($scheduling_result) && is_bool($scheduling_result);
                    return $argument_inspection;
                }))
                ->andReturn(true);

        $dummy_payload = [
            "type" => "block_actions",
            "actions" => [["block_id" => "confirm_meeting","value" => "both_meetings"]]
        ];

        $meeting_controller_mock->notifyMeetingSettingsCompletion($dummy_payload);
    }
}