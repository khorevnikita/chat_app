<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Space extends Model
{
    public function users()
    {
        return $this->belongsToMany("App\User")->withTimestamps()->withPivot('rights');
    }

    public function channels(){
        return $this->hasMany("App\Channel");
    }
}
