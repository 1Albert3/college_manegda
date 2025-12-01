<?php

namespace Modules\Communication\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Modules\Communication\Entities\CommunicationLog;
use Modules\Communication\Entities\CommunicationTemplate;
use Modules\Communication\Channels\EmailChannel;
use Modules\Communication\Channels\SmsChannel;
use Modules\Communication\Channels\PushChannel;
use Modules\Communication\Channels\InAppChannel;
use Modules\Communication\Jobs\SendEmail;
use Modules\Communication\Jobs\SendSms;
use Modules\Communication\Jobs\SendPushNotification;
use Exception;
use Illuminate\Support\Facades\Auth;

class CommunicationService
{
    protected array $channels = [];

    public function __construct()
    {
        $this->initializeChannels();
    }

    /**
     * Initialize communication channels
     */
    protected function initializeChannels(): void
    {
        $this->channels = [
            'email' => new EmailChannel(),
            'sms' => new SmsChannel(),
            'push' => new PushChannel(),
            'in_app' => new InAppChannel(),
        ];
    }

    /**
     * Send communication via specific channel
     */
    public function send(string $channel, string $recipient, string $templateName, array $variables = [], array $options = []): CommunicationLog
    {
        try {
            // Validate channel
            if (!isset($this->channels[$channel])) {
                throw new Exception("Channel '{$channel}' not supported");
            }

            // Get template
            $template = CommunicationTemplate::where('slug', $templateName)
                ->where('channel', $channel)
                ->where('is_active', true)
                ->first();

            if (!$template) {
                throw new Exception("Template '{$templateName}' not found for channel '{$channel}'");
            }

            // Validate required variables
            $missingVars = $template->validateVariables($variables);
            if (!empty($missingVars)) {
                throw new Exception("Missing required variables: " . implode(', ', $missingVars));
            }

            // Render content
            $content = $template->render($variables);
            $subject = $template->renderSubject($variables);

            // Create log entry
            $log = CommunicationLog::create([
                'channel' => $channel,
                'provider' => $this->getProviderForChannel($channel),
                'recipient_address' => $recipient,
                'template_name' => $templateName,
                'subject' => $subject,
                'content' => $content,
                'variables' => $variables,
                'status' => CommunicationLog::STATUS_PENDING,
                'priority' => $options['priority'] ?? $template->priority,
                'max_attempts' => $options['max_attempts'] ?? config('communication.queue.retry_attempts', 3),
                'batch_id' => $options['batch_id'] ?? null,
                'user_id' => $options['user_id'] ?? Auth::id(),
                'metadata' => $options['metadata'] ?? null,
            ]);

            // Check if queue is enabled
            if (config('communication.queue.enabled', true)) {
                $this->dispatchToQueue($log, $options);
            } else {
                $this->sendImmediately($log);
            }

            return $log;

        } catch (Exception $e) {
            Log::error('Failed to send communication', [
                'channel' => $channel,
                'recipient' => $recipient,
                'template' => $templateName,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Send communication to a user via multiple channels
     */
    public function sendToUser($user, string $templateName, array $variables = [], array $options = []): array
    {
        $logs = [];
        $channels = $options['channels'] ?? ['email'];

        foreach ($channels as $channel) {
            try {
                $recipient = $this->getRecipientAddress($user, $channel);
                if ($recipient) {
                    $log = $this->send($channel, $recipient, $templateName, $variables, $options);
                    $logs[] = $log;
                }
            } catch (Exception $e) {
                Log::warning("Failed to send {$channel} to user {$user->id}: {$e->getMessage()}");
            }
        }

        return $logs;
    }

    /**
     * Send bulk communication to multiple recipients
     */
    public function sendBulk(string $templateName, array $recipients, array $variables = [], array $options = []): array
    {
        $batchId = $options['batch_id'] ?? uniqid('bulk_', true);
        $logs = [];

        foreach ($recipients as $recipient) {
            try {
                $recipientVars = array_merge($variables, $this->extractRecipientVariables($recipient));

                $options['batch_id'] = $batchId;
                $options['recipient_type'] = get_class($recipient);
                $options['recipient_id'] = $recipient->id;

                $channel = $options['channel'] ?? 'email';
                $recipientAddress = $this->getRecipientAddress($recipient, $channel);

                if ($recipientAddress) {
                    $log = $this->send($channel, $recipientAddress, $templateName, $recipientVars, $options);
                    $logs[] = $log;
                }
            } catch (Exception $e) {
                Log::warning("Failed to send to recipient {$recipient->id}: {$e->getMessage()}");
            }
        }

        return $logs;
    }

    /**
     * Send communication immediately (without queue)
     */
    public function sendImmediately(CommunicationLog $log): bool
    {
        try {
            $channel = $this->channels[$log->channel];
            $result = $channel->send($log);

            if ($result) {
                $log->markAsSent();
                return true;
            } else {
                $log->markAsFailed('Channel returned false');
                return false;
            }
        } catch (Exception $e) {
            $log->markAsFailed($e->getMessage());
            return false;
        }
    }

    /**
     * Dispatch communication to queue
     */
    protected function dispatchToQueue(CommunicationLog $log, array $options = []): void
    {
        $queue = $options['queue'] ?? config('communication.channels.' . $log->channel . '.queue', 'default');
        $delay = $options['delay'] ?? null;

        switch ($log->channel) {
            case 'email':
                SendEmail::dispatch($log)->onQueue($queue)->delay($delay);
                break;
            case 'sms':
                SendSms::dispatch($log)->onQueue($queue)->delay($delay);
                break;
            case 'push':
                SendPushNotification::dispatch($log)->onQueue($queue)->delay($delay);
                break;
            case 'in_app':
                // In-app notifications are usually sent immediately
                $this->sendImmediately($log);
                break;
        }
    }

    /**
     * Get recipient address based on channel
     */
    protected function getRecipientAddress($recipient, string $channel): ?string
    {
        if (is_string($recipient)) {
            return $recipient;
        }

        switch ($channel) {
            case 'email':
                return $recipient->email ?? null;
            case 'sms':
                return $recipient->phone ?? null;
            case 'push':
                return $recipient->device_token ?? null;
            case 'in_app':
                return $recipient->id ?? null;
            default:
                return null;
        }
    }

    /**
     * Extract variables from recipient object
     */
    protected function extractRecipientVariables($recipient): array
    {
        if (is_string($recipient)) {
            return [];
        }

        return [
            'user_id' => $recipient->id ?? null,
            'user_name' => $recipient->name ?? null,
            'user_email' => $recipient->email ?? null,
            'user_phone' => $recipient->phone ?? null,
        ];
    }

    /**
     * Get provider for channel
     */
    protected function getProviderForChannel(string $channel): ?string
    {
        return config("communication.channels.{$channel}.provider");
    }

    /**
     * Test communication channel
     */
    public function testChannel(string $channel, string $recipient): array
    {
        try {
            $testData = [
                'subject' => 'Test Communication',
                'content' => 'This is a test message from ' . config('app.name'),
                'variables' => ['test' => true],
            ];

            $log = $this->send($channel, $recipient, 'test', $testData);

            return [
                'success' => true,
                'message' => 'Test communication sent successfully',
                'log_id' => $log->id,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Test communication failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get communication statistics
     */
    public function getStats(array $filters = []): array
    {
        $query = CommunicationLog::query();

        // Apply filters
        if (isset($filters['channel'])) {
            $query->byChannel($filters['channel']);
        }

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $total = $query->count();
        $sent = (clone $query)->whereIn('status', ['sent', 'delivered'])->count();
        $failed = (clone $query)->where('status', 'failed')->count();
        $pending = (clone $query)->where('status', 'pending')->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'pending' => $pending,
            'delivery_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Retry failed communications
     */
    public function retryFailed(): int
    {
        $failedLogs = CommunicationLog::readyForRetry()->get();
        $retried = 0;

        foreach ($failedLogs as $log) {
            try {
                if (config('communication.queue.enabled', true)) {
                    $this->dispatchToQueue($log);
                } else {
                    $this->sendImmediately($log);
                }
                $retried++;
            } catch (Exception $e) {
                Log::error("Failed to retry communication {$log->id}: {$e->getMessage()}");
            }
        }

        return $retried;
    }
}
