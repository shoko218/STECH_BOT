<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JoliCode\Slack\ClientFactory;

class CounselingController extends Controller
{
    private $slack_client;
    private $counseling_payloads;

    public function __construct()
    {
        $this->slack_client = ClientFactory::create(config('services.slack.token'));
        $this->counseling_payloads = app()->make('App\Http\Controllers\BlockPayloads\CounselingPayloadController');
    }

    /**
    * 相談会申し込みフォームを表示する
    *
    * @param Request $request
    */
    public function showApplicationModal(Request $request)
    {
        response('', 200)->send();
        try {
            $this->executeViewsOpenOfShowApplicationModal($request->trigger_id);
        } catch (\Throwable $th) {
            Log::info($th);
            $this->slack_client->chatPostMessage([
                'channel' => $request->user_id,
                'text' => ':warning: エラーが発生し、フォームを表示できませんでした。もう一度お試しください。'
            ]);
        }
    }

    /**
    * 相談会申し込みモーダルでslack apiのviews.Openを実行する
    *
    * @param string $trigger_id
    */
    public function executeViewsOpenOfShowApplicationModal($trigger_id)
    {
        $this->slack_client->viewsOpen([
            'trigger_id' => $trigger_id,
            'view' => json_encode($this->counseling_payloads->getModalConstitution())
        ]);
    }

    /**
    * メンターさんに申し込み内容を送信する
    *
    * @param array $payload
    */
    public function notifyToMentor($payload)
    {
        try {
            $this->executeChatPostMessageOfNotifyToMentor($payload);
        } catch (\Throwable $th) {
            Log::info($th);
            $this->slack_client->chatPostMessage([
                'channel' => $payload['user']['id'],
                'text' => ':warning: エラーが発生し、申し込みを完了できませんでした。もう一度お試しください。'
            ]);
        }
    }

    /**
    * 申し込み情報送信メッセージでslack apiのchat.postMessageを実行する
    *
    * @param array $payload
    */
    public function executeChatPostMessageOfNotifyToMentor($payload)
    {
        $this->slack_client->chatPostMessage([
            'channel' => config('const.slack_id.mentor_channel'),
            'blocks' => json_encode($this->counseling_payloads->getCompletedApplyBlockConstitution($payload))
        ]);

        $this->slack_client->chatPostMessage([
            'channel' => $payload['user']['id'],
            'blocks' => json_encode($this->counseling_payloads->getNotifyApplyBlockConstitution($payload))
        ]);
    }

    /**
     * 相談会申し込みフォームを紹介するメッセージを送信する
     */
    public function introduceQuestionForm()
    {
        try {
            $this->executeChatPostMessageOfIntroduceQuestionForm();
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }


    /**
     * 相談会申し込みフォーム紹介メッセージでslack apiのchat.postMessageを実行する
     */
    public function executeChatPostMessageOfIntroduceQuestionForm()
    {
        $this->slack_client->chatPostMessage([
            'channel' => config('const.slack_id.general'),
            'blocks' => json_encode($this->counseling_payloads->getIntroduceBlockConstitution())
        ]);
    }
}
