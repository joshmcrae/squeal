<?php

namespace Squeal;

use InvalidArgumentException;

class Builder
{
    /**
     * Query WHERE clauses.
     *
     * @var array
     */
    private array $where = [];

    /**
     * Query ORDER BY clauses.
     * 
     * @var array
     */
    private array $orderBy = [];

    /**
     * Bound parameters.
     *
     * @var mixed[]
     */
    private array $params = [];

    /**
     * Builder constructor.
     *
     * @param Database $db
     * @param string $table
     */
    public function __construct(private readonly Database $db, private readonly string $table)
    {
    }

    /**
     * Adds a WHERE clause to the query.
     *
     * @param string $column
     * @param mixed ...$args
     * @return $this
     */
    public function where(string $column, ...$args): self
    {
        foreach ($args as $operator => $value) {
            switch ($operator) {
            case 'eq':
                if (is_null($value)) {
                    $this->where[] = sprintf('%s is null', $column);
                    break;
                }

                $this->where[] = sprintf('%s = ?', $column);
                $this->params[] = $value;

                break;
            case 'neq':
                if (is_null($value)) {
                    $this->where[] = sprintf('%s is not null', $column);
                    break;
                }

                $this->where[] = sprintf('%s != ?', $column);
                $this->params[] = $value;

                break;
            case 'gt':
                $this->where[] = sprintf('%s > ?', $column);
                $this->params[] = $value;

                break;
            case 'gte':
                $this->where[] = sprintf('%s >= ?', $column);
                $this->params[] = $value;

                break;
            case 'lt':
                $this->where[] = sprintf('%s < ?', $column);
                $this->params[] = $value;

                break;
            case 'lte':
                $this->where[] = sprintf('%s <= ?', $column);
                $this->params[] = $value;

                break;
            case 'in':
                $value = (array) $value;

                if (empty($value)) {
                    throw new InvalidArgumentException('IN requires a non-empty array of values.');
                }

                $in = implode(', ', str_split(str_repeat('?', count($value))));
                $this->where[] = sprintf('%s in (%s)', $column, $in);
                $this->params = array_merge($this->params, $value);

                break;
            case 'nin':
                $value = (array) $value;

                if (empty($value)) {
                    throw new InvalidArgumentException('NOT IN requires a non-empty array of values.');
                }

                $in = implode(', ', str_split(str_repeat('?', count($value))));
                $this->where[] = sprintf('%s not in (%s)', $column, $in);
                $this->params = array_merge($this->params, $value);

                break;
            }
        }

        return $this;
    }

    /**
     * Executes a callable within which all new conditions
     * apply in a boolean AND expression.
     *
     * @param callable $fn
     * @return self
     */
    public function and(callable $fn)
    {
        $where = $this->where;
        $this->where = [];

        $fn($this);

        if (!empty($this->where)) {
            $conditions = implode(' and ', $this->where);

            $this->where = $where;
            $this->where[] = empty($where) ? $conditions : sprintf('(%s)', $conditions);
        } else {
            $this->where = $where;
        }

        return $this;
    }

    /**
     * Executes a callable within which all new conditions
     * apply in a boolean OR expression.
     *
     * @param callable $fn
     * @return self
     */
    public function or(callable $fn)
    {
        $where = $this->where;
        $this->where = [];

        $fn($this);

        if (!empty($this->where)) {
            $conditions = implode(' or ', $this->where);

            $this->where = $where;
            $this->where[] = empty($where) ? $conditions : sprintf('(%s)', $conditions);
        } else {
            $this->where = $where;
        }

        return $this;
    }

    /**
     * Adds an ORDER BY clause to the query.
     *
     * @param string $column
     * @param string $direction
     * @return self
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderBy[] = sprintf('%s %s', $column, $direction);

        return $this;
    }

    /**
     * Executes a select query.
     *
     * @return Result
     */
    public function select(array $columns = ['*']): Result
    {
        $where = '';
        $orderBy = '';

        if (!empty($this->where)) {
            $where = ' where ' . implode(' and ', $this->where);
        }

        if (!empty($this->orderBy)) {
            $orderBy = ' order by ' . implode(', ', $this->orderBy);
        }

        $sql = sprintf(
            'select %s from %s%s%s',
            implode(', ', $columns),
            $this->table,
            $where,
            $orderBy
        );

        return $this
            ->db
            ->exec($sql, $this->params);
    }

    /**
     * Executes an insert query.
     *
     * @param array<string,mixed> $values
     */
    public function insert(array $values): Result
    {
        $columns = array_keys($values);
        $params = array_values($values);

        $sql = sprintf(
            'insert into %s (%s) values (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', str_split(str_repeat('?', count($values))))
        );

        return $this
            ->db
            ->exec($sql, $params);
    }

    /**
     * Executes an update query.
     *
     * @param array<string,mixed> $values
     * @return Result
     */
    public function update(array $values): Result
    {
        $set = [];
        $params = [];
        $where = '';

        if (!empty($this->where)) {
            $where = ' where ' . implode('and ', $this->where);
        }

        if (empty($values)) {
            throw new \InvalidArgumentException('UPDATE requires values to set.');
        }

        foreach ($values as $column => $value) {
            $set[] = sprintf('%s = ?', $column);
            $params[] = $value;
        }

        $sql = sprintf(
            'update %s set %s%s',
            $this->table,
            implode(', ', $set),
            $where
        );

        return $this
            ->db
            ->exec($sql, array_merge($params, $this->params));
    }

    /**
     * Executes a delete query.
     *
     * @return Result
     */
    public function delete(): Result
    {
        $where = '';

        if (!empty($this->where)) {
            $where = ' where ' . implode('and ', $this->where);
        }

        $sql = sprintf(
            'delete from %s%s',
            $this->table,
            $where
        );

        return $this
            ->db
            ->exec($sql, $this->params);
    }
}
