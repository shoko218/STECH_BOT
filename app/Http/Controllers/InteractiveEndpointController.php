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
    public function __invoke(Request $request)
    {
        try {
            $payload = json_decode($request->input('payload'), true);

            if($payload['type'] === "view_submission"){//モーダルのフォームが送信された場合
                switch ($payload['view']['callback_id']) {
                    case 'create_event'://イベント作成フォーム
                        app()->make('App\Http\Controllers\EventController')->createEvent($payload);
                        break;
                }
            }else if($payload['type'] === "block_actions"){//block要素でアクションがあった場合
                switch ($payload['actions'][0]['block_id']) {
                    case 'confirm_meeting': //次週ミーティングの予定登録
                        app()->make('App\Http\Controllers\MeetingController')->notifyMeetingSettingsCompletion($payload);
                        break;
                }
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
