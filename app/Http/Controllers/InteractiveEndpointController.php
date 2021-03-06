<?php

namespace App\Http\Controllers;

use App\Model\Event;
use App\Model\EventParticipant;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vluzrmos\SlackApi\Facades\SlackChat;
use Vluzrmos\SlackApi\Facades\SlackUser;

class InteractiveEndpointController extends Controller
{
    public function __invoke(Request $request){
        try {
            response('',200)->send();//3秒以内にレスポンスを返さないとタイムアウト扱いになるので最初に空レスポンスをしておく

            $payload = $request->input('payload');
            $postData = \GuzzleHttp\json_decode($payload, true);

            //interactive endpointは全てのアクションにおいて共通なので、この先もっとアクションが増えることを考えるとアクションタイプで分類した後にアクションの識別子で各処理を区別する方がわかりやすいかと思いそうしています
            if($postData['type'] === "view_submission"){//モーダルのフォームが送信された場合
                switch ($postData['view']['callback_id']) {
                    case 'create_event'://イベント作成フォーム
                        DB::beginTransaction();

                        try {
                            $event_name = $postData['view']['state']['values']['name']['name']['value'];
                            $event_description = $postData['view']['state']['values']['description']['description']['value'];
                            $event_url = $postData['view']['state']['values']['url']['url']['value'];

                            $event_datetime=new DateTime($postData['view']['state']['values']['event_date']['event-date']['selected_date']);//年月日だけでDateTime型作成
                            $event_datetime->modify("+".$postData['view']['state']['values']['event_time']['event-hour']['selected_option']['value']." hour")->modify("+".$postData['view']['state']['values']['event_time']['event-minute']['selected_option']['value']." minute");//時、分を各フォームから取得し、上で作成したDateTime型に情報を追加

                            $notice_datetime=new DateTime($postData['view']['state']['values']['notice_date']['notice-date']['selected_date']);//年月日だけでDateTime型作成
                            $notice_datetime->modify("+".$postData['view']['state']['values']['notice_time']['notice-hour']['selected_option']['value']." hour")->modify("+".$postData['view']['state']['values']['notice_time']['notice-minute']['selected_option']['value']." minute");//時、分を各フォームから取得し、上で作成したDateTime型に情報を追加

                            $event = Event::create([
                                'name' => $$event_name,
                                'description' => $event_description,
                                'event_datetime' => $event_datetime,
                                'notice_datetime' => $notice_datetime,
                                'url' => $event_url
                            ]);

                            SlackChat::message($postData['user']['id'], "イベントを登録しました！\n\n```イベント名:{$event_name}\nイベント詳細:{$event_description}\nイベントURL:{$event_url}\nイベント日時:{$event_datetime->format('Y年m月d日 H:i')}\nお知らせする日時:{$notice_datetime->format('Y年m月d日 H:i')}```");

                            DB::commit();
                        } catch (\Throwable $th) {
                            DB::rollBack();

                            SlackChat::message($postData['user']['id'], "エラーが発生し、イベントを登録できませんでした。もう一度お試しください。");
                        }
                        break;
                }
            }else if($postData['type'] === "block_actions"){//block要素でアクションがあった場合
                switch ($postData['actions'][0]['action_id']) {
                    case 'Register_to_attend_the_event': //イベントの参加者登録
                        DB::beginTransaction();

                        try {
                            $event_id = $postData['message']['blocks'][1]['elements'][0]['value'];
                            $participant_slack_user_id = $postData['user']['id'];

                            $registered = EventParticipant::where('event_id',$event_id)->where('slack_user_id',$participant_slack_user_id)->first();
                            if($registered === null){//まだ参加登録していなければ登録
                                EventParticipant::create(['event_id' => $event_id,'slack_user_id' => $participant_slack_user_id]);
                            }else{//既に参加登録していれば削除
                                $registered->delete();
                            }

                            $event_participant_ids = EventParticipant::select('slack_user_id')->where('event_id',$event_id)->get();
                            $event_participants = "";
                            foreach ($event_participant_ids as $event_participant_id) {//参加者一覧を一つの文字列に
                                $event_participants .= "<@".$event_participant_id->slack_user_id."> ";
                            }
                            if($event_participants === ""){//参加者がいない場合
                                $event_participants = 'まだいません。';
                            }

                            $blocks = '[
                                            {
                                                "type": "section",
                                                "text": {
                                                    "type": "mrkdwn",
                                                    "text": "'.$postData['message']['blocks'][0]['text']['text'].'"
                                                }
                                            },
                                            {
                                                "type": "actions",
                                                "elements": [
                                                    {
                                                        "type": "button",
                                                        "text": {
                                                            "type": "plain_text",
                                                            "text": "参加する！",
                                                            "emoji": true
                                                        },
                                                        "value": "'.$event_id.'",
                                                        "action_id": "Register_to_attend_the_event"
                                                    }
                                                ]
                                            },
                                            {
                                                "type": "section",
                                                "text": {
                                                    "type": "mrkdwn",
                                                    "text": "参加者'."\n $event_participants".'"
                                                }
                                            }
                                        ]';

                            SlackChat::update($postData['container']['channel_id'],"",$postData['message']['ts'],['blocks'=>$blocks.']']);

                            DB::commit();
                        } catch (\Throwable $th) {
                            DB::rollBack();
                        }
                        break;
                }
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
        return 0;
    }
}
