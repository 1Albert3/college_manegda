<?php

namespace Modules\Communication\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Communication\Services\CommunicationService;
use Modules\Communication\Entities\CommunicationLog;
use Modules\Communication\Entities\CommunicationTemplate;
use App\Http\Responses\ApiResponse;

/**
 * @group Communication Management
 * Gestion des communications et notifications
 */
class CommunicationController extends Controller
{
    public function __construct(
        private CommunicationService $communicationService
    ) {}

    /**
     * Send a communication
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'channel' => 'required|string|in:email,sms,push,in_app',
            'recipient' => 'required|string',
            'template' => 'required|string',
            'variables' => 'nullable|array',
            'options' => 'nullable|array',
        ]);

        try {
            $log = $this->communicationService->send(
                $request->channel,
                $request->recipient,
                $request->template,
                $request->variables ?? [],
                $request->options ?? []
            );

            return ApiResponse::success($log, 'Communication envoyée avec succès');
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de l\'envoi: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Send communication to a user
     */
    public function sendToUser(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'template' => 'required|string',
            'variables' => 'nullable|array',
            'channels' => 'nullable|array',
            'channels.*' => 'string|in:email,sms,push,in_app',
        ]);

        try {
            $user = \Modules\Core\Entities\User::findOrFail($request->user_id);

            $logs = $this->communicationService->sendToUser(
                $user,
                $request->template,
                $request->variables ?? [],
                ['channels' => $request->channels]
            );

            return ApiResponse::success([
                'logs' => $logs,
                'count' => count($logs)
            ], 'Communication envoyée à l\'utilisateur');
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de l\'envoi: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Send bulk communication
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $request->validate([
            'template' => 'required|string',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'exists:users,id',
            'variables' => 'nullable|array',
            'channel' => 'nullable|string|in:email,sms,push,in_app',
        ]);

        try {
            $users = \Modules\Core\Entities\User::whereIn('id', $request->recipients)->get();

            $logs = $this->communicationService->sendBulk(
                $request->template,
                $users,
                $request->variables ?? [],
                ['channel' => $request->channel]
            );

            return ApiResponse::success([
                'logs' => $logs,
                'count' => count($logs)
            ], 'Communications en masse envoyées');
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de l\'envoi en masse: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Test a communication channel
     */
    public function testChannel(Request $request): JsonResponse
    {
        $request->validate([
            'channel' => 'required|string|in:email,sms,push,in_app',
            'recipient' => 'required|string',
        ]);

        $result = $this->communicationService->testChannel(
            $request->channel,
            $request->recipient
        );

        if ($result['success']) {
            return ApiResponse::success($result, 'Test de communication réussi');
        } else {
            return ApiResponse::error($result['message'], 400);
        }
    }

    /**
     * Get communication logs
     */
    public function logs(Request $request): JsonResponse
    {
        $query = CommunicationLog::with(['user'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('channel')) {
            $query->byChannel($request->channel);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('template')) {
            $query->byTemplate($request->template);
        }

        $logs = $query->paginate($request->get('per_page', 15));

        return ApiResponse::paginated($logs);
    }

    /**
     * Get communication statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->communicationService->getStats($request->all());

        return ApiResponse::success($stats, 'Statistiques récupérées');
    }

    /**
     * Get available templates
     */
    public function templates(Request $request): JsonResponse
    {
        $query = CommunicationTemplate::active();

        if ($request->has('channel')) {
            $query->byChannel($request->channel);
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        $templates = $query->get();

        return ApiResponse::success($templates, 'Templates récupérés');
    }

    /**
     * Retry failed communications
     */
    public function retryFailed(Request $request): JsonResponse
    {
        $retried = $this->communicationService->retryFailed();

        return ApiResponse::success([
            'retried_count' => $retried
        ], "{$retried} communications relancées");
    }
}
