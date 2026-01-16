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

    public function getQuote(array|string $fields, bool $quote = false): string
    {
        if ($quote) {
            return implode('.', array_map(function (string $field): string {
                return sprintf('`%s`', $field);
            }, is_array($fields) ? $fields : explode('.', $fields)));
        }

        return is_array($fields) ? implode('.', $fields) : $fields;
    }

    /**
     * @throws ParameterException
     */
    public function with(QueryInterface $query, array|string $table): static
    {
        $this->with = [];

        return $this->addWith($query, $table);
    }

    /**
     * @throws ParameterException
     */
    public function addWith(QueryInterface $query, array|string $table): static
    {
        $this->with[] = sprintf('%s AS (%s)', $this->getQuote($table), $query->getQuery());

        foreach ($query->getParameters() as $parameter) {
            $this->addParameter($parameter->getName(), $parameter->getData(), $parameter->getType());
        }

        return $this;
    }

    public function select(array|string $field, ?string $alias = null, bool $quote = false): static
    {
        $this->select = [];

        return $this->addSelect($field, $alias, $quote);
    }

    public function addSelect(array|string $field, ?string $alias = null, bool $quote = false): static
    {
        $this->select[] = null === $alias ? $this->getQuote($field, $quote) : sprintf('%s AS %s', $this->getQuote($field, $quote), $alias);

        return $this;
    }

    public function from(array|string $table, string $alias, bool $quote = false): static
    {
        $this->from = sprintf('%s AS %s', $this->getQuote($table, $quote), $alias);

        return $this;
    }

    public function innerJoin(array|string $table, string $alias, array $conditions, bool $quote = false): static
    {
        $this->join[] = sprintf('INNER JOIN %s AS %s ON %s', $this->getQuote($table, $quote), $alias, $this->getCondition($conditions));

        return $this;
    }

    public function leftJoin(array|string $table, string $alias, array $conditions, bool $quote = false): static
    {
        $this->join[] = sprintf('LEFT JOIN %s AS %s ON %s', $this->getQuote($table, $quote), $alias, $this->getCondition($conditions));

        return $this;
    }

    public function where(string $condition): static
    {
        $this->where = [];

        return $this->andWhere($condition);
    }

    public function andWhere(string $condition): static
    {
        $this->where[] = sprintf('(%s)', $condition);

        return $this;
    }

    public function groupBy(array|string $field, bool $quote = false): static
    {
        $this->groupBy = [];

        return $this->addGroupBy($field, $quote);
    }

    public function addGroupBy(array|string $field, bool $quote = false): static
    {
        $this->groupBy[] = $this->getQuote($field, $quote);

        return $this;
    }

    public function having(array|string $field, bool $quote = false): static
    {
        $this->having = [];

        return $this->andHaving($field, $quote);
    }

    public function andHaving(array|string $field, bool $quote = false): static
    {
        $this->having[] = $this->getQuote($field, $quote);

        return $this;
    }

    public function orderBy(array|string $field, ?string $direction = null, bool $quote = false): static
    {
        $this->orderBy = [];

        return $this->addOrderBy($field, $direction, $quote);
    }

    public function addOrderBy(array|string $field, ?string $direction = null, bool $quote = false): static
    {
        $this->orderBy[] = null === $direction ? $this->getQuote($field) : sprintf('%s %s', $this->getQuote($field), $direction);

        return $this;
    }

    public function setLimit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function setOffset(?int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @throws ParameterException
     */
    public function addParameter(string $name, array|DateTimeInterface|int|float|string|null $data, string $type = ParameterInterface::TYPE_STRING): static
    {
        if (array_key_exists($name, $this->parameters)) {
            throw new ParameterException(sprintf('Parameter "%s" already exists', $name));
        }

        $this->parameters[$name] = new Parameter($name, $data, $type);

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
