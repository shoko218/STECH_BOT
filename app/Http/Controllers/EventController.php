<?php

namespace App\Http\Controllers;

use App\Model\Event;
use DateTime;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JoliCode\Slack\ClientFactory;

class EventController extends Controller
{
    private $slack_client;

    public function __construct()//クライアントを作成
    {
        $this->slack_client = ClientFactory::create(config('services.slack.token'));
    }

    /**
     * イベント作成フォームを表示する
     *
     * @param Request $request
     * @return array
     * @todo slack-php-apiを利用してこの処理を行いたいのですが、slack-php-apiからこの処理を行うとモーダルのjsonが長すぎてエラーになってしまいます。
     * slack-php-apiが改善されるか、モーダルでtimepickerが利用できるようになった場合、slack-php-apiで送信できないか試してみてください。
     */
    public function showCreateEventModal($trigger_id)
    {
        $params = [
            'view' => json_encode($this->getModalConstitution()),
            'trigger_id' => $trigger_id
        ];

        $client = new Client();
        $response = $client->request(
            'POST',
            'https://slack.com/api/views.open',
            [
                'headers' => [
                    'Content-type' => 'application/json',
                    'Authorization'  =>  'Bearer ' . config('services.slack.token')
                ],
                'json' => $params
            ]
        );
        response('', 200)->send();
    }

