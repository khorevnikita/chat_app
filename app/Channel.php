<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    public function users()
    {
        return $this->belongsToMany("App\User")->withTimestamps()->withPivot('rights');
    }

    public function space()
    {
        return $this->belongsTo("App\Space");
    }

    public function messages()
    {
        return $this->hasMany("App\Message");
    }
}
