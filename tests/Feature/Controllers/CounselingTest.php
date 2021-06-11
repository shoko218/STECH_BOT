<?php

namespace Tests\Feature\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class CounselingTest extends TestCase
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
        $slack_api_client_mock->shouldReceive('viewsOpen')
            ->with(Mockery::on(function ($arg) {
                return ($arg['trigger_id'] !== null && $arg['view'] !== null);
            }))
            ->andReturn('ok')
            ->shouldReceive('viewsOpen')
            ->with(Mockery::on(function ($arg) {
                return !($arg['trigger_id'] !== null && $arg['view'] !== null);
            }))
            ->andReturn('error')
            ->shouldReceive('chatPostMessage')
            ->with(Mockery::on(function ($arg) {
                return ($arg['channel'] !== null && ((array_key_exists('blocks', $arg) && $arg['blocks'] !== null) || (array_key_exists('text', $arg) && $arg['text'] !== null)));
            }))
            ->andReturn(app()->make('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200'))
            ->shouldReceive('chatPostMessage')
            ->with(Mockery::on(function ($arg) {
                return !($arg['channel'] !== null && ((array_key_exists('blocks', $arg) && $arg['blocks'] !== null) || (array_key_exists('text', $arg) && $arg['text'] !== null)));
            }))
            ->andReturn('error')
            ->shouldReceive('create')
            ->andReturn(app()->make('JoliCode\Slack\Api\Client'));
    }

    /**
     * ルート"/slash/show_application_counseling_modal"が正しく設定されているかをテストする
     */
    public function testRoutingToShowCreateEventModal()
    {
        $counseling_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\CounselingPayloadController');

        $counseling_payload_mock->shouldReceive('getModalConstitution')
            ->andReturn(['key' => 'value']);

        $response = $this->json('POST', '/slash/show_application_counseling_modal', ['trigger_id' => $this->faker->userName]);
        $response->assertStatus(200);
    }
    /**
    * CounselingController@showApplicationModalの正常処理テスト
    * 正常に実行できるか
    *
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testSuccessShowApplicationModal()
    {
        $counseling_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\CounselingPayloadController');

        $counseling_payload_mock->shouldReceive('getModalConstitution')
            ->andReturn(['key' => 'value']);

        $request = new Request();
        $request->merge([
            'trigger_id' => $this->faker->userName,
            'user_id' => $this->faker->userName,
        ]);

        app()->make('App\Http\Controllers\CounselingController')->showApplicationModal($request);
    }

    /**
    * CounselingController@executeViewsOpenOfShowApplicationModalの正常処理テスト
    * 申し込みフォームが表示できるか
    *
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testSuccessExecuteViewsOpenOfShowApplicationModal()
    {
        $counseling_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\CounselingPayloadController');

        $counseling_payload_mock->shouldReceive('getModalConstitution')
            ->andReturn(['key' => 'value']);

        $request = new Request();
        $request->merge([
            'trigger_id' => $this->faker->userName,
            'user_id' => $this->faker->userName,
        ]);

        app()->make('App\Http\Controllers\CounselingController')->executeViewsOpenOfShowApplicationModal($request);
    }

    /**
    * CounselingController@executeViewsOpenOfShowApplicationModalの例外処理テスト
    * 申し込みフォーム表示の際に例外が発生した場合、例外を返せるか
    *
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testErrorShowApplicationModalIfExpectionOccurs()
    {
        $request = new Request();
        $request->merge([
            'user_id' => $this->faker->userName,
        ]);

        app()->make('App\Http\Controllers\CounselingController')->executeViewsOpenOfShowApplicationModal($request);
    }

    /**
    * CounselingController@notifyToMentorの正常処理テスト
    * 正常に実行できるか

    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testSuccessNotifyToMentor()
    {
        $counseling_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\CounselingPayloadController');

        $counseling_payload_mock->shouldReceive('getCompletedApplyBlockConstitution')
            ->with(Mockery::any())
            ->andReturn(['key' => 'value'])
            ->shouldReceive('getNotifyApplyBlockConstitution')
            ->with(Mockery::any());

        $payload = [
            'user' => [
                'id' => $this->faker->userName,
            ]
        ];

        app()->make('App\Http\Controllers\CounselingController')->notifyToMentor($payload);
    }

    /**
    * CounselingController@executeChatPostMessageOfNotifyToMentorの正常処理テスト
    * メンターさんに申し込み内容を送信できるか

    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testExecuteChatPostMessageOfNotifyToMentor()
    {
        $counseling_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\CounselingPayloadController');

        $counseling_payload_mock->shouldReceive('getCompletedApplyBlockConstitution')
            ->with(Mockery::any())
            ->andReturn(['key' => 'value'])
            ->shouldReceive('getNotifyApplyBlockConstitution')
            ->with(Mockery::any());

        $payload = [
            'user' => [
                'id' => $this->faker->userName,
            ]
        ];

        app()->make('App\Http\Controllers\CounselingController')->executeChatPostMessageOfNotifyToMentor($payload);
    }

    /**
    * CounselingController@executeChatPostMessageOfNotifyToMentorの例外処理テスト
    * メンターさんに申し込み内容を送信する際に例外が発生した場合、例外処理を無事に行えるか

    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testErrorNotifyToMentorIfExpectionOccurs()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Counseling payload controller returned error code');

        $counseling_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\CounselingPayloadController');

        $error = 'Counseling payload controller returned error code';

        $counseling_payload_mock->shouldReceive('getCompletedApplyBlockConstitution')
            ->andThrow(new InvalidArgumentException($error))
            ->shouldReceive('getNotifyApplyBlockConstitution')
            ->andThrow(new InvalidArgumentException($error))
            ->getMock();

        $payload = [
            'user' => [
                'id' => $this->faker->userName,
            ]
        ];

        app()->make('App\Http\Controllers\CounselingController')->executeChatPostMessageOfNotifyToMentor($payload);
    }

    /**
    * CounselingController@introduceQuestionFormの正常処理テスト
    * 正常に実行できるか
    *
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testSuccessIntroduceQuestionForm()
    {
        $counseling_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\CounselingPayloadController');

        $counseling_payload_mock->shouldReceive('getIntroduceBlockConstitution')
            ->andReturn(['key' => 'value']);

        app()->make('App\Http\Controllers\CounselingController')->introduceQuestionForm();
    }

    /**
    * CounselingController@executeChatPostMessageOfIntroduceQuestionFormの正常処理テスト
    * 相談会申し込みフォームを紹介するメッセージを送信できるか
    *
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testSuccessExecuteChatPostMessageOfIntroduceQuestionForm()
    {
        $counseling_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\CounselingPayloadController');

        $counseling_payload_mock->shouldReceive('getIntroduceBlockConstitution')
            ->andReturn(['key' => 'value']);

        app()->make('App\Http\Controllers\CounselingController')->executeChatPostMessageOfIntroduceQuestionForm();
    }

    /**
    * CounselingController@executeChatPostMessageOfIntroduceQuestionFormの例外処理テスト
    * 相談会申し込みフォームを紹介するメッセージを送信する際に例外が発生した場合、例外処理を無事に行えるか
    *
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testErrorExecuteChatPostMessageOfIntroduceQuestionFormIfExpectionOccurs()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Counseling payload controller returned error code');

        $counseling_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\CounselingPayloadController');

        $error = 'Counseling payload controller returned error code';

        $counseling_payload_mock->shouldReceive('getIntroduceBlockConstitution')
            ->andThrow(new InvalidArgumentException($error));

        app()->make('App\Http\Controllers\CounselingController')->executeChatPostMessageOfIntroduceQuestionForm();
    }
}
