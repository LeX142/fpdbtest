<?php

namespace FpDbTest\Grammar\Factory;

use FpDbTest\Grammar\ValidateArrayTrait;

class GrammarIdentifierFactory extends AbstractGrammarFactory
{
    use ValidateArrayTrait;

    public function __invoke(mixed $value): string
    {
        $values = $value;
        if (is_string($values)){
            $values = [$values];
        }

        $this->checkArrayMultidimensional($values);

        return implode(', ', array_map(fn($fieldName) => $this->fieldIdentifier($fieldName), $values));
    }

}