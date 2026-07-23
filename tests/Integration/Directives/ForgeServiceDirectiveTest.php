<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class ForgeServiceDirectiveTest extends IntegrationTestCase
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

    public function test_creates_service_successfully(): void
    {
        $response = $this->service->run('forge:service user');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Service created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Services/UserService.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserService', $content);
        $this->assertStringContainsString('namespace App\\Services', $content);
    }

    public function test_creates_service_with_contract4(): void
    {
        $response = $this->service->run('forge:service user --c');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Service created successfully', $response->output);

        $contractPath = $this->tempDir.'/app/Contracts/Services/UserServiceInterface.php';
        $this->assertFileExists($contractPath);

        $contractContent = file_get_contents($contractPath);
        $this->assertStringContainsString('interface UserServiceInterface', $contractContent);
        $this->assertStringContainsString('namespace App\\Contracts\\Services', $contractContent);

        $servicePath = $this->tempDir.'/app/Services/UserService.php';
        $this->assertFileExists($servicePath);

        $serviceContent = file_get_contents($servicePath);
        $this->assertStringContainsString('class UserService implements UserServiceInterface', $serviceContent);
        $this->assertStringContainsString('use App\\Contracts\\Services\\UserServiceInterface;', $serviceContent);
    }

    public function test_creates_service_with_contract_and_subdirectories(): void
    {
        $response = $this->service->run('forge:service admin.user --c');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $contractPath = $this->tempDir.'/app/Contracts/Services/Admin/UserServiceInterface.php';
        $this->assertFileExists($contractPath);

        $contractContent = file_get_contents($contractPath);
        $this->assertStringContainsString('interface UserServiceInterface', $contractContent);
        $this->assertStringContainsString('namespace App\\Contracts\\Services\\Admin', $contractContent);

        $servicePath = $this->tempDir.'/app/Services/Admin/UserService.php';
        $this->assertFileExists($servicePath);

        $serviceContent = file_get_contents($servicePath);
        $this->assertStringContainsString('class UserService implements UserServiceInterface', $serviceContent);
        $this->assertStringContainsString('use App\\Contracts\\Services\\Admin\\UserServiceInterface;', $serviceContent);
    }

    public function test_creates_service_with_description(): void
    {
        $response = $this->service->run('forge:service user <description="User management service">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Service created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Services/UserService.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserService', $content);
        $this->assertStringContainsString('namespace App\\Services', $content);
        $this->assertStringContainsString('User management service', $content);
    }

    public function test_creates_service_with_subdirectories(): void
    {
        $response = $this->service->run('forge:service admin.user-profile');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Services/Admin/UserProfileService.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserProfileService', $content);
        $this->assertStringContainsString('namespace App\\Services\\Admin', $content);
    }

    public function test_creates_service_with_deep_subdirectories(): void
    {
        $response = $this->service->run('forge:service api.v1.user.create');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Services/Api/V1/User/CreateService.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateService', $content);
        $this->assertStringContainsString('namespace App\\Services\\Api\\V1\\User', $content);
    }

    public function test_creates_service_with_suffix_already_present(): void
    {
        $response = $this->service->run('forge:service user-service');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Services/UserService.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserService', $content);
        $this->assertStringContainsString('namespace App\\Services', $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run('forge:service');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Service name is required', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run('forge:service ');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Service name is required', $response->output);
    }

    public function test_returns_error_when_service_already_exists(): void
    {
        $existingPath = $this->tempDir.'/app/Services/ExistingService.php';
        $this->filesystem->ensureDirectoryExists(dirname($existingPath));
        file_put_contents($existingPath, '<?php // Existing service');

        $response = $this->service->run('forge:service existing');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Service already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        $response = $this->service->run('forge:service invalid@name');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_rejects_snake_case_name(): void
    {
        $response = $this->service->run('forge:service my_custom_service');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Custom');

        $response = $this->service->run('forge:service custom-service');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Services/CustomService.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CustomService', $content);
        $this->assertStringContainsString('namespace Custom\\Services', $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run('forge:service my-custom-service');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Services/MyCustomService.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomService', $content);
    }

    public function test_handles_camel_case_name(): void
    {
        $response = $this->service->run('forge:service myCustomService');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Services/MyCustomService.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomService', $content);
    }

    public function test_creates_service_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Services', 0777, true);

        $response = $this->service->run('forge:service lib-service');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/src/Services/LibService.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class LibService', $content);
    }

    public function test_generates_correct_stub_content(): void
    {
        $response = $this->service->run('forge:service test.content <description="Content service for test">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Services/Test/ContentService.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Services\\Test;', $content);
        $this->assertStringContainsString('class ContentService', $content);
        $this->assertStringContainsString('Content service for test', $content);
    }

    public function test_works_with_create_service_alias(): void
    {
        $response = $this->service->run('create-service alias-test');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Services/AliasTestService.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_svc_alias(): void
    {
        $response = $this->service->run('make-svc svc-test');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Services/SvcTestService.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run('forge:service success-test');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run('forge:service invalid..name');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
