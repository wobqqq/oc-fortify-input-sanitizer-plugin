<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer;

use Event;
use System\Classes\PluginBase;
use Validator;
use Wobqqq\FortifyInputSanitizer\Console\InputSanitizerDisableCommand;
use Wobqqq\FortifyInputSanitizer\Listeners\FortifyListener;
use Wobqqq\FortifyInputSanitizer\Services\InputSanitizerService;
use Wobqqq\FortifyInputSanitizer\Validator\Rules\InputSanitizerRegexRule;

final class Plugin extends PluginBase
{
    /** @var array<int, string> */
    public $require = ['Wobqqq.Fortify'];

    public function register(): void
    {
        $this->registerConsoleCommand('wobqqq.fortify:input-sanitizer:disable', InputSanitizerDisableCommand::class);
    }

    public function boot(): void
    {
        $this->registerEvents();
        $this->registerValidatorRules();
        $this->runService();
    }

    private function registerEvents(): void
    {
        Event::subscribe(FortifyListener::class);
    }

    private function registerValidatorRules(): void
    {
        Validator::extend('input_sanitizer_regex', InputSanitizerRegexRule::class);
    }

    private function runService(): void
    {
        /** @var InputSanitizerService $inputSanitizerService */
        $inputSanitizerService = app(InputSanitizerService::class);
        $inputSanitizerService->addMiddleware();
    }
}
