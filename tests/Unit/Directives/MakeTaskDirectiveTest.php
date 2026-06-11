<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeTaskDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class MakeTaskDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DirectiveTestingService($this->app);
    }

    protected function tearDown(): void
    {
        $this->service->destroy();
        parent::tearDown();
    }

    public function test_get_signature_returns_make_task(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_get_description_returns_description(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $this->service->registerDirective(MakeTaskDirective::class);

        $response = $this->service->runDirective('create-task', ['test-alias']);
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $response = $this->service->runDirective('make-job', ['test-alias-2']);
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_execute_creates_task_file(): void
    {
        $taskName = 'send-welcome-email';

        $response = $this->service->run(MakeTaskDirective::class, [$taskName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Tasks/SendWelcomeEmailTask.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
        $this->assertStringContainsString('extends AbstractTask', $content);
        $this->assertStringContainsString('protected function process(): void', $content);
    }

    public function test_execute_creates_task_in_subdirectory(): void
    {
        $taskName = 'user/send-welcome-email';

        $response = $this->service->run(MakeTaskDirective::class, [$taskName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Tasks/User/SendWelcomeEmailTask.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Tasks\\User', $content);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
    }

    public function test_execute_adds_task_suffix_automatically(): void
    {
        $taskName = 'process-order';

        $response = $this->service->run(MakeTaskDirective::class, [$taskName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Tasks/ProcessOrderTask.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProcessOrderTask', $content);
    }

    public function test_execute_does_not_double_task_suffix(): void
    {
        $taskName = 'SendWelcomeEmailTask';

        $response = $this->service->run(MakeTaskDirective::class, [$taskName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Tasks/SendWelcomeEmailTask.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
        $this->assertStringNotContainsString('SendWelcomeEmailTaskTask', $content);
    }
}
