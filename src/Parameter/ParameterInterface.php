<?php

declare(strict_types=1);

namespace Spyck\QueryExtension\Parameter;

interface ParameterInterface
{
    public const string TYPE_ARRAY = 'array';
    public const string TYPE_BOOLEAN = 'boolean';
    public const string TYPE_DATE = 'date';
    public const string TYPE_DATETIME = 'datetime';
    public const string TYPE_FLOAT = 'float';
    public const string TYPE_INTEGER = 'integer';
    public const string TYPE_STRING = 'string';
    public const string TYPE_TIME = 'time';

    public function getName(): string;

    public function setName(string $name): self;

    public function getData(): array|string|int|float|null;

    public function setData(array|string|int|float|null $data): self;

    public function getType(): string;

    public function setType(string $type): self;
}
