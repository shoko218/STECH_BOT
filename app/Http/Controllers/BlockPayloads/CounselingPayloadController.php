<?php

namespace App\Http\Controllers\BlockPayloads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JoliCode\Slack\ClientFactory;

class CounselingPayloadController extends Controller
{


    /**
     * 相談会申し込みフォームを紹介するメッセージの構成を配列で返す(送信する際はjsonエンコードして送信)
     *
     * @return array
     */
    public function getIntroduceBlockConstitution()
    {
        return [
            [
                "type" => "header",
                "text" => [
                    "type" => "plain_text",
                    "text" => ":white_check_mark: 相談会を申し込める「/application-counseling」コマンドのご紹介",
                    "emoji" => true
                ]
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "このワークスペース内でメンターさんに相談会の開催を申し込むことができます！\nチャット入力欄にて */application-counseling* と打ち込むと表示されるフォームに\n必要事項を入力して送信すると申し込みが完了します！\nキャリアや各技術の勉強方法など、メンターさんに直接相談したい方は相談会の開催をお願いしてみましょう！"
                ]
            ],
        ];
    }

    /**
     * モーダルの構成を配列で返す(送信する際はjsonエンコードして送信)
     *
     * @return array
     */
    public function getModalConstitution()
    {
        $view = [
            "callback_id" => "apply_counseling",
            "title" => [
                "type" => "plain_text",
                "text" => "技術相談会を申し込む",
                "emoji" => true
            ],
            "submit" => [
                "type" => "plain_text",
                "text" => "送信",
                "emoji" => true
            ],
            "type" => "modal",
            "close" => [
                "type" => "plain_text",
                "text" => "キャンセル",
                "emoji" => true
            ],
            "blocks" => [
                [
                    "type" => "divider"
                ],
                [
                    "type" => "section",
                    "text" => [
                        "type" => "mrkdwn",
                        "text" => ":pencil2: *質問したいメンター*"
                    ]
                ],
                [
                    "type" => "actions",
                    "block_id" => "mentor",
                    "elements" => [
                        [
                            "type" => "radio_buttons",
                            "action_id" => "mentor",
                            "options" => [
                            ]
                        ],
                    ]
                ],
                [
                    "type" => "divider"
                ],
                [
                    "type" => "input",
                    "block_id" => "consultation_content",
                    "label" => [
                        "type" => "plain_text",
                        "text" => ":pencil2:どんなことを質問/相談したいですか？",
                        "emoji" => true
                    ],
                    "element" => [
                        "type" => "plain_text_input",
                        "action_id" => "consultation_content",
                        "multiline" => true,
                        "placeholder" => [
                            "type" => "plain_text",
                            "text" => "相談したい内容を入力してください。"
                        ]
                    ]
                ],
                [
                    "type" => "divider"
                ],
                [
                    "type" => "section",
                    "text" => [
                        "type" => "mrkdwn",
                        "text" => ":pencil2:*開催希望日時*\n※明日以降の日程を入力してください。"
                    ]
                ],
                [
                    "type" => "input",
                    "block_id" => "first_preferred_date_time",
                    "element" => [
                        "type" => "plain_text_input",
                        "action_id" => "first_preferred_date_time",
                        "placeholder" => [
                            "type" => "plain_text",
                            "text" => "1/1 10:00-18:00"
                        ]
                    ],
                    "label" => [
                        "type" => "plain_text",
                        "text" => "第一希望",
                        "emoji" => true
                    ]
                ],
                [
                    "type" => "input",
                    "block_id" => "second_preferred_date_time",
                    "element" => [
                        "type" => "plain_text_input",
                        "action_id" => "second_preferred_date_time",
                        "placeholder" => [
                            "type" => "plain_text",
                            "text" => "1/2 10:00-18:00"
                        ]
                    ],
                    "label" => [
                        "type" => "plain_text",
                        "text" => "第二希望",
                        "emoji" => true
                    ]
                ],
                [
                    "type" => "input",
                    "block_id" => "third_preferred_date_time",
                    "element" => [
                        "type" => "plain_text_input",
                        "action_id" => "third_preferred_date_time",
                        "placeholder" => [
                            "type" => "plain_text",
                            "text" => "1/3 10:00-18:00"
                        ]
                    ],
                    "label" => [
                        "type" => "plain_text",
                        "text" => "第三希望",
                        "emoji" => true
                    ]
                ]
            ]
        ];

        foreach (config('const.slack_id.mentors') as $key => $mentor) {
            $view['blocks'][2]['elements'][0]['options'][] = [
                "text" => [
                    "type" => "plain_text",
                    "text" => $mentor['name']."：".$mentor['description'],
                    "emoji" => true
                ],
                "value" => "$key"
            ];
        }

        return $view;
    }

    /**
     * 申し込みお知らせメッセージを配列で返す(送信する際はjsonエンコードして送信)
     *
     * @param array $payload
     * @return array
     */
    public function getNotifyApplyBlockConstitution($payload)
    {
        //変数に変換
        $mentor_number = $payload['view']['state']['values']['mentor']['mentor']['selected_option']['value'];
        $mentor_slack_id = config("const.slack_id.mentors")[$mentor_number]['id'];
        $mentor_name = config("const.slack_id.mentors")[$mentor_number]['name'];

        $consultation_content = $payload['view']['state']['values']['consultation_content']['consultation_content']['value'];

        $first_preferred_date_time = $payload['view']['state']['values']['first_preferred_date_time']['first_preferred_date_time']['value'];
        $second_preferred_date_time = $payload['view']['state']['values']['second_preferred_date_time']['second_preferred_date_time']['value'];
        $third_preferred_date_time = $payload['view']['state']['values']['third_preferred_date_time']['third_preferred_date_time']['value'];

        return [
            [
                "type" => "header",
                "text" => [
                    "type" => "plain_text",
                    "text" => ":spiral_calendar_pad:相談会を申し込みました！:spiral_calendar_pad:",
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
                    "text" => "*相談したいメンター*\n{$mentor_name}さん <@${mentor_slack_id}>\n\n*相談内容*\n{$consultation_content}\n\n*第一希望*:{$first_preferred_date_time}\n*第二希望*:{$second_preferred_date_time}\n*第三希望*:{$third_preferred_date_time}"
                ]
            ],
            [
                "type" => "divider"
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "メンターさんからの返信をお待ちください。"
                ]
            ]
        ];
    }

    /**
     * 申し込み完了メッセージを配列で返す(送信する際はjsonエンコードして送信)
     *
     * @param array $payload
     * @return array
     */
    public function getCompletedApplyBlockConstitution($payload)
    {
        //変数に変換
        $mentor_number = $payload['view']['state']['values']['mentor']['mentor']['selected_option']['value'];
        $mentor_slack_id = config("const.slack_id.mentors")[$mentor_number]['id'];
        $mentor_name = config("const.slack_id.mentors")[$mentor_number]['name'];
        $consultation_content = $payload['view']['state']['values']['consultation_content']['consultation_content']['value'];

        $first_preferred_date_time = $payload['view']['state']['values']['first_preferred_date_time']['first_preferred_date_time']['value'];
        $second_preferred_date_time = $payload['view']['state']['values']['second_preferred_date_time']['second_preferred_date_time']['value'];
        $third_preferred_date_time = $payload['view']['state']['values']['third_preferred_date_time']['third_preferred_date_time']['value'];

        $user_id = $payload['user']['id'];
        $user_name = ClientFactory::create(config('services.slack.token'))->usersProfileGet(['user' => $user_id])->getProfile()->getDisplayName();

        return [
            [
                "type" => "header",
                "text" => [
                    "type" => "plain_text",
                    "text" => ":spiral_calendar_pad:{$mentor_name}さんに相談会の申し込みがありました！:spiral_calendar_pad:",
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
                    "text" => "*名前*\n${user_name}さん <@${user_id}>\n\n*相談したいメンター*\n{$mentor_name}さん <@${mentor_slack_id}>\n\n*相談内容*\n{$consultation_content}\n\n*第一希望*:{$first_preferred_date_time}\n*第二希望*:{$second_preferred_date_time}\n*第三希望*:{$third_preferred_date_time}"
                ]
            ],
            [
                "type" => "divider"
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "{$mentor_name}さんは申し込み内容を確認し、${user_name}さんに必要事項の連絡をお願い致します。"
                ]
            ]
        ];
    }
}
