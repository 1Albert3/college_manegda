<?php

namespace Modules\Finance\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\FeeType;
use App\Http\Responses\ApiResponse;
use Modules\Finance\Http\Requests\StoreFeeTypeRequest;
use Modules\Finance\Http\Requests\UpdateFeeTypeRequest;

class FeeTypeController extends Controller
{
    /**
     * Display a listing of fee types
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $feeTypes = \Spatie\QueryBuilder\QueryBuilder::for(FeeType::class)
            ->allowedFilters([
                'frequency',
                'cycle_id', // Automatic exact match
                'level_id', 
                \Spatie\QueryBuilder\AllowedFilter::exact('is_active'),
                \Spatie\QueryBuilder\AllowedFilter::exact('is_mandatory'),
                \Spatie\QueryBuilder\AllowedFilter::callback('search', function ($query, $value) {
                    $query->where(function($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('description', 'like', "%{$value}%");
                    });
                }),
            ])
            ->defaultSort('name')
            ->allowedSorts(['name', 'amount', 'created_at'])
            ->with(['cycle', 'level'])
            ->paginate($request->get('per_page', 15));

        return ApiResponse::paginated($feeTypes);
    }

    /**
     * Store a newly created fee type
     *
     * @param StoreFeeTypeRequest $request
     * @return JsonResponse
     */
    public function store(StoreFeeTypeRequest $request): JsonResponse
    {
        try {
            $feeType = FeeType::create($request->validated());

            return response()->json([
                'message' => 'Type de frais créé avec succès',
                'data' => $feeType->load(['cycle', 'level']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du type de frais',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified fee type
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $feeType = FeeType::with(['cycle', 'level', 'payments', 'invoices'])
                             ->findOrFail($id);

            return response()->json([
                'data' => $feeType,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Type de frais non trouvé',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified fee type
     *
     * @param UpdateFeeTypeRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateFeeTypeRequest $request, int $id): JsonResponse
    {
        try {
            $feeType = FeeType::findOrFail($id);
            $feeType->update($request->validated());

            return response()->json([
                'message' => 'Type de frais mis à jour avec succès',
                'data' => $feeType->fresh(['cycle', 'level']),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du type de frais',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified fee type
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $feeType = FeeType::findOrFail($id);
            
            // Vérifier s'il y a des paiements ou factures liés
            if ($feeType->payments()->exists() || $feeType->invoices()->exists()) {
                return response()->json([
                    'message' => 'Impossible de supprimer un type de frais déjà utilisé',
                    'suggestion' => 'Vous pouvez le désactiver à la place',
                ], 409);
            }

            $feeType->delete();

            return response()->json([
                'message' => 'Type de frais supprimé avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du type de frais',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Activate a fee type
     *
     * @param int $id
     * @return JsonResponse
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $feeType = FeeType::findOrFail($id);
            $feeType->activate();

            return response()->json([
                'message' => 'Type de frais activé avec succès',
                'data' => $feeType->fresh(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'activation du type de frais',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Deactivate a fee type
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $feeType = FeeType::findOrFail($id);
            $feeType->deactivate();

            return response()->json([
                'message' => 'Type de frais désactivé avec succès',
                'data' => $feeType->fresh(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la désactivation du type de frais',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get fee types applicable to a student
     *
     * @param int $studentId
     * @return JsonResponse
     */
    public function getApplicableToStudent(int $studentId): JsonResponse
    {
        try {
            $student = \Modules\Student\Entities\Student::findOrFail($studentId);
            
            $feeTypes = FeeType::active()->get()->filter(function($feeType) use ($student) {
                return $feeType->isApplicableToStudent($student);
            });

            return response()->json([
                'data' => $feeTypes->values(),
                'count' => $feeTypes->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des frais applicables',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
