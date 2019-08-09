<?php

namespace App\Http\Middleware;

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
        if (!$request->header("authorization")) {
            return response()->json([
                'status'=>9,
                'msg'=>"Token lost"
            ]);
        }
        return $next($request);
    }
}
