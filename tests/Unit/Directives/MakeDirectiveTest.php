<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class MakeDirectiveTest extends IntegrationTestCase
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

    public function test_get_signature_returns_make_directive(): void
    {
        $response = $this->service->run(MakeDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
    }

    public function test_get_description_returns_description(): void
    {
        $response = $this->service->run(MakeDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $this->service->registerDirective(MakeDirective::class);

        $response = $this->service->runDirective('create-directive', ['test-alias']);
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $response = $this->service->runDirective('make-cmd', ['test-alias-2']);
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_execute_creates_file_with_correct_replacements(): void
    {
        $directiveName = 'user-list';

        $response = $this->service->run(MakeDirective::class, [$directiveName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Directives/UserListDirective.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserListDirective', $content);
        $this->assertStringContainsString("return 'user-list'", $content);
        $this->assertStringContainsString('namespace App\\Directives;', $content);
    }

    public function test_execute_creates_file_in_subdirectory(): void
    {
        $directiveName = 'user/domain/hello-directive';

        $response = $this->service->run(MakeDirective::class, [$directiveName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Directives/User/Domain/HelloDirective.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertDirectoryExists($tempDir . '/app/Directives/User/Domain');
        $this->assertStringContainsString('namespace App\\Directives\\User\\Domain;', $content);
        $this->assertStringContainsString('class HelloDirective', $content);
        $this->assertStringContainsString("return 'hello'", $content);
    }

    public function test_execute_adds_directive_suffix_automatically(): void
    {
        $directiveName = 'hello';

        $response = $this->service->run(MakeDirective::class, [$directiveName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Directives/HelloDirective.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class HelloDirective', $content);
        $this->assertStringNotContainsString('HelloDirectiveDirective', $content);
        $this->assertStringContainsString("return 'hello'", $content);
    }

    public function test_execute_does_not_double_directive_suffix(): void
    {
        $directiveName = 'hello-directive';

        $response = $this->service->run(MakeDirective::class, [$directiveName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Directives/HelloDirective.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class HelloDirective', $content);
        $this->assertStringNotContainsString('HelloDirectiveDirective', $content);
        $this->assertStringContainsString("return 'hello'", $content);
    }

    public function test_execute_rejects_invalid_directive_name(): void
    {
        $invalidName = 'user@list';

        $response = $this->service->run(MakeDirective::class, [$invalidName]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Invalid directive name', $response->output);
    }
}
