<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

$header = <<<EOF
This file is part of the package t3g/usercentrics.

For the full copyright and license information, please read the
LICENSE file that was distributed with this source code.
EOF;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'one'],
        'declare_equal_normalize' => ['space' => 'none'],
        'general_phpdoc_annotation_remove' => ['annotations' => ['author']],
        'header_comment' => ['header' => $header],
        'lowercase_cast' => true,
        'native_function_casing' => true,
        'no_alias_functions' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unused_imports' => true,
        'no_whitespace_in_blank_line' => true,
        'ordered_imports' => true,
        'phpdoc_no_package' => true,
        'phpdoc_scalar' => true,
        'self_accessor' => true,
        'single_line_comment_style' => true,
        'single_quote' => true,
        'type_declaration_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('.build')
            ->exclude('Build/tools')
            ->exclude('config')
            ->exclude('var')
            ->in(__DIR__)
    );
