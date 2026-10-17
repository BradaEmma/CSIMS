<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDepartment
{
    /**
     * Usage: ->middleware('department:operations') or 'department:operations,sales'
     *
     * Bypass rules match DepartmentScopeService: admin, hr and users with no
     * department (General Manager) are not restricted by department.
     */
    public function handle(Request $request, Closure $next, string ...$codes): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->hasRole('admin') || $user->hasRole('hr') || is_null($user->department_id)) {
            return $next($request);
        }

        if (!in_array($user->department?->code, $codes, true)) {
            return response()->json(['message' => 'Your department does not have access to this module.'], 403);
        }

        return $next($request);
    }
}