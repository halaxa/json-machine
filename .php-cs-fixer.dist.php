<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('ext')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@Symfony' => true,
    'yoda_style' => false,
    'single_line_throw' => false,
    'unary_operator_spaces' => false,
    'visibility_required' => false,
])
    ->setFinder($finder)
    ;
