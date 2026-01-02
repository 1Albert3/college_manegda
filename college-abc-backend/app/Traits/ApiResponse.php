<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Success response
     */
    protected function successResponse($data = null, string $message = 'Success', int $code = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Error response
     */
    protected function errorResponse(string $message = 'Error', int $code = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Paginated response
     */
    protected function paginatedResponse($paginator, string $message = 'Success')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]
        ]);
    }

    /**
     * Not found response
     */
    protected function notFoundResponse(string $message = 'Resource not found')
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse($errors, string $message = 'Validation failed')
    {
        return $this->errorResponse($message, 422, $errors);
    }
}