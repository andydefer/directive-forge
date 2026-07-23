<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class ForgeConfigDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    private string $tempDir;

    private FileSystemService $filesystem;

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

    public function test_creates_config_with_interface_and_implementation(): void
    {
        $response = $this->service->run('forge:config database');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Config created successfully', $response->output);

        $interfacePath = $this->tempDir.'/app/Contracts/Configs/DatabaseConfigInterface.php';
        $this->assertFileExists($interfacePath);

        $interfaceContent = file_get_contents($interfacePath);
        $this->assertStringContainsString('interface DatabaseConfigInterface', $interfaceContent);
        $this->assertStringContainsString('namespace App\\Contracts\\Configs', $interfaceContent);

        $implPath = $this->tempDir.'/app/Configs/DatabaseConfig.php';
        $this->assertFileExists($implPath);

        $implContent = file_get_contents($implPath);
        $this->assertStringContainsString('class DatabaseConfig', $implContent);
        $this->assertStringContainsString('namespace App\\Configs', $implContent);
        $this->assertStringContainsString('implements DatabaseConfigInterface', $implContent);
        $this->assertStringContainsString('use App\\Contracts\\Configs\\DatabaseConfigInterface;', $implContent);
    }

    public function test_creates_config_with_subdirectories(): void
    {
        $response = $this->service->run('forge:config admin.user-config');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $interfacePath = $this->tempDir.'/app/Contracts/Configs/Admin/UserConfigInterface.php';
        $this->assertFileExists($interfacePath);

        $interfaceContent = file_get_contents($interfacePath);
        $this->assertStringContainsString('interface UserConfigInterface', $interfaceContent);
        $this->assertStringContainsString('namespace App\\Contracts\\Configs\\Admin', $interfaceContent);

        $implPath = $this->tempDir.'/app/Configs/Admin/UserConfig.php';
        $this->assertFileExists($implPath);

        $implContent = file_get_contents($implPath);
        $this->assertStringContainsString('class UserConfig', $implContent);
        $this->assertStringContainsString('namespace App\\Configs\\Admin', $implContent);
        $this->assertStringContainsString('implements UserConfigInterface', $implContent);
        $this->assertStringContainsString('use App\\Contracts\\Configs\\Admin\\UserConfigInterface;', $implContent);
    }

    public function test_creates_config_with_deep_subdirectories(): void
    {
        $response = $this->service->run('forge:config api.v1.client.config');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $interfacePath = $this->tempDir.'/app/Contracts/Configs/Api/V1/Client/ConfigInterface.php';
        $this->assertFileExists($interfacePath);

        $interfaceContent = file_get_contents($interfacePath);
        $this->assertStringContainsString('interface ConfigInterface', $interfaceContent);
        $this->assertStringContainsString('namespace App\\Contracts\\Configs\\Api\\V1\\Client', $interfaceContent);

        $implPath = $this->tempDir.'/app/Configs/Api/V1/Client/Config.php';
        $this->assertFileExists($implPath);

        $implContent = file_get_contents($implPath);
        $this->assertStringContainsString('class Config', $implContent);
        $this->assertStringContainsString('namespace App\\Configs\\Api\\V1\\Client', $implContent);
        $this->assertStringContainsString('implements ConfigInterface', $implContent);
        $this->assertStringContainsString('use App\\Contracts\\Configs\\Api\\V1\\Client\\ConfigInterface;', $implContent);
    }

    public function test_creates_config_with_suffix_already_present(): void
    {
        $response = $this->service->run('forge:config database-config');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $implPath = $this->tempDir.'/app/Configs/DatabaseConfig.php';
        $this->assertFileExists($implPath);

        $content = file_get_contents($implPath);
        $this->assertStringContainsString('class DatabaseConfig', $content);
        $this->assertStringContainsString('namespace App\\Configs', $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run('forge:config');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Config name is required', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run('forge:config ');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Config name is required', $response->output);
    }

    public function test_returns_error_when_config_already_exists(): void
    {
        $this->service->run('forge:config existing');
        $response = $this->service->run('forge:config existing');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Config already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        $response = $this->service->run('forge:config invalid@name');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Custom');

        $response = $this->service->run('forge:config custom-config');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $interfacePath = $this->tempDir.'/app/Contracts/Configs/CustomConfigInterface.php';
        $this->assertFileExists($interfacePath);

        $interfaceContent = file_get_contents($interfacePath);
        $this->assertStringContainsString('namespace Custom\\Contracts\\Configs', $interfaceContent);

        $implPath = $this->tempDir.'/app/Configs/CustomConfig.php';
        $this->assertFileExists($implPath);

        $implContent = file_get_contents($implPath);
        $this->assertStringContainsString('namespace Custom\\Configs', $implContent);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run('forge:config my-custom-config');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $implPath = $this->tempDir.'/app/Configs/MyCustomConfig.php';
        $this->assertFileExists($implPath);

        $content = file_get_contents($implPath);
        $this->assertStringContainsString('class MyCustomConfig', $content);
    }

    public function test_handles_camel_case_name(): void
    {
        $response = $this->service->run('forge:config myCustomConfig');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $implPath = $this->tempDir.'/app/Configs/MyCustomConfig.php';
        $this->assertFileExists($implPath);

        $content = file_get_contents($implPath);
        $this->assertStringContainsString('class MyCustomConfig', $content);
    }

    public function test_handles_snake_case_name(): void
    {
        $response = $this->service->run('forge:config my_custom_config');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_creates_config_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Contracts/Configs', 0777, true);
        mkdir($this->tempDir.'/src/Configs', 0777, true);

        $response = $this->service->run('forge:config lib-config');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $interfacePath = $this->tempDir.'/src/Contracts/Configs/LibConfigInterface.php';
        $this->assertFileExists($interfacePath);

        $interfaceContent = file_get_contents($interfacePath);
        $this->assertStringContainsString('interface LibConfigInterface', $interfaceContent);
        $this->assertStringContainsString('namespace App\\Contracts\\Configs', $interfaceContent);

        $implPath = $this->tempDir.'/src/Configs/LibConfig.php';
        $this->assertFileExists($implPath);

        $implContent = file_get_contents($implPath);
        $this->assertStringContainsString('class LibConfig', $implContent);
        $this->assertStringContainsString('namespace App\\Configs', $implContent);
        $this->assertStringContainsString('implements LibConfigInterface', $implContent);
        $this->assertStringContainsString('use App\\Contracts\\Configs\\LibConfigInterface;', $implContent);
    }

    public function test_generates_correct_stub_content(): void
    {
        $response = $this->service->run('forge:config test.config <description="Config interface for test">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $interfacePath = $this->tempDir.'/app/Contracts/Configs/Test/ConfigInterface.php';
        $this->assertFileExists($interfacePath);

        $interfaceContent = file_get_contents($interfacePath);
        $this->assertStringContainsString('declare(strict_types=1);', $interfaceContent);
        $this->assertStringContainsString('namespace App\\Contracts\\Configs\\Test;', $interfaceContent);
        $this->assertStringContainsString('interface ConfigInterface', $interfaceContent);
        $this->assertStringContainsString('Config interface for test', $interfaceContent);

        $implPath = $this->tempDir.'/app/Configs/Test/Config.php';
        $this->assertFileExists($implPath);

        $implContent = file_get_contents($implPath);
        $this->assertStringContainsString('declare(strict_types=1);', $implContent);
        $this->assertStringContainsString('namespace App\\Configs\\Test;', $implContent);
        $this->assertStringContainsString('class Config', $implContent);
        $this->assertStringContainsString('use App\\Contracts\\Configs\\Test\\ConfigInterface;', $implContent);
        $this->assertStringContainsString('implements ConfigInterface', $implContent);
        $this->assertStringContainsString('Config interface for test', $implContent);
    }

    public function test_works_with_create_config_alias(): void
    {
        $response = $this->service->run('create-config alias-test');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $implPath = $this->tempDir.'/app/Configs/AliasTestConfig.php';
        $this->assertFileExists($implPath);
    }

    public function test_works_with_make_config_class_alias(): void
    {
        $response = $this->service->run('make-config-class class-test');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $implPath = $this->tempDir.'/app/Configs/ClassTestConfig.php';
        $this->assertFileExists($implPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run('forge:config success-test');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run('forge:config invalid..name');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }

    public function test_interface_and_implementation_have_consistent_names(): void
    {
        $response = $this->service->run('forge:config user-profile');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $interfacePath = $this->tempDir.'/app/Contracts/Configs/UserProfileConfigInterface.php';
        $this->assertFileExists($interfacePath);

        $interfaceContent = file_get_contents($interfacePath);
        $this->assertStringContainsString('interface UserProfileConfigInterface', $interfaceContent);
        $this->assertStringContainsString('namespace App\\Contracts\\Configs', $interfaceContent);

        $implPath = $this->tempDir.'/app/Configs/UserProfileConfig.php';
        $this->assertFileExists($implPath);

        $implContent = file_get_contents($implPath);
        $this->assertStringContainsString('implements UserProfileConfigInterface', $implContent);
        $this->assertStringContainsString('use App\\Contracts\\Configs\\UserProfileConfigInterface;', $implContent);
    }
}
