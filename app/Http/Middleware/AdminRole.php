<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $admin = $request->user();

        if (!$admin) {
            abort(401, 'Not authenticated.');
        }

        $allowedRoles = explode('|', $role);

        if (!in_array($admin->role, $allowedRoles)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
