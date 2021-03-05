<?php

namespace App\Console\Commands;

use App\Model\Event;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Vluzrmos\SlackApi\Facades\SlackChat;

class ShareEventUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:share_event_url';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
            $now = new DateTime();
            $events = Event::whereDate('event_datetime','=', date('Y-m-d'))->get();
            foreach($events as $event){
                $event_datetime = new DateTime($event->event_datetime);
                $event_datetime->modify('-30 minutes');
                if($event_datetime->format('Y/m/d H:i') === $now->format('Y/m/d H:i')){
                    $message = SlackChat::message("#general","【イベントURLのお知らせ】\n本日{$event_datetime->format('H時i分')}から開催する *{$event->name}* のURLはこちらです!\n{$event->url}");
                    Log::info("noticed!");
                }
            }
            Log::info("task completed!");
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
