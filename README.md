# laravel-search-query-parser

Parse GitHub-style search queries into structured filters for Laravel Eloquent. Supports `field:value` syntax, comparison operators, comma-separated values, exclusion terms, and relation filters — all from a single search string.

```
"web design" status:active,pending amount:>=500 -archived has:invoices
```

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require philiprehberger/laravel-search-query-parser
```

The service provider is registered automatically via Laravel's package auto-discovery.

## Quick Start

```php
use PhilipRehberger\SearchQueryParser\QueryParser;

$parser = new QueryParser();
$parsed = $parser->parse('design status:active amount:>1000 -archived');

$parsed->textSearch;    // "design"
$parsed->filters;       // [['field' => 'status', 'operator' => 'equals', 'value' => 'active'], ...]
$parsed->excludeTerms;  // ["archived"]

$parsed->hasTextSearch();    // true
$parsed->hasFilters();       // true
$parsed->hasExcludeTerms();  // true
$parsed->isEmpty();          // false
```

You can also resolve `QueryParser` from the container or use the facade:

```php
// Via facade
use PhilipRehberger\SearchQueryParser\Facades\SearchQueryParser;

$parsed = SearchQueryParser::parse('status:active');

// Via dependency injection
public function __construct(private QueryParser $parser) {}
```

## Syntax Reference

| Syntax | Example | Description |
|---|---|---|
| `keyword` | `design` | Plain keyword — goes into `textSearch` |
| `"phrase"` | `"web design"` | Quoted phrase — treated as a single text search term |
| `field:value` | `status:active` | Exact match filter (`equals` operator) |
| `field:>value` | `amount:>1000` | Greater than comparison |
| `field:<value` | `date:<2026-01-01` | Less than comparison |
| `field:>=value` | `total:>=500` | Greater than or equal |
| `field:<=value` | `hours:<=40` | Less than or equal |
| `field:!=value` | `status:!=archived` | Not equal comparison |
| `field:v1,v2,v3` | `status:active,pending` | Match any of (comma-separated → `in` operator) |
| `-keyword` | `-archived` | Exclude keyword — goes into `excludeTerms` |
| `has:relation` | `has:invoices` | Has related records |
| `no:relation` | `no:projects` | Has no related records |

### Notes

- Longer operators (`>=`, `<=`, `!=`) are checked before shorter ones (`>`, `<`, `=`) to prevent partial matches.
- URLs (`https://...`, `http://...`) are never treated as field filters.
- Field names must match `/^[a-zA-Z_][a-zA-Z0-9_]*$/` — digits-first tokens fall through to text search.
- `has:` and `no:` prefix matching is case-insensitive.

## Operator Reference

### Text Operators

| Class | Label | Input Type | Requires Value |
|---|---|---|---|
| `ContainsOperator` | contains | text | yes |
| `NotContainsOperator` | does not contain | text | yes |
| `EqualsOperator` | equals | text | yes |
| `NotEqualsOperator` | does not equal | text | yes |
| `StartsWithOperator` | starts with | text | yes |
| `EndsWithOperator` | ends with | text | yes |
| `IsEmptyOperator` | is empty | text | no |
| `IsNotEmptyOperator` | is not empty | text | no |

### Numeric Operators

| Class | Label | Input Type |
|---|---|---|
| `GreaterThanOperator` | greater than | number |
| `GreaterOrEqualOperator` | greater than or equal | number |
| `LessThanOperator` | less than | number |
| `LessOrEqualOperator` | less than or equal | number |
| `BetweenOperator` | between | range |
| `NotBetweenOperator` | not between | range |

`BetweenOperator` and `NotBetweenOperator` accept a value of `['min' => x, 'max' => y]` or `[x, y]`.

### Date Operators

| Class | Label | Input Type | Requires Value |
|---|---|---|---|
| `DateEqualsOperator` | on date | date | yes |
| `DateBeforeOperator` | before | date | yes |
| `DateAfterOperator` | after | date | yes |
| `DateBetweenOperator` | between dates | daterange | yes |
| `DateInLastOperator` | in the last | duration | yes |
| `DateInNextOperator` | in the next | duration | yes |
| `IsTodayOperator` | is today | text | no |
| `IsThisWeekOperator` | is this week | text | no |
| `IsThisMonthOperator` | is this month | text | no |

