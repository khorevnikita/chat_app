<?php

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

    Route::prefix('auth')->group(function () {
        Route::post("/register", "Api\Auth\AuthController@register");
        Route::post("/login", "Api\Auth\AuthController@login");
    });
    Route::middleware('auth_user')->group(function () {
        Route::prefix('spaces')->group(function () {
            Route::get("/", "Api\SpaceController@list");
            Route::post("/create", "Api\SpaceController@createSpace");
            Route::group(['prefix' => '{subdomain}'], function () {
                Route::get("/", "Api\SpaceController@show");
                Route::get("/users", "Api\SpaceController@users");
                Route::group(['prefix' => 'channels'], function () {
                    Route::post("/create", "Api\SpaceController@channelCreate");
                    Route::get("/{id}", "Api\SpaceController@channelMessages");
                    Route::get("/{id}/info", "Api\SpaceController@channelInfo");
                    Route::get("/{id}/users", "Api\SpaceController@channelUsers");
                    Route::post("/{id}/update", "Api\SpaceController@channelUpdate");
                    Route::post("/{id}/delete", "Api\SpaceController@channelDelete");
                    Route::post("/{id}/users/make-admin", "Api\SpaceController@channelUserMakeAdmin");
                    Route::post("/{id}/users/kick-out", "Api\SpaceController@channelUserKickOut");
                });
            });
        });
    });
