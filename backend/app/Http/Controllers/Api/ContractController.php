<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    /*
    |---------------------------------------
    | GET ALL CONTRACTS
    | Admin + Supervisor
    |---------------------------------------
    */
    public function index(Request $request)
    {
        $query = Contract::with(['client', 'sites'])->latest();

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        return response()->json($query->get());
    }

    /*
    |---------------------------------------
    | CREATE CONTRACT
    | Admin ONLY
    |---------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'reference_number' => 'required|string|unique:contracts,reference_number',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'monthly_fee' => 'nullable|numeric|min:0',
            'terms' => 'nullable|string',
        ]);

        $contract = Contract::create([
            ...$validated,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Contract created successfully',
            'data' => $contract->load('client')
        ]);
    }

    /*
    |---------------------------------------
    | SHOW SINGLE CONTRACT
    | Admin + Supervisor
    |---------------------------------------
    */
    public function show($id)
    {
        $contract = Contract::with(['client', 'sites'])->findOrFail($id);
        return response()->json($contract);
    }

    /*
    |---------------------------------------
    | UPDATE CONTRACT
    | Admin ONLY
    |---------------------------------------
    */
    public function update(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);

        $validated = $request->validate([
            'reference_number' => 'sometimes|string|unique:contracts,reference_number,' . $id,
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
            'monthly_fee' => 'sometimes|nullable|numeric|min:0',
            'terms' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,expired,terminated',
        ]);

        $contract->update($validated);

        return response()->json([
            'message' => 'Contract updated successfully',
            'data' => $contract
        ]);
    }

    /*
    |---------------------------------------
    | DELETE CONTRACT
    | Admin ONLY
    |---------------------------------------
    */
    public function destroy($id)
    {
        $contract = Contract::findOrFail($id);
        $contract->delete();
        return response()->json([
            'message' => 'Contract deleted successfully'
        ]);
    }
}