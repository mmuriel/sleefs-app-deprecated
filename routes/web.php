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

/*
Route::get('/', function () {
    return view('welcome');
});
*/

/*

	Rutas para Test con Phpunit

*/

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/', "\Sleefs\Controllers\Web\WebController@index");
Route::get('/pos', "\Sleefs\Controllers\Web\PosController@index");
Route::get('/pos/{poid}', "\Sleefs\Controllers\Web\PosController@showPo");
Route::post('/report', "\Sleefs\Controllers\Web\WebController@report");
