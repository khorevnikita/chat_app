<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    /*protected $fillable = [
        'name', 'email', 'password', 'surname', 'username',
    ];*/

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'api_token',
        'email', 'created_at', "email_verified_at", 'updated_at', "deleted_at", "phone"
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function spaces()
    {
        return $this->belongsToMany("App\Space")->withTimestamps()->withPivot('rights');
    }

    public function channels()
    {
        return $this->belongsToMany("App\Channel")->withTimestamps()->withPivot('rights');
    }

    public function messages()
    {
        return $this->belongsToMany("App\Message")->withTimestamps()->withPivot("is_author", "read");
    }

    public function getEmailVerificationUrlAttribute()
    {
        $hash = md5($this->email . "point break");
        return "http://chatclient.local:8080/#/verify-email?email=" . urlencode($this->email) . "&hash=$hash";
    }

    public function findByToken($token)
    {
        return User::where("api_token", $token)->first();
    }

    public function authUser()
    {
        $token = request()->header('Authorization');
        return $this->findByToken($token);
    }

    public function getAvatarAttribute($value)
    {
        return $value?:'https://www.nicepng.com/png/detail/164-1649946_happy-smiling-emoticon-face-vector-smile.png';
    }

    public function newMessagesCount($channel_id = null)
    {
        if (!$channel_id) {
            return $this->messages->where("pivot.read", 0)->count();
        }

        return $this->messages->where("channel_id", $channel_id)->where("pivot.read", 0)->count();
    }
}
