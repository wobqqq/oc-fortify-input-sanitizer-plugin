<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyInputSanitizer\Http\Middlewares\InputSanitizerMiddleware;
use Wobqqq\FortifyInputSanitizer\Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Feature');

/**
 * Enables the sanitizer with its default patterns, overridden by $settings.
 *
 * @param array<string, mixed> $settings
 */
function configureInputSanitizer(array $settings = []): void
{
    Fortify::clearInternalCache();
    $defaults = Fortify::instance()->input_sanitizer;

    Fortify::set('input_sanitizer', array_merge(is_array($defaults) ? $defaults : [], ['cms_enabled' => true], $settings));

    TestCase::bootPlugins();
}

/**
 * @param array<array-key, mixed> $query
 * @param array<array-key, mixed> $post
 * @param array<array-key, mixed> $headers
 */
function sanitizedRequest(string $uri = '/page', array $query = [], array $post = [], array $headers = []): Response
{
    $server = [];

    foreach ($headers as $name => $value) {
        $server['HTTP_' . strtoupper(str_replace('-', '_', (string)$name))] = is_string($value) ? $value : '';
    }

    $request = Request::create($uri, $post === [] ? 'GET' : 'POST', $post === [] ? $query : $post, [], [], $server);
    /** @var array<string, mixed> $query */
    $request->query->add($query);

    $response = app(InputSanitizerMiddleware::class)->handle($request, static fn (): Response => new Response('content'));

    return $response instanceof Response ? $response : throw new UnexpectedValueException('No response.');
}
