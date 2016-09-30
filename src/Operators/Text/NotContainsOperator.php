<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Operators\Text;

use Illuminate\Database\Eloquent\Builder;
use PhilipRehberger\SearchQueryParser\Operators\AbstractOperator;

class NotContainsOperator extends AbstractOperator
{
    public function apply(Builder $query, string $field, mixed $value): Builder
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $value);

        return $query->whereRaw(
            sprintf('"%s" not like ? escape \'\\\'', str_replace('"', '""', $field)),
            ["%{$escaped}%"]
        );
    }

    public function getLabel(): string
    {
        return 'does not contain';
    }
}
