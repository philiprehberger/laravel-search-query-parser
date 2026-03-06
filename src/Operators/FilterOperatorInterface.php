<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators;

use Illuminate\Database\Eloquent\Builder;

interface FilterOperatorInterface
{
    /**
     * Apply the filter operator to the query.
     */
    public function apply(Builder $query, string $field, mixed $value): Builder;

    /**
     * Get the human-readable label for this operator.
     */
    public function getLabel(): string;

    /**
     * Get the input type for this operator's value.
     */
    public function getInputType(): string;

    /**
     * Check if the operator requires a value.
     */
    public function requiresValue(): bool;
}
