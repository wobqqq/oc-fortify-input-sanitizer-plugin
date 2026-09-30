<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Cache;

use Illuminate\Support\Facades\Cache;
use Throwable;
use Wobqqq\Fortify\Cache\BasicCache;
use Wobqqq\FortifyInputSanitizer\Dto\InputSanitizerDto;
use Wobqqq\FortifyInputSanitizer\Transformers\FortifyTransformer;

final class InputSanitizerDtoCache extends BasicCache
{
    public function get(): InputSanitizerDto
    {
        $cacheKey = $this->cacheKey();

        try {
            $inputSanitizerDto = Cache::remember($cacheKey, self::TTL, FortifyTransformer::inputSanitizerDto(...));
        } catch (Throwable) {
            Cache::forget($cacheKey);
            $inputSanitizerDto = null;
        }

        return $inputSanitizerDto instanceof InputSanitizerDto ? $inputSanitizerDto : FortifyTransformer::inputSanitizerDto();
    }

    public function clear(): void
    {
        Cache::forget($this->cacheKey());
    }
}
