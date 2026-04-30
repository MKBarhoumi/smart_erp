<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Debug Test 2 ===\n\n";

$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::create('http://test-tenant2.localhost/test-tenancy', 'GET');
$request->headers->set('HOST', 'test-tenant2.localhost');

echo "Request:\n";
echo "  - URL: " . $request->url() . "\n";
echo "  - Host: " . $request->getHost() . "\n";

try {
    $response = $kernel->handle($request);
    $content = $response->getContent();

    echo "\nRaw response:\n";
    echo $content . "\n";

    echo "\nResponse status: " . $response->getStatusCode() . "\n";
    echo "Response content type: " . $response->headers->get('Content-Type') . "\n";

    $parsed = json_decode($content, true);
    echo "\nParsed JSON:\n";
    var_dump($parsed);

    if ($parsed) {
        echo "\nChecking values:\n";
        echo "  - db_connection: " . ($parsed['db_connection'] ?? 'NOT SET') . "\n";
        echo "  - db_name: " . ($parsed['db_name'] ?? 'NOT SET') . "\n";
    }

    $kernel->terminate($request, $response);
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}