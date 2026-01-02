<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(['data' => []]);
    }
    public function store(Request $request)
    {
        return response()->json(['message' => 'À implémenter'], 501);
    }
    public function show($id)
    {
        return response()->json(['message' => 'À implémenter'], 501);
    }
    public function update(Request $request, $id)
    {
        return response()->json(['message' => 'À implémenter'], 501);
    }
    public function destroy($id)
    {
        return response()->json(['message' => 'À implémenter'], 501);
    }
    public function permissions()
    {
        return response()->json(['data' => []]);
    }
}
