<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('guest.home')
            ->with('error', 'Please log in first.');
        }

        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('guest.home')->with('error', 'Access denied! Only admins can access this page.');
        }
        
        return $next($request);
    }
}
