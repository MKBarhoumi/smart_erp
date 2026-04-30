<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test Middleware Manually ===\n\n";

// Get the middleware
$middleware = app(\Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class);

// Create a request
$request = \Illuminate\Http\Request::create('http://test-tenant2.localhost/test-tenancy', 'GET');
$request->headers->set('HOST', 'test-tenant2.localhost');

echo "Request:\n";
echo "  - URL: " . $request->url() . "\n";
echo "  - Host: " . $request->getHost() . "\n";
echo "  - Method: " . $request->method() . "\n";

// Check tenancy before middleware
echo "\nBefore middleware:\n";
echo "  - Tenancy initialized: " . (tenancy()->initialized ? 'YES' : 'NO') . "\n";
echo "  - Current tenant: " . (tenancy()->tenant ? tenancy()->tenant->name : 'NULL') . "\n";
echo "  - DB connection: " . DB::getDefaultConnection() . "\n";
echo "  - DB name: " . DB::connection()->getDatabaseName() . "\n";

// Call the middleware
$response = $middleware->handle($request, function ($req) {
    echo "\nInside middleware callback:\n";
    echo "  - Tenancy initialized: " . (tenancy()->initialized ? 'YES' : 'NO') . "\n";
    echo "  - Current tenant: " . (tenancy()->tenant ? tenancy()->tenant->name : 'NULL') . "\n";
    echo "  - DB connection: " . DB::getDefaultConnection() . "\n";
    echo "  - DB name: " . DB::connection()->getDatabaseName() . "\n";

    return new \Illuminate\Http\Response('OK');
});

echo "\nAfter middleware:\n";
echo "  - Tenancy initialized: " . (tenancy()->initialized ? 'YES' : 'NO') . "\n";
echo "  - Current tenant: " . (tenancy()->tenant ? tenancy()->tenant->name : 'NULL') . "\n";
echo "  - DB connection: " . DB::getDefaultConnection() . "\n";
echo "  - DB name: " . DB::connection()->getDatabaseName() . "\n";

echo "\nResponse: " . $response->getContent() . "\n";