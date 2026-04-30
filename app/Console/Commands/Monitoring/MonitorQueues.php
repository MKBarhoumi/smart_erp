<?php

namespace App\Console\Commands\Monitoring;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

class MonitorQueues extends Command
{
    protected $signature = 'monitoring:check-queues';
    protected $description = 'Monitor queue status and performance';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Monitoring queue status...');

        $queues = $this->getQueueStatus();
        $this->displayQueueStatus($queues);

        $this->checkQueuePerformance($queues);

        return 0;
    }

    protected function getQueueStatus(): array
    {
        return [
            'default' => [
                'size' => Queue::size('default'),
                'failed' => $this->getFailedJobsCount('default'),
            ],
            'stripe' => [
                'size' => Queue::size('stripe'),
                'failed' => $this->getFailedJobsCount('stripe'),
            ],
            'high' => [
                'size' => Queue::size('high'),
                'failed' => $this->getFailedJobsCount('high'),
            ],
        ];
    }

    protected function getFailedJobsCount(string $queue): int
    {
        try {
            return DB::table('failed_jobs')
                ->where('queue', $queue)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function displayQueueStatus(array $queues): void
    {
        $this->table(
            ['Queue', 'Pending Jobs', 'Failed Jobs', 'Status'],
            $this->formatQueueStatus($queues)
        );
    }

    protected function formatQueueStatus(array $queues): array
    {
        $rows = [];

        foreach ($queues as $queue => $status) {
            $statusText = 'OK';
            
            if ($status['size'] > 1000) {
                $statusText = 'WARNING';
            }
            
            if ($status['failed'] > 10) {
                $statusText = 'CRITICAL';
            }

            $rows[] = [
                $queue,
                $status['size'],
                $status['failed'],
                $statusText,
            ];
        }

        return $rows;
    }

    protected function checkQueuePerformance(array $queues): void
    {
        foreach ($queues as $queue => $status) {
            if ($status['size'] > 1000) {
                $this->warn("Queue '{$queue}' has {$status['size']} pending jobs");
            }

            if ($status['failed'] > 10) {
                $this->error("Queue '{$queue}' has {$status['failed']} failed jobs");
            }
        }
    }
}