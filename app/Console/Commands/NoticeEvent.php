<?php

namespace App\Console\Commands;

use App\Model\Event;
use App\Model\EventParticipant;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use JoliCode\Slack\ClientFactory;

class NoticeEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:notice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command notices events.';

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
        app()->make('App\Http\Controllers\EventController')->noticeEvent();
    }
}
