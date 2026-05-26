<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests;

use AndyDefer\Directive\Testing\InteractsWithDirectives;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class IntegrationTestCase extends Orchestra
{
    use InteractsWithDirectives;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDirectiveTesting();
    }

    protected function tearDown(): void
    {
        $this->destroyDirectiveTesting();
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.env', 'testing');
        $app['config']->set('directive.path', getcwd() . '/app/Directives');
    }
}
