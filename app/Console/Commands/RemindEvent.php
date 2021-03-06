<?php

namespace App\Console\Commands;

use App\Model\Event;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Vluzrmos\SlackApi\Facades\SlackChat;

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
            $today_held_events = Event::whereDate('event_datetime','=', date('Y-m-d'))->get();//今日開催のイベントを取得
            foreach ($today_held_events as $event) {
                SlackChat::message("#seg-test-channel","<!channel>\n【リマインド】\n本日{$event->event_datetime->format('H時i分')}から、 *{$event->name}* を開催します！\n\n{$event->description}\n");
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
