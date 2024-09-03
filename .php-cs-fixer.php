<?php

$finder = PhpCsFixer\Finder::create()
    // ->exclude('somedir')
    // ->notPath('src/Symfony/Component/Translation/Tests/fixtures/resources.php')
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRules([
    'full_opening_tag' => false,
    '@PSR2' => true,
    'align_multiline_comment' => ['comment_type' => 'phpdocs_only'],
    'array_indentation' => true,
    'whitespace_after_comma_in_array' => true,
    'braces' => [
        'allow_single_line_closure' => true,
        'position_after_functions_and_oop_constructs' => 'same'
    ],
    'control_structure_continuation_position' => false,
    'linebreak_after_opening_tag' => false,
    'no_blank_lines_after_class_opening' => false,
    'no_empty_comment' => true,
    'single_line_comment_spacing' => true,
    'strict_param' => false,
    'trim_array_spaces' => true,
    'normalize_index_brace' => true,
    'increment_style' => ['style' => 'post'],
])
->setIndent("    ")
->setLineEnding("\n")
->setFinder($finder)
;
