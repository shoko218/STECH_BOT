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
    * @return void
    */
    public function registerToAttendEvent($payload)
    {
        response('',200)->send();//3秒以内にレスポンスを返さないとタイムアウト扱いになるので最初に空レスポンスをしておく

        DB::beginTransaction();
        try {
            $event = Event::find($payload['message']['blocks'][1]['elements'][0]['value']);
            $participant_slack_user_id = $payload['user']['id'];

            $registered = EventParticipant::where('event_id',$event->id)->where('slack_user_id',$participant_slack_user_id)->first();
            if($registered === null){//まだ参加登録していなければ登録
                EventParticipant::create(['event_id' =>  $event->id,'slack_user_id' => $participant_slack_user_id]);
            }else{//既に参加登録していれば削除
                $registered->delete();
            }

        $event_controller = app()->make('App\Http\Controllers\EventController');

        if($event->notice_ts != null){//既にお知らせしていればお知らせ投稿を更新
            $this->slack_client->chatUpdate([
                'channel' => 'C01NCNM4WQ6',
                'ts' => $event->notice_ts,
                'blocks' => json_encode($event_controller->getNoticeEventBlocks($event)),
            ]);
        }

        if ($event->remind_ts != null) {//既にリマインドしていればリマインド投稿を更新
            $this->slack_client->chatUpdate([
                'channel' => 'C01NCNM4WQ6',
                'ts' => $event->remind_ts,
                'blocks' => json_encode($event_controller->getRemindEventBlocks($event)),
            ]);
        }
            DB::commit();
        } catch (\Throwable $th) {
            Log::info($th);
            DB::rollBack();
        }
    }
}
