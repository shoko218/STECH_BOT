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
            if ($payload['type'] === "view_submission") {//モーダルのフォームが送信された場合
                switch ($payload['view']['callback_id']) {
                    case 'apply_counseling'://相談会申し込みフォーム
                        app()->make('App\Http\Controllers\CounselingController')->notifyToMentor($payload);
                        break;
                }
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
