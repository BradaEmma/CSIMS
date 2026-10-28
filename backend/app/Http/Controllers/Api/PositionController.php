<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Position;

class PositionController extends Controller
{
    /*
    |---------------------------------------
    | GET APPROVED POSITIONS
    | Each position carries its department.
    | Read-only: the list is maintained by admin (seeder).
    |---------------------------------------
    */
    public function index()
    {
        return response()->json(
            Position::with('department:id,name')->orderBy('name')->get()
        );
    }
}