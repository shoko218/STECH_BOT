<?php

namespace App\Http\Controllers\SlashCommand;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Illuminate\Http\Response;

class ShowCreateEventModalController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $url = 'https://slack.com/api/views.open';
            $token = config('services.slack.token');
            $view = $this->getModalConstitution();
            $trigger_id = $request->input('trigger_id');

            $params = [
                'view' => json_encode($view),
                'trigger_id' => $trigger_id
            ];

            $client = new Client();
            $response = $client->request('POST',$url,
                [
                    'headers' => [
                        'Content-type' => 'application/json',
                        'Authorization'  =>  'Bearer ' . $token
                    ],
                    'json' => $params
                ]
            );

            Log::info("succeed! -ShowCreateEventModalController".date("Y/m/d H:i:s"));
            return response('',200);
        } catch (\Throwable $th) {
            Log::info("failed... -ShowCreateEventModalController".date("Y/m/d H:i:s"));
            Log::info($th);
            return 1;
        }
    }

    //現在、timepickerがベータ版にしかないのでactionに日時のセレクターを埋め込み、無理矢理日時が横並びになるようにしています
    //timepickerが正式にリリースされたら速やかにblock kitの構成を変更し、それにしたがってInteractiveEndpointControllerでの処理も書き換えてください
    public function getModalConstitution(){//モーダルの構成を配列で返す
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
                "text" => "Submit",
                "emoji" => true
            ],
            "close" => [
                "type" => "plain_text",
                "text" => "Cancel",
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
}
