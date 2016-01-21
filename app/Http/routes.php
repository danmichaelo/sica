<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {

    Route::auth();

	Route::get('/', function () {
	    return view('welcome');
	});

    Route::get('/home', 'HomeController@index');
    Route::get('/records', 'RecordController@index');
    Route::post('/records/lookup', 'RecordController@lookup');
    Route::get('/records/{id}', 'RecordController@edit');
    Route::post('/records/{id}', 'RecordController@update');

});
