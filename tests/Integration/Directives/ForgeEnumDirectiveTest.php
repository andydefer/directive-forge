<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class ForgeEnumDirectiveTest extends IntegrationTestCase
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
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Enums');
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

    public function test_creates_enum_successfully(): void
    {
        $response = $this->service->run('forge:enum user-status <description="User status enum">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Enum created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Enums/UserStatus.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('enum UserStatus', $content);
        $this->assertStringContainsString('namespace App\\Enums;', $content);
        $this->assertStringContainsString('User status enum', $content);
    }

    public function test_creates_enum_with_subdirectories(): void
    {
        $response = $this->service->run('forge:enum users.status');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Enums/Users/Status.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('enum Status', $content);
        $this->assertStringContainsString('namespace App\\Enums\\Users;', $content);
    }

    public function test_creates_enum_with_deep_subdirectories(): void
    {
        $response = $this->service->run('forge:enum api.v1.users.status');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Enums/Api/V1/Users/Status.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('enum Status', $content);
        $this->assertStringContainsString('namespace App\\Enums\\Api\\V1\\Users;', $content);
    }

    public function test_creates_enum_without_enum_suffix(): void
    {
        $response = $this->service->run('forge:enum user');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Enums/User.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('enum User', $content);
        $this->assertStringContainsString('namespace App\\Enums;', $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run('forge:enum');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Enum name is required', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run('forge:enum ');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Enum name is required', $response->output);
    }

    public function test_returns_error_when_enum_already_exists(): void
    {
        $this->service->run('forge:enum user-status');

        $response = $this->service->run('forge:enum user-status');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Enum already exists', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Custom');

        $response = $this->service->run('forge:enum user-status');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Enums/UserStatus.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace Custom\\Enums;', $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run('forge:enum my-custom-status');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Enums/MyCustomStatus.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('enum MyCustomStatus', $content);
    }

    public function test_handles_snake_case_name(): void
    {
        $response = $this->service->run('forge:enum my_custom_status');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run('forge:enum success-status');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run('forge:enum invalid..name');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }

    public function test_works_with_create_enum_alias(): void
    {
        $response = $this->service->run('create-enum alias-status');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Enums/AliasStatus.php';
        $this->assertFileExists($expectedPath);
    }
}
