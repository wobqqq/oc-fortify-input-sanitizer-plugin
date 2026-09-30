<?php

declare(strict_types=1);

use Backend\Widgets\Form;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use System\Controllers\Settings;
use Wobqqq\Fortify\Dto\WidgetGroupItemDto;
use Wobqqq\Fortify\Enums\FortifyEvent;
use Wobqqq\Fortify\Enums\WidgetItemColor;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\Fortify\Transformers\FortifyTransformer as CoreTransformer;
use Wobqqq\FortifyInputSanitizer\Cache\InputSanitizerDtoCache;
use Wobqqq\FortifyInputSanitizer\Instances\InputSanitizerDtoInstance;
use Wobqqq\FortifyInputSanitizer\Services\InputSanitizerService;
use Wobqqq\FortifyInputSanitizer\Transformers\FortifyTransformer;

function inputSanitizerSettings(): Fortify
{
    $form = new Form(new Settings(), Fortify::instance());
    Event::dispatch('backend.form.extendFields', [$form]);

    return $form->model instanceof Fortify ? $form->model : throw new UnexpectedValueException('The form is not the Fortify settings.');
}

/**
 * @param array<string, mixed> $inputSanitizer
 */
function inputSanitizerValidates(array $inputSanitizer): bool
{
    $data = [
        'config' => ['password_policy_min_length' => 12, 'session_lifetime' => 30],
        'input_sanitizer' => array_merge(['view' => 'wobqqq.fortify::bad-request', 'block_threshold' => 1], $inputSanitizer),
    ];

    return Validator::make($data, inputSanitizerSettings()->rules)->passes();
}

it('adds its section to the Fortify settings form', function (): void {
    $form = new Form(new Settings(), Fortify::instance());
    Event::dispatch('backend.form.extendFields', [$form]);

    expect($form->tabFields)->toHaveKeys([
        'input_sanitizer[cms_enabled]',
        'input_sanitizer[view]',
        'input_sanitizer[block_threshold]',
        'input_sanitizer[xss_patterns]',
        'input_sanitizer[excluded_headers]',
        'input_sanitizer[excluded_inputs]',
    ]);
});

it('starts disabled with a pattern for every kind of attack', function (): void {
    $settings = inputSanitizerSettings()->input_sanitizer;

    expect($settings)->toBeArray()->toHaveKey('cms_enabled', false)->toHaveKey('block_threshold', 1);

    foreach (InputSanitizerService::PATTERNS as $pattern) {
        expect($settings)->toHaveKey($pattern);
    }
});

it('refuses a pattern that does not compile', function (string $pattern, bool $passes): void {
    expect(inputSanitizerValidates(['xss_patterns' => $pattern]))->toBe($passes);
})->with([
    ['~<script~i', true],
    ['', true],
    ['~(unclosed~', false],
    ['no delimiters', false],
]);

it('validates the threshold and the excluded names', function (string $field, mixed $value, bool $passes): void {
    expect(inputSanitizerValidates([$field => $value]))->toBe($passes);
})->with([
    ['block_threshold', 0, false],
    ['block_threshold', 1001, false],
    ['excluded_headers', [['name' => 'X-Template']], true],
    ['excluded_headers', [['name' => "X-Bad\r\n"]], false],
    ['excluded_inputs', [['name' => 'content.body']], true],
    ['excluded_inputs', [['name' => '<script>']], false],
]);

it('applies the settings as soon as they are saved, whenever the settings were loaded', function (): void {
    configureInputSanitizer(['block_threshold' => 2]);

    expect(InputSanitizerDtoInstance::instance()->get()->blockThreshold)->toBe(2);

    $settings = Fortify::get('input_sanitizer');
    Fortify::set('input_sanitizer', array_merge(is_array($settings) ? $settings : [], ['block_threshold' => 5]));
    InputSanitizerDtoInstance::forgetInstance();

    expect(InputSanitizerDtoInstance::instance()->get()->blockThreshold)->toBe(5);
});

it('falls back to safe values for a broken setting', function (): void {
    configureInputSanitizer([
        'view' => 'acme.theme::missing',
        'block_threshold' => 'many',
        'excluded_inputs' => 'not a table',
        'xss_patterns' => '~(broken~',
    ]);

    $dto = FortifyTransformer::inputSanitizerDto();

    expect($dto->view)->toBe('wobqqq.fortify::bad-request')
        ->and($dto->blockThreshold)->toBe(FortifyTransformer::DEFAULT_BLOCK_THRESHOLD)
        ->and($dto->excludedInputs)->toBe([])
        ->and($dto->excludedHeaders)->toHaveKeys(['cookie', 'accept'])
        ->and($dto->patterns)->toHaveCount(count(InputSanitizerService::PATTERNS) - 1);
});

it('shows on the dashboard whether it is on', function (): void {
    $item = CoreTransformer::widgetGroupItemDto('placeholder');
    Event::dispatch(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_INPUT_SANITIZER->value, [&$item]);

    expect($item)->toBeInstanceOf(WidgetGroupItemDto::class)
        ->and($item->color)->toBe(WidgetItemColor::DANGER);

    configureInputSanitizer();
    Event::dispatch(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_INPUT_SANITIZER->value, [&$item]);

    expect($item->color)->toBe(WidgetItemColor::SUCCESS);
});

it('rebuilds a cached rule set the previous version wrote in another shape', function (): void {
    configureInputSanitizer(['block_threshold' => 7]);

    Cache::shouldReceive('remember')->once()->andThrow(new TypeError('Cannot assign string to property'));
    Cache::shouldReceive('forget')->once();

    expect(app(InputSanitizerDtoCache::class)->get()->blockThreshold)->toBe(7);
});
