<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class ForgeDataDirectiveTest extends IntegrationTestCase
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

    public function test_creates_data_successfully(): void
    {
        // Arrange: Prepare the directive execution
        $response = $this->service->run('forge:data user');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Data created successfully', $response->output);

        // Assert: Verify the file was created with correct content
        $expectedPath = $this->tempDir.'/app/Datas/UserData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserData', $content);
        $this->assertStringContainsString('namespace App\\Datas', $content);
        $this->assertStringContainsString('extends AbstractData', $content);
    }

    public function test_creates_data_with_description(): void
    {
        // Arrange: Prepare the directive execution with description using custom tag
        $response = $this->service->run('forge:data user-profile <description="User profile data transfer object">');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Data created successfully', $response->output);

        // Assert: Verify the file was created with description in content
        $expectedPath = $this->tempDir.'/app/Datas/UserProfileData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserProfileData', $content);
        $this->assertStringContainsString('namespace App\\Datas', $content);
        $this->assertStringContainsString('extends AbstractData', $content);
        $this->assertStringContainsString('User profile data transfer object', $content);
    }

    public function test_creates_data_with_subdirectories(): void
    {
        // Arrange: Prepare the directive execution with subdirectory
        $response = $this->service->run('forge:data admin.user-profile');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the correct subdirectory
        $expectedPath = $this->tempDir.'/app/Datas/Admin/UserProfileData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserProfileData', $content);
        $this->assertStringContainsString('namespace App\\Datas\\Admin', $content);
        $this->assertStringContainsString('extends AbstractData', $content);
    }

    public function test_creates_data_with_deep_subdirectories(): void
    {
        // Arrange: Prepare the directive execution with deep subdirectory
        $response = $this->service->run('forge:data api.v1.user.create');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the correct deep subdirectory
        $expectedPath = $this->tempDir.'/app/Datas/Api/V1/User/CreateData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateData', $content);
        $this->assertStringContainsString('namespace App\\Datas\\Api\\V1\\User', $content);
        $this->assertStringContainsString('extends AbstractData', $content);
    }

    public function test_creates_data_with_suffix_already_present(): void
    {
        // Arrange: Prepare the directive execution with name already containing suffix
        $response = $this->service->run('forge:data user-data');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created without duplicating suffix
        $expectedPath = $this->tempDir.'/app/Datas/UserData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserData', $content);
        $this->assertStringContainsString('namespace App\\Datas', $content);
    }

    public function test_creates_data_with_suffix_in_subdirectory(): void
    {
        // Arrange: Prepare the directive execution with subdirectory and suffix
        $response = $this->service->run('forge:data admin.user-profile-data');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the correct subdirectory
        $expectedPath = $this->tempDir.'/app/Datas/Admin/UserProfileData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserProfileData', $content);
        $this->assertStringContainsString('namespace App\\Datas\\Admin', $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        // Act: Execute the directive without providing a name
        $response = $this->service->run('forge:data');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Data name is required', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        // Act: Execute the directive with an empty name
        $response = $this->service->run('forge:data ');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Data name is required', $response->output);
    }

    public function test_returns_error_when_data_already_exists(): void
    {
        // Arrange: Create an existing data file
        $existingPath = $this->tempDir.'/app/Datas/ExistingData.php';
        $this->filesystem->ensureDirectoryExists(dirname($existingPath));
        file_put_contents($existingPath, '<?php // Existing data');

        // Act: Attempt to create a data that already exists
        $response = $this->service->run('forge:data existing');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Data already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        // Act: Attempt to create a data with invalid characters
        $response = $this->service->run('forge:data invalid@name');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        // Arrange: Change the namespace configuration
        $this->app['config']->set('directive-forge.namespace', 'Custom\\Dto');

        // Act: Execute the directive with custom namespace
        $response = $this->service->run('forge:data custom-data');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with the custom namespace
        $expectedPath = $this->tempDir.'/app/Datas/CustomData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CustomData', $content);
        $this->assertStringContainsString('namespace Custom\\Dto', $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        // Arrange: Prepare the directive execution with kebab case
        $response = $this->service->run('forge:data my-custom-data');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with PascalCase class name
        $expectedPath = $this->tempDir.'/app/Datas/MyCustomData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomData', $content);
    }

    public function test_handles_camel_case_name(): void
    {
        // Arrange: Prepare the directive execution with camel case
        $response = $this->service->run('forge:data myCustomData');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with the same name
        $expectedPath = $this->tempDir.'/app/Datas/MyCustomData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomData', $content);
    }

    public function test_handles_snake_case_name(): void
    {
        // Act: Attempt to create a data with snake case (invalid)
        $response = $this->service->run('forge:data my_custom_data');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_creates_data_in_src_when_mode_library(): void
    {
        // Arrange: Change the mode to library
        $this->app['config']->set('directive-forge.mode', 'library');

        // Create the src directory structure
        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Datas', 0777, true);

        // Act: Execute the directive in library mode
        $response = $this->service->run('forge:data lib-data');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the src directory
        $expectedPath = $this->tempDir.'/src/Datas/LibData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class LibData', $content);
        $this->assertStringContainsString('extends AbstractData', $content);
    }

    public function test_generates_correct_stub_content(): void
    {
        // Arrange: Prepare the directive execution with description using custom tag
        $response = $this->service->run('forge:data test.content <description="My custom description">');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with correct stub content
        $expectedPath = $this->tempDir.'/app/Datas/Test/ContentData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Datas\\Test;', $content);
        $this->assertStringContainsString('class ContentData extends AbstractData', $content);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Abstracts\\AbstractData;', $content);
        $this->assertStringContainsString('My custom description', $content);
    }

    public function test_works_with_alias(): void
    {
        // Act: Execute the directive using the alias
        $response = $this->service->run('create-data alias-test');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created
        $expectedPath = $this->tempDir.'/app/Datas/AliasTestData.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_dto_alias(): void
    {
        // Act: Execute the directive using the alias
        $response = $this->service->run('make-data dto-test');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created
        $expectedPath = $this->tempDir.'/app/Datas/DtoTestData.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        // Act: Execute the directive successfully
        $response = $this->service->run('forge:data success-test');

        // Assert: Verify the success exit code
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        // Act: Execute the directive with invalid input
        $response = $this->service->run('forge:data invalid..name');

        // Assert: Verify the failure exit code
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
