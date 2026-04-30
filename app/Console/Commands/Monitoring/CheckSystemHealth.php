<?php

namespace App\Console\Commands\Monitoring;

use App\Services\Monitoring\HealthCheckService;
use App\Services\Monitoring\AlertService;
use Illuminate\Console\Command;

class CheckSystemHealth extends Command
{
    protected $signature = 'monitoring:check-health';
    protected $description = 'Check system health and send alerts if needed';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Checking system health...');

        $healthCheck = new HealthCheckService();
        $result = $healthCheck->check();

        $this->displayResults($result);

        if ($result['status'] !== 'healthy') {
            $this->sendAlert($result);
            return 1;
        }

        $this->info('System is healthy!');
        return 0;
    }

    protected function displayResults(array $result): void
    {
        $this->table(
            ['Component', 'Status', 'Details'],
            $this->formatResults($result)
        );
    }

    protected function formatResults(array $result): array
    {
        $rows = [];

        foreach ($result['checks'] as $component => $check) {
            if (is_array($check)) {
                $status = $check['status'] ?? 'unknown';
                $details = $this->formatDetails($check);
                $rows[] = [$component, strtoupper($status), $details];
            }
        }

        return $rows;
    }

    protected function formatDetails(array $check): string
    {
        $details = [];

        foreach ($check as $key => $value) {
            if ($key !== 'status') {
                $details[] = "{$key}: {$value}";
            }
        }

        return implode(', ', $details);
    }

    protected function sendAlert(array $result): void
    {
        $alertService = new AlertService();
        
        $alertService->critical('System health check failed', [
            'status' => $result['status'],
            'timestamp' => $result['timestamp'],
            'checks' => $result['checks'],
        ]);

        $this->error('Alert sent: System health check failed');
    }
}