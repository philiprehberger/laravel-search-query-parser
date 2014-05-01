<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Tests;

use Orchestra\Testbench\TestCase;
use PhilipRehberger\SearchQueryParser\ParsedQuery;

class ParsedQueryTest extends TestCase
{
    // -----------------------------------------------------------------------
    // hasTextSearch()
    // -----------------------------------------------------------------------

    public function test_has_text_search_returns_false_when_empty(): void
    {
        $query = new ParsedQuery(textSearch: '');

        $this->assertFalse($query->hasTextSearch());
    }

    public function test_has_text_search_returns_true_when_not_empty(): void
    {
        $query = new ParsedQuery(textSearch: 'design');

        $this->assertTrue($query->hasTextSearch());
    }

    public function test_has_text_search_with_whitespace_returns_true(): void
    {
        // A non-empty string, even whitespace, is considered having text
        $query = new ParsedQuery(textSearch: ' ');

        $this->assertTrue($query->hasTextSearch());
    }

    // -----------------------------------------------------------------------
    // hasFilters()
    // -----------------------------------------------------------------------

    public function test_has_filters_returns_false_when_no_filters(): void
    {
        $query = new ParsedQuery(textSearch: '');

        $this->assertFalse($query->hasFilters());
    }

    public function test_has_filters_returns_true_when_filters_present(): void
    {
        $query = new ParsedQuery(
            textSearch: '',
            filters: [['field' => 'status', 'operator' => 'equals', 'value' => 'active']],
        );

        $this->assertTrue($query->hasFilters());
    }

    public function test_has_filters_with_multiple_filters(): void
    {
        $query = new ParsedQuery(
            textSearch: '',
            filters: [
                ['field' => 'status', 'operator' => 'equals', 'value' => 'active'],
                ['field' => 'type', 'operator' => 'in', 'value' => ['invoice', 'quote']],
            ],
        );

        $this->assertTrue($query->hasFilters());
        $this->assertCount(2, $query->filters);
    }

    // -----------------------------------------------------------------------
    // hasExcludeTerms()
    // -----------------------------------------------------------------------

    public function test_has_exclude_terms_returns_false_when_none(): void
    {
        $query = new ParsedQuery(textSearch: '');

        $this->assertFalse($query->hasExcludeTerms());
    }

    public function test_has_exclude_terms_returns_true_when_present(): void
    {
        $query = new ParsedQuery(
            textSearch: '',
            excludeTerms: ['archived'],
        );

        $this->assertTrue($query->hasExcludeTerms());
    }

    public function test_has_exclude_terms_with_multiple_terms(): void
    {
        $query = new ParsedQuery(
            textSearch: '',
            excludeTerms: ['archived', 'deleted', 'draft'],
        );

        $this->assertTrue($query->hasExcludeTerms());
        $this->assertCount(3, $query->excludeTerms);
    }

    // -----------------------------------------------------------------------
    // isEmpty()
    // -----------------------------------------------------------------------

    public function test_is_empty_returns_true_when_nothing_set(): void
    {
        $query = new ParsedQuery(textSearch: '');

        $this->assertTrue($query->isEmpty());
    }

    public function test_is_empty_returns_false_when_text_search_present(): void
    {
        $query = new ParsedQuery(textSearch: 'design');

        $this->assertFalse($query->isEmpty());
    }

    public function test_is_empty_returns_false_when_filters_present(): void
    {
        $query = new ParsedQuery(
            textSearch: '',
            filters: [['field' => 'status', 'operator' => 'equals', 'value' => 'active']],
        );

        $this->assertFalse($query->isEmpty());
    }

    public function test_is_empty_returns_false_when_exclude_terms_present(): void
    {
        $query = new ParsedQuery(
            textSearch: '',
            excludeTerms: ['archived'],
        );

        $this->assertFalse($query->isEmpty());
    }

    public function test_is_empty_returns_false_when_all_components_present(): void
    {
        $query = new ParsedQuery(
            textSearch: 'design',
            filters: [['field' => 'status', 'operator' => 'equals', 'value' => 'active']],
            excludeTerms: ['archived'],
        );

        $this->assertFalse($query->isEmpty());
    }

    // -----------------------------------------------------------------------
    // toArray()
    // -----------------------------------------------------------------------

    public function test_to_array_returns_correct_structure(): void
    {
        $query = new ParsedQuery(
            textSearch: 'design',
            filters: [['field' => 'status', 'operator' => 'equals', 'value' => 'active']],
            excludeTerms: ['archived'],
        );

        $array = $query->toArray();

        $this->assertArrayHasKey('text_search', $array);
        $this->assertArrayHasKey('filters', $array);
        $this->assertArrayHasKey('exclude_terms', $array);
    }

    public function test_to_array_contains_correct_values(): void
    {
        $filters = [['field' => 'status', 'operator' => 'equals', 'value' => 'active']];
        $excludeTerms = ['archived'];

        $query = new ParsedQuery(
            textSearch: 'design',
            filters: $filters,
            excludeTerms: $excludeTerms,
        );

        $array = $query->toArray();

        $this->assertSame('design', $array['text_search']);
        $this->assertSame($filters, $array['filters']);
        $this->assertSame($excludeTerms, $array['exclude_terms']);
    }

    public function test_to_array_with_empty_query(): void
    {
        $query = new ParsedQuery(textSearch: '');

        $array = $query->toArray();

        $this->assertSame('', $array['text_search']);
        $this->assertSame([], $array['filters']);
        $this->assertSame([], $array['exclude_terms']);
    }

    // -----------------------------------------------------------------------
    // Immutability (readonly)
    // -----------------------------------------------------------------------

    public function test_parsed_query_is_readonly(): void
    {
        $query = new ParsedQuery(textSearch: 'design');

        $this->expectException(\Error::class);
        // @phpstan-ignore-next-line
        $query->textSearch = 'other';
    }

    // -----------------------------------------------------------------------
    // Constructor defaults
    // -----------------------------------------------------------------------

    public function test_constructor_defaults_filters_and_exclude_terms_to_empty_arrays(): void
    {
        $query = new ParsedQuery(textSearch: 'design');

        $this->assertSame([], $query->filters);
        $this->assertSame([], $query->excludeTerms);
    }

    public function test_all_properties_accessible_as_public(): void
    {
        $filters = [['field' => 'status', 'operator' => 'equals', 'value' => 'active']];
        $excludeTerms = ['archived'];

        $query = new ParsedQuery(
            textSearch: 'design',
            filters: $filters,
            excludeTerms: $excludeTerms,
        );

        $this->assertSame('design', $query->textSearch);
        $this->assertSame($filters, $query->filters);
        $this->assertSame($excludeTerms, $query->excludeTerms);
    }
}
