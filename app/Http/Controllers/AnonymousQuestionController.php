<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JoliCode\Slack\ClientFactory;
use JoliCode\Slack\Exception\SlackErrorResponse;

class AnonymousQuestionController extends Controller
{
    private $slack_client;

    public function __construct($generated_slack_client = null)
    {
        if (is_null($generated_slack_client)) {
            $this->slack_client = ClientFactory::create(config('services.slack.token'));
        } else {
            $this->slack_client = $generated_slack_client;
        }
    }

    /**
     * /ask-questionのコマンドの応答としてモーダルを表示
     *
     * @param Request $request
     */
    public function openQuestionForm(Request $request)
    {
        response('', 200)->send();

        try {
            $query_params = [
                'view' => json_encode(app()->make('App\Http\Controllers\BlockPayloads\AnonymousQuestionPayloadController')->createQuestionFormView()),
                'trigger_id' => $request->input('trigger_id')
            ];

            $this->slack_client->viewsOpen($query_params);

            return 'ok';
        } catch (SlackErrorResponse $e) {
            Log::info($e->getMessage());
            return 'error';
        }
    }

    /**
     * 受け付けた匿名質問をメッセージとして公開チャンネルに流す
     *
     * @param Request $request
     * @var string $mention メンション先を定義
     */
    public function sendQuestionToChannel($payload)
    {
        try {
            $user_inputs = $payload['view']['state']['values'];
            $mentor_number = intval($user_inputs['mentors-block']['mentor']['selected_option']['value']);
            $question_sentence = $user_inputs['question-block']['question']['value'];

            $mention = $mentor_number == count(config("const.slack_id.mentors")) ? ' 全体へ' : config("const.slack_id.mentors")[$mentor_number]['id'];
            $this->slack_client->chatPostMessage([
                'channel' => config('const.slack_id.question_channel'),
                'username' => '匿名の相談です',
                'icon_url' => 'https://2.bp.blogspot.com/-VVtgu8RyEJo/VZ-QWqgI_wI/AAAAAAAAvKY/N-xnZvqeGYY/s800/girl_question.png',
                'blocks' => json_encode([
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => "<@$mention>",
                        ]
                    ],
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => "匿名の質問です！",
                        ]
                    ],
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => "\n[内容] \n$question_sentence",
                        ]
                    ]
                ])
            ]);

            return true;
        } catch (SlackErrorResponse $e) {
            Log::info($e->getMessage());
            return false;
        }
    }/**
    * 匿名質問フォームを紹介するメッセージを送る
    */
    public function introduceQuestionForm()
    {
        try {
            $this->slack_client->chatPostMessage([
                'channel' => config('const.slack_id.question_channel'),
                'blocks' => json_encode(app()->make('App\Http\Controllers\BlockPayloads\AnonymousQuestionPayloadController')->createQuestionFormIntroductionBlocks())
            ]);

            return true;
        } catch (SlackErrorResponse $e) {
            Log::info($e->getMessage());
            return false;
        }
    }
}
