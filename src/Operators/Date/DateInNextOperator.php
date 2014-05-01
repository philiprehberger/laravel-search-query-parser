<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Date;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class DateInNextOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        $amount = $value['amount'] ?? 7;
        $unit = $value['unit'] ?? 'days';

        $date = match ($unit) {
            'days' => now()->addDays($amount),
            'weeks' => now()->addWeeks($amount),
            'months' => now()->addMonths($amount),
            'years' => now()->addYears($amount),
            default => now()->addDays($amount),
        };

        return $query->where($field, '>=', now())
            ->where($field, '<=', $date);
    }

    public function getLabel(): string
    {
        return 'in the next';
    }

    public function getInputType(): string
    {
        return 'duration';
    }
}
