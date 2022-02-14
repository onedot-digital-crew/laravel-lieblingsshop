<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyPixx
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // When testing locally, ignore the verification
        if ($request->ip() == '127.0.0.1') {
            return $next($request);
        }

        if (!hash_equals(hash_hmac('sha256', $request->getContent(), env('PIXX_SECRET')), $request->header('X-Hub-Signature-256'))) {
            return abort(401, 'Unauthorized');
        }

        return $next($request);
    }
}
