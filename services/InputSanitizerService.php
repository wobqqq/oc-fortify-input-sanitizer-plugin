<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Services;

use App;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use October\Rain\Router\CoreRouter;
use Str;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyInputSanitizer\Dto\InputSanitizerDto;
use Wobqqq\FortifyInputSanitizer\Http\Middlewares\InputSanitizerMiddleware;
use Wobqqq\FortifyInputSanitizer\Instances\InputSanitizerDtoInstance;

final class InputSanitizerService
{
    public const PATTERNS = [
        'xss_patterns',
        'encoded_xss_patterns',
        'command_injection_patterns',
        'path_traversal_patterns',
        'ssti_patterns',
        'null_byte_patterns',
        'csv_injection_patterns',
    ];

    private const DECODE_ROUNDS = 3;

    private static bool $addMiddleware = false;

    public function addMiddleware(): void
    {
        if (self::$addMiddleware) {
            return;
        }

        self::$addMiddleware = true;

        if (!$this->dto()->cmsEnabled) {
            return;
        }

        /** @var CoreRouter $coreRoute */
        $coreRoute = App::make('router');
        $coreRoute->aliasMiddleware(InputSanitizerMiddleware::ALIAS, InputSanitizerMiddleware::class);

        $this->overrideConfig();
    }

    public function disable(): void
    {
        /** @var array<string, mixed>|Collection<int, mixed>|null $inputSanitizer */
        $inputSanitizer = Fortify::get('input_sanitizer');

        if ($inputSanitizer instanceof Collection) {
            $inputSanitizer = $inputSanitizer->toArray();
        }

        $inputSanitizer = is_array($inputSanitizer) ? $inputSanitizer : [];

        $inputSanitizer['cms_enabled'] = false;

        Fortify::set('input_sanitizer', $inputSanitizer);
    }

    /**
     * Scores the query, the form input, the headers and the URL segments together; the request
     * is refused once the score reaches the threshold.
     *
     * @param array<int|string, mixed> $data
     *
     * @throws BadRequestHttpException
     */
    public function check(array $data, Request $request): void
    {
        $score = 0;

        $this->scanInput($data, $score);
        $this->scanHeaders($request, $score);
        $this->scanUrlSegments($request, $score);
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public function scanInput(array $data, int &$score): void
    {
        $excludedInputs = $this->dto()->excludedInputs;

        foreach ($data as $key => $value) {
            if (isset($excludedInputs[Str::lower((string)$key)])) {
                continue;
            }

            if (is_array($value)) {
                $this->scanInput($value, $score);

                continue;
            }

            if (is_string($value) && $value !== '') {
                $this->score($value, $score);
            }
        }
    }

    public function scanUrlSegments(Request $request, int &$score): void
    {
        foreach ($request->segments() as $segment) {
            if (is_string($segment) && $segment !== '') {
                $this->score($segment, $score);
            }
        }
    }

    public function scanHeaders(Request $request, int &$score): void
    {
        $excludedHeaders = $this->dto()->excludedHeaders;

        foreach ($request->headers->all() as $key => $values) {
            if (isset($excludedHeaders[Str::lower($key)])) {
                continue;
            }

            foreach ($values as $value) {
                if (is_string($value) && $value !== '') {
                    $this->score($value, $score);
                }
            }
        }
    }

    private function dto(): InputSanitizerDto
    {
        return InputSanitizerDtoInstance::instance()->get();
    }

    private function overrideConfig(): void
    {
        $middleware = Config::get('cms.middleware_group', []);
        $middleware = is_string($middleware) ? [$middleware] : (is_array($middleware) ? $middleware : []);
        $middleware = array_filter($middleware, static fn (mixed $name): bool => is_string($name) && $name !== '');

        $middleware[] = InputSanitizerMiddleware::ALIAS;

        Config::set('cms.middleware_group', array_values(array_unique($middleware)));
    }

    private function score(string $input, int &$score): void
    {
        $dto = $this->dto();
        $decoded = str_replace(["\r", "\n"], '', $this->multiDecode($input));

        foreach ($dto->patterns as $pattern) {
            if (PatternMatcher::matches($pattern, $decoded)) {
                $score++;
            }

            if ($score >= $dto->blockThreshold) {
                throw new BadRequestHttpException('Malicious input detected');
            }
        }
    }

    private function multiDecode(string $input): string
    {
        $decoded = $input;

        for ($i = 0; $i < self::DECODE_ROUNDS; $i++) {
            $new = urldecode($decoded);

            if ($new === $decoded) {
                break;
            }

            $decoded = $new;
        }

        return html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
