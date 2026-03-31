# Changelog

All notable changes to `laravel-search-query-parser` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.5] - 2026-03-31

### Changed
- Standardize README to 3-badge format with emoji Support section
- Update CI checkout action to v5 for Node.js 24 compatibility
- Add GitHub issue templates, dependabot config, and PR template

## [1.1.4] - 2026-03-17

### Fixed
- Add phpstan.neon configuration for CI static analysis

## [1.1.3] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

## [1.1.2] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts
- Add Development section to README

## [1.1.1] - 2026-03-15

### Changed
- Add README badges

## [1.1.0] - 2026-03-13

### Fixed
- LIKE wildcard injection in text operators (`ContainsOperator`, `StartsWithOperator`, `EndsWithOperator`, `NotContainsOperator`) — `%` and `_` in user input are now escaped
- `IsTodayOperator` now uses explicit `=` operator in `whereDate()` call

### Added
- Operator-level test suite (`tests/OperatorTest.php`) with 12 tests covering all text operators and `IsTodayOperator`

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
