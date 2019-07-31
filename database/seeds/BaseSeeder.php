<?php

use Illuminate\Database\Seeder;

class BaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\User::class, 10)->create();
        factory(App\Space::class, 2)->create();
        $spaces = \App\Space::all();
        \App\User::all()->each(function ($user) use ($spaces) {
            $rights = "member";
            $space_id = rand(1, 2);
            if (!$spaces->where("id", $space_id)->first()->users()->wherePivot("rights", "founder")->first()) {
                $rights = "founder";
            }
            $user->spaces()->attach(
                $spaces->random($space_id)->pluck("id")->toArray(), ['rights' => $rights]
            );
        });
        $spaces->each(function ($space) {
            $channelsNumber = random_int(1, 3);
            for ($i = 0; $i < $channelsNumber; $i++) {
                $space->channels()->save(factory(\App\Channel::class)->make());
            }
        });
        \App\Channel::all()->each(function ($channel) {
            $space = $channel->space;
            $usersInSpace = $space->users;
            $countInChannel = random_int(2, $usersInSpace->count());
            $channel->users()->attach($usersInSpace->random($countInChannel), ['rights' => "member"]);
            $channel->users->map(function ($user) use ($channel) {
                $message = $user->messages()->save(factory(\App\Message::class)->make(['channel_id' => $channel->id]));
                $user->messages()->updateExistingPivot($message->id, ['is_author' => 1, "read" => 1]);
            });
            $channelUsers = $channel->users;
            $channel->messages->each(function ($message) use ($channelUsers) {
                $channel = $message->channel;
                $users = $channelUsers->filter(function ($user) use ($channel,$message) {
                    return !$user->messages->where('channel_id', $channel->id)->where("message_id",$message->id)->count();
                });
                $message->users()->attach($users->pluck("id")->toArray(),['read'=>random_int(0,1)]);
            });
        });


        /*$messages = \App\Message::all()->each(function ($message) {
            if (!$message->users()->wherePivot("is_author", 1)->first()) {
                $message->users()->updateExistingPivot($message->users->first()->id, ['is_author' => 1, "read" => 1]);
            }
        });*/
    }
}
