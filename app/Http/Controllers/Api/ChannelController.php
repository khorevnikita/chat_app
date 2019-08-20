<?php

namespace App\Http\Controllers\Api;

use App\Channel;
use App\Http\Controllers\Controller;
use App\Message;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class ChannelController extends Controller
{
    protected $user;
    protected $subdomain;
    protected $space;

    public function __construct()
    {
        $this->user = User::where("api_token", request()->header('Authorization'))->first();;
        $this->subdomain = Route::current()->parameter('subdomain');
        $this->space = $this->user->spaces->where('subdomain', $this->subdomain)->first();

        if (!$this->space) {
            return response()->json([
                'status' => 0,
                'msg' => 'Has no rights'
            ]);
        }
    }

    public function getChannelFromUser($subdomain, Request $request)
    {

        $target = $this->space->users->find($request->user_id);
        if (!$target) {
            return response()->json([
                'status' => 0,
                'msg' => "User has not been found"
            ]);
        }
        $ids = [$this->user->id, $target->id];
        $checkChannel = $this->space->channels()->whereHas('users', function ($q) use ($ids) {
            $q->where("users.id", $ids[0]);
        })->whereHas('users', function ($q) use ($ids) {
            $q->where("users.id", $ids[1]);
        })->where("type", 'private')->first();
        if ($checkChannel) {
            $channel = $checkChannel;
        } else {
            $channel = new Channel();
            $channel->name = "private";
            $channel->access = "hidden";
            $channel->type = "private";
            $channel->space_id = $this->space->id;
            $channel->save();

            $channel->users()->attach($ids);
        }

        return response()->json([
            'status' => 1,
            'channel' => $channel
        ]);
    }

    public function channelCreate($subdomain, Request $request)
    {

        $request->validate([
            'name' => "required|max:100",
            'access' => "required|in:visible,hidden"
        ]);
        $channel = new Channel();
        $channel->name = $request->name;
        $channel->access = $request->access;
        $channel->space_id = $this->space->id;
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
        $channel = $this->space->channels()->with("users", "messages", 'messages.users')->where("id", $channel_id)->first();
        if (!$channel) {
            return response()->json([
                'status' => 0,
                'msg' => 'channel has not been found'
            ]);
        }
        $msg_ids = $this->user->messages->where("channel_id", $channel_id)->where("pivot.read", 0)->pluck("id");
        DB::table('message_user')->where("user_id", $this->user->id)->whereIn("message_id", $msg_ids)->update(['read' => 1]);
        $founder = $channel->users->where("pivot.rights", "founder")->first();
        $is_founder = $founder ? ($founder->id == $this->user->id) : false;
        return response()->json([
            'status' => 1,
            'channel' => $channel,
            'is_founder' => $is_founder
        ]);

    }

    public function channelUsers($subdomain, $channel_id)
    {

        $channel = $this->space->channels->find($channel_id);
        if (!$channel) {
            return response()->json([
                'status' => 0,
                'msg' => 'Channel not found'
            ]);
        }
        $users = $channel->users()->get(['users.id', 'users.name', 'users.surname', 'users.avatar']);

        $founder = $users->where("pivot.rights", 'founder')->first();
        $is_founder = $founder ? ($founder->id == $this->user->id) : false;

        return response()->json([
            'status' => 1,
            'users' => $users,
            'is_founder' => $is_founder,
            'me_id' => $this->user->id,
        ]);
    }

    public function channelUserMakeAdmin($subdomain, $channel_id, Request $request)
    {

        $channel = $this->space->channels->find($channel_id);
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

        $channel = $this->space->channels->find($channel_id);
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

    public function channelLeave($subdomain, $channel_id)
    {

        $channel = $this->space->channels->find($channel_id);
        if (!$channel) {
            return response()->json([
                'status' => 0,
                'msg' => 'Channel not found'
            ]);
        }
        if ($channel->users->find($this->user->id)->pivot->rights === "founder") {
            return response()->json([
                'status' => 0,
                'msg' => 'Make someone admin first'
            ]);
        }

        $channel->users()->detach($this->user->id);
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

        $channel = $this->space->channels->find($channel_id);
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

        $channel = $this->space->channels->find($channel_id);
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

    public function channelDelete($subdomain, $channel_id)
    {

        $channel = $this->space->channels->find($channel_id);
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

    public function newMessage($subdomain, Request $request)
    {
        $channel = $this->space->channels->find($request->channel_id);
        if (!$channel) {
            return response()->json([
                'status' => 0,
                'msg' => 'Channel not found'
            ]);
        }
        $message = new Message();
        $message->channel_id = $channel->id;
        $message->value = $request->value;
        $message->type = "text";
        $message->save();

        $attach = [];
        foreach ($channel->users as $user) {
            $attach[$user->id] = ['is_author' => 0, 'read' => 0];
        }

        $attach[$this->user->id] = ['is_author' => 1, "read" => 1];

        $message->users()->attach($attach);

        return response()->json([
            'status' => 1
        ]);
    }
}
