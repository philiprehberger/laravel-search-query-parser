<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Date;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class DateAfterOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        return $query->whereDate($field, '>', $value);
    }

    public function getLabel(): string
    {
        return 'after';
    }

    public function getInputType(): string
    {
        return 'date';
    }
}
