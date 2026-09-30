<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'views'])
    ->name(['*.php'])
    ->notPath('#/partials/#');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,
        '@PHP8x2Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => true,
        'blank_line_after_opening_tag' => true,
        'cast_spaces' => ['space' => 'none'],
        'class_attributes_separation' => [
            'elements' => ['const' => 'one', 'method' => 'one', 'property' => 'one'],
        ],
        'combine_consecutive_unsets' => true,
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => false, 'import_functions' => false],
        'lowercase_cast' => true,
        'method_argument_space' => true,
        'modifier_keywords' => ['elements' => ['const', 'method', 'property']],
        'native_function_invocation' => ['include' => [], 'strict' => true],
        'new_with_parentheses' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_leading_import_slash' => true,
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'remove_inheritdoc' => true],
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_whitespace_in_blank_line' => true,
        'ordered_class_elements' => ['order' => ['use_trait']],
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_trim' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last'],
        'return_type_declaration' => true,
        'short_scalar_cast' => true,
        'single_line_comment_style' => true,
        'single_quote' => true,
        'single_trait_insert_per_statement' => true,
        'space_after_semicolon' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => true,
        'trim_array_spaces' => true,
        'types_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ]);
