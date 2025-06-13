<?php

namespace Squeal;

use InvalidArgumentException;

class Directory
{
    /**
     * The name of the migrations table.
     *
     * @param string
     */
    private ?string $migrations = null;

    public function __construct(
        private readonly Database $db,
        private readonly string $path,
        private readonly string $extension
    ) {
    }

    /**
     * Lists all executable queries in the directory.
     *
     * @return string[]
     */
    public function list(): array
    {
        $dir = dir($this->path);
        $items = [];

        while ($item = $dir->read()) {
            if (str_ends_with($item, $this->extension)) {
                $items[] = str_replace($this->extension, '', $item);
            }
        }

        sort($items);

        return $items;
    }

    /**
     * Executes a query by name.
     *
     * @param string $name
     * @param mixed[] $params
     * @return Result
     */
    public function exec(string $name, array $params = [])
    {
        $filename = sprintf('%s/%s%s', $this->path, $name, $this->extension);

        if (!file_exists($filename)) {
            throw new InvalidArgumentException(
                sprintf("File %s does not exist."),
                realpath($filename)
            );
        }

        $sql = file_get_contents($filename);

        return $this
            ->db
            ->exec($sql, $params);
    }

    /**
     * Enables the directory for migrations and tracks
     * the current version in the database.
     *
     * @param string $table
     */
    public function asMigrations(string $table = 'migrations')
    {
        $this
            ->db
            ->exec(sprintf('
                create table if not exists %s (
                    name text primary key,
                    executed_at integer
                )',
                $table
            ));

        $this->migrations = $table;

        return $this;
    }

    /**
     * Executes pending migrations and returns an
     * array of the execute queries by name.
     *
     * @return string[]
     */
    public function up(): array
    {
        if (is_null($this->migrations)) {
            return [];
        }

        $versions = $this->listVersions();
        $executed = [];

        foreach ($this->list() as $query) {
            if (in_array($query, $versions)) {
                continue;
            }

            $this->exec($query);

            $this
                ->db
                ->table($this->migrations)
                ->insert([
                    'name' => $query,
                    'executed_at' => time()
                ]);

            $executed[] = $query;
        }

        return $executed;
    }

    /**
     * Lists all executed migration queries by name.
     *
     * @return string[]
     */
    private function listVersions(): array
    {
        $versions = $this
            ->db
            ->table($this->migrations)
            ->select(['name'])
            ->map(fn ($m) => $m['name']);

        sort($versions);

        return $versions;
    }
}
