<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Text;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class StartsWithOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        return $query->where($field, 'like', "{$value}%");
    }

    public function getLabel(): string
    {
        return 'starts with';
    }
}
