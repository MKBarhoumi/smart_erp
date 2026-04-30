<?php

// Load autoloader
require __DIR__ . '/vendor/autoload.php';

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/payments';
$_SERVER['HTTP_INERTIA'] = 'true';
$_SERVER['HTTP_INERTIA_VERSION'] = '1';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '8000';
$_SERVER['HTTP_HOST'] = 'localhost:8000';
$_SERVER['REQUEST_SCHEME'] = 'http';

// Get the application
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

try {
    $request = \Illuminate\Http\Request::capture();
    $response = $kernel->handle($request);

    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
    echo "\nContent (first 1000 chars):\n";
    echo substr($response->getContent(), 0, 1000);
    echo "\n\n... truncated\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
