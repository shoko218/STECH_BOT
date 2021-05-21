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
    * @return mixed
    */
    public function showApplicationModal(Request $request)
    {
        try {
            $response = $this->slack_client->viewsOpen([
                'trigger_id' => $request->trigger_id,
                'view' => json_encode($this->counseling_payloads->getModalConstitution())
            ]);
            response('', 200)->send();
            return $response;
        } catch (\Throwable $th) {
            Log::info($th);
            $response = $this->slack_client->chatPostMessage([
                'channel' => $request->user_id,
                'text' => ':warning: エラーが発生し、フォームを表示できませんでした。もう一度お試しください。'
            ]);
            return false;
        }
    }

    /**
    * メンターさんに申し込み内容を送信する
    *
    * @param array $payload
    * @return mixed
    */
    public function notifyToMentor($payload)
    {
        try {
            $response = [];
            $response[] = $this->slack_client->chatPostMessage([
                'channel' => config('const.slack_id.mentor_channel'),
                'blocks' => json_encode($this->counseling_payloads->getCompletedApplyBlockConstitution($payload))
            ]);

            $response[] = $this->slack_client->chatPostMessage([
                'channel' => $payload['user']['id'],
                'blocks' => json_encode($this->counseling_payloads->getNotifyApplyBlockConstitution($payload))
            ]);
            return $response;
        } catch (\Throwable $th) {
            Log::info($th);
            $this->slack_client->chatPostMessage([
                'channel' => $payload['user']['id'],
                'text' => ':warning: エラーが発生し、申し込みを完了できませんでした。もう一度お試しください。'
            ]);
            return false;
        }
    }


    /**
     * 相談会申し込みフォームを紹介するメッセージを送信する
     *
     * @return mixed
     */
    public function introduceQuestionForm()
    {
        try {
            $response = $this->slack_client->chatPostMessage([
                'channel' => config('const.slack_id.general'),
                'blocks' => json_encode($this->counseling_payloads->getIntroduceBlockConstitution())
            ]);
            return $response;
        } catch (\Throwable $th) {
            Log::info($th);
            return false;
        }
    }
}
