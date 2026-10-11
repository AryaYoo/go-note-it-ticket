<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check()) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();

        // Superadmin dapat mengakses semua route yang membutuhkan role apapun
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Cek apakah role user sesuai dengan salah satu role yang dibutuhkan
        if (! in_array($user->role, $roles)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
