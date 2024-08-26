<?php

declare(strict_types=1);

require __DIR__ . '/../../../vendor/autoload.php';

use Carte\Content\FileResolver;
use Carte\Content\PhpResolver;
use Carte\Content\RedirectResolver;
use Carte\Content\StreamResolver;
use Carte\Manifest;
use Carte\Router;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;

// Handle the request
$factory = new HttpFactory();
$manifest = new Manifest(__DIR__ . '/../manifests/site.yml');
$router = new Router(
    responseFactory: $factory,
    streamFactory: $factory,
    manifest: $manifest,
    resolvers: [
        new StreamResolver(__DIR__ . '/../content', $factory),
        new PhpResolver(__DIR__ . '/../content'),
        new FileResolver(__DIR__ . '/../content'),
        new RedirectResolver(),
    ],
);

$request = ServerRequest::fromGlobals();
$response = $router->dispatch($request);

// Emit the status code
http_response_code($response->getStatusCode());

// Emit the headers
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

// Emit the body
echo $response->getBody();
