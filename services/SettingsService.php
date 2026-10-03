<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Services;

use Backend;
use Backend\Widgets\Form;
use Wobqqq\Fortify\Dto\WidgetGroupItemDto;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\Fortify\Enums\WidgetItemColor;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\Fortify\Transformers\FortifyTransformer;
use Wobqqq\FortifyInputSanitizer\Instances\InputSanitizerDtoInstance;
use Wobqqq\FortifyInputSanitizer\Transformers\FortifyTransformer as InputSanitizerTransformer;

final readonly class SettingsService
{
    public function fillWidgetGroupItem(WidgetGroupItemDto &$widgetGroupItemDto): void
    {
        $settingsLink = FortifyTransformer::widgetItemLinkDto(
            'wobqqq.fortify::lang.buttons.edit',
            Backend::url('system/settings/update/wobqqq/fortify/fortify#primarytab-input-sanitizer'),
            'icon-wrench',
        );
        $inputSanitizerDto = InputSanitizerDtoInstance::instance()->get();
        $color = $inputSanitizerDto->cmsEnabled === true
            ? WidgetItemColor::SUCCESS
            : WidgetItemColor::DANGER;
        $widgetGroupItemDto = FortifyTransformer::widgetGroupItemDto(
            'wobqqq.fortify::lang.fields.input_sanitizer',
            [$settingsLink],
            $color,
            'icon-crosshairs',
        );
    }

    public function applyDefaults(Fortify $fortify): void
    {
        $inputSanitizer = (isset($fortify->input_sanitizer) && is_array($fortify->input_sanitizer))
            ? $fortify->input_sanitizer
            : [];

        if ($inputSanitizer !== []) {
            return;
        }

        $inputSanitizer['cms_enabled'] = false;
        $inputSanitizer['view'] = View::BAD_REQUEST->value;
        $inputSanitizer['block_threshold'] = InputSanitizerTransformer::DEFAULT_BLOCK_THRESHOLD;
        $inputSanitizer['xss_patterns'] = '~<\s*(script|iframe|object|embed|svg|meta|base|form|input|button)\b|javascript\s*:|vbscript\s*:|data\s*:\s*text/html|<[^>]+?\s+on[a-z]{3,30}\s*=~ix';
        $inputSanitizer['encoded_xss_patterns'] = '~(&lt;|%3c|%253c)\s*script~ix';
        $inputSanitizer['command_injection_patterns'] = '~(?:^|[;&\s])\s*(cmd|powershell|bash|sh|curl|wget|nc)\b|[a-z0-9]\s*\|\s*[a-z0-9]|&&|`[^`]+`|\$\([^)]*\)~ix';
        $inputSanitizer['path_traversal_patterns'] = '~\.\./|\.\.\\\\|%2e%2e%2f|%2e%2e%5c|/etc/passwd|windows/system32~ix';
        $inputSanitizer['ssti_patterns'] = '~\{\{.*?\}\}|\{%.*?%\}|\{!!.*?!!\}~sx';
        $inputSanitizer['null_byte_patterns'] = '~\x00|%00|\\\\0|\\\\x00~ix';
        $inputSanitizer['csv_injection_patterns'] = '~^\s*[=<$#]~x';
        $fortify->input_sanitizer = $inputSanitizer;
    }

    public function applyRules(Fortify $fortify): void
    {
        $fortify->attributeNames['input_sanitizer.excluded_headers.*.name'] = 'wobqqq.fortify::lang.fields.name';
        $fortify->attributeNames['input_sanitizer.excluded_inputs.*.name'] = 'wobqqq.fortify::lang.fields.name';

        $fortify->rules['input_sanitizer.view'] = 'required|string|max:100';
        $fortify->rules['input_sanitizer.block_threshold'] = 'required|int|min:1|max:1000';

        foreach (InputSanitizerService::PATTERNS as $pattern) {
            $fortify->rules[sprintf('input_sanitizer.%s', $pattern)] = 'nullable|string|max:500|input_sanitizer_regex';
        }

        $fortify->rules['input_sanitizer.excluded_headers.*.name'] = 'nullable|string|max:50|regex:/^[A-Za-z0-9-]+$/';
        $fortify->rules['input_sanitizer.excluded_headers'] = 'nullable|array|max:150';
        $fortify->rules['input_sanitizer.excluded_inputs.*.name'] = 'nullable|string|max:50|regex:/^[a-zA-Z][a-zA-Z0-9_\-\.]*$/';
        $fortify->rules['input_sanitizer.excluded_inputs'] = 'nullable|array|max:150';
    }

    public function addFields(Form $form): void
    {
        $form->removeField('input_sanitizer[section]');
        $form->removeField('input_sanitizer[plugin]');

        $fields = [
            'input_sanitizer[section]' => [
                'label' => 'wobqqq.fortify::lang.fields.input_sanitizer',
                'type' => 'section',
                'span' => 'full',
                'tab' => 'wobqqq.fortify::lang.tabs.input_sanitizer',
            ],

            'input_sanitizer[cms_enabled]' => [
                'label' => 'wobqqq.fortify::lang.fields.enabled',
                'span' => 'full',
                'type' => 'switch',
                'tab' => 'wobqqq.fortify::lang.tabs.input_sanitizer',
                'default' => false,
                'comment' => 'wobqqq.fortify::lang.comments.input_sanitizer_cms_enabled',
                'commentHtml' => true,
            ],

            'input_sanitizer[view]' => [
                'label' => 'wobqqq.fortify::lang.fields.view',
                'span' => 'full',
                'required' => true,
                'type' => 'dropdown',
                'default' => 'wobqqq.fortify::bad-request',
                'tab' => 'wobqqq.fortify::lang.tabs.input_sanitizer',
                'comment' => 'wobqqq.fortify::lang.comments.input_sanitizer_view',
                'options' => 'getViewOptions',
                'trigger' => [
                    'action' => 'show',
                    'field' => 'input_sanitizer[cms_enabled]',
                    'condition' => 'checked',
                ],
            ],

            'input_sanitizer[block_threshold]' => [
                'label' => 'wobqqq.fortify::lang.fields.block_threshold',
                'span' => 'full',
                'required' => true,
                'type' => 'number',
                'default' => InputSanitizerTransformer::DEFAULT_BLOCK_THRESHOLD,
                'tab' => 'wobqqq.fortify::lang.tabs.input_sanitizer',
                'comment' => 'wobqqq.fortify::lang.comments.block_threshold',
                'trigger' => [
                    'action' => 'show',
                    'field' => 'input_sanitizer[cms_enabled]',
                    'condition' => 'checked',
                ],
            ],
        ];

        foreach (InputSanitizerService::PATTERNS as $pattern) {
            $fields[sprintf('input_sanitizer[%s]', $pattern)] = [
                'label' => sprintf('wobqqq.fortify::lang.fields.%s', $pattern),
                'type' => 'textarea',
                'size' => 'small',
                'span' => 'full',
                'tab' => 'wobqqq.fortify::lang.tabs.input_sanitizer',
                'commentAbove' => sprintf('wobqqq.fortify::lang.comments.%s', $pattern),
                'comment' => sprintf('wobqqq.fortify::lang.comments.%s_example', $pattern),
                'trigger' => [
                    'action' => 'show',
                    'field' => 'input_sanitizer[cms_enabled]',
                    'condition' => 'checked',
                ],
            ];
        }

        $fields['input_sanitizer[excluded_headers]'] = [
            'label' => 'wobqqq.fortify::lang.fields.excluded_headers',
            'type' => 'datatable',
            'span' => 'left',
            'tab' => 'wobqqq.fortify::lang.tabs.input_sanitizer',
            'adding' => true,
            'deleting' => true,
            'searching' => false,
            'recordsPerPage' => 20,
            'commentAbove' => 'wobqqq.fortify::lang.comments.input_sanitizer_excluded_headers',
            'comment' => 'wobqqq.fortify::lang.comments.input_sanitizer_excluded_headers_example',
            'commentHtml' => true,
            'trigger' => [
                'action' => 'show',
                'field' => 'input_sanitizer[cms_enabled]',
                'condition' => 'checked',
            ],
            'columns' => [
                'name' => [
                    'type' => 'string',
                    'title' => 'wobqqq.fortify::lang.fields.name',
                ],
            ],
        ];

        $fields['input_sanitizer[excluded_inputs]'] = [
            'label' => 'wobqqq.fortify::lang.fields.excluded_inputs',
            'type' => 'datatable',
            'span' => 'right',
            'tab' => 'wobqqq.fortify::lang.tabs.input_sanitizer',
            'adding' => true,
            'deleting' => true,
            'searching' => false,
            'recordsPerPage' => 20,
            'commentAbove' => 'wobqqq.fortify::lang.comments.input_sanitizer_excluded_inputs',
            'comment' => 'wobqqq.fortify::lang.comments.input_sanitizer_excluded_inputs_example',
            'commentHtml' => true,
            'trigger' => [
                'action' => 'show',
                'field' => 'input_sanitizer[cms_enabled]',
                'condition' => 'checked',
            ],
            'columns' => [
                'name' => [
                    'type' => 'string',
                    'title' => 'wobqqq.fortify::lang.fields.name',
                ],
            ],
        ];

        $form->addTabFields($fields);
    }
}
