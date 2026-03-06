<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser;

readonly class ParsedQuery
{
    /**
     * @param  array<array{field: string, operator: string, value: mixed}>  $filters
     * @param  array<string>  $excludeTerms
     */
    public function __construct(
        public string $textSearch,
        public array $filters = [],
        public array $excludeTerms = [],
    ) {}

    public function hasTextSearch(): bool
    {
        return $this->textSearch !== '';
    }

    public function hasFilters(): bool
    {
        return count($this->filters) > 0;
    }

    public function hasExcludeTerms(): bool
    {
        return count($this->excludeTerms) > 0;
    }

    public function isEmpty(): bool
    {
        return ! $this->hasTextSearch() && ! $this->hasFilters() && ! $this->hasExcludeTerms();
    }

    /**
     * @return array{text_search: string, filters: array, exclude_terms: array}
     */
    public function toArray(): array
    {
        return [
            'text_search' => $this->textSearch,
            'filters' => $this->filters,
            'exclude_terms' => $this->excludeTerms,
        ];
    }
}
