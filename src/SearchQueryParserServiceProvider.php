<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser;

use Illuminate\Support\ServiceProvider;

class SearchQueryParserServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(QueryParser::class, function () {
            return new QueryParser;
        });

        $this->app->alias(QueryParser::class, 'search-query-parser');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
