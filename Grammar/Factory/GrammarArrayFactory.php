<?php

declare(strict_types=1);

namespace FpDbTest\Grammar\Factory;

use FpDbTest\Grammar\ValidateArrayTrait;

class GrammarArrayFactory extends AbstractGrammarFactory
{
    use ValidateArrayTrait;

    public function __invoke(mixed $value): string
    {
        if (!is_array($value)) {
            throw new \RuntimeException('Invalid argument type: ' . var_export($value, true));
        }

        $this->checkArrayMultidimensional($value);

        return implode(', ', $this->prepareArray($value));
    }

    private function prepareArray(array $data): array
    {
        return array_is_list($data) ?
            $data :
            array_map(
                fn($k, $v) => sprintf('%s = %s', $this->fieldIdentifier($k), $this->escapeString($v)),
                array_keys($data),
                $data
            );
    }
}