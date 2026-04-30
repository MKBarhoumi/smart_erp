<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Check Middleware Stack ===\n\n";

// Get the middleware stack for the test-tenancy route
$route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('test.tenancy');

if ($route) {
    echo "Route: " . $route->uri . "\n";
    echo "Middleware: " . implode(', ', $route->middleware()) . "\n";

    // Get the middleware stack
    $middlewareStack = $kernel->getMiddlewareGroups();

    echo "\nMiddleware groups:\n";
    foreach ($middlewareStack as $name => $middlewares) {
        echo "  - " . $name . ": " . implode(', ', $middlewares) . "\n";
    }
} else {
    echo "Route not found\n";
}