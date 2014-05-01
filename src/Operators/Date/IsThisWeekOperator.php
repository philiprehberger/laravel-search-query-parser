<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Date;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class IsThisWeekOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        return $query->whereBetween($field, [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function getLabel(): string
    {
        return 'is this week';
    }

    public function requiresValue(): bool
    {
        return false;
    }
}
