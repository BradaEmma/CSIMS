<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    /*
    |---------------------------------------
    | GET ALL CLIENTS
    | Admin + Supervisor
    |---------------------------------------
    */
    public function index()
    {
        return response()->json(
            Client::with('contracts')->latest()->get()
        );
    }

    /*
    |---------------------------------------
    | CREATE CLIENT
    | Admin ONLY
    |---------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'contact_person' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
        ]);

        $client = Client::create([
            ...$validated,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Client created successfully',
            'data' => $client
        ]);
    }

    /*
    |---------------------------------------
    | SHOW SINGLE CLIENT
    | Admin + Supervisor
    |---------------------------------------
    */
    public function show($id)
    {
        $client = Client::with(['contracts.sites'])->findOrFail($id);
        return response()->json($client);
    }

    /*
    |---------------------------------------
    | UPDATE CLIENT
    | Admin ONLY
    |---------------------------------------
    */
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'contact_person' => 'sometimes|nullable|string',
            'phone' => 'sometimes|nullable|string',
            'email' => 'sometimes|nullable|email',
            'address' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $client->update($validated);

        return response()->json([
            'message' => 'Client updated successfully',
            'data' => $client
        ]);
    }

    /*
    |---------------------------------------
    | DELETE CLIENT
    | Admin ONLY
    |---------------------------------------
    */
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();
        return response()->json([
            'message' => 'Client deleted successfully'
        ]);
    }
}