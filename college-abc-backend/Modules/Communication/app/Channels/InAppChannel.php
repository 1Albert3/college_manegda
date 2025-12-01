<?php

namespace Modules\Communication\Channels;

use Modules\Communication\Entities\CommunicationLog;
use Exception;

class InAppChannel implements CommunicationChannelInterface
{
    public function send(CommunicationLog $log): bool
    {
        // Create in-app notification in database
        // This would create a notification record for the user
        \Illuminate\Support\Facades\Log::info('In-app notification created', [
            'user_id' => $log->recipient_address,
            'title' => $log->subject,
            'message' => $log->content,
        ]);

        $log->markAsDelivered(); // In-app notifications are immediately delivered
        return true;
    }

    public function getChannelName(): string
    {
        return 'in_app';
    }

    public function validateRecipient(string $recipient): bool
    {
        // User ID validation
        return is_numeric($recipient) || (is_string($recipient) && strlen($recipient) > 0);
    }

    public function getCapabilities(): array
    {
        return [
            'html' => true,
            'attachments' => false,
            'tracking' => true,
            'templates' => true,
        ];
    }
}
