<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Cache;

use Illuminate\Support\Facades\Cache;
use Wobqqq\Fortify\Cache\BasicCache;
use Wobqqq\FortifyInputSanitizer\Dto\InputSanitizerDto;
use Wobqqq\FortifyInputSanitizer\Transformers\FortifyTransformer;

final class InputSanitizerDtoCache extends BasicCache
{
    public function get(): InputSanitizerDto
    {
        $cacheKey = $this->cacheKey();

        /** @var InputSanitizerDto $inputSanitizerDto */
        $inputSanitizerDto = Cache::remember($cacheKey, self::TTL, function () {
            return FortifyTransformer::inputSanitizerDto();
        });

        return $inputSanitizerDto;
    }

    public function clear(): void
    {
        Cache::forget($this->cacheKey());
    }
}
