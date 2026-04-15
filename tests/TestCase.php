<?php

namespace Atldays\JoinRelation\Tests;

use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('title');
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('bio')->nullable();
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('networks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamp('deleted_at')->nullable();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('publishers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('network_id')->nullable();
            $table->foreignId('primary_network_id')->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamp('deleted_at')->nullable();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('advertisers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('publisher_id')->nullable();
            $table->foreignId('source_publisher_id')->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamp('deleted_at')->nullable();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advertiser_id')->nullable();
            $table->foreignId('partner_advertiser_id')->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamp('deleted_at')->nullable();
        });
    }
}
