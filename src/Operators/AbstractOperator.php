<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators;

use Illuminate\Database\Eloquent\Builder;

abstract class AbstractOperator implements FilterOperatorInterface
{
    abstract public function apply(Builder $query, string $field, mixed $value): Builder;

    abstract public function getLabel(): string;

    public function getInputType(): string
    {
        return 'text';
    }

    public function requiresValue(): bool
    {
        return true;
    }
}
