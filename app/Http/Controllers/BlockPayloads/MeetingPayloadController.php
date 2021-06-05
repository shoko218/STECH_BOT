<?php

namespace App\Http\Controllers\BlockPayloads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeetingPayloadController extends Controller
{
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
                "block_id" => "confirm_meeting",
                "elements" => [
                    [
                        "type" => "button",
                        "text" => [
                            "type" => "plain_text",
                            "text" => ":o: 両日とも開催",
                            "emoji" => true
                        ],
                        "value" => "both_meetings",
                        "action_id" => "meeting_option1"
                    ],
                    [
                        "type" => "button",
                        "text" => [
                            "type" => "plain_text",
                            "text" => ":eight_pointed_black_star: 月曜日のみ",
                            "emoji" => true
                        ],
                        "value" => "first_meeting",
                        "action_id" => "meeting_option2"
                    ],
                    [
                        "type" => "button",
                        "text" => [
                            "type" => "plain_text",
                            "text" => ":sparkle: 木曜日のみ",
                            "emoji" => true
                        ],
                        "value" => "second_meeting",
                        "action_id" => "meeting_option3"
                    ],
                    [
                        "type" => "button",
                        "text" => [
                            "type" => "plain_text",
                            "text" => ":x: 開催しない",
                            "emoji" => true
                        ],
                        "value" => "not_both_meetings",
                        "action_id" => "meeting_option4"
                    ]
                ]
            ]
        ];
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

    


}
