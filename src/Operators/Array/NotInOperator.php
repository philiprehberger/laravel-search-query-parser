<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Array;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class NotInOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        $values = is_array($value) ? $value : explode(',', $value);

        return $query->whereNotIn($field, $values);
    }

    public function getLabel(): string
    {
        return 'is not any of';
    }

    public function getInputType(): string
    {
        return 'multiselect';
    }
}
