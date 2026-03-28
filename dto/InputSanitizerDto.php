<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Dto;

final readonly class InputSanitizerDto
{
    public function __construct(
        public bool $cmsEnabled,
        public string $view,
        public int $blockThreshold,
        /** @var array<string, int> $excludedHeaders */
        public array $excludedHeaders,
        /** @var array<string, int> $excludedInputs */
        public array $excludedInputs,
        /** @var array<int, string> $patterns */
        public array $patterns,
    ) {
    }
}
