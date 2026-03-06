<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Tests;

use Orchestra\Testbench\TestCase;
use PhilipRehberger\SearchQueryParser\ParsedQuery;
use PhilipRehberger\SearchQueryParser\QueryParser;
use PhilipRehberger\SearchQueryParser\SearchQueryParserServiceProvider;

class QueryParserTest extends TestCase
{
    private QueryParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new QueryParser;
    }

    protected function getPackageProviders($app): array
    {
        return [SearchQueryParserServiceProvider::class];
    }

    // -----------------------------------------------------------------------
    // Empty & trivial queries
    // -----------------------------------------------------------------------

    public function test_empty_query_returns_empty_parsed_query(): void
    {
        $result = $this->parser->parse('');

        $this->assertInstanceOf(ParsedQuery::class, $result);
        $this->assertTrue($result->isEmpty());
        $this->assertFalse($result->hasTextSearch());
        $this->assertFalse($result->hasFilters());
        $this->assertFalse($result->hasExcludeTerms());
    }

    public function test_whitespace_only_query_returns_empty_parsed_query(): void
    {
        $result = $this->parser->parse('   ');

        $this->assertTrue($result->isEmpty());
    }

    // -----------------------------------------------------------------------
    // Text search parsing
    // -----------------------------------------------------------------------

    public function test_simple_text_search(): void
    {
        $result = $this->parser->parse('design');

        $this->assertTrue($result->hasTextSearch());
        $this->assertSame('design', $result->textSearch);
        $this->assertFalse($result->hasFilters());
        $this->assertFalse($result->hasExcludeTerms());
    }

    public function test_multiple_words_join_as_text_search(): void
    {
        $result = $this->parser->parse('web design agency');

        $this->assertTrue($result->hasTextSearch());
        $this->assertSame('web design agency', $result->textSearch);
    }

    public function test_quoted_phrase_becomes_single_text_search_term(): void
    {
        $result = $this->parser->parse('"web design"');

        $this->assertTrue($result->hasTextSearch());
        $this->assertSame('web design', $result->textSearch);
        $this->assertFalse($result->hasFilters());
    }

    public function test_single_quoted_phrase_becomes_single_text_search_term(): void
    {
        $result = $this->parser->parse("'web design'");

        $this->assertTrue($result->hasTextSearch());
        $this->assertSame('web design', $result->textSearch);
    }

    public function test_mixed_quoted_and_unquoted_text(): void
    {
        $result = $this->parser->parse('hello "web design" world');

        $this->assertTrue($result->hasTextSearch());
        $this->assertSame('hello web design world', $result->textSearch);
    }

    // -----------------------------------------------------------------------
    // Field filter parsing — equals (default)
    // -----------------------------------------------------------------------

    public function test_field_value_filter_becomes_equals_operator(): void
    {
        $result = $this->parser->parse('status:active');

        $this->assertFalse($result->hasTextSearch());
        $this->assertTrue($result->hasFilters());
        $this->assertCount(1, $result->filters);

        $filter = $result->filters[0];
        $this->assertSame('status', $filter['field']);
        $this->assertSame('equals', $filter['operator']);
        $this->assertSame('active', $filter['value']);
    }

    public function test_field_filter_with_quoted_value(): void
    {
        $result = $this->parser->parse('name:"John Doe"');

        $this->assertCount(1, $result->filters);
        $filter = $result->filters[0];
        $this->assertSame('name', $filter['field']);
        $this->assertSame('equals', $filter['operator']);
        $this->assertSame('John Doe', $filter['value']);
    }

    // -----------------------------------------------------------------------
    // Comparison operators
    // -----------------------------------------------------------------------

    public function test_greater_than_operator(): void
    {
        $result = $this->parser->parse('amount:>1000');

        $filter = $result->filters[0];
        $this->assertSame('amount', $filter['field']);
        $this->assertSame('greater_than', $filter['operator']);
        $this->assertSame('1000', $filter['value']);
    }

    public function test_less_than_operator(): void
    {
        $result = $this->parser->parse('amount:<500');

        $filter = $result->filters[0];
        $this->assertSame('amount', $filter['field']);
        $this->assertSame('less_than', $filter['operator']);
        $this->assertSame('500', $filter['value']);
    }

    public function test_greater_or_equal_operator(): void
    {
        $result = $this->parser->parse('total:>=500');

        $filter = $result->filters[0];
        $this->assertSame('total', $filter['field']);
        $this->assertSame('greater_or_equal', $filter['operator']);
        $this->assertSame('500', $filter['value']);
    }

    public function test_less_or_equal_operator(): void
    {
        $result = $this->parser->parse('hours:<=40');

        $filter = $result->filters[0];
        $this->assertSame('hours', $filter['field']);
        $this->assertSame('less_or_equal', $filter['operator']);
        $this->assertSame('40', $filter['value']);
    }

    public function test_not_equals_operator(): void
    {
        $result = $this->parser->parse('status:!=archived');

        $filter = $result->filters[0];
        $this->assertSame('status', $filter['field']);
        $this->assertSame('not_equals', $filter['operator']);
        $this->assertSame('archived', $filter['value']);
    }

    public function test_equals_symbol_operator(): void
    {
        $result = $this->parser->parse('status:=active');

        $filter = $result->filters[0];
        $this->assertSame('status', $filter['field']);
        $this->assertSame('equals', $filter['operator']);
        $this->assertSame('active', $filter['value']);
    }

    public function test_longer_operators_take_precedence_over_shorter_ones(): void
    {
        // >= must not be parsed as > followed by =
        $result = $this->parser->parse('score:>=10');
        $this->assertSame('greater_or_equal', $result->filters[0]['operator']);

        $result2 = $this->parser->parse('score:<=10');
        $this->assertSame('less_or_equal', $result2->filters[0]['operator']);
    }

    // -----------------------------------------------------------------------
    // Comma-separated values (in operator)
    // -----------------------------------------------------------------------

    public function test_comma_separated_values_become_in_operator(): void
    {
        $result = $this->parser->parse('status:active,pending,draft');

        $filter = $result->filters[0];
        $this->assertSame('status', $filter['field']);
        $this->assertSame('in', $filter['operator']);
        $this->assertSame(['active', 'pending', 'draft'], $filter['value']);
    }

    public function test_two_comma_separated_values(): void
    {
        $result = $this->parser->parse('type:invoice,quote');

        $filter = $result->filters[0];
        $this->assertSame('in', $filter['operator']);
        $this->assertCount(2, $filter['value']);
    }

    // -----------------------------------------------------------------------
    // Exclusion terms
    // -----------------------------------------------------------------------

    public function test_exclusion_term_is_parsed(): void
    {
        $result = $this->parser->parse('-archived');

        $this->assertFalse($result->hasTextSearch());
        $this->assertFalse($result->hasFilters());
        $this->assertTrue($result->hasExcludeTerms());
        $this->assertSame(['archived'], $result->excludeTerms);
    }

    public function test_multiple_exclusion_terms(): void
    {
        $result = $this->parser->parse('-archived -deleted');

        $this->assertCount(2, $result->excludeTerms);
        $this->assertSame(['archived', 'deleted'], $result->excludeTerms);
    }

    public function test_single_dash_is_not_an_exclusion_token(): void
    {
        $result = $this->parser->parse('-');

        // A lone dash has length 1 and should not be treated as exclude token
        // It falls through to text search
        $this->assertTrue($result->hasTextSearch());
        $this->assertFalse($result->hasExcludeTerms());
    }

    // -----------------------------------------------------------------------
    // Relation filters
    // -----------------------------------------------------------------------

    public function test_has_relation_filter(): void
    {
        $result = $this->parser->parse('has:invoices');

        $filter = $result->filters[0];
        $this->assertSame('invoices', $filter['field']);
        $this->assertSame('has', $filter['operator']);
        $this->assertNull($filter['value']);
    }

    public function test_has_not_relation_filter(): void
    {
        $result = $this->parser->parse('no:projects');

        $filter = $result->filters[0];
        $this->assertSame('projects', $filter['field']);
        $this->assertSame('has_not', $filter['operator']);
        $this->assertNull($filter['value']);
    }

    public function test_has_relation_filter_is_case_insensitive_prefix(): void
    {
        $result = $this->parser->parse('HAS:invoices');

        $this->assertCount(1, $result->filters);
        $this->assertSame('has', $result->filters[0]['operator']);
    }

    public function test_no_relation_filter_is_case_insensitive_prefix(): void
    {
        $result = $this->parser->parse('NO:projects');

        $this->assertCount(1, $result->filters);
        $this->assertSame('has_not', $result->filters[0]['operator']);
    }

    // -----------------------------------------------------------------------
    // Combined queries
    // -----------------------------------------------------------------------

    public function test_combined_text_and_filter(): void
    {
        $result = $this->parser->parse('design status:active');

        $this->assertTrue($result->hasTextSearch());
        $this->assertSame('design', $result->textSearch);
        $this->assertTrue($result->hasFilters());
        $this->assertCount(1, $result->filters);
        $this->assertSame('status', $result->filters[0]['field']);
    }

    public function test_combined_text_filter_and_exclusion(): void
    {
        $result = $this->parser->parse('design status:active -archived');

        $this->assertSame('design', $result->textSearch);
        $this->assertCount(1, $result->filters);
        $this->assertCount(1, $result->excludeTerms);
        $this->assertSame('archived', $result->excludeTerms[0]);
    }

    public function test_multiple_filters(): void
    {
        $result = $this->parser->parse('status:active type:invoice amount:>1000');

        $this->assertCount(3, $result->filters);
        $this->assertSame('status', $result->filters[0]['field']);
        $this->assertSame('type', $result->filters[1]['field']);
        $this->assertSame('amount', $result->filters[2]['field']);
    }

    public function test_complex_combined_query(): void
    {
        $result = $this->parser->parse('"web design" status:active,pending amount:>=500 -archived has:invoices');

        $this->assertSame('web design', $result->textSearch);
        $this->assertCount(3, $result->filters);
        $this->assertCount(1, $result->excludeTerms);
        $this->assertSame(['archived'], $result->excludeTerms);

        // status:active,pending -> in operator
        $this->assertSame('in', $result->filters[0]['operator']);
        // amount:>=500 -> greater_or_equal
        $this->assertSame('greater_or_equal', $result->filters[1]['operator']);
        // has:invoices -> has
        $this->assertSame('has', $result->filters[2]['operator']);
    }

    // -----------------------------------------------------------------------
    // URL not treated as field filter
    // -----------------------------------------------------------------------

    public function test_http_url_is_not_treated_as_field_filter(): void
    {
        $result = $this->parser->parse('https://example.com/page');

        $this->assertTrue($result->hasTextSearch());
        $this->assertFalse($result->hasFilters());
        $this->assertSame('https://example.com/page', $result->textSearch);
    }

    public function test_https_url_is_not_treated_as_field_filter(): void
    {
        $result = $this->parser->parse('https://scopeforged.com');

        $this->assertTrue($result->hasTextSearch());
        $this->assertFalse($result->hasFilters());
    }

    // -----------------------------------------------------------------------
    // build() round-trip
    // -----------------------------------------------------------------------

    public function test_build_produces_simple_text_query(): void
    {
        $parsed = new ParsedQuery(textSearch: 'design');
        $built = $this->parser->build($parsed);

        $this->assertSame('design', $built);
    }

    public function test_build_quotes_text_with_spaces(): void
    {
        $parsed = new ParsedQuery(textSearch: 'web design');
        $built = $this->parser->build($parsed);

        $this->assertSame('"web design"', $built);
    }

    public function test_build_produces_field_value_filter(): void
    {
        $parsed = new ParsedQuery(
            textSearch: '',
            filters: [['field' => 'status', 'operator' => 'equals', 'value' => 'active']],
        );
        $built = $this->parser->build($parsed);

        $this->assertSame('status:active', $built);
    }

    public function test_build_produces_comparison_filter(): void
    {
        $parsed = new ParsedQuery(
            textSearch: '',
            filters: [['field' => 'amount', 'operator' => 'greater_than', 'value' => '1000']],
        );
        $built = $this->parser->build($parsed);

        $this->assertSame('amount:>1000', $built);
    }

    public function test_build_produces_in_filter(): void
    {
        $parsed = new ParsedQuery(
            textSearch: '',
            filters: [['field' => 'status', 'operator' => 'in', 'value' => ['active', 'pending']]],
        );
        $built = $this->parser->build($parsed);

        $this->assertSame('status:active,pending', $built);
    }

    public function test_build_produces_exclude_terms(): void
    {
        $parsed = new ParsedQuery(
            textSearch: '',
            filters: [],
            excludeTerms: ['archived', 'deleted'],
        );
        $built = $this->parser->build($parsed);

        $this->assertSame('-archived -deleted', $built);
    }

    public function test_build_produces_has_relation_filter(): void
    {
        $parsed = new ParsedQuery(
            textSearch: '',
            filters: [['field' => 'invoices', 'operator' => 'has', 'value' => null]],
        );
        $built = $this->parser->build($parsed);

        $this->assertSame('has:invoices', $built);
    }

    public function test_build_produces_has_not_relation_filter(): void
    {
        $parsed = new ParsedQuery(
            textSearch: '',
            filters: [['field' => 'projects', 'operator' => 'has_not', 'value' => null]],
        );
        $built = $this->parser->build($parsed);

        $this->assertSame('no:projects', $built);
    }

    public function test_parse_then_build_round_trip_simple(): void
    {
        $query = 'design status:active -archived';
        $parsed = $this->parser->parse($query);
        $built = $this->parser->build($parsed);

        $this->assertSame($query, $built);
    }

    public function test_parse_then_build_round_trip_with_comparison(): void
    {
        $query = 'amount:>1000';
        $parsed = $this->parser->parse($query);
        $built = $this->parser->build($parsed);

        $this->assertSame($query, $built);
    }

    public function test_parse_then_build_round_trip_with_in_operator(): void
    {
        $query = 'status:active,pending';
        $parsed = $this->parser->parse($query);
        $built = $this->parser->build($parsed);

        $this->assertSame($query, $built);
    }

    public function test_parse_then_build_round_trip_with_relations(): void
    {
        $query = 'has:invoices no:projects';
        $parsed = $this->parser->parse($query);
        $built = $this->parser->build($parsed);

        $this->assertSame($query, $built);
    }

    public function test_parse_then_build_round_trip_with_less_or_equal(): void
    {
        $query = 'hours:<=40';
        $parsed = $this->parser->parse($query);
        $built = $this->parser->build($parsed);

        $this->assertSame($query, $built);
    }

    public function test_parse_then_build_round_trip_with_not_equals(): void
    {
        $query = 'status:!=archived';
        $parsed = $this->parser->parse($query);
        $built = $this->parser->build($parsed);

        $this->assertSame($query, $built);
    }

    // -----------------------------------------------------------------------
    // getSyntaxHelp()
    // -----------------------------------------------------------------------

    public function test_get_syntax_help_returns_array(): void
    {
        $help = $this->parser->getSyntaxHelp();

        $this->assertIsArray($help);
        $this->assertNotEmpty($help);
    }

    public function test_get_syntax_help_entries_have_required_keys(): void
    {
        $help = $this->parser->getSyntaxHelp();

        foreach ($help as $entry) {
            $this->assertArrayHasKey('syntax', $entry);
            $this->assertArrayHasKey('example', $entry);
            $this->assertArrayHasKey('description', $entry);
        }
    }

    public function test_get_syntax_help_contains_all_syntax_patterns(): void
    {
        $help = $this->parser->getSyntaxHelp();
        $syntaxPatterns = array_column($help, 'syntax');

        $this->assertContains('keyword', $syntaxPatterns);
        $this->assertContains('"phrase"', $syntaxPatterns);
        $this->assertContains('field:value', $syntaxPatterns);
        $this->assertContains('field:>value', $syntaxPatterns);
        $this->assertContains('field:<value', $syntaxPatterns);
        $this->assertContains('field:>=value', $syntaxPatterns);
        $this->assertContains('field:<=value', $syntaxPatterns);
        $this->assertContains('field:!=value', $syntaxPatterns);
        $this->assertContains('field:value1,value2', $syntaxPatterns);
        $this->assertContains('-keyword', $syntaxPatterns);
        $this->assertContains('has:relation', $syntaxPatterns);
        $this->assertContains('no:relation', $syntaxPatterns);
    }

    public function test_get_syntax_help_has_twelve_entries(): void
    {
        $help = $this->parser->getSyntaxHelp();

        $this->assertCount(12, $help);
    }

    // -----------------------------------------------------------------------
    // Edge cases
    // -----------------------------------------------------------------------

    public function test_field_starting_with_number_is_not_a_filter(): void
    {
        $result = $this->parser->parse('1st:value');

        $this->assertFalse($result->hasFilters());
        $this->assertTrue($result->hasTextSearch());
    }

    public function test_colon_at_start_is_not_a_filter(): void
    {
        $result = $this->parser->parse(':value');

        $this->assertFalse($result->hasFilters());
        $this->assertTrue($result->hasTextSearch());
    }

    public function test_field_with_underscore_is_valid_filter(): void
    {
        $result = $this->parser->parse('created_at:2026-01-01');

        $this->assertTrue($result->hasFilters());
        $this->assertSame('created_at', $result->filters[0]['field']);
    }

    public function test_multibyte_text_search(): void
    {
        $result = $this->parser->parse('日本語テスト');

        $this->assertTrue($result->hasTextSearch());
        $this->assertSame('日本語テスト', $result->textSearch);
    }
}
