<?php

namespace FpDbTest\Grammar\Factory;

class GrammarIntegerFactory extends AbstractGrammarFactory
{

    public function __invoke(mixed $value): string
    {
        return match (gettype($value)) {
            'integer' => $value,
            'boolean' => (int) $value,
            default => throw new \RuntimeException('Invalid argument type: ' . $value)
        };
    }
}