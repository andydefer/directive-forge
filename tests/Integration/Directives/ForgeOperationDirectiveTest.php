<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class ForgeOperationDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DirectiveTestingService(
            application: $this->app,
            sourcePaths: []
        );

        $this->tempDir = $this->service->getTempDir();

        $this->app['config']->set('directive-forge.mode', 'app');
        $this->app['config']->set('directive-forge.namespace', 'App');
        $this->app['config']->set('directive-forge.extension', 'php');
        $this->app['config']->set('directive-forge.directory_permission', 0755);

        $filesystem = new FileSystemService;
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Operations');
    }

    protected function tearDown(): void
    {
        $this->service->destroy();

        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$file;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    public function test_creates_operation_successfully(): void
    {
        $response = $this->service->run('forge:operation generate-user <description="Generate a new user">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Operation created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Operations/GenerateUserOperation.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class GenerateUserOperation', $content);
        $this->assertStringContainsString('namespace App\\Operations;', $content);
        $this->assertStringContainsString('Generate a new user', $content);
        $this->assertStringContainsString('public function handle()', $content);
    }

    public function test_creates_operation_with_subdirectories(): void
    {
        $response = $this->service->run('forge:operation users.generate');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Operations/Users/GenerateOperation.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class GenerateOperation', $content);
        $this->assertStringContainsString('namespace App\\Operations\\Users;', $content);
    }

    public function test_creates_operation_with_deep_subdirectories(): void
    {
        $response = $this->service->run('forge:operation api.v1.users.create');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Operations/Api/V1/Users/CreateOperation.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateOperation', $content);
        $this->assertStringContainsString('namespace App\\Operations\\Api\\V1\\Users;', $content);
    }

    public function test_creates_operation_without_operation_suffix(): void
    {
        $response = $this->service->run('forge:operation generate');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Operations/GenerateOperation.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class GenerateOperation', $content);
        $this->assertStringContainsString('namespace App\\Operations;', $content);
    }

    public function test_creates_operation_with_suffix_already_present(): void
    {
        $response = $this->service->run('forge:operation generate-user-operation');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Operations/GenerateUserOperation.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class GenerateUserOperation', $content);
        $this->assertStringContainsString('namespace App\\Operations;', $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run('forge:operation');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Operation name is required', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run('forge:operation ');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Operation name is required', $response->output);
    }

    public function test_returns_error_when_operation_already_exists(): void
    {
        $this->service->run('forge:operation generate-user');

        $response = $this->service->run('forge:operation generate-user');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Operation already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        $response = $this->service->run('forge:operation invalid@name');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_handles_snake_case_name(): void
    {
        $response = $this->service->run('forge:operation my_custom_operation');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Custom');

        $response = $this->service->run('forge:operation generate-user');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Operations/GenerateUserOperation.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace Custom\\Operations;', $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run('forge:operation my-custom-operation');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Operations/MyCustomOperation.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomOperation', $content);
    }

    public function test_handles_camel_case_name(): void
    {
        $response = $this->service->run('forge:operation myCustomOperation');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Operations/MyCustomOperation.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomOperation', $content);
    }

    public function test_creates_operation_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        $filesystem = new FileSystemService;
        $filesystem->ensureDirectoryExists($this->tempDir.'/src/Operations');

        $response = $this->service->run('forge:operation generate-user');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/src/Operations/GenerateUserOperation.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class GenerateUserOperation', $content);
        $this->assertStringContainsString('namespace App\\Operations;', $content);
    }

    public function test_generates_correct_stub_content(): void
    {
        $response = $this->service->run('forge:operation test.content <description="Test operation">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Operations/Test/ContentOperation.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Operations\\Test;', $content);
        $this->assertStringContainsString('class ContentOperation', $content);
        $this->assertStringContainsString('Test operation', $content);
        $this->assertStringContainsString('public function handle()', $content);
        $this->assertStringContainsString('// TODO: Implement your operation logic here', $content);
    }

    public function test_works_with_create_operation_alias(): void
    {
        $response = $this->service->run('create-operation generate-user');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Operations/GenerateUserOperation.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_op_alias(): void
    {
        $response = $this->service->run('make-op generate-user');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Operations/GenerateUserOperation.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run('forge:operation success-operation');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run('forge:operation invalid..name');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
