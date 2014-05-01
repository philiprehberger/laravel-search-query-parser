<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Relation;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class HasNotRelationOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        return $query->doesntHave($field);
    }

    public function getLabel(): string
    {
        return 'does not have';
    }

    public function requiresValue(): bool
    {
        return false;
    }
}
