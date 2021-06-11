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
            if ($payload['type'] === "view_submission") {//モーダルのフォームが送信された場合
                switch ($payload['view']['callback_id']) {
                    case 'apply_counseling'://相談会申し込みフォーム
                        app()->make('App\Http\Controllers\CounselingController')->notifyToMentor($payload);
                        break;

                    case 'create_event'://イベント作成フォーム
                        app()->make('App\Http\Controllers\EventController')->createEvent($payload);
                        break;

                    case 'ask_questions': //匿名質問フォーム
                        app()->make('App\Http\Controllers\AnonymousQuestionController')->sendQuestionToChannel($payload);
                        break;
                }
            } elseif ($payload['type'] === "block_actions") {//block要素でアクションがあった場合
                switch ($payload['actions'][0]['action_id']) {
                    case 'register_participant': //イベントの参加者を登録する場合
                        app()->make('App\Http\Controllers\EventParticipantController')->create($payload);
                        break;

                    case 'remove_participant'://イベントの参加者を削除する場合
                        app()->make('App\Http\Controllers\EventParticipantController')->remove($payload);
                        break;

                    case 'delete_event': //イベントの削除
                        app()->make('App\Http\Controllers\EventController')->deleteEvent($payload);
                        break;
                }
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
