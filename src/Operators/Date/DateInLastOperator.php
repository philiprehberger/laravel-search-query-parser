<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Date;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class DateInLastOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        $amount = $value['amount'] ?? 7;
        $unit = $value['unit'] ?? 'days';

        $date = match ($unit) {
            'days' => now()->subDays($amount),
            'weeks' => now()->subWeeks($amount),
            'months' => now()->subMonths($amount),
            'years' => now()->subYears($amount),
            default => now()->subDays($amount),
        };

        return $query->where($field, '>=', $date);
    }

    public function getLabel(): string
    {
        return 'in the last';
    }

    public function getInputType(): string
    {
        return 'duration';
    }
}
