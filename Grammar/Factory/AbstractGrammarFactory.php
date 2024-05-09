<?php

namespace FpDbTest\Grammar\Factory;

abstract class AbstractGrammarFactory
{
    public function __construct(protected \mysqli $mysqli)
    {

    }

    protected function escapeString(mixed $value): string
    {
        return match (true) {
            is_string($value) => sprintf("'%s'", $this->mysqli->real_escape_string($value)),
            is_null($value) => 'NULL',
            default => $value
        };
    }

    protected function fieldIdentifier(string $value): string
    {
        return sprintf("`%s`", $this->mysqli->real_escape_string($value));
    }

    abstract public function __invoke(mixed $value): string;
}