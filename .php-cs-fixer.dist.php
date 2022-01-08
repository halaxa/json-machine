<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('ext')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR12' => true,
    'array_syntax' => ['syntax' => 'short'],
    'visibility_required' => false,
])
    ->setFinder($finder)
;
