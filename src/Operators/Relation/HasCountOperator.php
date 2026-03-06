<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Relation;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class HasCountOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        $operator = $value['operator'] ?? '>=';
        $count = $value['count'] ?? 1;

        return $query->has($field, $operator, $count);
    }

    public function getLabel(): string
    {
        return 'has count';
    }

    public function getInputType(): string
    {
        return 'count';
    }
}
