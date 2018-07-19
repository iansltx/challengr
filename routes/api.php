<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['auth:api']], function() {
    Route::get('/me', function (Request $request) {
        return $request->user();
    });

    Route::get('/me/activities', 'ActivityController@forCurrentUser');
    Route::post('/me/activities', 'ActivityController@create');
    Route::get('/me/activities/{id}', 'ActivityController@get');
    Route::patch('/me/activities/{id}', 'ActivityController@update');
    Route::delete('/me/activities/{id}', 'ActivityController@delete');

    Route::get('/me/challenges', 'ChallengeController@forCurrentUser');
    Route::post('/challenges', 'ChallengeController@create');
    Route::get('/challenges', 'ChallengeController@getAll');
    Route::get('/challenges/{id}', 'ChallengeController@get');
    Route::post('/challenges/{id}/join', 'ChallengeController@join');
    Route::delete('/me/challenges/{id}', 'ChallengeController@leave');
});

Route::post('/users', 'UserController@create');
