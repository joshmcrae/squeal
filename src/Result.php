<?php

namespace Squeal;

use PDO;
use PDOStatement;

class Result
{
    /**
     * Result constructor.
     *
     * @param PDOStatement $statement
     */
    public function __construct(readonly private PDOStatement $statement)
    {
        $this
            ->statement
            ->setFetchMode(PDO::FETCH_ASSOC);
    }

    /**
     * Returns the number of rows affected.
     *
     * @return int
     */
    public function rowCount(): int
    {
        return $this
            ->statement
            ->rowCount();
    }

    /**
     * Fetches a single row from the result.
     *
     * @return array|null
     */
    public function one(): ?array
    {
        return $this
            ->statement
            ->fetch();
    }

    /**
     * Fetches all rows from the result.
     *
     * @return array
     */
    public function all(): array
    {
        return $this
            ->statement
            ->fetchAll();
    }

    /**
     * Maps the result set through the provided transformation
     * function.
     *
     * @param callable $transform
     * @return array|null
     */
    public function map(callable $transform)
    {
        $rows = [];

        foreach ($this->statement as $offset => $row) {
            $rows[] = $transform($row, $offset);
        }

        return $rows;
    }
}
