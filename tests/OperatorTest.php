<?php

declare(strict_types=1);

namespace PhilipRehberger\SearchQueryParser\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\SearchQueryParser\Operators\Date\IsTodayOperator;
use PhilipRehberger\SearchQueryParser\Operators\Text\ContainsOperator;
use PhilipRehberger\SearchQueryParser\Operators\Text\EndsWithOperator;
use PhilipRehberger\SearchQueryParser\Operators\Text\NotContainsOperator;
use PhilipRehberger\SearchQueryParser\Operators\Text\StartsWithOperator;

class OperatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    // -- ContainsOperator tests --
    public function test_contains_operator_applies_like_query(): void
    {
        TestItem::create(['name' => 'Hello World']);
        TestItem::create(['name' => 'Goodbye World']);
        TestItem::create(['name' => 'Hello There']);

        $query = TestItem::query();
        (new ContainsOperator)->apply($query, 'name', 'World');

        $this->assertCount(2, $query->get());
    }

    public function test_contains_operator_escapes_percent_wildcard(): void
    {
        TestItem::create(['name' => '100% discount']);
        TestItem::create(['name' => '100 percent off']);

        $query = TestItem::query();
        (new ContainsOperator)->apply($query, 'name', '100%');

        $this->assertCount(1, $query->get());
        $this->assertSame('100% discount', $query->first()->name);
    }

    public function test_contains_operator_escapes_underscore_wildcard(): void
    {
        TestItem::create(['name' => 'file_name.txt']);
        TestItem::create(['name' => 'filename.txt']);

        $query = TestItem::query();
        (new ContainsOperator)->apply($query, 'name', 'file_name');

        $this->assertCount(1, $query->get());
        $this->assertSame('file_name.txt', $query->first()->name);
    }

    // -- StartsWithOperator tests --
    public function test_starts_with_operator_applies_like_query(): void
    {
        TestItem::create(['name' => 'Hello World']);
        TestItem::create(['name' => 'Goodbye World']);

        $query = TestItem::query();
        (new StartsWithOperator)->apply($query, 'name', 'Hello');

        $this->assertCount(1, $query->get());
        $this->assertSame('Hello World', $query->first()->name);
    }

    public function test_starts_with_operator_escapes_wildcards(): void
    {
        TestItem::create(['name' => '50% off sale']);
        TestItem::create(['name' => '50 items left']);

        $query = TestItem::query();
        (new StartsWithOperator)->apply($query, 'name', '50%');

        $this->assertCount(1, $query->get());
        $this->assertSame('50% off sale', $query->first()->name);
    }

    // -- EndsWithOperator tests --
    public function test_ends_with_operator_applies_like_query(): void
    {
        TestItem::create(['name' => 'report.pdf']);
        TestItem::create(['name' => 'image.png']);

        $query = TestItem::query();
        (new EndsWithOperator)->apply($query, 'name', '.pdf');

        $this->assertCount(1, $query->get());
        $this->assertSame('report.pdf', $query->first()->name);
    }

    public function test_ends_with_operator_escapes_wildcards(): void
    {
        TestItem::create(['name' => 'rate is 100%']);
        TestItem::create(['name' => 'rate is 100']);

        $query = TestItem::query();
        (new EndsWithOperator)->apply($query, 'name', '100%');

        $this->assertCount(1, $query->get());
        $this->assertSame('rate is 100%', $query->first()->name);
    }

    // -- NotContainsOperator tests --
    public function test_not_contains_operator_applies_not_like_query(): void
    {
        TestItem::create(['name' => 'Hello World']);
        TestItem::create(['name' => 'Goodbye World']);
        TestItem::create(['name' => 'Hello There']);

        $query = TestItem::query();
        (new NotContainsOperator)->apply($query, 'name', 'World');

        $this->assertCount(1, $query->get());
        $this->assertSame('Hello There', $query->first()->name);
    }

    public function test_not_contains_operator_escapes_wildcards(): void
    {
        TestItem::create(['name' => '100% discount']);
        TestItem::create(['name' => '100 percent off']);

        $query = TestItem::query();
        (new NotContainsOperator)->apply($query, 'name', '100%');

        $this->assertCount(1, $query->get());
        $this->assertSame('100 percent off', $query->first()->name);
    }

    // -- IsTodayOperator tests --
    public function test_is_today_operator_filters_todays_date(): void
    {
        TestItem::create(['name' => 'Today', 'created_at' => now()]);
        TestItem::create(['name' => 'Yesterday', 'created_at' => now()->subDay()]);

        $query = TestItem::query();
        (new IsTodayOperator)->apply($query, 'created_at', null);

        $this->assertCount(1, $query->get());
        $this->assertSame('Today', $query->first()->name);
    }

    public function test_is_today_operator_excludes_other_dates(): void
    {
        TestItem::create(['name' => 'Old', 'created_at' => now()->subDays(5)]);
        TestItem::create(['name' => 'Future', 'created_at' => now()->addDays(5)]);

        $query = TestItem::query();
        (new IsTodayOperator)->apply($query, 'created_at', null);

        $this->assertCount(0, $query->get());
    }

    public function test_is_today_operator_uses_explicit_equals_operator(): void
    {
        TestItem::create(['name' => 'Today', 'created_at' => now()]);

        $query = TestItem::query();
        $result = (new IsTodayOperator)->apply($query, 'created_at', null);

        // Verify the query contains the = operator by checking query SQL
        $sql = $result->toSql();
        $this->assertStringContainsString('=', $sql);
    }
}

class TestItem extends Model
{
    protected $table = 'items';

    protected $guarded = [];
}
