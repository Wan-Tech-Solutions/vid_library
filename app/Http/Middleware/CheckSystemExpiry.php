<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSystemExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expiry = Carbon::createFromFormat('Y-m-d H:i:s', env('SYSTEM_EXPIRY_DATE', '2025-09-01T00:00:00'));
        $now = now();

        if ($now->greaterThanOrEqualTo($expiry)) {
            return response()->view('expiry.locked');
        }

        return $next($request);
    }
}
