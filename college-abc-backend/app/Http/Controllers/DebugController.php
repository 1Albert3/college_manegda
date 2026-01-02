<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DebugController extends Controller
{
    public function testLogin(Request $request)
    {
        Log::info('Debug login request:', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
            'role' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'received_data' => $request->all()
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Debug test successful',
            'received_data' => $request->all()
        ]);
    }
}