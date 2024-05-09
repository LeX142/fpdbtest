<?php

declare(strict_types=1);

namespace FpDbTest;

use FpDbTest\Grammar\Grammar;
use mysqli;
use RuntimeException;

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
        return Grammar::make($this->mysqli,$query, $args, $this->skip());
    }


    public function skip(): mixed
    {
        return 'NULL';
    }

}
