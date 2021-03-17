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

            //知らせるべきイベントを取得
            $notice_events = Event::whereNull('notice_ts')//まだ知らせていないイベント
                ->where(function($query){//日付指定
                    $query->where(function ($q) {//前日以前に知らせるべきイベント
                        $q->whereDate('notice_datetime', '<', date('Y-m-d'));
                    })->orWhere(function ($q) {//今日知らせるべきで、現在時刻以前に知らせるべきイベント
                        $q->whereDate('notice_datetime', date('Y-m-d'))
                        ->whereTime('notice_datetime', '<=', date('H:i:s'));
                    });
                })
                ->where(function($query){//イベントがまだ終わっていないものを選択
                    $query->where(function ($q) {//明日以降に開催されるイベント
                        $q->whereDate('event_datetime', '>', date('Y-m-d'));
                    })->orWhere(function ($q) {//今日開催の、現在時刻より後に開催されるイベント
                        $q->whereDate('event_datetime', date('Y-m-d'))
                        ->whereTime('event_datetime', '>', date('H:i:s'));
                    });
                })
                ->get();

            foreach ($notice_events as $event) {
                $blocks = json_encode($this->getBlocks($event));
                $chat = $slack_client->chatPostMessage([
                    'channel' => '#seg-test-channel',
                    'blocks' => $blocks,
                ]);
                $event->notice_ts = $chat->getTs();
                $event->save();
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getBlocks($event){//送信するblockを配列で返す
        $event_participant_ids = EventParticipant::select('slack_user_id')->where('event_id',$event->id)->get();
        $event_participants = "";
        foreach ($event_participant_ids as $event_participant_id) {//参加者一覧を一つの文字列に
            $event_participants .= "<@".$event_participant_id->slack_user_id."> ";
        }
        if($event_participants === ""){//参加者がいない場合
            $event_participants = 'まだいません。';
        }

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
                    "text" => "参加者\n $event_participants"
                ]
            ]
        ];
    }
}
