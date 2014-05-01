<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Numeric;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class NotBetweenOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        $min = $value['min'] ?? $value[0] ?? null;
        $max = $value['max'] ?? $value[1] ?? null;

        return $query->whereNotBetween($field, [$min, $max]);
    }

    public function getLabel(): string
    {
        return 'not between';
    }

    public function getInputType(): string
    {
        return 'range';
    }
}
