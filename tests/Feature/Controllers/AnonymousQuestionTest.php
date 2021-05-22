<?php

namespace Tests\Feature\Controllers;

use App\Http\Controllers\AnonymousQuestionController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;
use Illuminate\Http\Request;
use JoliCode\Slack\Exception\SlackErrorResponse;

class AnonymousQuestionTest extends TestCase
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
    public function provideSlackClientMock()
    {
        $client_factory_mock = Mockery::mock('alias:'. JoliCode\Slack\ClientFactory::class);
        $client_factory_mock->shouldReceive('create')
                        ->with('dummy token')
                        ->andReturn(new JoliCode\Slack\Api\Client);
    
        return JoliCode\Slack\ClientFactory::create('dummy token');
    }

    /**
     * ルート"/slash/ask_questions"のテスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAskQuestionsRoute ()
    {
        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('viewsOpen')
            ->with(Mockery::any())
            ->andReturn(true);

        $slack_client_mock = $this->provideSlackClientMock();

        $anonymous_question_controller = Mockery::mock(
            'App\Http\Controllers\AnonymousQuestionController[openQuestionForm]', 
            [$slack_client_mock]
        );

        $anonymous_question_controller->shouldReceive('openQuestionForm')
                            ->with(Mockery::any())
                            ->andReturn(http_response_code( 200 ));

        $response = $this->json('POST', '/slash/ask_questions', ['trigger_id'=>'12345.98765.abcd2358fdea']);
        $response->assertStatus(200);
    }

    /**
     * AnonymousQuestionController@openQuestionFormの正常処理テスト
     *
     * @return void
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessOpenQuestionForm()
    {
        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('viewsOpen')
            ->with(\Mockery::on(function ($query_params) {
                $params_isset = isset($query_params['view']) && isset($query_params['trigger_id']);
                return $params_isset;
            }))
            ->andReturn(true);

        $slack_client_mock = $this->provideSlackClientMock();
        $anonymous_question_controller = new AnonymousQuestionController($slack_client_mock);

        $dummy_request = new Request();
        $dummy_request->replace(['trigger_id'=>'12345.98765.abcd2358fdea']);

        $this->assertEquals(
            'ok', 
            $anonymous_question_controller->openQuestionForm($dummy_request)
        );
    }

    /**
     * AnonymousQuestionController@openQuestionFormの例外処理テスト
     * 
     * 引数が存在しない場合とapi接続失敗時にエラー
     *
     * @return void
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorOpenQuestionForm ()
    {
        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('viewsOpen')
            ->andThrow(new SlackErrorResponse('Slack returned error code'))
            ->getMock();

        $slack_client_mock = $this->provideSlackClientMock();
        $anonymous_question_controller = new AnonymousQuestionController($slack_client_mock);
        
        $dummy_request = new Request();
        $dummy_request->replace(['trigger_id'=>'']);

        $this->assertEquals(
            'error',
            $anonymous_question_controller->openQuestionForm($dummy_request)
        );
    }

    /**
     * AnonymousQuestionController@sendQuestionToChannelの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessSendQuestionToChannel ()
    {
        $dummy_payload = ['view'=>[
            'state'=>[
                'values'=>[
                    'mentors-block'=>['mentor'=>['selected_option'=>['value'=>0]]],
                    'question-block'=>['question'=>['value'=>'Laravelについて']]
                ]
            ]
        ]];

        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->with(\Mockery::on(function ($contents) {
                $contents_isset = isset($contents['channel']) && isset($contents['username']) && isset($contents['blocks']);
                return $contents_isset;
            }))
            ->andReturn(true);

        $slack_client_mock = $this->provideSlackClientMock();
        $anonymous_question_controller = new AnonymousQuestionController($slack_client_mock);

        $this->assertTrue($anonymous_question_controller->sendQuestionToChannel($dummy_payload));
    }

    /**
     * AnonymousQuestionController@sendQuestionToChannelの例外処理テスト
     * 
     * api接続失敗時にエラー
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorSendQuestionToChannel ()
    {
        $dummy_payload = ['view'=>[
            'state'=>[
                'values'=>[
                    'mentors-block'=>['mentor'=>['selected_option'=>['value'=>0]]],
                    'question-block'=>['question'=>['value'=>'Laravelについて']]
                ]
            ]
        ]];

        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->andThrow(new SlackErrorResponse('Slack returned error code'))
            ->getMock();

        $slack_client_mock = $this->provideSlackClientMock();
        $anonymous_question_controller = new AnonymousQuestionController($slack_client_mock);
    
        $this->assertFalse($anonymous_question_controller->sendQuestionToChannel($dummy_payload));
    }

    /**
     * AnonymousQuestionController@introduceQuestionFormの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessIntroduceQuestionForm()
    {
        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->with(\Mockery::on(function ($message_contents) {
                $contents_isset = isset($message_contents['channel']) && isset($message_contents['blocks']);
                return $contents_isset;
            }))
            ->andReturn(true);

        $slack_client_mock = $this->provideSlackClientMock();
        $anonymous_question_controller = new AnonymousQuestionController($slack_client_mock);

        $this->assertTrue($anonymous_question_controller->introduceQuestionForm());
    }

    /**
     * AnonymousQuestionController@introduceQuestionFormの例外処理テスト
     * 
     * api接続失敗時にエラー
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorIntroduceQuestionForm()
    {
        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->andThrow(new SlackErrorResponse('Slack returned error code'))
            ->getMock();

        $slack_client_mock = $this->provideSlackClientMock();
        $anonymous_question_controller = new AnonymousQuestionController($slack_client_mock);

        $this->assertFalse($anonymous_question_controller->introduceQuestionForm());
    }
}
