<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Console;

use Illuminate\Console\Command;
use Wobqqq\FortifyInputSanitizer\Services\InputSanitizerService;

final class InputSanitizerDisableCommand extends Command
{
    /** @var string */
    protected $name = 'wobqqq.fortify:input-sanitizer:disable';

    /** @var string */
    protected $description = 'Disable Input Sanitizer.';

    public function handle(InputSanitizerService $inputSanitizerService): void
    {
        $inputSanitizerService->disable();

        $this->info('Input Sanitizer disabled.');
    }
}
