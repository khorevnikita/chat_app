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
        'password', 'remember_token', 'api_token'
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

    public static function findByToken($token)
    {
        return User::where("api_token", $token)->first();
    }
}
