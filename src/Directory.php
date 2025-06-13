<?php

namespace Squeal;

use InvalidArgumentException;

class Directory
{
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
}
