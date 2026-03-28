<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Transformers;

use Arr;
use Illuminate\Support\Facades\View as IlluminateView;
use Str;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyInputSanitizer\Dto\InputSanitizerDto;
use Wobqqq\FortifyInputSanitizer\Services\InputSanitizerService;

final readonly class FortifyTransformer
{
    public static function inputSanitizerDto(): InputSanitizerDto
    {
        /** @var bool|int|null $cmsEnabled */
        $cmsEnabled = Fortify::get('input_sanitizer.cms_enabled');
        $cmsEnabled = (bool)$cmsEnabled;

        /** @var string|null $view */
        $view = Fortify::get('input_sanitizer.view');
        $view = (string)$view;
        $view = empty($view) || !IlluminateView::exists($view) ? View::BAD_REQUEST->value : $view;

        /** @var string|null|int $blockThreshold */
        $blockThreshold = Fortify::get('input_sanitizer.block_threshold');
        $blockThreshold = (int)$blockThreshold;
        $blockThreshold = empty($blockThreshold) ? 1 : $blockThreshold;

        if ($cmsEnabled) {
            /** @var array<int, array<string, string|null>>|null $excludedHeadersTable */
            $excludedHeadersTable = Fortify::get('input_sanitizer.excluded_headers');
            $excludedHeadersTable = (empty($excludedHeadersTable) || !is_array($excludedHeadersTable))
                ? []
                : $excludedHeadersTable;
            $excludedHeaders = ['cookie' => 1, 'accept' => 1];

            foreach ($excludedHeadersTable as $excludedHeadersTableRow) {
                /** @var string|null $header */
                $header = Arr::get($excludedHeadersTableRow, 'name');

                if (empty($header)) {
                    continue;
                }

                $header = trim($header);
                $header = Str::lower($header);

                $excludedHeaders[$header] = 1;
            }

            /** @var array<int, array<string, string|null>>|null $excludedInputsTable */
            $excludedInputsTable = Fortify::get('input_sanitizer.excluded_inputs');
            $excludedInputsTable = (empty($excludedInputsTable) || !is_array($excludedInputsTable))
                ? []
                : $excludedInputsTable;
            $excludedInputs = [];

            foreach ($excludedInputsTable as $excludedInputsTableRow) {
                /** @var string|null $input */
                $input = Arr::get($excludedInputsTableRow, 'name');

                if (empty($input)) {
                    continue;
                }

                $input = trim($input);
                $input = Str::lower($input);

                $excludedInputs[$input] = 1;
            }

            $patterns = [];

            foreach (InputSanitizerService::PATTERNS as $pattern) {
                /** @var string|null $patternValue */
                $patternValue = Fortify::get(sprintf('input_sanitizer.%s', $pattern));
                $patternValue = trim((string)$patternValue);

                if (!empty($patternValue)) {
                    $patterns[] = $patternValue;
                }
            }
        } else {
            $excludedHeaders = [];
            $excludedInputs = [];
            $patterns = [];
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
}
