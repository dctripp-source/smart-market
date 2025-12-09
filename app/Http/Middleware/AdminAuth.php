<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')
                ->withErrors(['message' => 'Morate biti prijavljeni da pristupite admin panelu.']);
        }

        return $next($request);
    }
}