<?php

declare(strict_types=1);

namespace FpDbTest;

use RuntimeException;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * @throws RuntimeException
     */
    public function buildQuery(string $query, array $args = []): string
    {
        if (preg_match_all('/(\{.*?\})|(\?[adfb#]?)/', $query, $tokenizedData)) {
            foreach ($tokenizedData[0] as $key => $macro) {
                if ($this->isConditionalBlock($macro)) {
                    $conditionalBlockMacro = trim($macro, '{}');
                    if (str_contains($conditionalBlockMacro, '{') || str_contains($conditionalBlockMacro, '}')) {
                        throw new \RuntimeException('Conditional block cannot be nested.');
                    }
                    $macroValue = is_array($args[$key]) ? [...$args[$key]] : [$args[$key]];
                    $macroValueFiltered = array_filter($macroValue, fn($v) => $this->skip() !== $v);
                    $value = match (count($macroValue) === count($macroValueFiltered)) {
                        true => $this->buildQuery($conditionalBlockMacro, $macroValue),
                        false => ''
                    };
                } else {
                    $value = $this->getBindingValueByType($macro, $args[$key]);
                }
                $query = substr_replace($query, (string)$value, strpos($query, $macro), strlen($macro));
            }
        }
        return $query;
    }

    private function isConditionalBlock(string $block)
    {
        return str_starts_with($block, '{') && str_ends_with($block, '}');
    }

    /**
     * @throws RuntimeException
     */
    private function getBindingValueByType(string $type, mixed $arg): mixed
    {
        $result = match ($type) {
            '?' => !is_array($arg) ? sprintf("'%s'", $arg) : null,
            '?d' => is_numeric($arg) || is_bool($arg) ? (int)$arg : null,
            '?f' => is_numeric((float)$arg) ? (float)$arg : null,
            '?#' => $this->getSqlIdentifiersValue($arg),
            '?a' => $this->getSqlArrayValue($arg),
            default => null
        };

        if (!$result) {
            throw new \RuntimeException('Invalid macro <' . $type . '> value in query: ' . PHP_EOL . var_export($arg, true));
        }

        return $result;
    }

    /**
     * @throws RuntimeException
     */
    private function checkArrayMultidimensional(mixed $data): void
    {
        if (is_array($data) && count($data) !== count($data, COUNT_RECURSIVE)){
            throw new RuntimeException();
        }
    }

    /**
     * @throws RuntimeException
     */
    private function getSqlIdentifiersValue(mixed $data): string
    {
        $this->checkArrayMultidimensional($data);

        if (!is_array($data)) {
            $data = [$data];
        }

        return implode(', ', array_map(fn($fieldName) => $this->getSqlFieldName($fieldName), $data));
    }

    /**
     * @throws RuntimeException
     */
    private function getSqlArrayValue(array $data): string
    {
        $this->checkArrayMultidimensional($data);

        return implode(
            ', ',
            array_map(
                function ($k, $v) {
                    return is_numeric($k) ? $v : sprintf(
                        '%s = %s',
                        $this->getSqlFieldName($k),
                        $this->getSqlFieldValue($v)
                    );
                },
                array_keys($data),
                $data
            )
        );
    }

    private function getSqlFieldName(string $fieldName): string
    {
        return sprintf('`%s`', $this->mysqli->real_escape_string($fieldName));
    }

    private function getSqlFieldValue(mixed $fieldValue)
    {
        return match (true) {
            is_string($fieldValue) => sprintf("'%s'", $fieldValue),
            is_null($fieldValue) => $this->skip(),
            default => $fieldValue
        };
    }

    public function skip()
    {
        return 'NULL';
    }

}
