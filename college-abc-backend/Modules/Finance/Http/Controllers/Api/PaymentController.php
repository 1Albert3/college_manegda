<?php

namespace Modules\Finance\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Entities\Payment;
use Modules\Finance\Http\Requests\StorePaymentRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Display a listing of payments
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::with(['student', 'feeType', 'academicYear', 'validator']);

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

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $payments = $query->orderBy('payment_date', 'desc')->paginate($perPage);

        return ApiResponse::paginated($payments);
    }

    /**
     * Store a newly created payment
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        // Authorization handled in Request
        // $this->authorize('create', Payment::class); 

        $payment = $this->paymentService->recordPayment($request->validated());

        return ApiResponse::success($payment, 'Paiement enregistré avec succès', 201);
    }

    /**
     * Display the specified payment
     */
    public function show(int $id): JsonResponse
    {
        $payment = Payment::with(['student', 'feeType', 'academicYear', 'validator'])
                         ->findOrFail($id);
        
        $this->authorize('view', $payment);

        return ApiResponse::success($payment);
    }

    /**
     * Get payment history for a specific student
     */
    public function getStudentPayments(int $studentId, Request $request): JsonResponse
    {
        // We need to check if user can view THIS student's payments. 
        // Logic similar to view(), but we don't have a Payment instance yet.
        // We can check StudentPolicy for 'view' on the Student model, 
        // OR rely on general 'view-finance' or if parent matches.
        // Let's rely on StudentPolicy:
        $student = \Modules\Student\Entities\Student::findOrFail($studentId);
        // Using StudentPolicy to check if we can view this student. 
        // Ideally we should have a specific 'viewPayments' on PaymentPolicy or StudentPolicy.
        // For now, let's use PaymentPolicy 'viewAny' for admin, and manual check for parent/student? 
        // Actually best is:
        if (!$request->user()->can('view-finance')) {
             // Check ownership
             if ($request->user()->hasRole('parent') && !$student->parents->contains($request->user()->id)) {
                 abort(403);
             }
             if ($request->user()->hasRole('student') && $student->user_id !== $request->user()->id) {
                 abort(403);
             }
        }

        $filters = $request->only([
            'academic_year_id',
            'status',
            'payment_method',
            'start_date',
            'end_date',
            'fee_type_id',
            'sort_by',
            'sort_order',
        ]);

        $payments = $this->paymentService->getStudentPaymentHistory($studentId, $filters);

        return ApiResponse::success([
            'data' => $payments,
            'count' => $payments->count(),
        ]);
    }

    /**
     * Download payment receipt as PDF
     */
    public function downloadReceipt(int $id)
    {
        $payment = Payment::findOrFail($id);
        $this->authorize('view', $payment);

        return $this->paymentService->generateReceipt($payment);
    }

    /**
     * Validate a pending payment
     */
    public function validatePayment(int $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        $this->authorize('validate', $payment);

        $payment = $this->paymentService->validatePayment($payment);

        return ApiResponse::success($payment, 'Paiement validé avec succès');
    }

    /**
     * Cancel a payment
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        $this->authorize('cancel', $payment);
        
        $reason = $request->input('reason');
        $payment = $this->paymentService->cancelPayment($payment, $reason);

        return ApiResponse::success($payment, 'Paiement annulé avec succès');
    }

    /**
     * Get student balance
     */
    public function getStudentBalance(int $studentId, Request $request): JsonResponse
    {
        // Security check similar to getStudentPayments
        $student = \Modules\Student\Entities\Student::findOrFail($studentId);
        if (!$request->user()->can('view-finance')) {
             if ($request->user()->hasRole('parent') && !$student->parents->contains($request->user()->id)) {
                 abort(403);
             }
             if ($request->user()->hasRole('student') && $student->user_id !== $request->user()->id) {
                 abort(403);
             }
        }

        $academicYearId = $request->get('academic_year_id');
        $balance = $this->paymentService->calculateBalance($studentId, $academicYearId);

        return ApiResponse::success($balance);
    }

    /**
     * Get payment statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $request->only(['academic_year_id', 'start_date', 'end_date']);
        $statistics = $this->paymentService->getPaymentStatistics($filters);

        return ApiResponse::success($statistics);
    }
}
