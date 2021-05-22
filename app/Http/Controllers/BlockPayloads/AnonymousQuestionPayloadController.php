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
    public function createQuestionFormIntroductionBlocks () 
    {
        return [
            [
                "type" => "header",
                "text" => [
                    "type" => "plain_text",
                    "text" => ":white_check_mark: 匿名質問ができる「/ask-questions」コマンドのご紹介",
                    "emoji" => true
                ]
            ],
            [
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
    public function createQuestionFormView () 
    {
        return [
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
                                    [
                                        "text" => [
                                            "type" => "plain_text",
                                            "text" => "今川メンター：Ruby, Scala, PHP, AWSなど",
                                            "emoji" => true
                                        ],
                                        "value" => "0"
                                    ],
                                    [
                                        "text" => [
                                            "type" => "plain_text",
                                            "text" => "菊池メンター：Kotlin, flutter など",
                                            "emoji" => true
                                        ],
                                        "value" => "1"
                                    ],
                                    [
                                        "text" => [
                                            "type" => "plain_text",
                                            "text" => "工藤メンター：PHP, Go, ハッカソンの審査/勝ち方 など",
                                            "emoji" => true
                                        ],
                                        "value" => "2"
                                    ],
                                    [
                                        "text" => [
                                            "type" => "plain_text",
                                            "text" => "近藤メンター：JavaScript, TypeScript, フロントエンド全般 など",
                                            "emoji" => true
                                        ],
                                        "value" => "3"
                                    ],
                                    [
                                        "text" => [
                                            "type" => "plain_text",
                                            "text" => "noppe(平野)メンター：Swift, 個人開発 など",
                                            "emoji" => true
                                        ],
                                        "value" => "4"
                                    ],
                                    [
                                        "text" => [
                                            "type" => "plain_text",
                                            "text" => "山際メンター：Python, Go, 機械学習, 就活相談 など",
                                            "emoji" => true
                                        ],
                                        "value" => "5"
                                    ],
                                    [
                                        "text" => [
                                            "type" => "plain_text",
                                            "text" => "その他",
                                            "emoji" => true
                                        ],
                                        "value" => "6"
                                    ]
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
    }
}
