<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser;

class QueryParser
{
    /**
     * Supported comparison operators for field:operator:value syntax.
     * Order matters - longer operators must come first to avoid partial matches.
     */
    private const COMPARISON_OPERATORS = [
        '>=' => 'greater_or_equal',
        '<=' => 'less_or_equal',
        '!=' => 'not_equals',
        '>' => 'greater_than',
        '<' => 'less_than',
        '=' => 'equals',
    ];

    /**
     * Special field prefixes for relations.
     */
    private const RELATION_PREFIXES = [
        'has:' => 'has',
        'no:' => 'has_not',
    ];

    /**
     * Parse a search query string into structured components.
     */
    public function parse(string $query): ParsedQuery
    {
        $tokens = $this->tokenize($query);
        $filters = [];
        $textSearch = [];
        $excludeTerms = [];

        foreach ($tokens as $token) {
            if ($this->isExcludeToken($token)) {
                $excludeTerms[] = $this->parseExcludeToken($token);
            } elseif ($this->isRelationFilter($token)) {
                $filters[] = $this->parseRelationFilter($token);
            } elseif ($this->isFieldFilter($token)) {
                $filters[] = $this->parseFieldFilter($token);
            } else {
                $textSearch[] = $this->cleanToken($token);
            }
        }

        return new ParsedQuery(
            textSearch: implode(' ', array_filter($textSearch)),
            filters: $filters,
            excludeTerms: $excludeTerms,
        );
    }

