<?php

namespace Modules\Communication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Communication\Entities\CommunicationLog;
use Modules\Communication\Services\CommunicationService;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected CommunicationLog $log;

    /**
     * Create a new job instance.
     */
    public function __construct(CommunicationLog $log)
    {
        $this->log = $log;
    }

    /**
     * Execute the job.
     */
    public function handle(CommunicationService $communicationService): void
    {
        $communicationService->sendImmediately($this->log);
    }
}
