<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request){

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->surname = $request->surname;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->password = Hash::make($request->password);
        $user->api_token = Str::random(60);
        $user->save();

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
}
