<?php

namespace Modules\Communication\Channels;

use Modules\Communication\Entities\CommunicationLog;

interface CommunicationChannelInterface
{
    /**
     * Send communication via this channel
     *
     * @param CommunicationLog $log
     * @return bool
     */
    public function send(CommunicationLog $log): bool;

    /**
     * Get the channel name
     *
     * @return string
     */
    public function getChannelName(): string;

    /**
     * Validate recipient address for this channel
     *
     * @param string $recipient
     * @return bool
     */
    public function validateRecipient(string $recipient): bool;

    /**
     * Get channel capabilities
     *
     * @return array
     */
    public function getCapabilities(): array;
}
