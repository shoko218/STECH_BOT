<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/interactive_endpoint', 'InteractiveEndpointController');

Route::prefix('/slash')->group(function () {
    Route::post('/show_application_counseling_modal', 'CounselingController@showApplicationModal');
    Route::post('/ask_questions', 'AnonymousQuestionController@openQuestionForm');
    Route::group(['middleware' => 'check.admin'], function () {
        Route::post('/show_create_event_modal', 'EventController@showCreateEventModal');
        Route::post('/show_events', 'EventController@showEvents');
    });
});
