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

header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');

//Route::middleware('cors')->group(function(){
    Route::prefix('auth')->group(function () {
        Route::post("/register", "Api\Auth\AuthController@register");
        Route::post("/login", "Api\Auth\AuthController@login");
    });
    Route::prefix('spaces')->group(function () {
        Route::get("/", "Api\SpaceController@list");
    });


//});
Route::group(['middleware' => 'cors'], function () {

    Route::middleware('auth:api')->get('/user', function (Request $request) {
        return $request->user();
    });
});
