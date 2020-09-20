<?php

use Illuminate\Support\Facades\Route;

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

Route::group(['prefix' => LaravelLocalization::setLocale()], function() {
	Auth::routes([
		'login' => TRUE,
		'logout' => TRUE,
		'reset' => FALSE,
		'confirm' => FALSE,
		'verify' => FALSE,
		'register' => FALSE,
	]);

	Route::get('home','HomeController@home');
	Route::get('info','HomeController@info');
	Route::post('render','HomeController@render');
});

Route::redirect('/','home');
Route::get('logout','Auth\LoginController@logout');

Route::group(['prefix'=>'user'],function() {
	Route::get('image','HomeController@user_image');
});
