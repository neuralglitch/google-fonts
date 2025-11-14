<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => false,
        'trailing_comma_in_multiline' => true,
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'concat_space' => ['spacing' => 'one'], // Require spaces around concatenation operator
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'phpdoc_align' => false, // Disable PHPDoc alignment
        'phpdoc_to_comment' => ['ignored_tags' => ['psalm-suppress', 'phpstan-ignore-next-line', 'phpstan-ignore-line']], // Keep DocBlocks with static analysis annotations
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
            ],
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => true,
        ],
        'single_trait_insert_per_statement' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);

