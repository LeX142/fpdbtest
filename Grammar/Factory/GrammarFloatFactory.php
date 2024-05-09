<?php

namespace FpDbTest\Grammar\Factory;

class GrammarFloatFactory extends AbstractGrammarFactory
{

    public function __invoke(mixed $value): string
    {
        return match (gettype($value)) {
            'integer' => $value,
            'double' => (float)$value,
            'string' => (float)$this->escapeString($value),
            default => throw new \RuntimeException('Invalid argument type: ' . $value)
        };
    }
}