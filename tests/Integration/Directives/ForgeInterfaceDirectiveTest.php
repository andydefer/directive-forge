<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class ForgeInterfaceDirectiveTest extends IntegrationTestCase
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

    public function test_creates_interface_successfully(): void
    {
        // Arrange: Prepare the directive execution
        $response = $this->service->run('forge:interface database');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Interface created successfully', $response->output);

        // Assert: Verify the file was created with correct content
        $expectedPath = $this->tempDir.'/app/Contracts/DatabaseInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface DatabaseInterface', $content);
        $this->assertStringContainsString('namespace App\\Contracts', $content);
    }

    public function test_creates_interface_with_description(): void
    {
        // Arrange: Prepare the directive execution with description using custom tag
        $response = $this->service->run('forge:interface database <description="Database configuration interface">');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Interface created successfully', $response->output);

        // Assert: Verify the file was created with description in content
        $expectedPath = $this->tempDir.'/app/Contracts/DatabaseInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface DatabaseInterface', $content);
        $this->assertStringContainsString('namespace App\\Contracts', $content);
        $this->assertStringContainsString('Database configuration interface', $content);
    }

    public function test_creates_interface_with_subdirectories(): void
    {
        // Arrange: Prepare the directive execution with subdirectory
        $response = $this->service->run('forge:interface admin.user-profile');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the correct subdirectory
        $expectedPath = $this->tempDir.'/app/Contracts/Admin/UserProfileInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface UserProfileInterface', $content);
        $this->assertStringContainsString('namespace App\\Contracts\\Admin', $content);
    }

    public function test_creates_interface_with_deep_subdirectories(): void
    {
        // Arrange: Prepare the directive execution with deep subdirectory
        $response = $this->service->run('forge:interface api.v1.client.config');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the correct deep subdirectory
        $expectedPath = $this->tempDir.'/app/Contracts/Api/V1/Client/ConfigInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface ConfigInterface', $content);
        $this->assertStringContainsString('namespace App\\Contracts\\Api\\V1\\Client', $content);
    }

    public function test_creates_interface_with_suffix_already_present(): void
    {
        // Arrange: Prepare the directive execution with name already containing suffix
        $response = $this->service->run('forge:interface database-interface');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created without duplicating suffix
        $expectedPath = $this->tempDir.'/app/Contracts/DatabaseInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface DatabaseInterface', $content);
    }

    public function test_creates_interface_with_suffix_in_subdirectory(): void
    {
        // Arrange: Prepare the directive execution with subdirectory and suffix
        $response = $this->service->run('forge:interface admin.user-profile-interface');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the correct subdirectory
        $expectedPath = $this->tempDir.'/app/Contracts/Admin/UserProfileInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface UserProfileInterface', $content);
        $this->assertStringContainsString('namespace App\\Contracts\\Admin', $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        // Act: Execute the directive without providing a name
        $response = $this->service->run('forge:interface');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Interface name is required', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        // Act: Execute the directive with an empty name
        $response = $this->service->run('forge:interface ');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Interface name is required', $response->output);
    }

    public function test_returns_error_when_interface_already_exists(): void
    {
        // Arrange: Create an existing interface
        $this->service->run('forge:interface existing');

        // Act: Attempt to create an interface that already exists
        $response = $this->service->run('forge:interface existing');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Interface already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        // Act: Attempt to create an interface with invalid characters
        $response = $this->service->run('forge:interface invalid@name');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        // Arrange: Change the namespace configuration
        $this->app['config']->set('directive-forge.namespace', 'Custom');

        // Act: Execute the directive with custom namespace
        $response = $this->service->run('forge:interface custom-interface');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with the custom namespace
        $expectedPath = $this->tempDir.'/app/Contracts/CustomInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace Custom\\Contracts', $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        // Arrange: Prepare the directive execution with kebab case
        $response = $this->service->run('forge:interface my-custom-interface');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with PascalCase class name
        $expectedPath = $this->tempDir.'/app/Contracts/MyCustomInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface MyCustomInterface', $content);
    }

    public function test_handles_camel_case_name(): void
    {
        // Arrange: Prepare the directive execution with camel case
        $response = $this->service->run('forge:interface myCustomInterface');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with the same name
        $expectedPath = $this->tempDir.'/app/Contracts/MyCustomInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface MyCustomInterface', $content);
    }

    public function test_handles_snake_case_name(): void
    {
        // Act: Attempt to create an interface with snake case (invalid)
        $response = $this->service->run('forge:interface my_custom_interface');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_creates_interface_in_src_when_mode_library(): void
    {
        // Arrange: Change the mode to library
        $this->app['config']->set('directive-forge.mode', 'library');

        // Create the src directory structure
        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Contracts', 0777, true);

        // Act: Execute the directive in library mode
        $response = $this->service->run('forge:interface lib-interface');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the src directory
        $expectedPath = $this->tempDir.'/src/Contracts/LibInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('interface LibInterface', $content);
        $this->assertStringContainsString('namespace App\\Contracts', $content);
    }

    public function test_generates_correct_stub_content(): void
    {
        // Arrange: Prepare the directive execution
        $response = $this->service->run('forge:interface test.config <description="Config interface for test">');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with correct stub content
        $expectedPath = $this->tempDir.'/app/Contracts/Test/ConfigInterface.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Contracts\\Test;', $content);
        $this->assertStringContainsString('interface ConfigInterface', $content);
        $this->assertStringContainsString('Config interface for test', $content);
    }

    public function test_works_with_create_interface_alias(): void
    {
        // Act: Execute the directive using the alias
        $response = $this->service->run('create-interface alias-test');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created
        $expectedPath = $this->tempDir.'/app/Contracts/AliasTestInterface.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_contract_alias(): void
    {
        // Act: Execute the directive using the alias
        $response = $this->service->run('make-contract contract-test');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created
        $expectedPath = $this->tempDir.'/app/Contracts/ContractTestInterface.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        // Act: Execute the directive successfully
        $response = $this->service->run('forge:interface success-test');

        // Assert: Verify the success exit code
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        // Act: Execute the directive with invalid input
        $response = $this->service->run('forge:interface invalid..name');

        // Assert: Verify the failure exit code
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
