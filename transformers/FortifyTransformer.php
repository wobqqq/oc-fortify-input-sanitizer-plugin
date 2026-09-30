<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Transformers;

use Illuminate\Support\Facades\View as IlluminateView;
use Str;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyInputSanitizer\Dto\InputSanitizerDto;
use Wobqqq\FortifyInputSanitizer\Services\InputSanitizerService;
use Wobqqq\FortifyInputSanitizer\Services\PatternMatcher;

final readonly class FortifyTransformer
{
    public const DEFAULT_BLOCK_THRESHOLD = 1;

    /** Headers whose values are never input a visitor typed. */
    private const ALWAYS_EXCLUDED_HEADERS = ['cookie' => 1, 'accept' => 1];

    public static function inputSanitizerDto(): InputSanitizerDto
    {
        $cmsEnabled = (bool)Fortify::get('input_sanitizer.cms_enabled');

        $view = Fortify::get('input_sanitizer.view');
        $view = is_string($view) && $view !== '' && IlluminateView::exists($view) ? $view : View::BAD_REQUEST->value;

        $blockThreshold = Fortify::get('input_sanitizer.block_threshold');
        $blockThreshold = is_numeric($blockThreshold) && (int)$blockThreshold > 0 ? (int)$blockThreshold : self::DEFAULT_BLOCK_THRESHOLD;

        $excludedHeaders = [];
        $excludedInputs = [];
        $patterns = [];

        if ($cmsEnabled) {
            $excludedHeaders = self::ALWAYS_EXCLUDED_HEADERS + self::names('input_sanitizer.excluded_headers');
            $excludedInputs = self::names('input_sanitizer.excluded_inputs');

            foreach (InputSanitizerService::PATTERNS as $pattern) {
                $value = Fortify::get(sprintf('input_sanitizer.%s', $pattern));
                $value = is_string($value) ? trim($value) : '';

                if (PatternMatcher::compiles($value)) {
                    $patterns[] = $value;
                }
            }
        }

        return new InputSanitizerDto(
            $cmsEnabled,
            $view,
            $blockThreshold,
            $excludedHeaders,
            $excludedInputs,
            $patterns,
        );
    }

    /**
     * @return array<string, int> lower-case names found in the table
     */
    private static function names(string $setting): array
    {
        $rows = Fortify::get($setting);
        $names = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $name = is_array($row) && is_scalar($row['name'] ?? null) ? Str::lower(trim((string)$row['name'])) : '';

            if ($name !== '') {
                $names[$name] = 1;
            }
        }

        return $names;
    }
}
