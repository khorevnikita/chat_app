<?php

namespace App\Http\Middleware;

use App\User;
use Closure;

class AuthUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->header("Authorization")) {
            return response()->json([
                'status'=>9,
                'msg'=>"Token lost"
            ]);
        }
        $user = User::where("api_token", request()->header('Authorization'))->first();
        if (!$user) {
            return response()->json([
                'status' => 9,
                'msg' => "Token lost"
            ]);
        }
        app()->instance(User::class, $user);
        return $next($request);
    }
}
