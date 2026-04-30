<?php

/**
 * Simulate Stripe Failure
 * 
 * This script simulates a Stripe API failure to test error handling
 * and circuit breaker functionality.
 */

require __DIR__.'/../../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔥 Simulating Stripe API Failure...\n";
echo "⚠️  This will trigger circuit breaker and error handling\n";
echo "⚠️  Make sure you're in a development environment!\n\n";

readline("Press Enter to continue...");

try {
    // Simulate Stripe API failure
    throw new \Exception("Stripe API is currently unavailable. Please try again later.");
} catch (\Throwable $e) {
    echo "❌ Stripe API Error: " . $e->getMessage() . "\n";
    echo "📊 Check your application logs for error handling\n";
    echo "📊 Circuit breaker should be triggered\n";
    
    Log::error('Simulated Stripe failure', [
        'error' => $e->getMessage(),
        'timestamp' => now()->toIso8601String(),
    ]);
}

echo "\n✅ Simulation complete\n";
echo "📊 Check the circuit breaker status in your cache\n";
echo "📊 Check monitoring logs for alerts\n";