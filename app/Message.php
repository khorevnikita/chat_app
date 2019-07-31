<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public function users()
    {
        return $this->belongsToMany("App\User")->withTimestamps()->withPivot("is_author", "read");
    }

    public function channel(){
        return $this->belongsTo("App\Channel");
    }
}
