<?php

namespace App\Http\Controllers\BlockPayloads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnonymousQuestionPayloadController extends Controller
{
    /**
     * 匿名質問フォームを紹介するメッセージを構成するブロックを作成する
     *
     * @return array
     */
    public function createQuestionFormIntroductionBlocks()
    {
        return [
            [
                "type" => "header",
                "text" => [
                    "type" => "plain_text",
                    "text" => ":white_check_mark: 匿名質問ができる「/ask-questions」コマンドのご紹介",
                    "emoji" => true
                ]
            ],[
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "このワークスペースには、メンターさんや運営者に質問できるフォームがあります！\nチャット入力欄にて */ask-questions* を打ち込んでみてください:eyes:\n就活や技術のことなど、気になることを匿名で気軽に質問できます。"
                ]
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "plain_text",
                    "text" => "- 全体のslackやメンターさんへの質問にハードルを感じている方\n- 技術のトレンドやオススメの勉強法など、ざっくりとした質問をしてみたい方",
                    "emoji" => true
                ]
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "様々な質問を募集しておりますのでぜひ活用してみてください！"
                ]
            ]
        ];
    }

    /**
    * 匿名質問フォームのモーダルを構成するビューを作成する
    *
    * @return array
    */
    public function createQuestionFormView()
    {
        $view = [
                "type"=> "modal",
                "callback_id" => "ask_questions",
                "title"=> [
                    "type"=> "plain_text",
                    "text"=> "匿名質問フォーム",
                    "emoji"=> true
                ],
                "submit"=> [
                    "type"=> "plain_text",
                    "text" => "送信",
                    "emoji" => true
                ],
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
                            "text" => "*※概要と注意事項*"
                        ]
                    ],
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "plain_text",
                            "text" => "メンターさん(+運営)に、就活や技術など\n気になることを匿名で気軽に質問できる質問フォームです。\n※入力いただいた回答は雑談チャンネルへ投稿されます。",
                            "emoji" => true
                        ]
                    ],
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "plain_text",
                            "text" => "あまりに私的な質問や答えづらい質問・意見はご遠慮ください。 ",
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
                            "text" => ":pencil2: *質問したいメンター*"
                        ]
                    ],
                    [
                        "type" => "actions",
                        "block_id" => "mentors-block",
                        "elements" => [
                            [
                                "type" => "radio_buttons",
                                "action_id" => "mentor",
                                "options" => [
                                ]
                            ]
                        ]
                    ],
                    [
                        "type" => "input",
                        "block_id" => "question-block",
                        "element" => [
                            "type" => "plain_text_input",
                            "action_id" => "question",
                            "multiline" => true,
                        ],
                        "label" => [
                            "type" => "plain_text",
                            "text" => ":pencil2: どんなことを質問したいですか？",
                            "emoji" => true
                        ],
                    ]
                ]
            ];

        foreach (config('const.slack_id.mentors') as $key => $mentor) {
            $view['blocks'][6]['elements'][0]['options'][] = [
                "text" => [
                    "type" => "plain_text",
                    "text" => $mentor['name']."：".$mentor['description'],
                    "emoji" => true
                ],
                "value" => "$key"
            ];
        }

        $view['blocks'][6]['elements'][0]['options'][] = [
            "text" => [
                "type" => "plain_text",
                "text" => "その他",
                "emoji" => true
            ],
            "value" => (string)($key+1)
        ];

        return $view;
    }
}
