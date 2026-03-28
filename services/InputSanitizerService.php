<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Services;

use App;
use Config;
use Illuminate\Http\Request;
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

    private InputSanitizerDto $inputSanitizerDto;

    private static bool $addMiddleware = false;

    public function __construct()
    {
        $this->inputSanitizerDto = InputSanitizerDtoInstance::instance()->get();
    }

    public function addMiddleware(): void
    {
        if (self::$addMiddleware) {
            return;
        }

        self::$addMiddleware = true;

        $inputSanitizer = InputSanitizerDtoInstance::instance()->get();

        if (!$inputSanitizer->cmsEnabled) {
            return;
        }

        /** @var CoreRouter $coreRoute */
        $coreRoute = App::make('router');
        $coreRoute->aliasMiddleware(InputSanitizerMiddleware::ALIAS, InputSanitizerMiddleware::class);

        $this->overrideConfig();
    }

    public function disable(): void
    {
        /** @var array<string, mixed>|\Illuminate\Support\Collection<int, mixed> $inputSanitizer */
        $inputSanitizer = Fortify::get('input_sanitizer');

        if ($inputSanitizer instanceof \Illuminate\Support\Collection) {
            $inputSanitizer = $inputSanitizer->toArray();
        }

        $inputSanitizer = !is_array($inputSanitizer) ? [] : $inputSanitizer;

        $inputSanitizer['cms_enabled'] = false;

        Fortify::set('input_sanitizer', $inputSanitizer);
    }

    /**
     * @param array<int|string, mixed> $data
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
        foreach ($data as $key => $value) {
            $key = Str::lower((string)$key);

            if (isset($this->inputSanitizerDto->excludedInputs[$key])) {
                continue;
            }

            if (is_array($value)) {
                $this->scanInput($value, $score);

                continue;
            }

            if (!is_string($value) || $value === '') {
                continue;
            }

            $this->score($value, $score);
        }
    }

    public function scanUrlSegments(Request $request, int &$score): void
    {
        foreach ($request->segments() as $segment) {
            if ($segment === '') {
                continue;
            }

            $this->score($segment, $score);
        }
    }

    public function scanHeaders(Request $request, int &$score): void
    {
        foreach ($request->headers->all() as $key => $values) {
            $key = Str::lower($key);

            if (isset($this->inputSanitizerDto->excludedHeaders[$key])) {
                continue;
            }

            foreach ($values as $value) {
                if (!is_string($value) || $value === '') {
                    continue;
                }

                $this->score($value, $score);
            }
        }
    }

    private function overrideConfig(): void
    {
        /** @var string|null|array<int, string> $middleware */
        $middleware = Config::get('cms.middleware_group', []);

        if (is_string($middleware)) {
            $middleware = [$middleware];
        }

        if (empty($middleware)) {
            $middleware = [];
        }

        $middleware[] = InputSanitizerMiddleware::ALIAS;
        /** @var array<int, string> $middleware */
        $middleware = array_unique($middleware);
        $middleware = array_filter($middleware);

        Config::set('cms.middleware_group', $middleware);
    }

    private function score(string $input, int &$score): void
    {
        $decoded = str_replace(["\r", "\n"], '', $this->multiDecode($input));

        foreach ($this->inputSanitizerDto->patterns as $pattern) {
            if (preg_match($pattern, $decoded)) {
                $score += 1;
            }

            if ($score >= $this->inputSanitizerDto->blockThreshold) {
                throw new BadRequestHttpException('Malicious input detected');
            }
        }
    }

    private function multiDecode(string $input): string
    {
        $decoded = $input;

        for ($i = 0; $i < 3; $i++) {
            $new = urldecode($decoded);

            if ($new === $decoded) {
                break;
            }

            $decoded = $new;
        }

        return html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
