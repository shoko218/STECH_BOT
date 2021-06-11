<?php

namespace Tests\Feature\Controllers;

use App\Http\Controllers\AnonymousQuestionController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use Illuminate\Http\Request;
use JoliCode\Slack\Exception\SlackErrorResponse;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

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
    public function provideSlackClientMock ()
    {
        $client_factory_mock = Mockery::mock('alias:'. JoliCode\Slack\ClientFactory::class);
        $client_factory_mock->shouldReceive('create')
                        ->with('dummy token')
                        ->andReturn(new JoliCode\Slack\Api\Client);
    
        return JoliCode\Slack\ClientFactory::create('dummy token');
    }

    /**
     * AnonymousQuestionController@openQuestionFormのテスト
     * 
     * ルート"/slash/ask_questions"が正しく設定されているかをテストする
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRoutingToOpenQuestionForm ()
    {
        $mock = Mockery::mock('overload:'.AnonymousQuestionController::class);
        $mock->shouldReceive('openQuestionForm')
            ->andReturn(response('', 200));

        $response = $this->json('POST', '/slash/ask_questions', ['trigger_id'=>'12345.98765.abcd2358fdea']);
        $response->assertStatus(200);
    }

    /**
     * AnonymousQuestionController@executeViewsOpenの正常処理テスト
     *
     * @return void
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessExecuteViewsOpen ()
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

        $anonymous_question_controller->openQuestionForm($dummy_request);
    }

    /**
     * AnonymousQuestionController@executeViewsOpenの例外処理テスト
     *
     * @return void
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorExecuteViewsOpen ()
    {
        $this->expectException(SlackErrorResponse::class);
        $this->expectExceptionMessage('Slack returned error code "dummy exception: viewsOpen"');

        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('viewsOpen')
            ->andThrow(new SlackErrorResponse('dummy exception: viewsOpen'))
            ->getMock();

        $slack_client_mock = $this->provideSlackClientMock();
        $anonymous_question_controller = new AnonymousQuestionController($slack_client_mock);
        

        $dummy_request = new Request();
        $dummy_request->replace(['trigger_id'=>'']);
        $anonymous_question_controller->executeViewsOpen($dummy_request); 
    }

    /**
     * ダミーのpayloadを返す
     */
    public function provideDummyPayload ()
    {
        return ['view'=>[
            'state'=>[
                'values'=>[
                    'mentors-block'=>['mentor'=>['selected_option'=>['value'=>0]]],
                    'question-block'=>['question'=>['value'=>'Laravelについて']]
                ]
            ]
        ]];
    }

    /**
     * AnonymousQuestionController@executeChatPostMessageOfQuestionの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessExecuteChatPostMessageOfQuestion ()
    {
        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->with(\Mockery::on(function ($contents) {
                $contents_isset = isset($contents['channel']) && isset($contents['username']) && isset($contents['blocks']);
                return $contents_isset;
            }))
            ->andReturn(true);

        $slack_client_mock = $this->provideSlackClientMock();
        $anonymous_question_controller = new AnonymousQuestionController($slack_client_mock);

        $anonymous_question_controller->executeChatPostMessageOfQuestion($this->provideDummyPayload());
    }

    /**
     * AnonymousQuestionController@executeChatPostMessageOfQuestionの例外処理テスト
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorExecuteChatPostMessageOfQuestion ()
    {
        $this->expectException(SlackErrorResponse::class);
        $this->expectExceptionMessage('Slack returned error code "dummy exception: chatPostMessage"');

        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->andThrow(new SlackErrorResponse('dummy exception: chatPostMessage'))
            ->getMock();

        $slack_client_mock = $this->provideSlackClientMock();
        $anonymous_question_controller = new AnonymousQuestionController($slack_client_mock);
    
        $anonymous_question_controller->executeChatPostMessageOfQuestion($this->provideDummyPayload());
    }

    /**
     * AnonymousQuestionController@sendQuestionToChannelの正常処理テスト
     */
    public function testSuccessSendQuestionToChannel ()
    {
        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->with(\Mockery::on(function ($contents) {
                $contents_isset = isset($contents['channel']) && isset($contents['username']) && isset($contents['blocks']);
                return $contents_isset;
            }))
            ->andReturn(true);
        
        $slack_client_mock = $this->provideSlackClientMock();
        $anonymous_question_controller = new AnonymousQuestionController($slack_client_mock);

        $anonymous_question_controller->sendQuestionToChannel($this->provideDummyPayload());
    }

    /**
     * AnonymousQuestionController@executeChatPostMessageOfIntroductionの正常処理テスト
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessExecuteChatPostMessageOfIntroduction ()
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

        $anonymous_question_controller->executeChatPostMessageOfIntroduction();
    }

    /**
     * AnonymousQuestionController@executeChatPostMessageOfIntroductionの例外処理テスト
     * 
     * api接続失敗時にエラー
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorExecuteChatPostMessageOfIntroduction ()
    {
        $this->expectException(SlackErrorResponse::class);
        $this->expectExceptionMessage('Slack returned error code "dummy exception: chatPostMessage"');

        $api_mock = Mockery::mock('overload:'.JoliCode\Slack\Api\Client::class);
        $api_mock->shouldReceive('chatPostMessage')
            ->andThrow(new SlackErrorResponse('dummy exception: chatPostMessage'))
            ->getMock();

        $slack_client_mock = $this->provideSlackClientMock();
        $anonymous_question_controller = new AnonymousQuestionController($slack_client_mock);

        $anonymous_question_controller->executeChatPostMessageOfIntroduction();
    }
}