<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Relation;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class HasRelationOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        return $query->has($field);
    }

    public function getLabel(): string
    {
        return 'has';
    }

    public function requiresValue(): bool
    {
        return false;
    }
}
