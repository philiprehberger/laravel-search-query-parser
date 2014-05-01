<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Date;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class DateBetweenOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        $start = $value['start'] ?? $value[0] ?? null;
        $end = $value['end'] ?? $value[1] ?? null;

        return $query->whereBetween($field, [$start, $end]);
    }

    public function getLabel(): string
    {
        return 'between dates';
    }

    public function getInputType(): string
    {
        return 'daterange';
    }
}
