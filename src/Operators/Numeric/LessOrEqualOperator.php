<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Numeric;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class LessOrEqualOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        return $query->where($field, '<=', $value);
    }

    public function getLabel(): string
    {
        return 'less than or equal';
    }

    public function getInputType(): string
    {
        return 'number';
    }
}
