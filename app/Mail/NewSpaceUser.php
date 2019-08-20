<?php

namespace App\Mail;

use App\Space;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewSpaceUser extends Mailable
{
    use Queueable, SerializesModels;

    public $space;
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Space $space, User $user)
    {
        $this->space = $space;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.spaces.new_user')->subject("Join " . $this->space->name . " team");
    }
}
