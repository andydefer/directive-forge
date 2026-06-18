<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeInterfaceDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class MakeInterfaceDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    private string $tempDir;

    private FileSystemService $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DirectiveTestingService($this->app);
        $this->tempDir = $this->service->getTempDir();

        $this->app['config']->set('directive-forge.mode', 'app');
        $this->app['config']->set('directive-forge.namespace', 'App');
        $this->app['config']->set('directive-forge.extension', 'php');
        $this->app['config']->set('directive-forge.directory_permission', 0755);

        $this->filesystem = new FileSystemService;
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

    public function test_creates_interface_successfully(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'database',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Interface created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Contracts/DatabaseInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface DatabaseInterface', $content);
        $this->assertStringContainsString('namespace App\\Contracts', $content);
    }

    public function test_creates_interface_with_subdirectories(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'admin.user-profile',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Contracts/Admin/UserProfileInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface UserProfileInterface', $content);
        $this->assertStringContainsString('namespace App\\Contracts\\Admin', $content);
    }

    public function test_creates_interface_with_deep_subdirectories(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'api.v1.client.config',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Contracts/Api/V1/Client/ConfigInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface ConfigInterface', $content);
        $this->assertStringContainsString('namespace App\\Contracts\\Api\\V1\\Client', $content);
    }

    public function test_creates_interface_with_suffix_already_present(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'database-interface',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Contracts/DatabaseInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface DatabaseInterface', $content);
    }

    public function test_creates_interface_with_suffix_in_subdirectory(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'admin.user-profile-interface',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Contracts/Admin/UserProfileInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface UserProfileInterface', $content);
        $this->assertStringContainsString('namespace App\\Contracts\\Admin', $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, ['']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Interface name is required', $response->output);
    }

    public function test_returns_error_when_interface_already_exists(): void
    {
        $this->service->run(MakeInterfaceDirective::class, ['existing']);

        $response = $this->service->run(MakeInterfaceDirective::class, ['existing']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Interface already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, ['invalid@name']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Custom');

        $response = $this->service->run(MakeInterfaceDirective::class, [
            'custom-interface',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Contracts/CustomInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace Custom\\Contracts', $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'my-custom-interface',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Contracts/MyCustomInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface MyCustomInterface', $content);
    }

    public function test_handles_camel_case_name(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'myCustomInterface',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Contracts/MyCustomInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface MyCustomInterface', $content);
    }

    public function test_handles_snake_case_name(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'my_custom_interface',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_creates_interface_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Contracts', 0777, true);

        $response = $this->service->run(MakeInterfaceDirective::class, [
            'lib-interface',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/src/Contracts/LibInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface LibInterface', $content);
        $this->assertStringContainsString('namespace App\\Contracts', $content);
    }

    public function test_generates_correct_stub_content(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'test.config',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Contracts/Test/ConfigInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Contracts\\Test;', $content);
        $this->assertStringContainsString('interface ConfigInterface', $content);
        $this->assertStringContainsString('Interface for test.config', $content);
    }

    public function test_works_with_create_interface_alias(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'alias-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Contracts/AliasTestInterface.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_contract_alias(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'contract-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Contracts/ContractTestInterface.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'success-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run(MakeInterfaceDirective::class, [
            'invalid..name',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
