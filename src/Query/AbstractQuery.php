<?php

declare(strict_types=1);

namespace Spyck\QueryExtension\Query;

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
    private array $parameters = [];

    public function getQuote(array $fields): string
    {
        return implode('.', array_map(function (string $field): string {
            return sprintf('`%s`', $field);
        }, $fields));
    }

    public function with(AbstractQuery $query, string $alias): self
    {
        $this->with = [];

        return $this->addWith($query, $alias);
    }

    public function addWith(AbstractQuery $query, string $alias): self
    {
        $this->with[] = sprintf('`%s` AS (%s)', $alias, $query->getQuery());

        return $this;
    }

    public function select(string $field, ?string $alias = null): self
    {
        $this->select = [];

        return $this->addSelect($field, $alias);
    }

    public function addSelect(string $field, ?string $alias = null): self
    {
        $this->select[] = null === $alias ? $field : sprintf('%s.%s', $alias, $field);

        return $this;
    }

    public function from(string $table, string $alias): self
    {
        $this->from = sprintf('`%s` AS `%s`', $table, $alias);

        return $this;
    }

    public function innerJoin(string $table, string $alias, array $conditions): self
    {
        $this->join[] = sprintf('INNER JOIN `%s` AS `%s` ON %s', $table, $alias, $this->getCondition($conditions));

        return $this;
    }

    public function leftJoin(string $table, string $alias, array $conditions): self
    {
        $this->join[] = sprintf('LEFT JOIN `%s` AS `%s` ON %s', $table, $alias, $this->getCondition($conditions));

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

    public function groupBy(string $field, ?string $alias = null): self
    {
        $this->groupBy = [];

        return $this->addGroupBy($field, $alias);
    }

    public function addGroupBy(string $field, ?string $alias = null): self
    {
        $this->groupBy[] = null === $alias ? $field : sprintf('%s.%s', $alias, $field);

        return $this;
    }

    public function having(string $field, ?string $alias = null): self
    {
        $this->having = [];

        return $this->andHaving($field, $alias);
    }

    public function andHaving(string $field, ?string $alias = null): self
    {
        $this->having[] = null === $alias ? $field : sprintf('%s.%s', $alias, $field);

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

        return implode(' ', $parts);
    }

    public function addParameter(string $name, mixed $value): self
    {
        $this->parameters[$name] = $value;

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
