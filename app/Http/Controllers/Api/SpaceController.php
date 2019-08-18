<?php

namespace App\Http\Controllers\Api;

use App\Channel;
use App\Http\Controllers\Controller;
use App\Space;
use App\User;
use Illuminate\Http\Request;

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

    public function channelCreate($subdomain, Request $request)
    {
        /**
         * Подобную проверку вывести в отдельный посредник
         */
        $space = $this->user->spaces->where("subdomain", $subdomain)->first();
        if (!$space) {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }
        $request->validate([
            'name' => "required|max:100",
            'access' => "required|in:visible,hidden"
        ]);
        $channel = new Channel();
        $channel->name = $request->name;
        $channel->access = $request->access;
        $channel->space_id = $space->id;
        $channel->type = "public";
        $channel->save();

        $attach = [];
        foreach ($request->users as $user) {
            $attach[$user['id']] = ['rights' => "member"];
        }
        $attach[$this->user->id] = ['rights' => "founder"];
        $channel->users()->attach($attach);

        return response()->json([
            'status' => 1,
            'channel_id' => $channel->id
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

    public function channelUsers($subdomain, $channel_id)
    {
        /**
         * Подобную проверку вывести в отдельный посредник
         */
        $space = $this->user->spaces->where("subdomain", $subdomain)->first();
        if (!$space) {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }
        $channel = $space->channels->find($channel_id);
        if (!$channel) {
            return response()->json([
                'status' => 0,
                'msg' => 'Channel not found'
            ]);
        }
        $users = $channel->users()->get(['users.id', 'users.name', 'users.surname', 'users.avatar']);

        $is_founder = $users->where("pivot.rights", 'founder')->first()->id == $this->user->id;

        return response()->json([
            'status' => 1,
            'users' => $users,
            'is_founder' => $is_founder,
            'me_id' => $this->user->id,
        ]);
    }

    public function channelUserMakeAdmin($subdomain, $channel_id, Request $request)
    {
        /**
         * Подобную проверку вывести в отдельный посредник
         */
        $space = $this->user->spaces->where("subdomain", $subdomain)->first();
        if (!$space) {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }
        $channel = $space->channels->find($channel_id);
        if (!$channel) {
            return response()->json([
                'status' => 0,
                'msg' => 'Channel not found'
            ]);
        }

        if ($channel->users->find($this->user->id)->pivot->rights !== "founder") {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }

        $channel->users()->updateExistingPivot($request->user_id, ['rights' => "founder"]);
        $channel->users()->updateExistingPivot($this->user->id, ['rights' => "member"]);
        return response()->json([
            'status' => 1,
        ]);
    }

    public function channelUserKickOut($subdomain, $channel_id, Request $request)
    {
        /**
         * Подобную проверку вывести в отдельный посредник
         */
        $space = $this->user->spaces->where("subdomain", $subdomain)->first();
        if (!$space) {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }
        $channel = $space->channels->find($channel_id);
        if (!$channel) {
            return response()->json([
                'status' => 0,
                'msg' => 'Channel not found'
            ]);
        }
        if ($channel->users->find($this->user->id)->pivot->rights !== "founder") {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }

        $channel->users()->detach($request->user_id);

        $deleted = false;
        if ($channel->users->count() == 2) {
            $deleted = $channel->delete();
        }

        return response()->json([
            'status' => 1,
            'channel_deleted' => $deleted
        ]);
    }

    public function channelInfo($subdomain, $channel_id)
    {
        /**
         * Подобную проверку вывести в отдельный посредник
         */
        $space = $this->user->spaces->where("subdomain", $subdomain)->first();
        if (!$space) {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }
        $channel = $space->channels->find($channel_id);
        if (!$channel) {
            return response()->json([
                'status' => 0,
                'msg' => 'Channel not found'
            ]);
        }

        return response()->json([
            'status' => 1,
            'channel' => $channel->only(['id', 'name', 'access'])
        ]);
    }

    public function channelUpdate($subdomain, $channel_id, Request $request)
    {
        /**
         * Подобную проверку вывести в отдельный посредник
         */
        $space = $this->user->spaces->where("subdomain", $subdomain)->first();
        if (!$space) {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }
        $channel = $space->channels->find($channel_id);
        if (!$channel) {
            return response()->json([
                'status' => 0,
                'msg' => 'Channel not found'
            ]);
        }
        if ($channel->users->find($this->user->id)->pivot->rights !== "founder") {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }
        $channel->name = $request->name;
        $channel->access = $request->access;
        $channel->save();
        return response()->json([
            'status' => 1,
        ]);

    }

    public function channelDelete($subdomain, $channel_id, Request $request)
    {
        /**
         * Подобную проверку вывести в отдельный посредник
         */
        $space = $this->user->spaces->where("subdomain", $subdomain)->first();
        if (!$space) {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }
        $channel = $space->channels->find($channel_id);
        if (!$channel) {
            return response()->json([
                'status' => 0,
                'msg' => 'Channel not found'
            ]);
        }
        if ($channel->users->find($this->user->id)->pivot->rights !== "founder") {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }
        $channel->delete();
        return response()->json([
            'status' => 1,
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
}
