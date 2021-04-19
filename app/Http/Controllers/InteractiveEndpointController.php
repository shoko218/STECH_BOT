<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InteractiveEndpointController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $payload = json_decode($request->input('payload'), true);

            /*interactive endpointは全てのアクションにおいて共通なので、
            この先もっとアクションが増えることを考えるとアクションタイプで分類した後に
            アクションの識別子で各処理を区別する方がわかりやすいかと思いそうしています*/

            if ($payload['type'] === "view_submission") {//モーダルのフォームが送信された場合
                switch ($payload['view']['callback_id']) {
                    case 'create_event'://イベント作成フォーム
                        app()->make('App\Http\Controllers\EventController')->createEvent($payload);
                        break;
                }
            } elseif ($payload['type'] === "block_actions") {//block要素でアクションがあった場合
                switch ($payload['actions'][0]['block_id']) {
                    case 'change_participant': //イベントの参加者に変更がある場合
                        if ($payload['actions'][0]['action_id'] === "register_participant") {
                            app()->make('App\Http\Controllers\EventParticipantController')->create($payload);
                        } elseif ($payload['actions'][0]['action_id'] === "remove_participant") {
                            app()->make('App\Http\Controllers\EventParticipantController')->remove($payload);
                        }
                        break;
                    case 'delete_event':
                        app()->make('App\Http\Controllers\EventController')->deleteEvent($payload);
                        break;
                }
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
