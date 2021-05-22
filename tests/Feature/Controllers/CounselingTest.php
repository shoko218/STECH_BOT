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
    * CounselingController@showApplicationModalの正常処理テスト
    * 申し込みフォームが表示できるか
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

        $response = app()->make('App\Http\Controllers\CounselingController')->showApplicationModal($request);

        $this->assertStringContainsString('ok', $response);
    }

    /**
    * CounselingController@showApplicationModalの例外処理テスト
    * 申し込みフォーム表示の際に例外が発生した場合、例外処理を無事に行えるか
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

        $response = app()->make('App\Http\Controllers\CounselingController')->showApplicationModal($request);

        $this->assertStringContainsString(false, $response);
    }

    /**
    * CounselingController@notifyToMentorの正常処理テスト
    * メンターさんに申し込み内容を送信できるか

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

        $response = app()->make('App\Http\Controllers\CounselingController')->notifyToMentor($payload);

        $this->assertCount(2, $response);
        $this->assertContainsOnly('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response);
    }

    /**
    * CounselingController@notifyToMentorの例外処理テスト
    * メンターさんに申し込み内容を送信する際に例外が発生した場合、例外処理を無事に行えるか

    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testErrorNotifyToMentorIfExpectionOccurs()
    {
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

        $response = app()->make('App\Http\Controllers\CounselingController')->notifyToMentor($payload);

        $this->assertStringContainsString(false, $response);
    }

    /**
    * CounselingController@introduceQuestionFormの正常処理テスト
    * 相談会申し込みフォームを紹介するメッセージを送信できるか
    *
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testSuccessIntroduceQuestionForm()
    {
        $counseling_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\CounselingPayloadController');

        $counseling_payload_mock->shouldReceive('getIntroduceBlockConstitution')
            ->andReturn(['key' => 'value']);

        $response = app()->make('App\Http\Controllers\CounselingController')->introduceQuestionForm();

        $this->assertInstanceOf('JoliCode\Slack\Api\Model\ChatPostMessagePostResponse200', $response);
    }

    /**
    * CounselingController@introduceQuestionFormの例外処理テスト
    * 相談会申し込みフォームを紹介するメッセージを送信する際に例外が発生した場合、例外処理を無事に行えるか
    *
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testErrorIntroduceQuestionFormIfExpectionOccurs()
    {
        $counseling_payload_mock = Mockery::mock('overload:App\Http\Controllers\BlockPayloads\CounselingPayloadController');

        $error = 'Counseling payload controller returned error code';

        $counseling_payload_mock->shouldReceive('getIntroduceBlockConstitution')
            ->andThrow(new InvalidArgumentException($error));

        $response = app()->make('App\Http\Controllers\CounselingController')->introduceQuestionForm();

        $this->assertStringContainsString(false, $response);
    }
}
