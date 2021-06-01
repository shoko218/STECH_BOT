<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use JoliCode\Slack\ClientFactory;
use JoliCode\Slack\Exception\SlackErrorResponse;
use Carbon\CarbonImmutable;

class MeetingController extends Controller
{
    private $slack_client;

    private $guzzle;

    public function __construct($generated_slack_client = null, $generated_guzzle_client = null)
    {
        if (is_null($generated_slack_client)) {
            $this->slack_client = ClientFactory::create(config('services.slack.token'));
        } else {
            $this->slack_client = $generated_slack_client;
        }

        if (is_null($generated_guzzle_client)) {
            $this->guzzle = new \GuzzleHttp\Client();
        } else {
            $this->guzzle = $generated_guzzle_client;
        }
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
                'channel' => config('const.slack_id.administrator'),
                'text' => '来週の定期ミーティングを予定通り開催しますか？',
                'blocks' => json_encode(app()->make('App\Http\Controllers\BlockPayloads\MeetingPayloadController')->createMeetingConfirmationMessageBlocks())
            ]);

        } catch (SlackErrorResponse $e) {
            $error_message = $e->getMessage();

            Log::info($error_message);
            echo $error_message;
        }
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
    public function scheduleMeetings($array_about_next_meeting) 
    {
        try {
            $button_value = $array_about_next_meeting[0];
            $first_meeting_day = $array_about_next_meeting[1];
            $second_meeting_day = $array_about_next_meeting[2];

            $first_meeting_day_name = '月曜日';
            $second_meeting_day_name = '木曜日';

            switch ($button_value) {
                case 'both_meetings':                    
                    $this->slack_client->chatScheduleMessage([
                        'channel' => config('const.slack_id.general'),
                        'post_at' => $first_meeting_day,
                        'blocks' => json_encode(app()->make('App\Http\Controllers\BlockPayloads\MeetingPayloadController')->createMeetingMessageBlocks($first_meeting_day_name))
                    ]);

                    $this->slack_client->chatScheduleMessage([
                        'channel' => config('const.slack_id.general'),
                        'post_at' => $second_meeting_day,
                        'blocks' => json_encode(app()->make('App\Http\Controllers\BlockPayloads\MeetingPayloadController')->createMeetingMessageBlocks($second_meeting_day_name))
                    ]);

                    return true;
                case 'first_meeting':
                    $this->slack_client->chatScheduleMessage([
                        'channel' => config('const.slack_id.general'),
                        'post_at' => $first_meeting_day,
                        'blocks' => json_encode(app()->make('App\Http\Controllers\BlockPayloads\MeetingPayloadController')->createMeetingMessageBlocks($first_meeting_day_name))
                    ]);

                    return true;
                case 'second_meeting':
                    $this->slack_client->chatScheduleMessage([
                        'channel' => config('const.slack_id.general'),
                        'post_at' => $second_meeting_day,
                        'blocks' => json_encode(app()->make('App\Http\Controllers\BlockPayloads\MeetingPayloadController')->createMeetingMessageBlocks($second_meeting_day_name))
                    ]);

                    return true;
                case 'not_both_meetings':
                    return false;
                default:
                    $this->slack_client->chatPostMessage([
                        'channel' => config('const.slack_id.administrator'),
                        'text' => 'エラー発生によりミーティングのスケジュールを行うことができませんでした',
                    ]);

                    return false;
            }

        } catch (SlackErrorResponse $e) {
            $error_message = $e->getMessage();

            Log::info($error_message);
            echo $error_message;
        }
    }

   /**
    *  スケジュール済みのミーティングと重複したものを削除する
    */
    public function deleteOverlappedMeeting ($array_about_next_meeting_days)
    {
        try {
            $next_monday = $array_about_next_meeting_days[0];
            $next_thursday = $array_about_next_meeting_days[1];

            $scheduled_meeting_list = $this->getScheduledMeetingList();

            $deleted = [];
            foreach ($scheduled_meeting_list as $meeting) { 
                if ($meeting['post_at'] == $next_monday || $meeting['post_at'] == $next_thursday) {
                    $this->slack_client->chatDeleteScheduledMessage([
                        'channel' => $meeting['channel_id'],
                        'scheduled_message_id' => $meeting['id']
                    ]);
                    $deleted[] = $meeting;
                }
            }

            return $deleted;

        } catch (\InvalidArgumentException $e) {
            $error_message = $e->getMessage();

            Log::info($error_message);
            echo $error_message;

        } catch (SlackErrorResponse $e) {
            $error_message = $e->getMessage();

            Log::info($error_message);
            echo $error_message;

        } catch (\Throwable $th) {
            $error_message = $th->getMessage();

            Log::info($error_message);
            echo $error_message;
        }
    }

   /**
    *  現在スケジュール済みのミーティングリストを取得し、配列として返す
    *
    * @return array
    * @todo スケジューリングリストを取得する際に、slack-php-apiのchatScheduledMessageListで実装する
    */
    public function getScheduledMeetingList ()
    {
        try {
            $response = $this->guzzle->request(
                'GET', 
                'https://slack.com/api/chat.scheduledMessages.list', 
                ['headers' => ['Authorization' => 'Bearer ' . config('services.slack.token')]]
            );
            
            $scheduled_list = json_decode($response->getBody()->getContents(), true);
            
            return $scheduled_list['scheduled_messages'];

        } catch (\InvalidArgumentException $e) {
            $error_message = $e->getMessage();

            Log::info($error_message);
            echo $error_message;
            
        } catch (\Throwable $th) {
            $error_message = $th->getMessage();

            Log::info($error_message);
            echo $error_message;
        }
    }

   /**
    *  ミーティングの設定を行った後、完了メッセージを通知する
    *
    *  リクエストを受け取り、getActionResponseに渡す
    *  deleteOverlappedMeetingでメッセージの重複を防止した後、scheduleMeetingsでミーティング開催通知を予約する
    *  以上の処理が終わった後、ミーティング設定が完了したことを通知する
    *
    * @param $payload json_decodeされたペイロード。InteractiveEndpointControllerから受け取る。
    * @var $scheduling_result scheduleMeetingsの結果(true|false)を受け取る変数。falseの場合は別途メッセージを送信
    * @todo slack apiで例外が発生したとき、SlackErrorResponseで例外をキャッチしてGuzzleなど別方法でSlackにメッセージを送信する
    */
    public function notifyMeetingSettingsCompletion ($payload) 
    {
        response('', 200)->send();

        try {
            $next_meeting = $payload['actions'];

            $today = CarbonImmutable::today('Asia/Tokyo');
            // todayを基準に今週の月曜日・木曜日を取得し7日分加算
            $next_monday = intval($today->startOfWeek()->addDays(7)->addHours(10)->format('U'));
            $next_thursday = intval($today->startOfWeek()->addDays(10)->addHours(10)->format('U'));

            $this->deleteOverlappedMeeting([$next_monday, $next_thursday]);
            $scheduling_result = $this->scheduleMeetings([$next_meeting[0]['value'], $next_monday, $next_thursday]);

            if (!$scheduling_result) {
                $this->slack_client->chatPostMessage([
                    'channel' => config('const.slack_id.administrator'),
                    'text' => '次回ミーティングはパスされました！'
                ]);

            } else {
                $next_meetings = $this->getScheduledMeetingList();
                $next_meeting_date_list = array();
                foreach ($next_meetings as $meeting) {
                    $next_meeting_date_list[] = CarbonImmutable::createFromTimestamp($meeting['post_at'])->format('Y年m月d日');
                }
    
                if (count($next_meeting_date_list) > 1) {
                    $this->slack_client->chatPostMessage([
                        'channel' => config('const.slack_id.administrator'),
                        'text' => "次回ミーティングの予定を確定しました！\n {$next_meeting_date_list[0]} と {$next_meeting_date_list[1]} に通知します"
                    ]);
                } else {
                    $this->slack_client->chatPostMessage([
                        'channel' => config('const.slack_id.administrator'),
                        'text' => "次回ミーティングの予定を確定しました！\n {$next_meeting_date_list[0]} に通知します"
                    ]);
                }

                return $next_meeting_date_list;

            }
            
        } catch (\Throwable $th) {
            Log::info($th);
            return $th->getMessage();

            $this->slack_client->chatPostMessage([
                'channel' => config('const.slack_id.administrator'),
                'text' => 'ミーティングの設定は正常に行われませんでした。'
                ]);
            }
        }

}