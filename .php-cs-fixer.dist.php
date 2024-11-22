<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('ext')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@Symfony' => true,
    'not_operator_with_space' => true,
    'yoda_style' => false,
    'single_line_throw' => false,
    'unary_operator_spaces' => false,
    'visibility_required' => false,
    'php_unit_test_class_requires_covers' => true,
    'declare_strict_types' => true,
    'phpdoc_to_comment' => false, // todo remove when we move to GeneratorAggregate

])
    ->setFinder($finder)
;
