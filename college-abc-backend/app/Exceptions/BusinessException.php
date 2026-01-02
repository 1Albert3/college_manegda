<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BusinessException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     */
    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 400);
    }
}
