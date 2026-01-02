<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Modules\Core\Entities\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class DocumentController extends Controller
{
    /**
     * Display a listing of accessible documents.
     */
    public function index(Request $request)
    {
        try {
            $documents = Document::accessibleBy($request->user())
                ->latest()
                ->paginate($request->get('per_page', 20));

            return ApiResponse::success('Documents retrieved successfully', $documents);
        } catch (Exception $e) {
            Log::error('Failed to get documents', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to retrieve documents', 500);
        }
    }

    /**
     * Store a new document (Admin only or manager).
     */
    public function store(Request $request)
    {
        // Simple auth check, ideally use Policy
        if (!$request->user()->can('manage-documents') && !$request->user()->hasRole('super_admin')) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|max:10240', // 10MB max
            'type' => 'required|string',
            'roles_access' => 'nullable|array'
        ]);

        try {
            $path = $request->file('file')->store('documents', 'public');

            $document = Document::create([
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $path,
                'type' => $request->type,
                'roles_access' => $request->roles_access, // e.g. ['teacher', 'student']
                'created_by' => $request->user()->id,
            ]);

            return ApiResponse::success('Document uploaded successfully', $document, 201);
        } catch (Exception $e) {
            Log::error('Failed to upload document', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to upload document', 500);
        }
    }

    /**
     * Download a document.
     */
    public function download(Request $request, int $id)
    {
        try {
            $document = Document::findOrFail($id);

            // Check access
            $user = $request->user();
            $accessible = false;
            if ($user->hasRole('super_admin') || $user->can('manage-documents')) {
                $accessible = true;
            } elseif (
                $document->roles_access === null ||
                in_array('all', $document->roles_access) ||
                (count(array_intersect($document->roles_access, $user->roles->pluck('name')->toArray())) > 0)
            ) {
                $accessible = true;
            }

            if (!$accessible) {
                return ApiResponse::error('You do not have permission to download this document.', 403);
            }

            if (!Storage::disk('public')->exists($document->file_path)) {
                return ApiResponse::error('File not found on server.', 404);
            }

            return Storage::disk('public')->download($document->file_path, $document->title . '.' . pathinfo($document->file_path, PATHINFO_EXTENSION));
        } catch (Exception $e) {
            Log::error('Failed to download document', ['id' => $id, 'error' => $e->getMessage()]);
            return ApiResponse::error('Failed to download document', 500);
        }
    }

    /**
     * Delete a document.
     */
    public function destroy(Request $request, int $id)
    {
        if (!$request->user()->can('manage-documents') && !$request->user()->hasRole('super_admin')) {
            return ApiResponse::error('Unauthorized', 403);
        }

        try {
            $document = Document::findOrFail($id);

            // Delete file
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            return ApiResponse::success('Document deleted successfully');
        } catch (Exception $e) {
            Log::error('Failed to delete document', ['id' => $id, 'error' => $e->getMessage()]);
            return ApiResponse::error('Failed to delete document', 500);
        }
    }
}
