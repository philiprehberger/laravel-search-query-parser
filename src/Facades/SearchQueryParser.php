<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Facades;

use Illuminate\Support\Facades\Facade;
use PhilipRehberger\SearchQueryParser\ParsedQuery;
use PhilipRehberger\SearchQueryParser\QueryParser;

/**
 * @method static ParsedQuery parse(string $query)
 * @method static string build(ParsedQuery $parsed)
 * @method static array<array{syntax: string, example: string, description: string}> getSyntaxHelp()
 *
 * @see QueryParser
 */
class SearchQueryParser extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'search-query-parser';
    }
}
