<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemindEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:remind_event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command reminds events.';

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
            $event_controller = app()->make('App\Http\Controllers\EventController');
            $event_controller->remindEvent();
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
