<?php

declare(strict_types=1);

namespace Spyck\QueryExtension\Query;

use Spyck\QueryExtension\Parameter\ParameterInterface;

interface QueryInterface
{
    public const string ASC = 'ASC';
    public const string DESC = 'DESC';

    public function getQuery(): string;

    /**
     * @return array<int, ParameterInterface>
     */
    public function getParameters(): array;
}
