<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConfirmMeeting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meeting:confirm';

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
            app()->make('App\Http\Controllers\MeetingController')->askToHoldMeeting();
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
