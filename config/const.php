<?php

$mentor_names = array_map('trim', explode(',', env('SLACK_ID_MENTORS_NAME')));
$mentor_descriptions = array_map('trim', explode(',', env('SLACK_ID_MENTORS_DESCRIPTION')));
$mentor_ids = array_map('trim', explode(',', env('SLACK_ID_MENTORS_ID')));

$const = [
    'slack_id' => [
        'general' => env('SLACK_ID_GENERAL'),
        'event_channel' => env('SLACK_ID_EVENT_CHANNEL'),
        'administrator' => env('SLACK_ID_ADMINISTRATOR'),
        'mentor_channel' => env('SLACK_ID_MENTOR_CHANNEL'),
        'question_channel' => env('SLACK_ID_QUESTION_CHANNEL'),
    ]
];

for ($i=0; $i < count($mentor_names); $i++) {
    $const['slack_id']['mentors'][$i]['name'] = $mentor_names[$i];
    $const['slack_id']['mentors'][$i]['description'] = $mentor_descriptions[$i];
    $const['slack_id']['mentors'][$i]['id'] = $mentor_ids[$i];
}

return $const;
