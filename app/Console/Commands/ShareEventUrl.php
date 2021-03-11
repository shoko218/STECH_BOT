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
            $start_time = new DateTime();
            $start_time->modify('+15 minutes');
            $coming_soon_events = Event::whereDate('event_datetime',$start_time->format('Y-m-d'))->whereTime('event_datetime',$start_time->format('H:i:').'00')->get();//15分後に始まるイベントを取得
            foreach($coming_soon_events as $event){
                SlackChat::message("#seg-test-channel","<!channel> 【イベントURLのお知らせ】\nこの後{$event->event_datetime->format('H時i分')}から開催する *{$event->name}* のURLはこちらです!\n{$event->url}");
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
