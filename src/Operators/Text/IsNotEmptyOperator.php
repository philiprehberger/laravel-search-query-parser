<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Text;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class IsNotEmptyOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        return $query->whereNotNull($field)
            ->where($field, '!=', '');
    }

    public function getLabel(): string
    {
        return 'is not empty';
    }

    public function requiresValue(): bool
    {
        return false;
    }
}
