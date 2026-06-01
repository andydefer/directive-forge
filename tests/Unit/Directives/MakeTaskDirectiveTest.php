<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeTaskDirective;
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

    private function getDirective(): MakeTaskDirective
    {
        return new MakeTaskDirective($this->interaction);
    }

    private function registerAndRun(string $signature, array $arguments = []): DirectiveResponseRecord
    {
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        return $this->runDirective($signature, $arguments);
    }

    public function test_get_signature_returns_make_task(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the signature
        $signature = $directive->getSignature();

        // Assert: Verify the signature is correct
        $this->assertSame('make-task {name}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the description
        $description = $directive->getDescription();

        // Assert: Verify the description is correct
        $this->assertSame('Create a new task class', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the aliases
        $aliases = $directive->getAliases();

        // Assert: Verify the aliases are correct
        $this->assertTrue($aliases->contains('create-task'));
        $this->assertTrue($aliases->contains('make-job'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act: Run the directive without name argument
        $response = $this->registerAndRun('make-task');

        // Assert: Verify invalid argument error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_execute_creates_task_file(): void
    {
        // Arrange: Prepare task name
        $taskName = 'send-welcome-email';

        // Act: Run the directive to create the task file
        $response = $this->registerAndRun('make-task', [$taskName]);

        $expectedPath = $this->directiveTempDir.'/app/Tasks/SendWelcomeEmailTask.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
        $this->assertStringContainsString('extends AbstractTask', $content);
        $this->assertStringContainsString('protected function process(): void', $content);
    }

    public function test_execute_creates_task_in_subdirectory(): void
    {
        // Arrange: Prepare task name with subdirectories
        $taskName = 'user/send-welcome-email';

        // Act: Run the directive to create the task file
        $response = $this->registerAndRun('make-task', [$taskName]);

        $expectedPath = $this->directiveTempDir.'/app/Tasks/User/SendWelcomeEmailTask.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and correct namespace
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Tasks\\User', $content);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
    }

    public function test_execute_adds_task_suffix_automatically(): void
    {
        // Arrange: Prepare task name without suffix
        $taskName = 'process-order';

        // Act: Run the directive to create the task file
        $response = $this->registerAndRun('make-task', [$taskName]);

        $expectedPath = $this->directiveTempDir.'/app/Tasks/ProcessOrderTask.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix was added automatically
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProcessOrderTask', $content);
    }

    public function test_execute_does_not_double_task_suffix(): void
    {
        // Arrange: Prepare task name that already has suffix
        $taskName = 'SendWelcomeEmailTask';

        // Act: Run the directive to create the task file
        $response = $this->registerAndRun('make-task', [$taskName]);

        $expectedPath = $this->directiveTempDir.'/app/Tasks/SendWelcomeEmailTask.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix is not duplicated
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
        $this->assertStringNotContainsString('SendWelcomeEmailTaskTask', $content);
    }
}
