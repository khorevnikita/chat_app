<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Request;

class SpaceController extends Controller
{
    private $token;
    private $user;

    public function __construct(Request $request)
    {
        $this->token = Request::header()['authorization'][0];
        $this->user = User::findByToken($this->token);
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
}
