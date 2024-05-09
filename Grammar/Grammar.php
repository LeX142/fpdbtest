<?php

namespace FpDbTest\Grammar;

use FpDbTest\Grammar\Factory\GrammarArrayFactory;
use FpDbTest\Grammar\Factory\GrammarDefaultFactory;
use FpDbTest\Grammar\Factory\GrammarFloatFactory;
use FpDbTest\Grammar\Factory\GrammarIdentifierFactory;
use FpDbTest\Grammar\Factory\GrammarIntegerFactory;
use RuntimeException;

class Grammar
{
    private array $specs = [
        '',
        'a',
        'f',
        'd',
        '#',
    ];

    private string $conditionalBlockPattern = '(\{.*?\})';
    private string $specsPattern = '';
    private string $matchPattern = '';

    public function __construct(
        private \mysqli $mysqli,
        private string $skipValue
    ) {
        $this->specsPattern = '(\?[' . implode('', $this->specs) . ']?)';
        $this->matchPattern = sprintf('/%s|%s/', $this->conditionalBlockPattern, $this->specsPattern);
    }

    public static function make(\mysqli $mysqli, string $query, array $args, string $skipValue): string
    {
        $grammar = new static($mysqli, $skipValue);

        return $grammar->prepareQuery($query, $args);
    }

    public function prepareQuery(string $query, mixed $args): string
    {
        if (preg_match_all($this->matchPattern, $query, $tokenizedData)) {
            foreach ($tokenizedData[0] as $key => $macro) {
                $arg = $args[$key] ?? throw new \RuntimeException('Invalid number of input arguments');
                if (!$this->isConditionalBlock($macro)) {
                    $value = $this->getBindingValueByType($macro, $arg);
                } else {
                    $value = $this->prepareConditionalBlock($macro, $arg);
                }
                $query = substr_replace($query, (string)$value, strpos($query, $macro), strlen($macro));
            }
        }

        return $query;
    }

    private function isConditionalBlock(string $block): bool
    {
        return str_starts_with($block, '{') && str_ends_with($block, '}');
    }

    private function checkBlockNested(string $conditionalBlock): void
    {
        if (str_contains($conditionalBlock, '{') || str_contains($conditionalBlock, '}')) {
            throw new \RuntimeException('Conditional block cannot be nested.');
        }
    }

    private function prepareConditionalBlock(string $block, mixed $arg): string
    {
        $conditionalBlock = trim($block, '{}');

        $this->checkBlockNested($conditionalBlock);

        $macroValue = is_array($arg) ? [...$arg] : [$arg];
        $macroValueFiltered = array_filter($macroValue, fn($v) => $this->skipValue !== $v);

        return (count($macroValue) === count($macroValueFiltered)) ?
            $this->prepareQuery($conditionalBlock, $macroValue) :
            '';
    }

    /**
     * @throws RuntimeException
     */
    private function getBindingValueByType(string $type, mixed $arg): mixed
    {
        $className = match ($type) {
            '?' => GrammarDefaultFactory::class,
            '?d' => GrammarIntegerFactory::class,
            '?f' => GrammarFloatFactory::class,
            '?#' => GrammarIdentifierFactory::class,
            '?a' => GrammarArrayFactory::class,
            default => null
        };

        if (!$className) {
            throw new \RuntimeException(
                'Invalid macro <' . $type . '> value in query: ' . PHP_EOL . var_export($arg, true)
            );
        }

        return (new $className($this->mysqli))($arg);
    }
}