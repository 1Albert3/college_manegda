<?php

namespace Modules\Communication;

use Illuminate\Support\Facades\Facade;

/**
 * Communication Facade
 *
 * Provides a simple interface to the communication system
 *
 * @method static \Modules\Communication\Entities\CommunicationLog send(string $channel, string $recipient, string $template, array $variables = [], array $options = [])
 * @method static array sendToUser($user, string $template, array $variables = [], array $options = [])
 * @method static array sendBulk(string $template, array $recipients, array $variables = [], array $options = [])
 * @method static array testChannel(string $channel, string $recipient)
 * @method static array getStats(array $filters = [])
 * @method static int retryFailed()
 */
class Communication extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'communication';
    }
}
