<?php

namespace App\Console\Commands;

use App\Model\Event;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Vluzrmos\SlackApi\Facades\SlackChat;

class NoticeEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:notice_event';

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
        try {
            $now = new DateTime();
            $events = Event::whereDate('notice_datetime','=', date('Y-m-d'))->get();
            foreach($events as $event){
                $notice_datetime = new DateTime($event->notice_datetime);
                if($notice_datetime->format('Y/m/d H:i') === $now->format('Y/m/d H:i')){
                    $event_datetime = new DateTime($event->event_datetime);
                    $message = SlackChat::message("#general","",['blocks'=>'[{"type": "section","text": {"type": "mrkdwn","text": "<!channel> '."\n".'【イベントのお知らせ】'."\n *{$event->name}* ".'を開催します！'."\n{$event_datetime->format('Y年m月d日 H時i分~')}\n".'概要:'."{$event->description}\n".'参加したい方はボタンを押してください！"}},{"type": "actions","elements": [{"type": "button","text": {"type": "plain_text","text": "参加する！","emoji": true},"value": "'.$event->id.'","action_id": "Register_to_attend_the_event"}]}]']);
                    Log::info("noticed!");
                }
            }
            Log::info("task completed!");
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
