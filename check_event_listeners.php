<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Step 2: Verify Event Listeners ===\n\n";

$listeners = app('events')->getListeners(\Stancl\Tenancy\Events\TenancyInitialized::class);

echo "Event listeners for TenancyInitialized:\n";
var_dump($listeners);

if (empty($listeners)) {
    echo "\n❌ NO EVENT LISTENERS REGISTERED - THIS IS THE BUG!\n";
} else {
    echo "\n✅ Event listeners registered\n";
}