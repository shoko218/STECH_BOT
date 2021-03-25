<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InteractiveEndpointController extends Controller
{
    public function __invoke(Request $request){
        try {
            $payload = json_decode($request->input('payload'), true);

            /*interactive endpointは全てのアクションにおいて共通なので、
            この先もっとアクションが増えることを考えるとアクションタイプで分類した後に
            アクションの識別子で各処理を区別する方がわかりやすいかと思いそうしています*/

            if($payload['type'] === "view_submission"){//モーダルのフォームが送信された場合
                switch ($payload['view']['callback_id']) {
                    case 'create_event'://イベント作成フォーム
                        app()->make('App\Http\Controllers\EventController')->createEvent($payload);
                        break;
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

