<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test Tenant Middleware (with debug) ===\n\n";

// Simulate a request to test-tenant2.localhost
$request = \Illuminate\Http\Request::create('http://test-tenant2.localhost/test-tenancy', 'GET');
$request->headers->set('HOST', 'test-tenant2.localhost');

echo "Request:\n";
echo "  - URL: " . $request->url() . "\n";
echo "  - Host: " . $request->getHost() . "\n";
echo "  - Method: " . $request->method() . "\n";

// Check if the host is a central domain
$centralDomains = config('tenancy.central_domains');
echo "\nCentral domains: " . implode(', ', $centralDomains) . "\n";
echo "Is central domain: " . (in_array($request->getHost(), $centralDomains) ? 'YES' : 'NO') . "\n";

try {
    $response = $kernel->handle($request);

    echo "\nResponse:\n";
    echo "  - Status: " . $response->getStatusCode() . "\n";
    echo "  - Content: " . $response->getContent() . "\n";

    $kernel->terminate($request, $response);
} catch (\Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}