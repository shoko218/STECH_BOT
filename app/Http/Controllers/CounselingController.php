<?php

namespace App\Http\Controllers;

use DateTime;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JoliCode\Slack\ClientFactory;

class CounselingController extends Controller
{
    private $slack_client;

    public function __construct()//クライアントを作成
    {
        $this->slack_client = ClientFactory::create(config('services.slack.token'));
    }

    /**
    * 申し込みフォームを表示する
    *
    * @param Request $request
    * @return void
    */
    public function showApplicationModal($trigger_id)
    {
        $params = [
            'view' => json_encode($this->getModalConstitution()),
            'trigger_id' => $trigger_id
        ];

        $this->slack_client->viewsOpen($params);
        response('', 200)->send();
    }

    /**
    * メンターさんに申し込み内容を送信する
    *
    * @param Request $request
    * @return void
    */
    public function notifyToMenter($payload)
    {
        //変数に変換
        $menter_slack_id = $payload['view']['state']['values']['menter_slack_id']['menter_slack_id']['selected_option']['value'];
        $menter_name = $payload['view']['state']['values']['menter_slack_id']['menter_slack_id']['selected_option']['text']['text'];
        $consultation_content = $payload['view']['state']['values']['consultation_content']['consultation_content']['value'];

        $first_preferred_date_time = $payload['view']['state']['values']['first_preferred_date_time']['first_preferred_date_time']['value'];
        $second_preferred_date_time = $payload['view']['state']['values']['second_preferred_date_time']['second_preferred_date_time']['value'];
        $third_preferred_date_time = $payload['view']['state']['values']['third_preferred_date_time']['third_preferred_date_time']['value'];

        $user_id = $payload['user']['id'];
        $user_name = $this->slack_client->usersProfileGet(['user' => $user_id])->getProfile()->getDisplayName();

        $this->slack_client->chatPostMessage([
            'channel' => config('const.slack_id.menter_channel'),
            'text' => "<@".$menter_slack_id.">\n{$menter_name}さんに相談会の申し込みがありました！\n\n```名前:{$user_name}さん【<@".$user_id.">】\n相談内容:{$consultation_content}\n第一希望:{$first_preferred_date_time}\n第二希望:{$second_preferred_date_time}\n第三希望:{$third_preferred_date_time}\n```",
        ]);

        $this->slack_client->chatPostMessage([
            'channel' => $user_id,
            'text' => "相談会を申し込みました！\nメンターさんからの返信をお待ちください。\n\n```メンター:{$menter_name}さん\n相談内容:{$consultation_content}\n第一希望:{$first_preferred_date_time}\n第二希望:{$second_preferred_date_time}\n第三希望:{$third_preferred_date_time}\n```",
        ]);

        response('', 200)->send();
    }

    /**
     * モーダルの構成を配列で返す(送信する際はjsonエンコードして送信)
     *
     * @return array
     */
    public function getModalConstitution()
    {
        return [
            "callback_id" => "apply_counseling",
            "title" => [
                "type" => "plain_text",
                "text" => "技術相談会を申し込む",
                "emoji" => true
            ],
            "submit" => [
                "type" => "plain_text",
                "text" => "Submit",
                "emoji" => true
            ],
            "type" => "modal",
            "close" => [
                "type" => "plain_text",
                "text" => "Cancel",
                "emoji" => true
            ],
            "blocks" => [
                [
                    "type" => "input",
                    "block_id" => "menter_slack_id",
                    "label" => [
                        "type" => "plain_text",
                        "text" => "相談したいメンター",
                        "emoji" => true
                    ],
                    "element" => [
                        "type" => "static_select",
                        "placeholder" => [
                            "type" => "plain_text",
                            "text" => "メンターさんを選択してください。",
                            "emoji" => true
                        ],
                        "options" => [
                            [
                                "text" => [
                                    "type" => "plain_text",
                                    "text" => "メンター1",
                                    "emoji" => true
                                ],
                                "value" => config('const.slack_id.menters')[0]
                            ],
                            [
                                "text" => [
                                    "type" => "plain_text",
                                    "text" => "メンター2",
                                    "emoji" => true
                                ],
                                "value" => config('const.slack_id.menters')[1]
                            ]
                        ],
                        "action_id" => "menter_slack_id"
                    ]
                ],
                [
                    "type" => "input",
                    "block_id" => "consultation_content",
                    "label" => [
                        "type" => "plain_text",
                        "text" => "どんなことを質問/相談したいですか？",
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
                    "type" => "section",
                    "text" => [
                        "type" => "mrkdwn",
                        "text" => "*開催希望日時*"
                    ]
                ],
                [
                    "type" => "divider"
                ],
                [
                    "type" => "input",
                    "block_id" => "first_preferred_date_time",
                    "element" => [
                        "type" => "plain_text_input",
                        "action_id" => "first_preferred_date_time",
                        "placeholder" => [
                            "type" => "plain_text",
                            "text" => "○月○日 xx時xx分〜xx時xx分"
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
                            "text" => "○月○日 xx時xx分〜xx時xx分"
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
                            "text" => "○月○日 xx時xx分〜xx時xx分"
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
    }
}
