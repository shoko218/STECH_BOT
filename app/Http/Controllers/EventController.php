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
    private $event_payloads;

    public function __construct()//クライアントを作成
    {
        $this->slack_client = ClientFactory::create(config('services.slack.token'));
        $this->event_payloads = app()->make('App\Http\Controllers\BlockPayloads\EventPayloadController');
    }

    /**
     * イベント作成フォームを表示する
     *
     * @param Request $request
     * @return array
     * @todo slack-php-apiを利用してこの処理を行いたいのですが、slack-php-apiからこの処理を行うとモーダルのjsonが長すぎてエラーになってしまいます。
     * slack-php-apiが改善されるか、モーダルでtimepickerが利用できるようになった場合、slack-php-apiで送信できないか試してみてください。
     */
    public function showCreateEventModal(Request $request)
    {
        response('', 200)->send();

        try {
            $params = [
                'view' => json_encode($this->event_payloads->getCreateEventModalConstitution()),
                'trigger_id' => $request->trigger_id
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
        } catch (\Throwable $th) {
            Log::info($th);
        }
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
            DB::commit();

            $this->slack_client->chatPostMessage([
                'channel' => $payload['user']['id'],
                'blocks' => json_encode($this->event_payloads->getCreatedEventMessageBlockConstitution($event))
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info($th);
            response('エラーが発生し、イベントを登録できませんでした。もう一度お試しください。', 200)->send();
        }
    }

    /**
     * イベントを削除する
     *
     * @param array $payload
     * @return void
     */
    public function deleteEvent($payload)
    {
        DB::beginTransaction();
        try {
            $event = Event::find($payload['actions'][0]['value']);

            $event_name = $event->name;

            if ($event->notice_ts != null) {//既にお知らせしていればお知らせ投稿を削除
                $this->slack_client->chatDelete([
                    'channel' => config('const.slack_id.general'),
                    'ts' => $event->notice_ts,
                ]);
            }

            if ($event->remind_ts != null) {//既にリマインドしていればリマインド投稿を削除
                $this->slack_client->chatDelete([
                    'channel' => config('const.slack_id.general'),
                    'ts' => $event->remind_ts,
                ]);
            }

            $event->delete();

            $this->slack_client->chatPostMessage([
                'channel' => $payload['user']['id'],
                'text' => "イベント *{$event_name}* を削除しました。"
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info($th);
            response('エラーが発生し、イベントを削除できませんでした。もう一度お試しください。', 200)->send();
        }
    }

    /**
     * イベントを表示する
     *
     * @param Request $request
     * @return void
     * @todo この機能はそのうちwebアプリ等、別手段に移行したいと考えています。
     */
    public function showEvents(Request $request)
    {
        response('', 200)->send();

        try {
            $this->slack_client->chatPostMessage([
                'channel' => $request->user_id,
                'blocks' => json_encode($this->event_payloads->getShowHeaderBlockConstitution()),
            ]);

            $events = Event::where(function ($query) {//開催前のイベントを選択
                $query->where(function ($q) {//明日以降に開催されるイベント
                    $q->whereDate('event_datetime', '>', date('Y-m-d'));
                })->orWhere(function ($q) {//今日開催の、現在時刻より後に開催されるイベント
                    $q->whereDate('event_datetime', date('Y-m-d'))
                    ->whereTime('event_datetime', '>', date('H:i:s'));
                });
            })->get()->sortBy('event_datetime');

            foreach ($events as $event) {
                $this->slack_client->chatPostMessage([
                    'channel' => $request->user_id,
                    'blocks' => json_encode($this->event_payloads->getShowEventBlockConstitution($event)),
                ]);
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }

    /**
     * 開催予定のイベントを知らせる
     *
     * @return void
     */
    public function noticeEvent()
    {
        try {
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
                    'channel' => config('const.slack_id.general'),
                    'blocks' => json_encode($this->event_payloads->getNoticeEventBlocks($event)),
                ]);
                $event->notice_ts = $chat->getTs();
                $event->save();
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }

    /**
     * 午前10時に今日開催するイベントをリマインドする
     *
     * @return void
     */
    public function remindEvent()
    {
        try {
            $today_held_events = Event::whereDate('event_datetime', '=', date('Y-m-d'))//今日開催のイベント
                ->whereNull('remind_ts')//まだリマインドしていないもの
                ->get();
            foreach ($today_held_events as $event) {
                $chat = $this->slack_client->chatPostMessage([
                    'channel' => config('const.slack_id.general'),
                    'blocks' => json_encode($this->event_payloads->getRemindEventBlocks($event)),
                ]);
                $event->remind_ts = $chat->getTs();
                $event->save();
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }

    /**
     * 15分後に始まるイベントのURLを共有する
     *
     * @return void
     */
    public function shareEventUrl()
    {
        try {
            $start_time = new DateTime();
            $start_time->modify('+15 minutes');
            $coming_soon_events = Event::whereDate('event_datetime', $start_time->format('Y-m-d'))
                ->whereTime('event_datetime', $start_time
                ->format('H:i:').'00')
                ->get();//15分後に始まるイベントを取得

            foreach ($coming_soon_events as $event) {
                $this->slack_client->chatPostMessage([
                    'channel' => config('const.slack_id.general'),
                    'blocks' => json_encode($this->event_payloads->getShareEventUrlBlocks($event)),
                ]);
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }

    /**
     * イベントに関する投稿の参加者情報を更新する
     *
     * @param Event $event
     * @return void
     */
    public function updateEventPosts(Event $event)
    {
        try {
            if ($event->notice_ts != null) {//既にお知らせしていればお知らせ投稿を更新
                $this->slack_client->chatUpdate([
                    'channel' => config('const.slack_id.general'),
                    'ts' => $event->notice_ts,
                    'blocks' => json_encode($this->event_payloads->getNoticeEventBlocks($event)),
                ]);
            }

            if ($event->remind_ts != null) {//既にリマインドしていればリマインド投稿を更新
                $this->slack_client->chatUpdate([
                    'channel' => config('const.slack_id.general'),
                    'ts' => $event->remind_ts,
                    'blocks' => json_encode($this->event_payloads->getRemindEventBlocks($event)),
                ]);
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
