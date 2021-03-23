<?php

namespace App\Http\Controllers\SlashCommand;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShowCreateEventModalController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $event_controller = app()->make('App\Http\Controllers\EventController');
            $event_controller->showCreateEventModal($request->input('trigger_id'));
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
