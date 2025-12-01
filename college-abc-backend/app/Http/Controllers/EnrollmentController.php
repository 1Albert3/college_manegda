<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function store(Request $request)
    {
        // Log the request data
        \Log::info('Enrollment Request:', $request->all());

        // Return success response
        return response()->json(['success' => true, 'message' => 'Demande enregistrée avec succès']);
    }
}
