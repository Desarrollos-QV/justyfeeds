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

Route::group(array('namespace' => 'Api'), function () {

    /**
     * 
     * Funciones de incio & Bienvenida
     * 
     */
    Route::get('welcome','ApiController@welcome');
    Route::get('homepage','ApiController@homepage');
    Route::get('getInterest','ApiController@getInterest');
    Route::post('setInterest','ApiController@setInterest');

    /**
     * 
     * Funciones de usuarios
     * 
     */
    Route::get('userinfo/{id}','ApiController@userinfo');
    Route::get('getProfile/{user}','ApiController@getProfile');
    Route::post('login','ApiController@login');

    /**
     * 
     * Funciones de posts
     * 
     */
 
    Route::get('getComments/{id}/{user_id}/{username}','ApiController@getComments');
    Route::post('addNewComment','ApiController@addNewComment');
    Route::post('addNewReaction','ApiController@addNewReaction');
    Route::post('following','ApiController@following'); 
});
