<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Event Listeners for TenancyInitialized ===\n\n";

$listeners = app('events')->getListeners(\Stancl\Tenancy\Events\TenancyInitialized::class);

echo "Number of listeners: " . count($listeners) . "\n\n";

foreach ($listeners as $listener) {
    if (is_array($listener)) {
        echo "Listener: " . get_class($listener[0]) . ' -> ' . $listener[1] . "\n";
    } else {
        echo "Listener: " . get_class($listener) . "\n";
    }
}