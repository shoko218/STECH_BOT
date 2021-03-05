<?php

namespace App\Http\Controllers;

use App\Model\Event;
use App\Model\EventParticipant;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Vluzrmos\SlackApi\Facades\SlackChat;
use Vluzrmos\SlackApi\Facades\SlackUser;

class InteractiveEndpointController extends Controller
{
    public function __invoke(Request $request){
        try {
            //code...
            response('',200)->send();

            $payload = $request->input('payload');
            $postData = \GuzzleHttp\json_decode($payload, true);

            if($postData['type'] === "view_submission"){
                switch ($postData['view']['callback_id']) {
                    case 'create_event':
                        $name = $postData['view']['state']['values']['name']['name']['value'];
                        $description = $postData['view']['state']['values']['description']['description']['value'];
                        $url = $postData['view']['state']['values']['url']['url']['value'];

                        $event_datetime=new DateTime($postData['view']['state']['values']['event_date']['event-date']['selected_date']);
                        $event_datetime->modify("+".$postData['view']['state']['values']['event_time']['event-hour']['selected_option']['value']." hour")->modify("+".$postData['view']['state']['values']['event_time']['event-minute']['selected_option']['value']." minute");

                        $notice_datetime=new DateTime($postData['view']['state']['values']['notice_date']['notice-date']['selected_date']);
                        $notice_datetime->modify("+".$postData['view']['state']['values']['notice_time']['notice-hour']['selected_option']['value']." hour")->modify("+".$postData['view']['state']['values']['notice_time']['notice-minute']['selected_option']['value']." minute");

                        $event = Event::create([
                            'name' => $name,
                            'description' => $description,
                            'event_datetime' => $event_datetime,
                            'notice_datetime' => $notice_datetime,
                            'url' => $url
                        ]);

                        SlackChat::message($postData['user']['id'], "イベントを登録しました！\n\n```イベント名:{$name}\nイベント詳細:{$description}\nイベントURL:{$url}\nイベント日時:{$event_datetime->format('Y年m月d日 H:i')}\nお知らせする日時:{$notice_datetime->format('Y年m月d日 H:i')}```");

                        break;
                    }
            }else if($postData['type'] === "block_actions"){
                switch ($postData['actions'][0]['action_id']) {
                    case 'Register_to_attend_the_event':
                        $event_id = $postData['message']['blocks'][1]['elements'][0]['value'];
                        $slack_user_id = $postData['user']['id'];

                        $event = EventParticipant::where('event_id',$event_id)->where('slack_user_id',$slack_user_id)->first();
                        if($event === null){
                            EventParticipant::create(['event_id' => $event_id,'slack_user_id' => $slack_user_id]);
                        }else{
                            $event->delete();
                        }

                        $participant_ids = EventParticipant::select('slack_user_id')->where('event_id',$event_id)->get();
                        $participants = "";
                        foreach ($participant_ids as $participant_id) {
                            $participants .= "<@".$participant_id->slack_user_id."> ";
                        }

                        $blocks = '[{"type": "section","text": {"type": "mrkdwn","text": "'.$postData['message']['blocks'][0]['text']['text'].'"}},{"type": "actions","elements": [{"type": "button","text": {"type": "plain_text","text": "参加する！","emoji": true},"value": "'.$event_id.'","action_id": "Register_to_attend_the_event"}]}';

                        if($participants != ""){
                            $blocks .= ',{"type": "section","text": {"type": "mrkdwn","text": "参加者'."\n $participants".'"}}]';
                        }

                        $s = SlackChat::update($postData['container']['channel_id'],"",$postData['message']['ts'],['blocks'=>$blocks.']']);

                        Log::debug($postData['message']['ts']);
                        Log::debug(print_r($s, true));
                        break;
                }
            }
            Log::info("Task completed!");
        } catch (\Throwable $th) {
            Log::info($th);
        }
        return 0;
    }
}
