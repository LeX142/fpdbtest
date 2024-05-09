<?php

namespace FpDbTest\Grammar\Factory;

class GrammarDefaultFactory extends AbstractGrammarFactory
{

    public function __invoke(mixed $value): string
    {
        return match (gettype($value)) {
            'boolean' => (int)$value,
            'integer' => $value,
            'double' => (float)$value,
            'string' => $this->escapeString($value),
            'NULL' => 'NULL',
            default => throw new \RuntimeException('Invalid argument type: ' . $value)
        };
    }
}