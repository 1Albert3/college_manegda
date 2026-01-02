<?php

namespace Modules\Finance\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Services\InvoiceService;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Http\Requests\StoreInvoiceRequest;
use App\Http\Responses\ApiResponse;

class InvoiceController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct(protected InvoiceService $invoiceService) {}

    /**
     * Display a listing of invoices
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::with(['student', 'academicYear', 'feeTypes']);

        // Filtres
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('period')) {
            $query->where('period', $request->period);
        }

        $perPage = $request->get('per_page', 15);
        $invoices = $query->orderBy('issue_date', 'desc')->paginate($perPage);

        return ApiResponse::paginated($invoices);
    }

    /**
     * Generate a new invoice
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        // Authorization handled in Request if implemented, or here:
        $this->authorize('create', Invoice::class);

        $invoice = $this->invoiceService->generateInvoice($request->validated());

        return ApiResponse::success($invoice, 'Facture générée avec succès', 201);
    }

    /**
     * Display the specified invoice
     */
    public function show(int $id): JsonResponse
    {
        $invoice = Invoice::with(['student', 'academicYear', 'feeTypes', 'reminders'])
                         ->findOrFail($id);
        
        $this->authorize('view', $invoice);

        return ApiResponse::success($invoice);
    }

    /**
     * Get unpaid invoices
     */
    public function getUnpaid(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = $request->only([
            'academic_year_id',
            'class_id',
            'status',
            'period',
            'overdue_only',
            'due_soon_days',
            'sort_by',
            'sort_order',
        ]);

        $invoices = $this->invoiceService->getUnpaidInvoices($filters);

        return ApiResponse::success([
            'data' => $invoices,
            'count' => $invoices->count(),
        ]);
    }

    /**
     * Download invoice as PDF
     */
    public function downloadPdf(int $id)
    {
        $invoice = Invoice::findOrFail($id);
        $this->authorize('view', $invoice);
        
        return $this->invoiceService->generateInvoicePDF($invoice);
    }

    /**
     * Issue an invoice (change status from draft to issued)
     */
    public function issue(int $id, Request $request): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $this->authorize('update', $invoice);

        $createReminders = $request->boolean('create_reminders', true);
        
        $invoice = $this->invoiceService->issueInvoice($invoice, $createReminders);

        return ApiResponse::success($invoice, 'Facture émise avec succès');
    }

    /**
     * Cancel an invoice
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $this->authorize('delete', $invoice); // Cancel is effectively a soft delete or managed action

        $reason = $request->input('reason');
        
        $invoice = $this->invoiceService->cancelInvoice($invoice, $reason);

        return ApiResponse::success($invoice, 'Facture annulée avec succès');
    }

    /**
     * Calculate total due for a student
     */
    public function calculateDue(Request $request): JsonResponse
    {
        // Check access to student's financials
        $student = \Modules\Student\Entities\Student::findOrFail($request->student_id);
        if (!$request->user()->can('view-finance')) {
             if ($request->user()->hasRole('parent') && !$student->parents->contains($request->user()->id)) {
                 abort(403);
             }
             if ($request->user()->hasRole('student') && $student->user_id !== $request->user()->id) {
                 abort(403);
             }
        }
        
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'period' => 'nullable|in:annuel,trimestriel_1,trimestriel_2,trimestriel_3,mensuel',
        ]);

        $calculation = $this->invoiceService->calculateTotalDue(
            $request->student_id,
            $request->academic_year_id,
            $request->period
        );

        return ApiResponse::success($calculation);
    }

    /**
     * Export invoices for a specific class
     */
    public function exportByClass(int $classId, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $academicYearId = $request->get('academic_year_id');
        $period = $request->get('period');

        $invoices = Invoice::where('academic_year_id', $academicYearId)
                          ->where('period', $period)
                          ->whereHas('student.currentEnrollment', function($q) use ($classId) {
                              $q->where('class_id', $classId);
                          })
                          ->with(['student', 'feeTypes'])
                          ->get();

        return ApiResponse::success([
            'data' => $invoices,
            'count' => $invoices->count(),
        ], 'Export en cours de développement');
    }

    /**
     * Get invoice statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = $request->only(['academic_year_id']);
        $statistics = $this->invoiceService->getInvoiceStatistics($filters);

        return ApiResponse::success($statistics);
    }
}
