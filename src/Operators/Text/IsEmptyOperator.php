<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Text;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class IsEmptyOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        return $query->where(function (Builder $q) use ($field) {
            $q->whereNull($field)
                ->orWhere($field, '=', '');
        });
    }

    public function getLabel(): string
    {
        return 'is empty';
    }

    public function requiresValue(): bool
    {
        return false;
    }
}
