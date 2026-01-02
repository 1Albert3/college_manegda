<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index()
    {
        return response()->json(['data' => []]);
    }
    public function export()
    {
        return response()->json(['message' => 'À implémenter'], 501);
    }
}
