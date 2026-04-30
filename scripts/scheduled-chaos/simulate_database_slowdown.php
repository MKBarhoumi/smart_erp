<?php

/**
 * Simulate Database Slowdown
 * 
 * This script simulates database performance issues to test
 * system resilience under degraded database conditions.
 */

require __DIR__.'/../../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🐌 Simulating Database Slowdown...\n";
echo "⚠️  This will artificially slow down database queries\n";
echo "⚠️  Make sure you're in a development environment!\n\n";

readline("Press Enter to continue...");

try {
    // Simulate slow queries by adding sleep
    echo "🔧 Enabling database slowdown...\n";
    
    // This is a simulation - in production you'd use actual database tools
    // to simulate slowdown (e.g., pg_sleep for PostgreSQL)
    
    $slowQueries = [
        "SELECT SLEEP(2) as delay",
        "SELECT SLEEP(1) as delay",
        "SELECT SLEEP(3) as delay",
    ];
    
    foreach ($slowQueries as $query) {
        $start = microtime(true);
        DB::statement($query);
        $duration = (microtime(true) - $start) * 1000;
        
        echo "⏱️  Query executed in {$duration}ms\n";
    }
    
    echo "✅ Database slowdown simulation complete\n";
    echo "📊 Check your application logs for error handling\n";
    echo "📊 Check monitoring dashboards for performance impact\n";
    
    Log::warning('Database slowdown simulation completed', [
        'timestamp' => now()->toIso8601String(),
        'duration' => '6 seconds total',
    ]);
    
} catch (\Throwable $e) {
    echo "❌ Error during database slowdown simulation: " . $e->getMessage() . "\n";
    Log::error('Database slowdown simulation failed', [
        'error' => $e->getMessage(),
    ]);
}

echo "\n✅ Simulation complete\n";
echo "📊 Check the circuit breaker status in your cache\n";
echo "📊 Check monitoring logs for alerts\n";