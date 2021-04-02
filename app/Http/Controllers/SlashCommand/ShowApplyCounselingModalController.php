<?php

namespace App\Http\Controllers\SlashCommand;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShowApplyCounselingModalController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            app()->make('App\Http\Controllers\CounselingController')->showApplicationModal($request->input('trigger_id'));
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
