<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test Middleware Response ===\n\n";

$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::create('http://test-tenant2.localhost/test-tenancy', 'GET');
$request->headers->set('HOST', 'test-tenant2.localhost');

try {
    $response = $kernel->handle($request);
    $content = $response->getContent();

    echo "Raw response content:\n";
    echo $content . "\n";

    echo "\nParsed JSON:\n";
    $parsed = json_decode($content, true);
    var_dump($parsed);

    $kernel->terminate($request, $response);
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}