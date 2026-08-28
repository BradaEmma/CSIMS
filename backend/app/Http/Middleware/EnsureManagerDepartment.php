<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnsureManagerDepartment
{
    /**
     * Allows access to:
     *   - admin (always)
     *   - manager with department_id = null (General Manager — sees everything)
     *   - manager whose department name matches the given $department param
     *
     * Usage: Route::middleware('manager.department:operations')
     */
    public function handle(Request $request, Closure $next, string $department)
    {
        $user = $request->user();

        if (!$user) {
            throw new HttpException(403, 'User does not have the right roles.');
        }

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        if ($user->hasRole('manager')) {
            if (is_null($user->department_id)) {
                return $next($request);
            }

        if ($user->department && $user->department->code === $department) {
                return $next($request);
            }
        }

        throw new HttpException(403, 'User does not have the right roles.');
    }
}