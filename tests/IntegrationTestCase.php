<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests;

use AndyDefer\Directive\DirectiveServiceProvider;
use AndyDefer\DirectiveForge\DirectiveForgeServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class IntegrationTestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void {}

    /**
     * Get the package providers for the test.
     *
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            DirectiveServiceProvider::class,
            DirectiveForgeServiceProvider::class,  // ← AJOUTER CECI
        ];
    }
}
