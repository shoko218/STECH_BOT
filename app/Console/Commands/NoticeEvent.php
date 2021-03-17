<?php

namespace App\Console\Commands;

use App\Model\Event;
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
            $slack_client = ClientFactory::create(config('services.slack.token'));
            $notice_events = Event::whereDate('notice_datetime', date('Y-m-d'))->whereTime('notice_datetime', date('H:i:').'00')->get();//今知らせる予定のイベントを取得
            foreach ($notice_events as $event) {
                $blocks = $this->getBlocks($event);
                $slack_client->chatPostMessage([
                    'channel' => '#seg-test-channel',
                    'blocks' => json_encode($blocks),
                ]);
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getBlocks($event){//送信するblockを配列で返す
        return [
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "<!channel> \n【イベントのお知らせ】\n{$event->event_datetime->format('m月d日 H時i分~')}\n *{$event->name}* を開催します！\n\n{$event->description}\n\n参加を希望する方は下のボタンを押してください！"
                ]
            ],
            [
                "type" => "actions",
                "elements" => [
                    [
                        "type" => "button",
                        "text" => [
                            "type" => "plain_text",
                            "text" => "参加する！",
                            "emoji" => true
                        ],
                        "value" => "$event->id",
                        "action_id" => "Register_to_attend_the_event"
                    ]
                ]
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "参加者\n まだいません。"
                ]
            ]
        ];
    }
}
