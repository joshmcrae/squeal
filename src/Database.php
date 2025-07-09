<?php

namespace Squeal;

use PDO;

class Database
{
    /**
     * Database connection.
     *
     * @var PDO
     */
    readonly private PDO $conn;

    /**
     * Whether to log queries.
     *
     * @var bool
     */
    public bool $logQueries = false;

    /**
     * Logged SQL queries.
     *
     * @var string[]
     */
    public array $log;

    /**
     * Creates a new database object from a file.
     *
     * @param string $path
     * @return static
     */
    public static function fromFile(string $path): static
    {
        return new static($path);
    }

    /**
     * Creates a new in-memory database object.
     *
     * @return static
     */
    public static function inMemory(): static
    {
        return new static(':memory:');
    }

    /**
     * Database constructor.
     *
     * @param string $dsn
     */
    public function __construct(string $dsn)
    {
        $this->conn = new PDO(sprintf('sqlite:%s', $dsn));
        $this->log = [];
    }

    /**
     * Executes a SQL query.
     *
     * @return Result
     */
    public function exec(string $sql, array $params = []): Result
    {
        $sql = $this->renderFragments($sql, $params);

        if ($this->logQueries) {
            $this->log[] = $sql;
        }

        $statement = $this
            ->conn
            ->prepare($sql);

        $statement->execute($params);

        return new Result($statement, $this->conn->lastInsertId());
    }

    /**
     * Renders conditional fragments of a SQL query based on
     * the presence of named parameters contained in those
     * fragments.
     *
     * @param string $sql
     * @param array $params
     * @return string
     */
    private function renderFragments(string $sql, array $params): string
    {
        $fragments = preg_split(
            '/\[\[(.+)\]\]/', 
            $sql, 
            -1, 
            PREG_SPLIT_DELIM_CAPTURE
        );

        $rendered = '';

        foreach ($fragments as $offset => $fragment) {
            if ($offset % 2 === 0) {
                $rendered .= $fragment;
                continue;
            }

            preg_match_all('/:(.+)/', $fragment, $matched);

            foreach ($matched[1] ?? [] as $name) {
                if (!isset($params[$name]) && !isset($params[':' . $name])) {
                    continue;
                }
            }

            $rendered .= $fragment;
        }

        return trim($rendered);
    }

    /**
     * Starts a query builder for a table.
     *
     * @param string $table
     * @return Builder
     */
    public function table(string $table): Builder
    {
        return new Builder($this, $table);
    }

    /**
     * Returns a directory of executable queries.
     *
     * @param string $path
     * @param string $extension
     * @return Directory
     */
    public function directory(string $path, string $extension = '.sql')
    {
        return new Directory(
            $this,
            $path,
            $extension
        );
    }
}
