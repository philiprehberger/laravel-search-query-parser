<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Date;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class IsThisMonthOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        return $query->whereBetween($field, [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }

    public function getLabel(): string
    {
        return 'is this month';
    }

    public function requiresValue(): bool
    {
        return false;
    }
}
