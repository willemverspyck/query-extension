<?php

declare(strict_types=1);

namespace Spyck\QueryExtension\Query;

use DateTimeInterface;
use Spyck\QueryExtension\Exception\ParameterException;
use Spyck\QueryExtension\Parameter\Parameter;
use Spyck\QueryExtension\Parameter\ParameterInterface;

abstract class AbstractQuery implements QueryInterface
{
    private array $with = [];
    private array $select = [];
    private string $from;
    private array $join = [];
    private array $where = [];
    private array $groupBy = [];
    private array $having = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $parameters = [];

    public function getQuote(array|string $fields): string
    {
        return implode('.', array_map(function (string $field): string {
            return sprintf('`%s`', $field);
        }, is_array($fields) ? $fields : explode('.', $fields)));
    }

    /**
     * @throws ParameterException
     */
    public function with(QueryInterface $query, array|string $table): self
    {
        $this->with = [];

        return $this->addWith($query, $table);
    }

    /**
     * @throws ParameterException
     */
    public function addWith(QueryInterface $query, array|string $table): self
    {
        $this->with[] = sprintf('%s AS (%s)', $this->getQuote($table), $query->getQuery());

        foreach ($query->getParameters() as $parameter) {
            $this->addParameter($parameter->getName(), $parameter->getData(), $parameter->getType());
        }

        return $this;
    }

    public function select(string $field, ?string $alias = null): self
    {
        $this->select = [];

        return $this->addSelect($field, $alias);
    }

    public function addSelect(string $field, ?string $alias = null): self
    {
        $this->select[] = null === $alias ? $field : sprintf('%s AS %s', $field, $alias);

        return $this;
    }

    public function from(array|string $table, string $alias): self
    {
        $this->from = sprintf('%s AS %s', $this->getQuote($table), $alias);

        return $this;
    }

    public function innerJoin(array|string $table, string $alias, array $conditions): self
    {
        $this->join[] = sprintf('INNER JOIN %s AS %s ON %s', $this->getQuote($table), $alias, $this->getCondition($conditions));

        return $this;
    }

    public function leftJoin(array|string $table, string $alias, array $conditions): self
    {
        $this->join[] = sprintf('LEFT JOIN %s AS %s ON %s', $this->getQuote($table), $alias, $this->getCondition($conditions));

        return $this;
    }

    public function where(string $condition): self
    {
        $this->where = [];

        return $this->andWhere($condition);
    }

    public function andWhere(string $condition): self
    {
        $this->where[] = sprintf('(%s)', $condition);

        return $this;
    }

    public function groupBy(string $field): self
    {
        $this->groupBy = [];

        return $this->addGroupBy($field);
    }

    public function addGroupBy(string $field): self
    {
        $this->groupBy[] = $field;

        return $this;
    }

    public function having(string $field): self
    {
        $this->having = [];

        return $this->andHaving($field);
    }

    public function andHaving(string $field): self
    {
        $this->having[] = $field;

        return $this;
    }

    public function orderBy(string $field, ?string $direction = null): self
    {
        $this->orderBy = [];

        return $this->addOrderBy($field, $direction);
    }

    public function addOrderBy(string $field, ?string $direction = null): self
    {
        $this->orderBy[] = null === $direction ? $field : sprintf('%s %s', $field, $direction);

        return $this;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function setOffset(?int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function getQuery(): string
    {
        $parts = [];

        if (count($this->with) > 0) {
            $parts[] = sprintf('WITH %s', implode(', ', $this->with));
        }

        $parts[] = sprintf('SELECT %s', implode(', ', $this->select));
        $parts[] = sprintf('FROM %s', $this->from);

        if (count($this->join) > 0) {
            $parts = array_merge($parts, $this->join);
        }

        if (count($this->where) > 0) {
            $parts[] = sprintf('WHERE %s', implode(' AND ', $this->where));
        }

        if (count($this->groupBy) > 0) {
            $parts[] = sprintf('GROUP BY %s', implode(', ', $this->groupBy));
        }

        if (count($this->having) > 0) {
            $parts[] = sprintf('HAVING %s', implode(', ', $this->having));
        }

        if (count($this->orderBy) > 0) {
            $parts[] = sprintf('ORDER BY %s', implode(', ', $this->orderBy));
        }

        if (null !== $this->limit) {
            $parts[] = sprintf('LIMIT %d', $this->limit);
        }

        if (null !== $this->offset) {
            $parts[] = sprintf('OFFSET %d', $this->offset);
        }

        return implode(' ', $parts);
    }

    /**
     * @throws ParameterException
     */
    public function addParameter(string $name, array|DateTimeInterface|int|float|string|null $data, string $type = ParameterInterface::TYPE_STRING): self
    {
        if (array_key_exists($name, $this->parameters)) {
            throw new ParameterException(sprintf('Parameter "%s" already exists', $name));
        }

        $this->parameters[$name] = new Parameter($name, $data, $type);

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    private function getCondition(array $conditions): string
    {
        $conditions = array_filter($conditions, function (?string $condition): bool {
            return null !== $condition;
        });

        return implode(' AND ', array_map(function (string $condition): string {
            return sprintf('(%s)', $condition);
        }, $conditions));
    }
}
