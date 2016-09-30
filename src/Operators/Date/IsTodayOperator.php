<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Date;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class IsTodayOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        return $query->whereDate($field, '=', now()->toDateString());
    }

    public function getLabel(): string
    {
        return 'is today';
    }

    public function requiresValue(): bool
    {
        return false;
    }
}
