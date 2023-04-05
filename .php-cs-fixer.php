<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['vendor', 'report'])
    ->in(__DIR__)
    ->name('*.php');

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@Symfony' => true,
    'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
    'concat_space' => ['spacing' => 'one'],
])->setFinder($finder);