`DateInLastOperator` and `DateInNextOperator` accept `['amount' => int, 'unit' => 'days|weeks|months|years']`.

`DateBetweenOperator` accepts `['start' => date, 'end' => date]` or `[date, date]`.

### Array Operators (JSON columns)

| Class | Label | Input Type |
|---|---|---|
| `InOperator` | is any of | multiselect |
| `NotInOperator` | is not any of | multiselect |
| `HasAnyOperator` | has any of | multiselect |
| `HasAllOperator` | has all of | multiselect |

`HasAnyOperator` and `HasAllOperator` use `whereJsonContains` for JSON array columns.

### Relation Operators

| Class | Label | Requires Value |
|---|---|---|
| `HasRelationOperator` | has | no |
| `HasNotRelationOperator` | does not have | no |
| `HasCountOperator` | has count | yes |

`HasCountOperator` accepts `['operator' => '>=', 'count' => 1]`.

## Usage with Eloquent

The `ParsedQuery` DTO gives you structured data you can apply to your queries however you like. Here is a typical pattern:

```php
use PhilipRehberger\SearchQueryParser\QueryParser;
use PhilipRehberger\SearchQueryParser\Operators\Text\ContainsOperator;
use PhilipRehberger\SearchQueryParser\Operators\Text\EqualsOperator;
use PhilipRehberger\SearchQueryParser\Operators\Numeric\GreaterThanOperator;
use PhilipRehberger\SearchQueryParser\Operators\Array\InOperator;
use PhilipRehberger\SearchQueryParser\Operators\Relation\HasRelationOperator;
use PhilipRehberger\SearchQueryParser\Operators\Relation\HasNotRelationOperator;

$parser = new QueryParser();
$parsed = $parser->parse($request->input('q', ''));

$query = Project::query();

// Apply free-text search
if ($parsed->hasTextSearch()) {
    $term = $parsed->textSearch;
    $query->where(function ($q) use ($term) {
        $q->where('name', 'like', "%{$term}%")
          ->orWhere('description', 'like', "%{$term}%");
    });
}

// Apply field filters
$operatorMap = [
    'equals'        => new EqualsOperator(),
    'in'            => new InOperator(),
    'greater_than'  => new GreaterThanOperator(),
    'has'           => new HasRelationOperator(),
    'has_not'       => new HasNotRelationOperator(),
];

foreach ($parsed->filters as $filter) {
    $operator = $operatorMap[$filter['operator']] ?? null;
    if ($operator) {
        $operator->apply($query, $filter['field'], $filter['value']);
    }
}

// Apply exclusion terms
foreach ($parsed->excludeTerms as $term) {
    $query->where('name', 'not like', "%{$term}%");
}

$projects = $query->get();
```

## Build / Round-trip

`QueryParser::build()` serializes a `ParsedQuery` back into a query string. This is useful for storing canonical search state or passing queries between requests.

```php
$parsed = $parser->parse('design status:active -archived');

// Modify the parsed query...
$built = $parser->build($parsed);
// "design status:active -archived"
```

## Syntax Help

`getSyntaxHelp()` returns all supported syntax patterns, suitable for rendering a help tooltip or autocomplete:

```php
$help = $parser->getSyntaxHelp();
// [
//   ['syntax' => 'keyword',       'example' => 'design',           'description' => 'Search for keyword in all fields'],
//   ['syntax' => '"phrase"',      'example' => '"web design"',     'description' => 'Search for exact phrase'],
//   ['syntax' => 'field:value',   'example' => 'status:active',    'description' => 'Filter by specific field'],
//   ...
// ]
```

## ParsedQuery DTO

```php
readonly class ParsedQuery
{
    public string $textSearch;
    public array  $filters;      // array<{field: string, operator: string, value: mixed}>
    public array  $excludeTerms; // array<string>

    public function hasTextSearch(): bool;
    public function hasFilters(): bool;
    public function hasExcludeTerms(): bool;
    public function isEmpty(): bool;
    public function toArray(): array;
}
```

## License

MIT License. Copyright (c) 2026 Philip Rehberger. See [LICENSE](LICENSE) for details.
