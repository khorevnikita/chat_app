<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Space;
use App\User;
use Illuminate\Support\Facades\Request;

class SpaceController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = User::where("api_token", request()->header('authorization'))->first();
    }

    public function list(Request $request)
    {
        $spaces = $this->user->spaces;
        return response()->json([
            'status'=>1,
            'data'=>[
                'spaces'=>$spaces
            ]
        ]);
    }

    public function show($subdomain)
    {
        $user = $this->user;
        $space = Space::whereHas('users', function ($q) use ($user) {
            $q->where("users.id", $user->id);
        })->with("channels", "users")->where("subdomain", $subdomain)->first();

        if (!$space) {
            return response()->json([
                'status' => 0,
                'msg' => 'space has not been found'
            ]);
        }
        return response()->json([
            'space' => $space->toArray(),
            'me'=>$user->only(['name',"surname",'avatar']),
            "status" => 1
        ]);
    }

    public function channelMessages($subdomain, $channel_id)
    {
        $user = $this->user;
        $space = Space::whereHas('users', function ($q) use ($user) {
            $q->where("users.id", $user->id);
        })->where("subdomain", $subdomain)->first();

        $channel = $space->channels()->with("users", "messages",'messages.users')->where("id", $channel_id)->first();
        if (!$channel) {
            return response()->json([
                'status' => 0,
                'msg' => 'channel has not been found'
            ]);
        }
        return response()->json([
            'status' => 1,
            'channel' => $channel
        ]);

    }
}
