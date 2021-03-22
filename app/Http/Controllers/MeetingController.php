<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JoliCode\Slack\ClientFactory;
use JoliCode\Slack\Exception\SlackErrorResponse;
use Carbon\CarbonImmutable;

class MeetingController extends Controller
{
   /**
    *  @var string $administrator ミーティング開催確認メッセージの送信先(STECHの管理者)
    */
    public static $administrator = 'U01MGNLUDFV';
    
   /**
    *  @var string $notice_channel ミーティング開催を通知するチャンネル
    */
    public static $notice_channel = '#general';

   /**
    *  ミーティングを開催するかどうかメッセージを送信する
    *
    *  cronで定期実行させる関数です
    *  定期実行はCommands/CondirmMeeting.php, Console/Kernel.phpにて制御
    */
    public function AskToHoldMeeting () 
    {
        $slack_client = ClientFactory::create(config('services.slack.token'));

        try {
            $slack_client->chatPostMessage([
                'channel' => self::$administrator,
                'text' => '来週の定期ミーティングを予定通り開催しますか？',
                'blocks' => json_encode([
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => ":alarm_clock: 来週のミーティングを予定通り開催しますか？"
                        ]
                    ],
                    [
                        "type" => "actions",
                        "elements" => [
                            [
                                "type" => "button",
                                "text" => [
                                    "type" => "plain_text",
                                    "text" => ":o: 両日とも開催",
                                    "emoji" => true
                                ],
                                "value" => "both_meetings",
                                "action_id" => "actionId-0"
                            ],
                            [
                                "type" => "button",
                                "text" => [
                                    "type" => "plain_text",
                                    "text" => ":eight_pointed_black_star: 月曜日のみ",
                                    "emoji" => true
                                ],
                                "value" => "first_meeting",
                                "action_id" => "actionId-1"
                            ],
                            [
                                "type" => "button",
                                "text" => [
                                    "type" => "plain_text",
                                    "text" => ":sparkle: 木曜日のみ",
                                    "emoji" => true
                                ],
                                "value" => "second_meeting",
                                "action_id" => "actionId-2"
                            ],
                            [
                                "type" => "button",
                                "text" => [
                                    "type" => "plain_text",
                                    "text" => ":x: 開催しない",
                                    "emoji" => true
                                ],
                                "value" => "not_both_meetings",
                                "action_id" => "actionId-3"
                            ]
                        ]
                    ]
                ])
            ]);

        } catch (SlackErrorResponse $e) {
            echo $e->getMessage();
        }
    }

   /**
    *  ミーティングボタンが押されたときのパラメータを受け取り処理する
    *
    *  slackにてボタンが押された際のパラメータを取得し、actions部分だけ取得する
    *
    * @param Request $request
    * @return string $payload['actions'] 押されたボタンの情報を保有しているactionsのみ返す
    */
    public function getActionsResponse (Request $request) 
    {
        try {
            $payload = json_decode($request->input('payload'), true);
            return $payload['actions'];

        } catch (\Throwable $th) {
            Log::info($th);
        }
    }

   /**
    *  ミーティングを通知するためのメッセージを作成し、配列として返す
    *
    * @param string $post_to メッセージの送信先
    * @param int $post_at メッセージの送信予定日時
    * @param string $meeting_day ミーティングを開催する曜日
    * @return array
    */
    public function createMeetingMessage($post_to, $post_at, $meeting_day) 
    {
        return [
            'channel' => $post_to,
            'post_at' => $post_at,
            'blocks' => json_encode([
                [
                    "type" => "section",
                    "text" => [
                        "type" => "mrkdwn",
                        "text" => ":bell: *$meeting_day MTG参加の皆様リマインドです* :bell:"
                    ]
                ],
                [
                    "type" => "section",
                    "text" => [
                        "type" => "mrkdwn",
                        "text" => "定例ミーティングは本日の *20:00* - です"
                    ]
                ],
                [
                    "type" => "divider"
                ],
                [
                    "type" => "section",
                    "text" => [
                        "type" => "mrkdwn",
                        "text" => "・ 個人開発に注力している方\n・STECHのチーム開発プロジェクトに参加している方\nぜひご参加お願いいたします。\n\n *URL: <https://zoom.us/j/97315206739>*"
                    ],
                    "accessory" => [
                        "type" => "image",
                        "image_url" => "https://api.slack.com/img/blocks/bkb_template_images/notifications.png",
                        "alt_text" => "calendar thumbnail"
                    ]
                ],
                [
                    "type" => "divider"
                ],
                [
                    "type" => "section",
                    "text" => [
                        "type" => "mrkdwn",
                        "text" => "※月木の参加が難しい方、Googleフォームでの回答をお願いします。"
                    ],
                    "accessory" => [
                        "type" => "button",
                        "text" => [
                            "type" => "plain_text",
                            "text" => "回答する",
                            "emoji" => true
                        ],
                        "url" => "https://forms.gle/MgMhcocvmJUfBbYZ6",
                    ]
                ]
            ])
            ];
    }

   /**
    *  渡されたパラメータに基づいてミーティングをスケジュールする
    *
    * @param string $meeting Actionsのパラメータの一つで、どのボタンが押されたか判別するためのvalue
    * @param int $first_meeting_day 1回目のミーティングお知らせ予定日時(UNIXTIME形式)
    * @param int $second_meeting_day 2回目のミーティングお知らせ予定日時(UNIXTIME形式)
    * @param string $first_meeting_day_name 1回目のミーティングの曜日。現在は月曜日
    * @param string $second_meeting_day_name 2回目のミーティングの曜日。現在は木曜日
    * @return true|false ミーティングをスケジュールした場合はtrue、開催がない場合とvalueが正しく判定されなかった場合はfalse
    */
    public function scheduleMeetings($button_value, $first_meeting_day, $second_meeting_day) 
    {
        try {
            $slack_client = ClientFactory::create(config('services.slack.token'));

            $first_meeting_day_name = '月曜日';
            $second_meeting_day_name = '木曜日';

            switch ($button_value) {
                case 'both_meetings':
                    $slack_client->chatScheduleMessage(
                        $this->createMeetingMessage(self::$notice_channel, $first_meeting_day, $first_meeting_day_name)
                    );
                    $slack_client->chatScheduleMessage(
                        $this->createMeetingMessage(self::$notice_channel, $second_meeting_day, $second_meeting_day_name)
                    );
    
                    return true;
                case 'first_meeting':
                    $slack_client->chatScheduleMessage(
                        $this->createMeetingMessage(self::$notice_channel, $first_meeting_day, $first_meeting_day_name)
                    );
                    return true;
                case 'second_meeting':
                    $slack_client->chatScheduleMessage(
                        $this->createMeetingMessage(self::$notice_channel, $second_meeting_day, $second_meeting_day_name)
                    );
                    return true;
                case 'not_both_meetings':
                    return false;
                default:
                    $slack_client->chatPostMessage([
                        'channel' => self::$administrator,
                        'text' => 'エラー発生によりミーティングのスケジュールを行うことができませんでした',
                    ]);
                    return false;
            }

        } catch (SlackErrorResponse $e) {
            echo $e->getMessage();
        }
    }

   /**
    *  同じ日時にスケジュールされているミーティングがないか確認し、重複している場合は削除する
    *
    *  お知らせメッセージのスケジューリングリストを確認し、同じ日時のメッセージがある場合は削除
    *  削除した場合も、削除しない場合も、処理結果のメッセージを送信
    *
    * @param int $post_at ミーティング開催通知の投稿予定日時。この値でスケジューリングリストとの重複を確認する
    * @todo スケジューリングリストを取得する際に、slack-php-apiのchatScheduledMessageListで実装する
    */
    public function scheduledMeetingExists ($post_at) 
    {
        try {
            $slack_client = ClientFactory::create(config('services.slack.token'));
            
            // chatScheduledMessageListが使えず取り急ぎGuzzleで実装しています
            // slack-apiから帰ってくるid(string)が、パッケージ側のsetId()で処理されるときにint or null 指定されており弾かれているようです
            $guzzle = new \GuzzleHttp\Client();
            $response = $guzzle->request(
                'GET', 
                'https://slack.com/api/chat.scheduledMessages.list', 
                ['headers' => ['Authorization' => 'Bearer ' . config('services.slack.token')]]
            );
    
            $scheduled_list = json_decode($response->getBody()->getContents(), true);
            $scheduled_meetings = $scheduled_list['scheduled_messages'];
                
            foreach ($scheduled_meetings as $scheduled_meeting) {
                $scheduled_meeting_date = CarbonImmutable::createFromTimestamp($scheduled_meeting['post_at'])->format('Y年m月d日');
    
                if ($scheduled_meeting['post_at'] == $post_at) {
                    $slack_client->chatDeleteScheduledMessage([
                        'channel' => $scheduled_meeting['channel_id'],
                        'scheduled_message_id' => $scheduled_meeting['id']
                    ]);
    
                    $slack_client->chatPostMessage([
                        'channel' => self::$administrator,
                        'text' => "$scheduled_meeting_date 開催予定のミーティングは削除されました！"
                    ]);
    
                } else {
                    $slack_client->chatPostMessage([
                        'channel' => self::$administrator,
                        'text' => "$scheduled_meeting_date 開催予定のミーティングは削除されませんでした！"
                    ]);
                }
            }

        } catch (\Throwable $th) {
            Log::info($th);
        }
    }

   /**
    *  ミーティングの設定を行った後、完了メッセージを通知する
    *
    *  リクエストを受け取り、getActionResponseに渡す
    *  scheduledMeetingExistsでメッセージの重複を確認・防止した後、scheduleMeetingsでミーティング開催通知を予約する
    *  以上の処理が終わった後、ミーティング設定が完了したことを通知する
    *
    * @param Request $request
    * @var $scheduling_result scheduleMeetingsの結果(true|false)を受け取る変数。falseの場合は別途メッセージを送信
    * @todo $requestのチェック、処理の流れが冗長なのでリファクタリング
    */
    public function notifyMeetingSettingsCompletion (Request $request) 
    {
        try {
            response('', 200)->send();
            
            $next_meeting = $this->getActionsResponse($request);
            $slack_client = ClientFactory::create(config('services.slack.token'));

            $today = CarbonImmutable::today('Asia/Tokyo');
            // todayを基準に今週の月曜日・木曜日を取得し7日分加算
            $next_monday = intval($today->startOfWeek()->addDays(7)->addHours(10)->format('U'));
            $next_thursday = intval($today->startOfWeek()->addDays(10)->addHours(10)->format('U'));

            $this->scheduledMeetingExists($next_monday);
            $this->scheduledMeetingExists($next_thursday);
           
            $scheduling_result = $this->scheduleMeetings($next_meeting[0]['value'], $next_monday, $next_thursday);

            if ($scheduling_result == false) {
                $slack_client->chatPostMessage([
                    'channel' => self::$administrator, 
                    'text' => '次回ミーティングはパスされました！'
                ]);
                return;
            }
            
            $next_meeting_text = $next_meeting[0]['text']['text'];
            $slack_client->chatPostMessage([
                'channel' => self::$administrator,
                'blocks' => json_encode([
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "plain_text",
                            "text" => "次回ミーティングの予定を確定しました！： $next_meeting_text ",
                            "emoji" => true
                        ]
                    ]
                ])
            ]);
            
        } catch (\Throwable $th) {
            Log::info($th);
            $slack_client->chatPostMessage([
                'channel' => self::$administrator,
                'text' => 'ミーティングの設定は正常に行われていません。'
            ]);
        }
    }

}