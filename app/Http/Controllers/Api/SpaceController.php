<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NewSpaceUser;
use App\Space;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SpaceController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = User::authUser();
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

    public function createSpace(Request $request)
    {
        $request->validate([
            'name' => "required|max:150|unique:spaces",
            'subdomain' => "required|max:150|unique:spaces",
        ]);
        $space = new Space;
        $space->name = $request->name;
        $space->subdomain = $request->subdomain;
        $space->save();


        $space->users()->attach($this->user->id, ['rights' => "founder"]);

        return response()->json([
            'status' => 1,
            'space' => $space
        ]);
    }

    public function show($subdomain)
    {
        $user = $this->user;
        $space = Space::whereHas('users', function ($q) use ($user) {
            $q->where("users.id", $user->id);
        })->with(["users" => function ($q) use ($user) {
            $q->where("users.id", "!=", $user->id);
        }])->with(['channels' => function ($q) use ($user) {
            $q->where(function ($q) use ($user) {
                $q->whereHas("users", function ($q) use ($user) {
                    $q->where("users.id", $user->id);
                });
            })->where("type", "public");
        }])->where("subdomain", $subdomain)->first();

        if (!$space) {
            return response()->json([
                'status' => 0,
                'msg' => 'space has not been found'
            ]);
        }
        $badges = [];
        foreach ($space->channels as $channel) {
            $badges[$channel->id] = $user->newMessagesCount($channel->id);
        }

        foreach ($space->users as $member) {
            // try to find unread messages in private channels

            //$member->private_channel_id = $space->channels->where("type", "private")->where('channels');
        }

        return response()->json([
            'space' => $space->toArray(),
            'me' => $user->only(['name', "surname", 'avatar', 'id']),
            'badges' => $badges,
            "status" => 1
        ]);
    }

    public function users($subdomain, Request $request)
    {
        if (!$this->user->spaces->where("subdomain", $subdomain)->first()) {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }

        $query_string = $request->q;
        if (!$query_string) {
            return response()->json([
                'status' => 1,
                'users' => []
            ]);
        }
        $users = User::whereHas("spaces", function ($q) use ($subdomain) {
            $q->where("subdomain", $subdomain);
        })->where(function ($q) use ($query_string) {
            $q->where("name", "like", "%$query_string%")->orWhere("surname", "like", "%$query_string%")->orWhere("username", "like", "%$query_string%")->orWhere("email", "like", "%$query_string%");
        })->where("id", "!=", $this->user->id)->get(['id', "name", "surname", "username"]);

        return response()->json([
            'status' => 1,
            'users' => $users
        ]);
    }

    public function inviteUser($subdomain, Request $request)
    {
        $space = $this->user->spaces->where("subdomain", $subdomain)->first();
        if (!$space) {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }

        $request->validate([
            'email' => "required|email"
        ]);

        $checkUser = User::where("email", $request->email)->first();

        if ($checkUser) {
            $checkAttachment = $checkUser->spaces->where("id", $space->id)->first();
            if ($checkAttachment) {
                return response()->json([
                    'status' => 0,
                    'msg' => "Already in space"
                ]);
            }
            $user = $checkUser;
        } else {
            $user = new User();
            $user->email = $request->email;
            $user->username = explode("@", $request->email)[0];
            $user->password = bcrypt(str_random(12));
            $user->save();
        }
        $space->users()->attach($user->id, ['rights' => "member"]);
        Mail::to($user)->send(new NewSpaceUser($space, $user));
        return response()->json([
            'status' => 1
        ]);
    }
}
