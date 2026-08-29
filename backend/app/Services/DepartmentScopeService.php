<?php

namespace App\Services;

class DepartmentScopeService
{
    /**
     * Scopes a query to the acting user's department.
     *
     * - admin: no filter, sees everything
     * - user with department_id = null (General Manager): no filter
     * - otherwise: filtered to the user's own department_id
     */
    public static function apply($query, $user, string $column = 'department_id')
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if (is_null($user->department_id)) {
            return $query;
        }

        return $query->where($column, $user->department_id);
    }
}