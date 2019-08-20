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
        Route::get("/verify", "Api\Auth\AuthController@findUser");
        Route::post("/verify", "Api\Auth\AuthController@verifyUser")->middleware("auth_user");
        Route::get("/profile", "Api\Auth\AuthController@getProfile")->middleware("auth_user");
        Route::post("/profile", "Api\Auth\AuthController@setProfile")->middleware("auth_user");
    });
    Route::middleware('auth_user')->group(function () {
        Route::prefix('spaces')->group(function () {
            Route::get("/", "Api\SpaceController@list");
            Route::post("/create", "Api\SpaceController@createSpace");
            Route::group(['prefix' => '{subdomain}'], function () {
                Route::get("/", "Api\SpaceController@show");
                Route::get("/users", "Api\SpaceController@users");
                Route::post("/invite", "Api\SpaceController@inviteUser");
                Route::get("/get-user-channel", "Api\ChannelController@getChannelFromUser");
                Route::group(['prefix' => 'channels'], function () {
                    Route::post("/create", "Api\ChannelController@channelCreate");
                    Route::get("/{id}", "Api\ChannelController@channelMessages");
                    Route::get("/{id}/info", "Api\ChannelController@channelInfo");
                    Route::get("/{id}/users", "Api\ChannelController@channelUsers");
                    Route::post("/{id}/update", "Api\ChannelController@channelUpdate");
                    Route::post("/{id}/delete", "Api\ChannelController@channelDelete");
                    Route::post("/{id}/leave", "Api\ChannelController@channelLeave");
                    Route::post("/{id}/users/make-admin", "Api\ChannelController@channelUserMakeAdmin");
                    Route::post("/{id}/users/kick-out", "Api\ChannelController@channelUserKickOut");
                    Route::post("/{id}/send-message", "Api\ChannelController@newMessage");
                });
            });
        });
    });
