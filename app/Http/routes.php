<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


if (!request('mobile') && !request()->isSecure()) {
//    return response()->redirectTo('https://www.photoshare.space');
    header("Location: https://www.photoshare.space");
    exit;

}

$host = request()->header('host');
if ($host == 'www.welapp.net' || $host == 'welapp.net' || $host == 'photoshare.space') {
    header("Location: http://www.photoshare.space");
    exit;
}

DB::connection()->enableQueryLog();

Route::any('allEvents', ['uses' => 'EventController@allEvents']);
Route::any('events', ['uses' => 'EventController@allEvents']);

Route::any('adminEvents', 'EventController@adminEvents');
Route::any('sharedEvents', 'EventController@sharedEvents');

Route::resource('user.event', 'UserEventController');
Route::resource('photo', 'PhotoController');
Route::resource('event', 'EventController');
Route::resource('event.photo', 'EventPhotoController');
Route::resource('event.tag', 'EventTagController');
Route::resource('vue', 'VueController');
Route::controller('vue', 'VueController');
Route::controller('photos', 'PhotoController', [
    'getUpload' => 'photos.upload'
]);
Route::controller('user', 'UserController');
Route::controller('events', 'EventController', [
    'anyAdmin' => 'events.admin',
    'anyShared' => 'events.shared'
]);
Route::controller('comments', 'CommentController');

Route::any('/', ['uses' => 'htmlController@index']);
Route::any('/home', ['uses' => 'htmlController@home']);
//Route::any('/pusher', ['uses' => 'htmlController@pusher']);
Route::controller('t', 'tController');

Route::any('user', ['uses' => 'UserController@getInfo']);
Route::any('u/{id}', ['uses' => 'UserController@id'])->where(['id' => '[0-9]+']);
Route::controller('u', 'UserController');

Route::controller('auth', 'Auth\AuthController');
Route::controller('password', 'Auth\PasswordController');

//sub domain for angular assets
Route::group(['domain' => 'assets.welapp.net'], function () {
    /* angular js , css assets
     * use:  angularController@index
     */
    Route::controller('show', 'assetsController');
//    Route::controller('resources', 'assetsController');
});
Route::group(['domain' => '{sub}.welapp.net'], function () {
    /* angular js , css assets
     * use:  angularController@index
     */
    Route::any('lib/angular/{p1?}/{p2?}/{p3?}/{p4?}/{p5?}/{p6?}/{p7?}', ['uses' => 'angularController@index']);
});
Route::controller('angular', 'angularController');
Route::controller('assets', 'assetsController');


Route::any('token', function () {
    return csrf_token();
});
Route::any('progress', function () {
    return view('progress');
});

get('gettoken', function () {
    return ['X-CSRF-TOKEN' => csrf_token()];
});