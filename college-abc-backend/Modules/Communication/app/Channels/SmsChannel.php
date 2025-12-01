<?php

namespace Modules\Communication\Channels;

use Illuminate\Support\Facades\Log;
use Modules\Communication\Entities\CommunicationLog;
use Exception;

class SmsChannel implements CommunicationChannelInterface
{
    public function send(CommunicationLog $log): bool
    {
        $config = config('communication.channels.sms', []);

        if (!($config['enabled'] ?? false)) {
            throw new Exception('SMS channel is disabled');
        }

        // For now, just log the SMS (implement actual SMS sending later)
        // This would integrate with Twilio, AfricasTalking, etc.

        Log::info('SMS would be sent', [
            'to' => $log->recipient_address,
            'message' => $log->content,
        ]);

        $log->markAsSent();
        return true;
    }

    public function getChannelName(): string
    {
        return 'sms';
    }

    public function validateRecipient(string $recipient): bool
    {
        // Basic phone number validation
        return preg_match('/^\+?[0-9\s\-\(\)]+$/', $recipient);
    }

    public function getCapabilities(): array
    {
        return [
            'html' => false,
            'attachments' => false,
            'tracking' => true,
            'templates' => true,
        ];
    }
}
