<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
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
    * 申し込みフォームを表示する
    *
    * @param Request $request
    * @return void
    */
    public function showApplicationModal(Request $request)
    {
        $params = [
            'view' => json_encode($this->counseling_payloads->getModalConstitution()),
            'trigger_id' => $request->trigger_id
        ];

        $this->slack_client->viewsOpen($params);
        response('', 200)->send();
    }

    /**
    * メンターさんに申し込み内容を送信する
    *
    * @param array $payload
    * @return void
    */
    public function notifyToMentor($payload)
    {
        $this->slack_client->chatPostMessage([
            'channel' => config('const.slack_id.mentor_channel'),
            'blocks' => json_encode($this->counseling_payloads->getCompletedApplyBlockConstitution($payload))
        ]);

        $this->slack_client->chatPostMessage([
            'channel' => $payload['user']['id'],
            'blocks' => json_encode($this->counseling_payloads->getNotifyApplyBlockConstitution($payload))
        ]);

        response('', 200)->send();
    }


    /**
     * 相談会申し込みフォームを紹介するメッセージを送信する
     */
    public function introduceQuestionForm()
    {
        $this->slack_client->chatPostMessage([
            'channel' => config('const.slack_id.general'),
            'blocks' => json_encode($this->counseling_payloads->getIntroduceBlockConstitution())
        ]);
    }
}
