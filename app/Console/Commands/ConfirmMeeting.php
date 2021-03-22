<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConfirmMeeting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:confirm_meeting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ask to hold regular meetings.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            app()->make('App\Http\Controllers\MeetingController')->AskToHoldMeeting();
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
