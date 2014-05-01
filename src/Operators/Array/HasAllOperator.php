<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Array;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class HasAllOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        $values = is_array($value) ? $value : explode(',', $value);

        return $query->where(function (Builder $q) use ($field, $values) {
            foreach ($values as $val) {
                $q->whereJsonContains($field, $val);
            }
        });
    }

    public function getLabel(): string
    {
        return 'has all of';
    }

    public function getInputType(): string
    {
        return 'multiselect';
    }
}
