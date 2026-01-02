<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    public function index()
    {
        return response()->json(['data' => []]);
    }
    public function update(Request $request)
    {
        return response()->json(['message' => 'À implémenter'], 501);
    }
}
