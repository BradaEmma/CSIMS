<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /*
    |---------------------------------------
    | GET ALL USERS
    | Admin ONLY
    |---------------------------------------
    */
    public function index()
    {
        $users = User::with(['roles', 'department'])->latest()->get();
        return response()->json($users);
    }

    /*
    |---------------------------------------
    | CREATE USER
    | Admin ONLY
    |
    | Creates a new User account and assigns roles via Spatie's
    | syncRoles(). department_id and client_id are optional ΓÇö not
    | every role needs one (e.g. admin, guard). Does not create or
    | link an Employee record ΓÇö use Employee::store + link-user for
    | that, separately.
    |---------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'department_id' => 'nullable|exists:departments,id',
            'client_id' => 'nullable|exists:clients,id',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'department_id' => $validated['department_id'] ?? null,
            'client_id' => $validated['client_id'] ?? null,
        ]);

        $user->syncRoles($validated['roles']);

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user->load(['roles', 'department'])
        ]);
    }

    /*
    |---------------------------------------
    | UPDATE USER
    | Admin ONLY
    |
    | Password is optional here ΓÇö only updated if provided.
    |---------------------------------------
    */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:8',
            'department_id' => 'sometimes|nullable|exists:departments,id',
            'client_id' => 'sometimes|nullable|exists:clients,id',
            'roles' => 'sometimes|array|min:1',
            'roles.*' => 'string|exists:roles,name',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $roles = $validated['roles'] ?? null;
        unset($validated['roles']);

        $user->update($validated);

        if ($roles !== null) {
            $user->syncRoles($roles);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user->load(['roles', 'department'])
        ]);
    }
}