# Changelog

All notable changes to `laravel-search-query-parser` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-03-05

### Added
- `QueryParser` class with `parse()` and `build()` methods
- `ParsedQuery` readonly DTO with `hasTextSearch()`, `hasFilters()`, `hasExcludeTerms()`, `isEmpty()`, `toArray()`
- `getSyntaxHelp()` returning all supported syntax patterns
- Full operator library:
  - **Text**: `ContainsOperator`, `NotContainsOperator`, `EqualsOperator`, `NotEqualsOperator`, `StartsWithOperator`, `EndsWithOperator`, `IsEmptyOperator`, `IsNotEmptyOperator`
  - **Numeric**: `GreaterThanOperator`, `GreaterOrEqualOperator`, `LessThanOperator`, `LessOrEqualOperator`, `BetweenOperator`, `NotBetweenOperator`
  - **Date**: `DateEqualsOperator`, `DateBeforeOperator`, `DateAfterOperator`, `DateBetweenOperator`, `DateInLastOperator`, `DateInNextOperator`, `IsTodayOperator`, `IsThisWeekOperator`, `IsThisMonthOperator`
  - **Array**: `InOperator`, `NotInOperator`, `HasAnyOperator`, `HasAllOperator`
  - **Relation**: `HasRelationOperator`, `HasNotRelationOperator`, `HasCountOperator`
- `SearchQueryParserServiceProvider` for Laravel auto-discovery
- `SearchQueryParser` facade
- GitHub Actions CI for PHP 8.2, 8.3, and 8.4
