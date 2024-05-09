<?php

namespace FpDbTest\Grammar;

use RuntimeException;

trait ValidateArrayTrait
{
    /**
     * @throws RuntimeException
     */
    private function checkArrayMultidimensional(mixed $data): void
    {
        if (is_array($data) && count($data) !== count($data, COUNT_RECURSIVE)){
            throw new RuntimeException('Array value cannot be nested: ' . var_export($data, true));
        }
    }

}