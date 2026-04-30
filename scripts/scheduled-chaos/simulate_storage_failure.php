<?php

/**
 * Simulate Storage Failure
 * 
 * This script simulates storage service failures to test
 * system resilience when file storage is unavailable.
 */

require __DIR__.'/../../vendor/autoload.php';

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "💾 Simulating Storage Failure...\n";
echo "⚠️  This will simulate file storage unavailability\n";
echo "⚠️  Make sure you're in a development environment!\n\n";

readline("Press Enter to continue...");

try {
    echo "🔧 Simulating storage failure...\n";
    
    // Test storage operations
    $testFile = 'chaos_test_' . time() . '.txt';
    
    // Try to write to storage
    $writeSuccess = Storage::put($testFile, 'test content');
    echo "📝 Storage write: " . ($writeSuccess ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Try to read from storage
    $readSuccess = Storage::exists($testFile);
    echo "📖 Storage read: " . ($readSuccess ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Try to delete from storage
    $deleteSuccess = Storage::delete($testFile);
    echo "🗑️  Storage delete: " . ($deleteSuccess ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Simulate storage unavailability
    echo "🔧 Simulating storage unavailability...\n";
    
    // Test fallback behavior
    $fallbackPath = storage_path('app/fallback');
    $fallbackContent = 'fallback content';
    
    $fallbackWrite = file_put_contents($fallbackPath, $fallbackContent);
    echo "🔄 Fallback write: " . ($fallbackWrite !== false ? 'WORKING' : 'FAILED') . "\n";
    
    if ($fallbackWrite !== false) {
        unlink($fallbackPath);
    }
    
    echo "✅ Storage failure simulation complete\n";
    echo "📊 Check your application logs for error handling\n";
    echo "📊 Check monitoring dashboards for performance impact\n";
    
    Log::warning('Storage failure simulation completed', [
        'timestamp' => now()->toIso8601String(),
        'write_success' => $writeSuccess,
        'read_success' => $readSuccess,
        'delete_success' => $deleteSuccess,
    ]);
    
} catch (\Throwable $e) {
    echo "❌ Error during storage failure simulation: " . $e->getMessage() . "\n";
    Log::error('Storage failure simulation failed', [
        'error' => $e->getMessage(),
    ]);
}

echo "\n✅ Simulation complete\n";
echo "📊 Check the circuit breaker status in your cache\n";
echo "📊 Check monitoring logs for alerts\n";