    /**
     * Tokenize the query string, respecting quoted strings.
     *
     * @return array<string>
     */
    private function tokenize(string $query): array
    {
        $tokens = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';

        $chars = mb_str_split($query);
        $length = count($chars);

        for ($i = 0; $i < $length; $i++) {
            $char = $chars[$i];

            if (($char === '"' || $char === "'") && ! $inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                $inQuotes = false;
                $quoteChar = '';
                $current .= $char;
            } elseif ($char === ' ' && ! $inQuotes) {
                if ($current !== '') {
                    $tokens[] = $current;
                    $current = '';
                }
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $tokens[] = $current;
        }

        return $tokens;
    }

    /**
     * Check if token is an exclusion (starts with -).
     */
    private function isExcludeToken(string $token): bool
    {
        return str_starts_with($token, '-') && strlen($token) > 1;
    }

    /**
     * Parse an exclusion token.
     */
    private function parseExcludeToken(string $token): string
    {
        $term = substr($token, 1);

        return $this->cleanToken($term);
    }

    /**
     * Check if token is a relation filter (has:relation or no:relation).
     */
    private function isRelationFilter(string $token): bool
    {
        foreach (array_keys(self::RELATION_PREFIXES) as $prefix) {
            if (str_starts_with(strtolower($token), $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse a relation filter token.
     *
     * @return array{field: string, operator: string, value: mixed}
     */
    private function parseRelationFilter(string $token): array
    {
        $lowerToken = strtolower($token);
        $field = $token;
        $operator = 'has';

        foreach (self::RELATION_PREFIXES as $prefix => $prefixOperator) {
            if (str_starts_with($lowerToken, $prefix)) {
                $field = substr($token, strlen($prefix));
                $operator = $prefixOperator;

                break;
            }
        }

        return [
            'field' => $field,
            'operator' => $operator,
            'value' => null,
        ];
    }

    /**
     * Check if token is a field filter (field:value or field:operator:value).
     */
    private function isFieldFilter(string $token): bool
    {
        // Must contain : but not start with it
        if (! str_contains($token, ':') || str_starts_with($token, ':')) {
            return false;
        }

        // Don't match URLs
        if (preg_match('/^https?:\/\//', $token)) {
            return false;
        }

        // Field name should only contain letters, numbers, underscores
        $colonPos = strpos($token, ':');
        $field = substr($token, 0, $colonPos);

        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field);
    }

    /**
     * Parse a field filter token.
     *
     * Supported formats:
     * - field:value (equals)
     * - field:>value (greater than)
     * - field:<value (less than)
     * - field:>=value (greater or equal)
     * - field:<=value (less or equal)
     * - field:!=value (not equals)
     * - field:value1,value2,value3 (in array)
     *
     * @return array{field: string, operator: string, value: mixed}
     */
    private function parseFieldFilter(string $token): array
    {
        [$field, $rest] = explode(':', $token, 2);

        // Check for comparison operators at the start of value
        foreach (self::COMPARISON_OPERATORS as $symbol => $operator) {
            if (str_starts_with($rest, $symbol)) {
                $value = substr($rest, strlen($symbol));

                return [
                    'field' => $field,
                    'operator' => $operator,
                    'value' => $this->cleanToken($value),
                ];
            }
        }

        // Check for comma-separated values (in operator)
        if (str_contains($rest, ',')) {
            $values = array_map(
                fn ($v) => $this->cleanToken($v),
                explode(',', $rest)
            );

            return [
                'field' => $field,
                'operator' => 'in',
                'value' => $values,
            ];
        }

        // Default to equals
        return [
            'field' => $field,
            'operator' => 'equals',
            'value' => $this->cleanToken($rest),
        ];
    }

    /**
     * Clean a token by removing surrounding quotes.
     */
    private function cleanToken(string $token): string
    {
        // Remove surrounding quotes
        if (
            (str_starts_with($token, '"') && str_ends_with($token, '"')) ||
            (str_starts_with($token, "'") && str_ends_with($token, "'"))
        ) {
            return substr($token, 1, -1);
        }

        return $token;
    }

    /**
     * Build a query string from parsed components.
     */
    public function build(ParsedQuery $parsed): string
    {
        $parts = [];

        // Add text search terms
        if ($parsed->hasTextSearch()) {
            $text = $parsed->textSearch;
            // Quote if contains spaces
            if (str_contains($text, ' ')) {
                $text = '"'.$text.'"';
            }
            $parts[] = $text;
        }

        // Add filters
        foreach ($parsed->filters as $filter) {
            $parts[] = $this->buildFilterToken($filter);
        }

        // Add exclude terms
        foreach ($parsed->excludeTerms as $term) {
            $parts[] = '-'.$term;
        }

        return implode(' ', $parts);
    }

    /**
     * Build a filter token string.
     *
     * @param  array{field: string, operator: string, value: mixed}  $filter
     */
    private function buildFilterToken(array $filter): string
    {
        $field = $filter['field'];
        $operator = $filter['operator'];
        $value = $filter['value'];

        // Handle relation operators
        if ($operator === 'has') {
            return "has:{$field}";
        }
        if ($operator === 'has_not') {
            return "no:{$field}";
        }

        // Handle comparison operators
        $symbol = array_search($operator, self::COMPARISON_OPERATORS, true);
        if ($symbol !== false && $symbol !== '=') {
            return "{$field}:{$symbol}{$value}";
        }

        // Handle in operator
        if ($operator === 'in' && is_array($value)) {
            return "{$field}:".implode(',', $value);
        }

        // Default field:value format
        $valueStr = is_array($value) ? json_encode($value) : (string) $value;
        if (str_contains($valueStr, ' ')) {
            $valueStr = '"'.$valueStr.'"';
        }

        return "{$field}:{$valueStr}";
    }

    /**
     * Get available syntax examples for help text.
     *
     * @return array<array{syntax: string, example: string, description: string}>
     */
    public function getSyntaxHelp(): array
    {
        return [
            [
                'syntax' => 'keyword',
                'example' => 'design',
                'description' => 'Search for keyword in all fields',
            ],
            [
                'syntax' => '"phrase"',
                'example' => '"web design"',
                'description' => 'Search for exact phrase',
            ],
            [
                'syntax' => 'field:value',
                'example' => 'status:active',
                'description' => 'Filter by specific field',
            ],
            [
                'syntax' => 'field:>value',
                'example' => 'amount:>1000',
                'description' => 'Greater than comparison',
            ],
            [
                'syntax' => 'field:<value',
                'example' => 'date:<2026-01-01',
                'description' => 'Less than comparison',
            ],
            [
                'syntax' => 'field:>=value',
                'example' => 'total:>=500',
                'description' => 'Greater than or equal',
            ],
            [
                'syntax' => 'field:<=value',
                'example' => 'hours:<=40',
                'description' => 'Less than or equal',
            ],
            [
                'syntax' => 'field:!=value',
                'example' => 'status:!=archived',
                'description' => 'Not equal comparison',
            ],
            [
                'syntax' => 'field:value1,value2',
                'example' => 'status:active,pending',
                'description' => 'Match any of values',
            ],
            [
                'syntax' => '-keyword',
                'example' => '-archived',
                'description' => 'Exclude keyword',
            ],
            [
                'syntax' => 'has:relation',
                'example' => 'has:invoices',
                'description' => 'Has related records',
            ],
            [
                'syntax' => 'no:relation',
                'example' => 'no:projects',
                'description' => 'Has no related records',
            ],
        ];
    }
}
