<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['vendor', 'bootstrap/cache'])
    ->in(__DIR__);

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'type_declaration_spaces' => [
            'elements' => ['function', 'property'],
        ],
        'no_extra_blank_lines' => true,
        'blank_line_before_statement' => [
            'statements' => ['return'],
        ],
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder($finder);
