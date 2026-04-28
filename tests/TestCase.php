<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Tests;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private static bool $initialized = false;

    protected static Capsule $capsule;

    public static function setUpBeforeClass(): void
    {
        if (self::$initialized) {
            return;
        }

        static::$capsule = new Capsule;
        static::$capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        static::$capsule->setAsGlobal();
        static::$capsule->bootEloquent();

        $app = new Container;
        $app->instance('db', static::$capsule->getDatabaseManager());
        $app->instance('config', new class
        {
            public function get(string $key, mixed $default = null): mixed
            {
                return match ($key) {
                    'app.locale' => 'en',
                    default => $default,
                };
            }
        });
        Facade::setFacadeApplication($app);

        static::createSchema();

        self::$initialized = true;
    }

    protected static function createSchema(): void
    {
        $schema = static::$capsule->schema();

        if (! $schema->hasTable('orders')) {
            $schema->create('orders', function ($table) {
                $table->id();
                $table->string('status')->default('pending');
                $table->decimal('amount', 10, 2)->default(0);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        static::$capsule->table('orders')->truncate();
    }

    protected function insert(string $createdAt, string $status = 'pending', float $amount = 100.00): void
    {
        static::$capsule->table('orders')->insert([
            'status' => $status,
            'amount' => $amount,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    protected function db(): \Illuminate\Database\Query\Builder
    {
        return static::$capsule->table('orders');
    }
}
