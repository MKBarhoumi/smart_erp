<?php

namespace App\Jobs\Monitoring;

use App\Services\Monitoring\LoggingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;

class MonitorStripeQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('monitoring');
    }

    public function handle(LoggingService $loggingService): void
    {
        $queueSize = Queue::size('stripe');
        $failedJobs = $this->getFailedStripeJobs();

        $loggingService->logQueueJob([
            'job' => 'MonitorStripeQueue',
            'queue' => 'stripe',
            'status' => 'completed',
            'queue_size' => $queueSize,
            'failed_jobs' => $failedJobs,
        ]);

        if ($queueSize > 100) {
            $loggingService->logWarning('Stripe queue size is high', [
                'queue_size' => $queueSize,
                'threshold' => 100,
            ]);
        }

        if ($failedJobs > 5) {
            $loggingService->logError('Stripe queue has many failed jobs', [
                'failed_jobs' => $failedJobs,
                'threshold' => 5,
            ]);
        }
    }

    protected function getFailedStripeJobs(): int
    {
        try {
            return \Illuminate\Support\Facades\DB::table('failed_jobs')
                ->where('queue', 'stripe')
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}