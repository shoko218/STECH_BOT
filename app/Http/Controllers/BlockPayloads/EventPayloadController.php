<?php

namespace App\Http\Controllers\BlockPayloads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EventPayloadController extends Controller
{
    /**
     * イベント作成モーダルの構成を配列で返す(送信する際はjsonエンコードして送信)
     *
     * @return array
     * @todo 現在、timepickerがベータ版にしかないのでactionに日時のセレクターを埋め込み、無理矢理日時が横並びになるようにしています。
     * timepickerが正式にリリースされたら速やかにblock kitの構成を変更し、それにしたがってInteractiveEndpointControllerでの処理も書き換えてください。
     */
    public function getCreateEventModalConstitution()
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
                "text" => "登録",
                "emoji" => true
            ],
            "close" => [
                "type" => "plain_text",
                "text" => "キャンセル",
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
     * イベント登録完了メッセージの構成を配列で返す(送信する際はjsonエンコードして送信)
     *
     * @param Event $event
     * @return array
     */
    public function getCreatedEventMessageBlockConstitution($event)
    {
        return[
            [
                "type" => "header",
                "text" => [
                    "type" => "plain_text",
                    "text" => ":spiral_calendar_pad:イベントを登録しました！:spiral_calendar_pad:",
                    "emoji" => true
                ]
            ],
            [
                "type" => "divider"
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "*イベント名*\n{$event->name}\n*イベント概要*\n{$event->description}\n*イベントURL*\n{$event->url}\n*イベント日時*\n{$event->event_datetime->format('Y年n月j日 G:i')}\n*お知らせ日時*\n{$event->notice_datetime->format('Y年n月j日 G:i')}"
                ]
            ],
        ];
    }

    /**
     * イベント一覧を表示するメッセージの構成の一部(イベント情報1つ)を配列で返す(送信する際はjsonエンコードして送信)
     *
     * @param Event $event
     * @return array
     */
    public function getShowEventBlockConstitution($event)
    {
        return[
            [
                "type" => "section",
                "block_id" => "delete_event",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "*イベント名*\n{$event->name}\n*イベント概要*\n{$event->description}\n*イベントURL*\n{$event->url}\n*イベント日時*\n{$event->event_datetime->format('Y年n月j日 G:i')}\n*お知らせ日時*\n{$event->notice_datetime->format('Y年n月j日 G:i')}",
                ],
                "accessory" => [
                    "type" => "button",
                    "text" => [
                        "type" => "plain_text",
                        "text" => ":wastebasket: 削除",
                        "emoji" => true
                    ],
                    "value" => "$event->id",
                    "style" => "danger",
                    "confirm" => [
                        "title" => [
                            "type" => "plain_text",
                            "text" => "本当に削除しますか？"
                        ],
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => "{$event->name}を削除しますか？"
                        ],
                        "confirm" => [
                            "type" => "plain_text",
                            "text" => "はい"
                        ],
                        "deny" => [
                            "type" => "plain_text",
                            "text" => "いいえ"
                        ]
                    ],
                ]
            ],
            [
                "type" => "divider",
            ]
        ];
    }

    /**
     * イベント一覧を表示するメッセージのヘッダーを配列で返す(送信する際はjsonエンコードして送信)
     *
     * @return array
     */
    public function getShowHeaderBlockConstitution()
    {
        return[
                [
                    "type" => "header",
                    "text" => [
                        "type" => "plain_text",
                        "text" => "開催予定のイベント一覧",
                        "emoji" => true
                    ]
                ],
                [
                    "type" => "divider",
                ],
        ];
    }

    /**
     * お知らせ登録の内容を配列で返す(送信する際はjsonエンコードして送信)
     *
     * @param Event $event
     * @return array
     */
    public function getNoticeEventBlocks($event)
    {
        try {
            $event_participants = "";
            foreach ($event->eventParticipants as $event_participant) {//参加者一覧を一つの文字列に
                $event_participants .= "<@".$event_participant->slack_user_id."> ";
            }
            if ($event_participants === "") {//参加者がいない場合
                $event_participants = 'まだいません。';
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }

        return [
            [
                "type" => "header",
                "text" => [
                    "type" => "plain_text",
                    "text" => ":mega:イベントのお知らせ:mega:",
                    "emoji" => true
                ]
            ],
            [
                "type" => "divider"
            ],
            [
                "type" => "header",
                "text" => [
                    "type" => "plain_text",
                    "text" => "{$event->name}を開催します！",
                    "emoji" => true
                ]
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "*日時*\n{$event->event_datetime->format('Y年n月j日 G:i')}〜\n*概要*\n{$event->description}\n\n参加を希望する方は「参加する！」ボタンを押してください！\n参加を取りやめたい方は「参加をやめる」ボタンを押してください。\n"
                ]
            ],
            [
                "type" => "actions",
                "elements" => [
                    [
                        "type" => "button",
                        "text" => [
                            "type" => "plain_text",
                            "text" => ":hand:参加する！",
                            "emoji" => true
                        ],
                        "value" => "$event->id",
                        "style" => "primary",
                        "action_id" => "register_participant"
                    ],
                    [
                        "type" => "button",
                        "text" => [
                            "type" => "plain_text",
                            "text" => "参加をやめる",
                            "emoji" => true
                        ],
                        "value" => "$event->id",
                        "action_id" => "remove_participant"
                    ]
                ],
                "block_id" => "change_participant",
            ],
            [
                "type" => "divider"
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "*参加者*\n $event_participants"
                ]
            ],
        ];
    }

    /**
     * リマインド投稿の内容を配列で返す(送信する際はjsonエンコードして送信)
     *
     * @param Event $event
     * @return array
     */
    public function getRemindEventBlocks($event)
    {
        try {
            $event_participants = "";
            foreach ($event->eventParticipants as $event_participant) {//参加者一覧を一つの文字列に
                $event_participants .= "<@".$event_participant->slack_user_id."> ";
            }
            if ($event_participants === "") {//参加者がいない場合
                $event_participants = 'まだいません。';
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }

        return [
            [
                "type" => "header",
                "text" => [
                    "type" => "plain_text",
                    "text" => ":bell:イベントリマインド:bell:",
                    "emoji" => true
                ]
            ],
            [
                "type" => "divider"
            ],
            [
                "type" => "header",
                "text" => [
                    "type" => "plain_text",
                    "text" => "本日、{$event->name}を開催します！",
                    "emoji" => true
                ]
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "*時間*\n{$event->event_datetime->format('G:i')}〜\n*概要*\n{$event->description}\n\n参加を希望する方は「参加する！」ボタンを押してください！\n参加を取りやめたい方は「参加をやめる」ボタンを押してください。\n"
                ]
            ],
            [
                "type" => "actions",
                "elements" => [
                    [
                        "type" => "button",
                        "text" => [
                            "type" => "plain_text",
                            "text" => ":hand:参加する！",
                            "emoji" => true
                        ],
                        "value" => "$event->id",
                        "style" => "primary",
                        "action_id" => "register_participant"
                    ],
                    [
                        "type" => "button",
                        "text" => [
                            "type" => "plain_text",
                            "text" => "参加をやめる",
                            "emoji" => true
                        ],
                        "value" => "$event->id",
                        "action_id" => "remove_participant"
                    ]
                ],
                "block_id" => "change_participant",
            ],
            [
                "type" => "divider"
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
     * イベントのURL共有投稿の内容を配列で返す(送信する際はjsonエンコードして送信)
     *
     * @param Event $event
     * @return array
     */
    public function getShareEventUrlBlocks($event)
    {
        return [
            [
                "type" => "header",
                "text" => [
                    "type" => "plain_text",
                    "text" => ":link:イベントURLの共有:link:",
                    "emoji" => true
                ]
            ],
            [
                "type" => "divider"
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "この後{$event->event_datetime->format('G:i')}から開催する *{$event->name}* のURLはこちら！\n*{$event->url}*"
                ]
            ]
        ];
    }
}
