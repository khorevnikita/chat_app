<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegisterUser;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request){

        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        ]);

        $user = new User();
        $user->username = explode("@", $request->email)[0];
        $user->email = $request->email;
        $user->password = bcrypt(str_random(8));
        $user->save();

        Mail::to($user)->send(new RegisterUser($user));

        return response()->json([
            'status' => 1,
            'data' => [
                'token' => $user->api_token
            ]
        ]);
    }

    public function login(Request $request)
    {

        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users'],
            'password' => ['required', 'string', 'min:6'],
        ]);
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            // Authentication passed...
            $user = User::where("email", $request->email)->first();
            $token = Str::random(60);
            $user->api_token = $token;
            $user->save();

            Auth::login($user, true);

            return response()->json([
                'status' => 1,
                'data' => [
                    'token' => $token
                ]
            ]);
        }
        return response()->json([
            'errors' => [
                'password' => ['Wrong email or password']
            ]
        ], 422);
    }

    public function findUser(Request $request)
    {
        $request->validate([
            'email' => "required",
            "hash" => "required"
        ]);

        $hash = md5($request->email . "point break");

        if ($hash !== $request->hash) {
            return response()->json([
                'status' => 1,
                'msg' => "Something went wrong"
            ]);
        }

        $user = User::where("email", $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => 1,
                'msg' => "User has not been found"
            ]);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'status' => 1,
                'msg' => "E-mail has already been verified"
            ]);
        }

        $user->api_token = Str::random(60);
        $user->save();

        return response()->json([
            'status' => 1,
            'user' => $user,
            'token' => $user->api_token,
        ]);
    }

    public function verifyUser(Request $request)
    {
        $user = User::where("api_token", request()->header('Authorization'))->first();

        Validator::make($request->all(), [
            'username' => [
                'required',
                "string",
                Rule::unique('users')->ignore($user),
            ],
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],

        ])->validate();

        $user->name = $request->name;
        $user->surname = $request->surname;
        $user->username = $request->username;
        $user->password = bcrypt($request->password);
        $user->email_verified_at = Carbon::now();
        $user->save();

        Auth::login($user, true);

        return response()->json([
            'status' => 1
        ]);
    }

}
