<?php

declare(strict_types=1);

namespace Spyck\QueryExtension\Parameter;

final class Parameter implements ParameterInterface
{
    private string $name;
    private array|float|int|string|null $data;
    private string $type;

    public function __construct(string $name, array|float|int|string|null $data, string $type = ParameterInterface::TYPE_STRING)
    {
        $this->setName($name);
        $this->setData($data);
        $this->setType($type);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getData(): array|float|int|string|null
    {
        return $this->data;
    }

    public function setData(array|float|int|string|null $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
