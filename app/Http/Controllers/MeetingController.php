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

    private $slack_client;

    public function __construct()
    {
        $this->slack_client = ClientFactory::create(config('services.slack.token'));
    }

   /**
    *  ミーティングを開催するかどうかメッセージを送信する
    *
    *  cronで定期実行させる関数です
    *  定期実行はCommands/CondirmMeeting.php, Console/Kernel.phpにて制御
    */
    public function AskToHoldMeeting () 
    {
        try {
            $this->slack_client->chatPostMessage([
                'channel' => self::$administrator,
                'text' => '来週の定期ミーティングを予定通り開催しますか？',
                'blocks' => json_encode($this->createMeetingConfirmationMessageBlocks())
            ]);

        } catch (SlackErrorResponse $e) {
            echo $e->getMessage();
        }
    }

   /**
    *  ミーティング開催を確認するメッセージのブロックを配列として作成する
    *
    *  @return array
    */
    public function createMeetingConfirmationMessageBlocks ()
    {
        return [
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
        ];
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
    *  ミーティングを通知するメッセージを作成するためのブロックを配列として作成する
    *
    * @param string $meeting_day_name ミーティングを開催する曜日
    * @return array
    */
    public function createMeetingMessageBlocks($meeting_day_name) 
    {
        return [
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => ":bell: *$meeting_day_name MTG参加の皆様リマインドです* :bell:"
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
        ];
    }

   /**
    *  渡されたパラメータに基づいてミーティングをスケジュールする
    *
    * @param string $meeting Actionsのパラメータの一つで、どのボタンが押されたか判別するためのvalue
    * @param int $first_meeting_day 1回目のミーティングお知らせ予定日時(UNIXTIME形式)
    * @param int $second_meeting_day 2回目のミーティングお知らせ予定日時(UNIXTIME形式)
    * @var string $first_meeting_day_name 1回目のミーティングの曜日。現在は月曜日
    * @var string $second_meeting_day_name 2回目のミーティングの曜日。現在は木曜日
    * @return true|false ミーティングをスケジュールした場合はtrue、開催がない場合とvalueが正しく判定されなかった場合はfalse
    */
    public function scheduleMeetings($button_value, $first_meeting_day, $second_meeting_day) 
    {
        try {
            $first_meeting_day_name = '月曜日';
            $second_meeting_day_name = '木曜日';

            switch ($button_value) {
                case 'both_meetings':
                    $this->slack_client->chatScheduleMessage([
                        'channel' => self::$notice_channel,
                        'post_at' => $first_meeting_day,
                        'blocks' => json_encode($this->createMeetingMessageBlocks($first_meeting_day_name))
                    ]);
                    $this->slack_client->chatScheduleMessage([
                        'channel' => self::$notice_channel,
                        'post_at' => $second_meeting_day,
                        'blocks' => json_encode($this->createMeetingMessageBlocks($second_meeting_day_name))
                    ]);    
                    return true;
                case 'first_meeting':
                    $this->slack_client->chatScheduleMessage([
                        'channel' => self::$notice_channel,
                        'post_at' => $first_meeting_day,
                        'blocks' => json_encode($this->createMeetingMessageBlocks($first_meeting_day_name))
                    ]);
                    return true;
                case 'second_meeting':
                    $this->slack_client->chatScheduleMessage([
                        'channel' => self::$notice_channel,
                        'post_at' => $second_meeting_day,
                        'blocks' => json_encode($this->createMeetingMessageBlocks($second_meeting_day_name))
                    ]);
                    return true;
                case 'not_both_meetings':
                    return false;
                default:
                    $this->slack_client->chatPostMessage([
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
    *  スケジュール済みのミーティングと重複したものを削除する
    *
    * @param int $next_monday 次週の月曜日の日時(UNIXTIME形式)
    * @param int $next_thursday 次週の木曜日の日時(UNIXTIME形式)
    */
    public function deleteOverlappedMeeting ($next_monday, $next_thursday)
    {
        try {
            $scheduled_meeting_list = $this->getScheduledMeetingList();
                    
            foreach ($scheduled_meeting_list as $meeting) { 
                if ($meeting['post_at'] == $next_monday || $meeting['post_at'] == $next_thursday) {
                    $this->slack_client->chatDeleteScheduledMessage([
                        'channel' => $meeting['channel_id'],
                        'scheduled_message_id' => $meeting['id']
                    ]);
                }
            }

        } catch (\Throwable $th) {
            Log::info($th);
        }
    }

   /**
    *  現在スケジュール済みのミーティングリストを取得し、その日時を配列として返す
    *
    * @return array
    * @todo スケジューリングリストを取得する際に、slack-php-apiのchatScheduledMessageListで実装する
    */
    public function getScheduledMeetingList ()
    {
        try {
            $guzzle = new \GuzzleHttp\Client();
            $response = $guzzle->request(
                'GET', 
                'https://slack.com/api/chat.scheduledMessages.list', 
                ['headers' => ['Authorization' => 'Bearer ' . config('services.slack.token')]]
            );
    
            $scheduled_list = json_decode($response->getBody()->getContents(), true);
            return $scheduled_list['scheduled_messages'];

        } catch (\Throwable $th) {
            Log::info($th);
        }
    }

   /**
    *  ミーティングの設定を行った後、完了メッセージを通知する
    *
    *  リクエストを受け取り、getActionResponseに渡す
    *  deleteOverlappedMeetingでメッセージの重複を防止した後、scheduleMeetingsでミーティング開催通知を予約する
    *  以上の処理が終わった後、ミーティング設定が完了したことを通知する
    *
    * @param Request $request
    * @var $scheduling_result scheduleMeetingsの結果(true|false)を受け取る変数。falseの場合は別途メッセージを送信
    */
    public function notifyMeetingSettingsCompletion (Request $request) 
    {
        try {
            response('', 200)->send();
            $next_meeting = $this->getActionsResponse($request);

            $today = CarbonImmutable::today('Asia/Tokyo');
            // todayを基準に今週の月曜日・木曜日を取得し7日分加算
            $next_monday = intval($today->startOfWeek()->addDays(7)->addHours(10)->format('U'));
            $next_thursday = intval($today->startOfWeek()->addDays(10)->addHours(10)->format('U'));

            $this->deleteOverlappedMeeting($next_monday, $next_thursday);
            $scheduling_result = $this->scheduleMeetings($next_meeting[0]['value'], $next_monday, $next_thursday);

            if (!$scheduling_result) {
                $this->slack_client->chatPostMessage([
                    'channel' => self::$administrator, 
                    'text' => '次回ミーティングはパスされました！'
                ]);
                exit;
            }

            $next_meetings = $this->getScheduledMeetingList();
            $next_meeting_date_list = array();
            foreach ($next_meetings as $meeting) {
                $next_meeting_date_list[] = CarbonImmutable::createFromTimestamp($meeting['post_at'])->format('Y年m月d日');
            }

            if (count($next_meeting_date_list) > 1) {
                $this->slack_client->chatPostMessage([
                    'channel' => self::$administrator,
                    'text' => "次回ミーティングの予定を確定しました！\n {$next_meeting_date_list[0]} と {$next_meeting_date_list[1]} に通知します"
                ]);
            } else {
                $this->slack_client->chatPostMessage([
                    'channel' => self::$administrator,
                    'text' => "次回ミーティングの予定を確定しました！\n {$next_meeting_date_list[0]} に通知します"
                ]);
            }
            
        } catch (\Throwable $th) {
            Log::info($th);
            $this->slack_client->chatPostMessage([
                'channel' => self::$administrator,
                'text' => 'ミーティングの設定は正常に行われませんでした。'
            ]);
        }
    }

}