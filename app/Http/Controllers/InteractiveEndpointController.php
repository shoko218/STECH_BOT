<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InteractiveEndpointController extends Controller
{
   /**
    *  全てのアクションにおいて共通のinteractive endpoint
    *  
    *  処理の分岐点としてアクションタイプと識別子で各処理を区別
    */
    public function __invoke(Request $request){
        try {
            $payload = json_decode($request->input('payload'), true);

            if($payload['type'] === "view_submission"){//モーダルのフォームが送信された場合
                switch ($payload['view']['callback_id']) {
                    case 'create_event'://イベント作成フォーム
                        app()->make('App\Http\Controllers\EventController')->createEvent($payload);
                        break;
                    case 'ask_questions': //匿名質問フォーム
                        app()->make('App\Http\Controllers\AnonymousQuestionController')->sendQuestionToChannel($payload);
                }
            }else if($payload['type'] === "block_actions"){//block要素でアクションがあった場合
                switch ($payload['actions'][0]['action_id']) {
                    case 'register_to_attend_event': //イベントの参加者登録
                        app()->make('App\Http\Controllers\EventParticipantController')->registerToAttendEvent($payload);
                        break;
                }
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
