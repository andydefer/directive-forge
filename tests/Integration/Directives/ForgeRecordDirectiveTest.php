<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class ForgeRecordDirectiveTest extends IntegrationTestCase
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

    public function test_creates_record_successfully(): void
    {
        // Arrange: Prepare the directive execution
        $response = $this->service->run('forge:record user');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Record created successfully', $response->output);

        // Assert: Verify the file was created with correct content
        $expectedPath = $this->tempDir.'/app/Records/UserRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserRecord', $content);
        $this->assertStringContainsString('namespace App\\Records', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_creates_record_with_description(): void
    {
        // Arrange: Prepare the directive execution with description using custom tag
        $response = $this->service->run('forge:record user-profile <description="User profile record data transfer object">');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Record created successfully', $response->output);

        // Assert: Verify the file was created with description in content
        $expectedPath = $this->tempDir.'/app/Records/UserProfileRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserProfileRecord', $content);
        $this->assertStringContainsString('namespace App\\Records', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
        $this->assertStringContainsString('User profile record data transfer object', $content);
    }

    public function test_creates_record_with_subdirectories(): void
    {
        // Arrange: Prepare the directive execution with subdirectory
        $response = $this->service->run('forge:record admin.user-profile');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the correct subdirectory
        $expectedPath = $this->tempDir.'/app/Records/Admin/UserProfileRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserProfileRecord', $content);
        $this->assertStringContainsString('namespace App\\Records\\Admin', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_creates_record_with_deep_subdirectories(): void
    {
        // Arrange: Prepare the directive execution with deep subdirectory
        $response = $this->service->run('forge:record api.v1.user.create');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the correct deep subdirectory
        $expectedPath = $this->tempDir.'/app/Records/Api/V1/User/CreateRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateRecord', $content);
        $this->assertStringContainsString('namespace App\\Records\\Api\\V1\\User', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_creates_record_with_suffix_already_present(): void
    {
        // Arrange: Prepare the directive execution with name already containing suffix
        $response = $this->service->run('forge:record user-record');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created without duplicating suffix
        $expectedPath = $this->tempDir.'/app/Records/UserRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserRecord', $content);
        $this->assertStringContainsString('namespace App\\Records', $content);
    }

    public function test_creates_record_with_suffix_in_subdirectory(): void
    {
        // Arrange: Prepare the directive execution with subdirectory and suffix
        $response = $this->service->run('forge:record admin.user-profile-record');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the correct subdirectory
        $expectedPath = $this->tempDir.'/app/Records/Admin/UserProfileRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserProfileRecord', $content);
        $this->assertStringContainsString('namespace App\\Records\\Admin', $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        // Act: Execute the directive without providing a name
        $response = $this->service->run('forge:record');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Record name is required', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        // Act: Execute the directive with an empty name
        $response = $this->service->run('forge:record ');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Record name is required', $response->output);
    }

    public function test_returns_error_when_record_already_exists(): void
    {
        // Arrange: Create an existing record file
        $existingPath = $this->tempDir.'/app/Records/ExistingRecord.php';
        $this->filesystem->ensureDirectoryExists(dirname($existingPath));
        file_put_contents($existingPath, '<?php // Existing record');

        // Act: Attempt to create a record that already exists
        $response = $this->service->run('forge:record existing');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Record already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        // Act: Attempt to create a record with invalid characters
        $response = $this->service->run('forge:record invalid@name');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        // Arrange: Change the namespace configuration
        $this->app['config']->set('directive-forge.namespace', 'Custom\\Dto');

        // Act: Execute the directive with custom namespace
        $response = $this->service->run('forge:record custom-record');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with the custom namespace
        $expectedPath = $this->tempDir.'/app/Records/CustomRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CustomRecord', $content);
        $this->assertStringContainsString('namespace Custom\\Dto', $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        // Arrange: Prepare the directive execution with kebab case
        $response = $this->service->run('forge:record my-custom-record');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with PascalCase class name
        $expectedPath = $this->tempDir.'/app/Records/MyCustomRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomRecord', $content);
    }

    public function test_handles_camel_case_name(): void
    {
        // Arrange: Prepare the directive execution with camel case
        $response = $this->service->run('forge:record myCustomRecord');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with the same name
        $expectedPath = $this->tempDir.'/app/Records/MyCustomRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomRecord', $content);
    }

    public function test_handles_snake_case_name(): void
    {
        // Act: Attempt to create a record with snake case (invalid)
        $response = $this->service->run('forge:record my_custom_record');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_creates_record_in_src_when_mode_library(): void
    {
        // Arrange: Change the mode to library
        $this->app['config']->set('directive-forge.mode', 'library');

        // Create the src directory structure
        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Records', 0777, true);

        // Act: Execute the directive in library mode
        $response = $this->service->run('forge:record lib-record');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the src directory
        $expectedPath = $this->tempDir.'/src/Records/LibRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class LibRecord', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_generates_correct_stub_content(): void
    {
        // Arrange: Prepare the directive execution
        $response = $this->service->run('forge:record test.content');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with correct stub content
        $expectedPath = $this->tempDir.'/app/Records/Test/ContentRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Records\\Test;', $content);
        $this->assertStringContainsString('class ContentRecord extends AbstractRecord', $content);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Abstracts\\AbstractRecord;', $content);
    }

    public function test_works_with_create_record_alias(): void
    {
        // Act: Execute the directive using the alias
        $response = $this->service->run('create-record alias-test');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created
        $expectedPath = $this->tempDir.'/app/Records/AliasTestRecord.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_dto_alias(): void
    {
        // Act: Execute the directive using the alias
        $response = $this->service->run('make-record dto-test');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created
        $expectedPath = $this->tempDir.'/app/Records/DtoTestRecord.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        // Act: Execute the directive successfully
        $response = $this->service->run('forge:record success-test');

        // Assert: Verify the success exit code
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        // Act: Execute the directive with invalid input
        $response = $this->service->run('forge:record invalid..name');

        // Assert: Verify the failure exit code
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