    /**
     * イベントをDBに登録する
     *
     * @param array $payload
     * @return void
     * @todo スマホから年を入れると和暦表示になるのですが、なぜか和暦の値がそのまま渡ってくる(2021年の場合、令和3年なので年月日が'0003-m-d'で渡ってきます)ので、
     * 渡ってきた年に+2018した年が今年から100年以内だった場合は和暦で渡ってきているとみなし、+2018して処理を進めます。
     * この仕様が改善された場合は以下3行の処理は削除してください。また、年号が変わった場合は新しい年号が始まった年-1で処理を書き換えてください。
     */
    public function createEvent($payload)
    {
        DB::beginTransaction();
        try {
            $event_name = $payload['view']['state']['values']['name']['name']['value'];
            $event_description = $payload['view']['state']['values']['description']['description']['value'];
            $event_url = $payload['view']['state']['values']['url']['url']['value'];

            //イベント日時の処理
            $event_datetime = new DateTime($payload['view']['state']['values']['event_date']['event_date']['selected_date']);//年月日だけでDateTime型作成
            if ((int)$event_datetime->format('Y')+2018 < (int)date('Y')+100) {//和暦で値が渡って来ていたら西暦に変換
                $event_datetime->modify("+ 2018 year");
            }
            $event_datetime->modify("+".$payload['view']['state']['values']['event_time']['event_hour']['selected_option']['value']." hour")->modify("+".$payload['view']['state']['values']['event_time']['event_minute']['selected_option']['value']." minute");//時、分を各フォームから取得し、上で作成したDateTime型に情報を追加

            //お知らせ日時の処理
            $notice_datetime = new DateTime($payload['view']['state']['values']['notice_date']['notice_date']['selected_date']);//年月日だけでDateTime型作成
            if ((int)$notice_datetime->format('Y')+2018 < (int)date('Y')+100) {//和暦で値が渡って来ていたら西暦に変換
                $notice_datetime->modify("+ 2018 year");
            }
            $notice_datetime->modify("+".$payload['view']['state']['values']['notice_time']['notice_hour']['selected_option']['value']." hour")->modify("+".$payload['view']['state']['values']['notice_time']['notice_minute']['selected_option']['value']." minute");//時、分を各フォームから取得し、上で作成したDateTime型に情報を追加

            //バリデーション処理
            $errors = [];
            $now = new DateTime();
            if ($notice_datetime <= $now) {//お知らせ日時が現在時刻以前の場合
                $errors["errors"]["notice_date"] = "現在時刻以降の日時を入力してください。";
            }
            if ($event_datetime <= $now) {//イベント日時が現在時刻以前の場合
                $errors["errors"]["event_date"] =  "現在時刻以降の日時を入力してください。";
            }
            if ($event_datetime <= $notice_datetime) {//イベント日時がお知らせ日時以前の場合
                $errors["errors"]["notice_date"] = "お知らせする日時はイベントの日時より前に設定してください。";
            }
            if (!filter_var($event_url, FILTER_VALIDATE_URL)) {//URLの有効性を確認(ASCIIオンリーのURLのみの対応となるので、URLに日本語が含まれるものは弾かれる)
                $errors["errors"]["url"] = "有効なURLを入力してください。";
            }

            if (empty($errors["errors"])) {//バリデーションエラーがなければ
                response('', 200)->send();//3秒以内にレスポンスを返さないとタイムアウト扱いになるので、バリデーションが済んだらすぐにレスポンスを返す
            } else {//バリデーションエラーがあれば
                $errors["response_action"] = "errors";
                response()->json($errors)->send();//エラーの箇所とともにエラーレスポンスを返す
                return;//処理を終了
            }

            $event = Event::create([
                'name' => $event_name,
                'description' => $event_description,
                'event_datetime' => $event_datetime,
                'notice_datetime' => $notice_datetime,
                'url' => $event_url
            ]);

            $this->slack_client->chatPostMessage([
                'channel' => $payload['user']['id'],
                'text' => "イベントを登録しました！\n\n```イベント名:{$event_name}\nイベント詳細:{$event_description}\nイベントURL:{$event_url}\nイベント日時:{$event_datetime->format('Y年m月d日 H:i')}\nお知らせする日時:{$notice_datetime->format('Y年m月d日 H:i')}```",
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info($th);
            response('エラーが発生し、イベントを登録できませんでした。もう一度お試しください。', 200)->send();
        }
    }

    /**
     * 開催予定のイベントを知らせる
     *
     * @return void
     */
    public function noticeEvent()
    {
        //知らせるべきイベントを取得
        $notice_events = Event::whereNull('notice_ts')//まだ知らせていないイベント
            ->where(function ($query) {//お知らせ日時がすぎているものを指定
                $query->where(function ($q) {//前日以前に知らせるべきイベント
                    $q->whereDate('notice_datetime', '<', date('Y-m-d'));
                })->orWhere(function ($q) {//今日知らせるべきで、現在時刻以前に知らせるべきイベント
                    $q->whereDate('notice_datetime', date('Y-m-d'))
                    ->whereTime('notice_datetime', '<=', date('H:i:s'));
                });
            })
            ->where(function ($query) {//イベントがまだ終わっていないものを選択
                $query->where(function ($q) {//明日以降に開催されるイベント
                    $q->whereDate('event_datetime', '>', date('Y-m-d'));
                })->orWhere(function ($q) {//今日開催の、現在時刻より後に開催されるイベント
                    $q->whereDate('event_datetime', date('Y-m-d'))
                    ->whereTime('event_datetime', '>', date('H:i:s'));
                });
            })
            ->get();

        foreach ($notice_events as $event) {
            $chat = $this->slack_client->chatPostMessage([
                'channel' => 'C01NCNM4WQ6',
                'blocks' => json_encode($this->getNoticeEventBlocks($event)),
            ]);
            $event->notice_ts = $chat->getTs();
            $event->save();
        }
    }

    /**
     * 午前10時に今日開催するイベントをリマインドする
     *
     * @return void
     */
    public function remindEvent()
    {
        $today_held_events = Event::whereDate('event_datetime', '=', date('Y-m-d'))//今日開催のイベント
            ->whereNull('remind_ts')//まだリマインドしていないもの
            ->get();
        foreach ($today_held_events as $event) {
            $chat = $this->slack_client->chatPostMessage([
                'channel' => 'C01NCNM4WQ6',
                'blocks' => json_encode($this->getRemindEventBlocks($event)),
            ]);
            $event->remind_ts = $chat->getTs();
            $event->save();
        }
    }

    /**
     * 15分後に始まるイベントのURLを共有する
     *
     * @return void
     */
    public function shareEventUrl()
    {
        $start_time = new DateTime();
        $start_time->modify('+15 minutes');
        $coming_soon_events = Event::whereDate('event_datetime', $start_time->format('Y-m-d'))
            ->whereTime('event_datetime', $start_time
            ->format('H:i:').'00')
            ->get();//15分後に始まるイベントを取得

        foreach ($coming_soon_events as $event) {
            $this->slack_client->chatPostMessage([
                'channel' => 'C01NCNM4WQ6',
                'text' => "<!channel> 【イベントURLのお知らせ】\nこの後{$event->event_datetime->format('H時i分')}から開催する *{$event->name}* のURLはこちらです!\n{$event->url}",
            ]);
        }
    }

    /**
     * モーダルの構成を配列で返す(送信する際はjsonエンコードして送信)
     *
     * @return array
     * @todo 現在、timepickerがベータ版にしかないのでactionに日時のセレクターを埋め込み、無理矢理日時が横並びになるようにしています。
     * timepickerが正式にリリースされたら速やかにblock kitの構成を変更し、それにしたがってInteractiveEndpointControllerでの処理も書き換えてください。
     */
    public function getModalConstitution()
    {
        return [
            "callback_id" => "create_event",
            "type" => "modal",
            "title" => [
                "type" => "plain_text",
                "text" => "イベントを登録する",
                "emoji" => true
            ],
            "submit" => [
                "type" => "plain_text",
                "text" => "Submit",
                "emoji" => true
            ],
            "close" => [
                "type" => "plain_text",
                "text" => "Cancel",
                "emoji" => true
            ],
            "blocks" => [
                [
                    "type" => "input",
                    "block_id" => "name",
                    "label" => [
                        "type" => "plain_text",
                        "text" => "イベント名",
                        "emoji" => true
                    ],
                    "element" => [
                        "type" => "plain_text_input",
                        "action_id" => "name"
                    ]
                ],
                [
                    "type" => "input",
                    "block_id" => "description",
                    "label" => [
                        "type" => "plain_text",
                        "text" => "イベント概要",
                        "emoji" => true
                    ],
                    "element" => [
                        "type" => "plain_text_input",
                        "action_id" => "description",
                        "multiline" => true
                    ]
                ],
                [
                    "type" => "input",
                    "block_id" => "url",
                    "label" => [
                        "type" => "plain_text",
                        "text" => "イベントURL",
                        "emoji" => true
                    ],
                    "element" => [
                        "type" => "plain_text_input",
                        "action_id" => "url"
                    ]
                ],
                [
                    "type" => "input",
                    "block_id" => "event_date",
                    "element" => [
                        "type" => "datepicker",
                        "action_id" => "event_date",
                        "placeholder" => [
                            "type" => "plain_text",
                            "text" => "年月日",
                            "emoji" => true
                        ]
                    ],
                    "label" => [
                        "type" => "plain_text",
                        "text" => "イベント日時",
                        "emoji" => true
                    ]
                ],
                [
                    "type" => "actions",
                    "block_id" => "event_time",
                    "elements" => [
                        [
                            "type" => "static_select",
                            "action_id" => "event_hour",
                            "placeholder" => [
                                "type" => "plain_text",
                                "text" => "時"
                            ],
                            "initial_option" => [
                                "text" => [
                                    "type" => "plain_text",
                                    "text" => "0時"
                                ],
                                "value" => "0"
                            ],
                            "options" => [
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "0時"
                                    ],
                                    "value" => "0"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "1時"
                                    ],
                                    "value" => "1"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "2時"
                                    ],
                                    "value" => "2"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "3時"
                                    ],
                                    "value" => "3"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "4時"
                                    ],
                                    "value" => "4"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "5時"
                                    ],
                                    "value" => "5"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "6時"
                                    ],
                                    "value" => "6"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "7時"
                                    ],
                                    "value" => "7"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "8時"
                                    ],
                                    "value" => "8"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "9時"
                                    ],
                                    "value" => "9"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "10時"
                                    ],
                                    "value" => "10"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "11時"
                                    ],
                                    "value" => "11"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "12時"
                                    ],
                                    "value" => "12"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "13時"
                                    ],
                                    "value" => "13"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "14時"
                                    ],
                                    "value" => "14"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "15時"
                                    ],
                                    "value" => "15"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "16時"
                                    ],
                                    "value" => "16"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "17時"
                                    ],
                                    "value" => "17"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "18時"
                                    ],
                                    "value" => "18"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "19時"
                                    ],
                                    "value" => "19"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "20時"
                                    ],
                                    "value" => "20"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "21時"
                                    ],
                                    "value" => "21"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "22時"
                                    ],
                                    "value" => "22"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "23時"
                                    ],
                                    "value" => "23"
                                ]
                            ]
                        ],
                        [
                            "type" => "static_select",
                            "action_id" => "event_minute",
                            "placeholder" => [
                                "type" => "plain_text",
                                "text" => "分"
                            ],
                            "initial_option" => [
                                "text" => [
                                    "type" => "plain_text",
                                    "text" => "00分"
                                ],
                                "value" => "0"
                            ],
                            "options" => [
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "00分"
                                    ],
                                    "value" => "0"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "15分"
                                    ],
                                    "value" => "15"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "30分"
                                    ],
                                    "value" => "30"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "45分"
                                    ],
                                    "value" => "45"
                                ],
                            ]
                        ]
                    ]
                ],
                [
                    "type" => "input",
                    "block_id" => "notice_date",
                    "element" => [
                        "type" => "datepicker",
                        "action_id" => "notice_date",
                        "placeholder" => [
                            "type" => "plain_text",
                            "text" => "年月日",
                            "emoji" => true
                        ]
                    ],
                    "label" => [
                        "type" => "plain_text",
                        "text" => "お知らせする日時",
                        "emoji" => true
                    ]
                ],
                [
                    "type" => "actions",
                    "block_id" => "notice_time",
                    "elements" => [
                        [
                            "type" => "static_select",
                            "action_id" => "notice_hour",
                            "placeholder" => [
                                "type" => "plain_text",
                                "text" => "時"
                            ],
                            "initial_option" => [
                                "text" => [
                                    "type" => "plain_text",
                                    "text" => "0時"
                                ],
                                "value" => "0"
                            ],
                            "options" => [
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "0時"
                                    ],
                                    "value" => "0"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "1時"
                                    ],
                                    "value" => "1"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "2時"
                                    ],
                                    "value" => "2"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "3時"
                                    ],
                                    "value" => "3"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "4時"
                                    ],
                                    "value" => "4"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "5時"
                                    ],
                                    "value" => "5"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "6時"
                                    ],
                                    "value" => "6"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "7時"
                                    ],
                                    "value" => "7"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "8時"
                                    ],
                                    "value" => "8"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "9時"
                                    ],
                                    "value" => "9"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "10時"
                                    ],
                                    "value" => "10"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "11時"
                                    ],
                                    "value" => "11"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "12時"
                                    ],
                                    "value" => "12"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "13時"
                                    ],
                                    "value" => "13"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "14時"
                                    ],
                                    "value" => "14"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "15時"
                                    ],
                                    "value" => "15"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "16時"
                                    ],
                                    "value" => "16"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "17時"
                                    ],
                                    "value" => "17"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "18時"
                                    ],
                                    "value" => "18"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "19時"
                                    ],
                                    "value" => "19"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "20時"
                                    ],
                                    "value" => "20"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "21時"
                                    ],
                                    "value" => "21"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "22時"
                                    ],
                                    "value" => "22"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "23時"
                                    ],
                                    "value" => "23"
                                ]
                            ]
                        ],
                        [
                            "type" => "static_select",
                            "action_id" => "notice_minute",
                            "placeholder" => [
                                "type" => "plain_text",
                                "text" => "分"
                            ],
                            "initial_option" => [
                                "text" => [
                                    "type" => "plain_text",
                                    "text" => "00分"
                                ],
                                "value" => "0"
                            ],
                            "options" => [
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "00分"
                                    ],
                                    "value" => "0"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "15分"
                                    ],
                                    "value" => "15"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "30分"
                                    ],
                                    "value" => "30"
                                ],
                                [
                                    "text" => [
                                        "type" => "plain_text",
                                        "text" => "45分"
                                    ],
                                    "value" => "45"
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * お知らせ登録の内容を配列で返す(送信する際はjsonエンコードして送信)
     *
     * @param object $event
     * @return array
     */
    public function getNoticeEventBlocks($event)
    {
        $event_participants = "";
        foreach ($event->eventParticipants as $event_participant) {//参加者一覧を一つの文字列に
            $event_participants .= "<@".$event_participant->slack_user_id."> ";
        }
        if ($event_participants === "") {//参加者がいない場合
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
                        "action_id" => "register_to_attend_event"
                    ]
                ],
                "block_id" => "register_to_attend_event",
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

    /**
     * リマインド投稿の内容を配列で返す(送信する際はjsonエンコードして送信)
     *
     * @param object $event
     * @return array
     */
    public function getRemindEventBlocks($event)
    {
        $event_participants = "";
        foreach ($event->eventParticipants as $event_participant) {//参加者一覧を一つの文字列に
            $event_participants .= "<@".$event_participant->slack_user_id."> ";
        }
        if ($event_participants === "") {//参加者がいない場合
            $event_participants = 'まだいません。';
        }

        return [
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "<!channel>\n【リマインド】\nこの後{$event->event_datetime->format('H時i分')}から、 *{$event->name}* を開催します！\n\n{$event->description}\n\n参加を希望する方は下のボタンを押してください！"
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
                        "action_id" => "register_to_attend_event"
                    ]
                ],
                "block_id" => "register_to_attend_event",
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
