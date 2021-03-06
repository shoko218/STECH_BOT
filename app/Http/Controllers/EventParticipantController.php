<?php

namespace App\Http\Controllers;

use App\Model\Event;
use App\Model\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JoliCode\Slack\ClientFactory;

class EventParticipantController extends Controller
{
    private $slack_client;

    public function __construct()//クライアントを作成
    {
        $this->slack_client = ClientFactory::create(config('services.slack.token'));
    }

    /**
     * イベントの参加者を登録する
     *
     * @param array $payload
     */
    public function create($payload)
    {
        response('', 200)->send();//3秒以内にレスポンスを返さないとタイムアウト扱いになるので最初に空レスポンスをしておく

        DB::beginTransaction();
        try {
            $event = $this->executeCreateEventParticipantToDB($payload);
            app()->make('App\Http\Controllers\EventController')->updateEventPosts($event);
            DB::commit();
        } catch (\Throwable $th) {
            Log::info($th);
            DB::rollBack();
        }
    }

    /**
     * DBへのイベントの参加者登録を実行する
     *
     * @param array $payload
     * @return Event $event
     */
    public function executeCreateEventParticipantToDB($payload)
    {
        $event = Event::find($payload['message']['blocks'][4]['elements'][0]['value']);
        $participant_slack_user_id = $payload['user']['id'];

        $registered = EventParticipant::where('event_id', $event->id)->where('slack_user_id', $participant_slack_user_id)->first();
        if ($registered === null) {//まだ参加登録していなければ登録
            EventParticipant::create(['event_id' =>  $event->id,'slack_user_id' => $participant_slack_user_id]);
        }
        return $event;
    }

    /**
     * イベントの参加者を削除する
     *
     * @param array $payload
     */
    public function remove($payload)
    {
        response('', 200)->send();//3秒以内にレスポンスを返さないとタイムアウト扱いになるので最初に空レスポンスをしておく

        DB::beginTransaction();
        try {
            $event = $this->executeRemoveEventParticipantFromDB($payload);
            app()->make('App\Http\Controllers\EventController')->updateEventPosts($event);
            DB::commit();
        } catch (\Throwable $th) {
            Log::info($th);
            DB::rollBack();
        }
    }

    /**
     * DBからのイベント参加登録の削除を実行する
     *
     * @param array $payload
     * @return Event $event
     */
    public function executeRemoveEventParticipantFromDB($payload)
    {
        $event = Event::find($payload['message']['blocks'][4]['elements'][0]['value']);
        $participant_slack_user_id = $payload['user']['id'];

        $registered = EventParticipant::where('event_id', $event->id)->where('slack_user_id', $participant_slack_user_id)->first();
        if ($registered !== null) {//既に参加登録していれば削除
            $registered->delete();
        }
        return $event;
    }
}
