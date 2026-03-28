<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Instances;

use October\Rain\Support\Traits\Singleton;
use Wobqqq\FortifyInputSanitizer\Cache\InputSanitizerDtoCache;
use Wobqqq\FortifyInputSanitizer\Dto\InputSanitizerDto;

final class InputSanitizerDtoInstance
{
    use Singleton;

    private ?InputSanitizerDto $inputSanitizerDto = null;

    public function get(): InputSanitizerDto
    {
        if ($this->inputSanitizerDto instanceof InputSanitizerDto) {
            return $this->inputSanitizerDto;
        }

        /** @var InputSanitizerDtoCache $inputSanitizerDtoCache */
        $inputSanitizerDtoCache = app(InputSanitizerDtoCache::class);

        return $this->inputSanitizerDto = $inputSanitizerDtoCache->get();
    }
}
