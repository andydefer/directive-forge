<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeTaskDirectiveTest extends UnitTestCase
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

    public function test_get_signature_returns_make_task(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTaskDirective::class);

        $this->assertSame('make-task {name}', $directive->getSignature());
    }

    public function test_get_description_returns_description(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTaskDirective::class);

        $this->assertSame('Create a new task class', $directive->getDescription());
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTaskDirective::class);
        $aliases = $directive->getAliases();

        $this->assertTrue($aliases->contains('create-task'));
        $this->assertTrue($aliases->contains('make-job'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTaskDirective::class);

        $response = $this->runDirective('make-task');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        // Le message d'erreur réel vient du kernel Laravel Directive
        $this->assertStringContainsString('Not enough arguments', $response->getOutput());
    }

    public function test_execute_creates_task_file(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTaskDirective::class);

        $response = $this->runDirective('make-task', ['send-welcome-email']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('task created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Tasks/SendWelcomeEmailTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
        $this->assertStringContainsString('extends AbstractTask', $content);
        $this->assertStringContainsString('protected function process(): void', $content);
    }

    public function test_execute_creates_task_in_subdirectory(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTaskDirective::class);

        $response = $this->runDirective('make-task', ['user/send-welcome-email']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('task created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Tasks/User/SendWelcomeEmailTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace App\\Tasks\\User', $content);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
    }

    public function test_execute_adds_task_suffix_automatically(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTaskDirective::class);

        $response = $this->runDirective('make-task', ['process-order']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $expectedPath = $this->directiveTempDir . '/app/Tasks/ProcessOrderTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ProcessOrderTask', $content);
    }

    public function test_execute_does_not_double_task_suffix(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTaskDirective::class);

        $response = $this->runDirective('make-task', ['SendWelcomeEmailTask']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $expectedPath = $this->directiveTempDir . '/app/Tasks/SendWelcomeEmailTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
        $this->assertStringNotContainsString('SendWelcomeEmailTaskTask', $content);
    }
}
