<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class ForgeRequestDirectiveTest extends IntegrationTestCase
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

    public function test_creates_request_without_record(): void
    {
        // Arrange: Prepare the directive execution without record
        $response = $this->service->run('forge:request create-user');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Request created successfully', $response->output);

        // Assert: Verify the request file was created
        $expectedPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateUserRequest', $content);
        $this->assertStringContainsString('namespace App\\Requests', $content);
        $this->assertStringContainsString('extends AbstractRequest', $content);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Utils\\EmptyRecord;', $content);
        $this->assertStringContainsString('return new EmptyRecord();', $content);
    }

    public function test_creates_request_with_record(): void
    {
        // Arrange: Prepare the directive execution with record
        $response = $this->service->run('forge:request create-user --r');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Request created successfully', $response->output);

        // Assert: Verify the request file was created with record import
        $requestPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class CreateUserRequest', $requestContent);
        $this->assertStringContainsString('namespace App\\Requests', $requestContent);
        $this->assertStringContainsString('extends AbstractRequest', $requestContent);
        $this->assertStringContainsString('use App\\Records\\CreateUserRecord;', $requestContent);
        $this->assertStringContainsString('return CreateUserRecord::from([ // TODO: Map request data to record properties ])', $requestContent);

        // Assert: Verify the record file was created
        $recordPath = $this->tempDir.'/app/Records/CreateUserRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class CreateUserRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Records', $recordContent);
        $this->assertStringContainsString('extends AbstractRecord', $recordContent);
    }

    public function test_creates_request_with_subdirectories(): void
    {
        // Arrange: Prepare the directive execution with subdirectory
        $response = $this->service->run('forge:request admin.user-create --r');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the request file was created in the correct subdirectory
        $requestPath = $this->tempDir.'/app/Requests/Admin/UserCreateRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class UserCreateRequest', $requestContent);
        $this->assertStringContainsString('namespace App\\Requests\\Admin', $requestContent);
        $this->assertStringContainsString('use App\\Records\\Admin\\UserCreateRecord;', $requestContent);

        // Assert: Verify the record file was created in the correct subdirectory
        $recordPath = $this->tempDir.'/app/Records/Admin/UserCreateRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class UserCreateRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Records\\Admin', $recordContent);
    }

    public function test_creates_request_with_deep_subdirectories(): void
    {
        // Arrange: Prepare the directive execution with deep subdirectory
        $response = $this->service->run('forge:request api.v1.user.create --r');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the request file was created in the correct deep subdirectory
        $requestPath = $this->tempDir.'/app/Requests/Api/V1/User/CreateRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class CreateRequest', $requestContent);
        $this->assertStringContainsString('namespace App\\Requests\\Api\\V1\\User', $requestContent);
        $this->assertStringContainsString('use App\\Records\\Api\\V1\\User\\CreateRecord;', $requestContent);

        // Assert: Verify the record file was created in the correct deep subdirectory
        $recordPath = $this->tempDir.'/app/Records/Api/V1/User/CreateRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class CreateRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Records\\Api\\V1\\User', $recordContent);
    }

    public function test_creates_request_with_suffix_already_present(): void
    {
        // Arrange: Prepare the directive execution with name already containing suffix
        $response = $this->service->run('forge:request create-user-request');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created without duplicating suffix
        $expectedPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateUserRequest', $content);
        $this->assertStringContainsString('namespace App\\Requests', $content);
    }

    public function test_creates_request_with_suffix_already_present_and_record(): void
    {
        // Arrange: Prepare the directive execution with suffix and record
        $response = $this->service->run('forge:request create-user-request --r');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the request file was created with correct record import
        $requestPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class CreateUserRequest', $requestContent);
        $this->assertStringContainsString('use App\\Records\\CreateUserRecord;', $requestContent);

        // Assert: Verify the record file was created
        $recordPath = $this->tempDir.'/app/Records/CreateUserRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class CreateUserRecord', $recordContent);
    }

    public function test_returns_error_when_name_missing(): void
    {
        // Act: Execute the directive without providing a name
        $response = $this->service->run('forge:request');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Request name is required', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        // Act: Execute the directive with an empty name
        $response = $this->service->run('forge:request ');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Request name is required', $response->output);
    }

    public function test_returns_error_when_request_already_exists(): void
    {
        // Arrange: Create an existing request
        $this->service->run('forge:request existing');

        // Act: Attempt to create a request that already exists
        $response = $this->service->run('forge:request existing');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Request already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        // Act: Attempt to create a request with invalid characters
        $response = $this->service->run('forge:request invalid@name');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        // Arrange: Change the namespace configuration
        $this->app['config']->set('directive-forge.namespace', 'Custom');

        // Act: Execute the directive with custom namespace
        $response = $this->service->run('forge:request custom-request --r');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the request file was created with the custom namespace
        $requestPath = $this->tempDir.'/app/Requests/CustomRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class CustomRequest', $requestContent);
        $this->assertStringContainsString('namespace Custom\\Requests', $requestContent);
        $this->assertStringContainsString('use Custom\\Records\\CustomRecord;', $requestContent);

        // Assert: Verify the record file was created with the custom namespace
        $recordPath = $this->tempDir.'/app/Records/CustomRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class CustomRecord', $recordContent);
        $this->assertStringContainsString('namespace Custom\\Records', $recordContent);
    }

    public function test_handles_kebab_case_name(): void
    {
        // Arrange: Prepare the directive execution with kebab case
        $response = $this->service->run('forge:request my-custom-request --r');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the request file was created with PascalCase class name
        $requestPath = $this->tempDir.'/app/Requests/MyCustomRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class MyCustomRequest', $requestContent);
        $this->assertStringContainsString('use App\\Records\\MyCustomRecord;', $requestContent);

        // Assert: Verify the record file was created with PascalCase class name
        $recordPath = $this->tempDir.'/app/Records/MyCustomRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class MyCustomRecord', $recordContent);
    }

    public function test_handles_camel_case_name(): void
    {
        // Arrange: Prepare the directive execution with camel case
        $response = $this->service->run('forge:request myCustomRequest --r');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the request file was created with the same name
        $requestPath = $this->tempDir.'/app/Requests/MyCustomRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class MyCustomRequest', $requestContent);
        $this->assertStringContainsString('use App\\Records\\MyCustomRecord;', $requestContent);

        // Assert: Verify the record file was created with the same name
        $recordPath = $this->tempDir.'/app/Records/MyCustomRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class MyCustomRecord', $recordContent);
    }

    public function test_handles_snake_case_name(): void
    {
        // Act: Attempt to create a request with snake case (invalid)
        $response = $this->service->run('forge:request my_custom_request');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_creates_request_in_src_when_mode_library(): void
    {
        // Arrange: Change the mode to library
        $this->app['config']->set('directive-forge.mode', 'library');

        // Create the src directory structure
        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Requests', 0777, true);
        mkdir($this->tempDir.'/src/Records', 0777, true);

        // Act: Execute the directive in library mode
        $response = $this->service->run('forge:request lib-request --r');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the request file was created in the src directory
        $requestPath = $this->tempDir.'/src/Requests/LibRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class LibRequest', $requestContent);
        $this->assertStringContainsString('namespace App\\Requests', $requestContent);
        $this->assertStringContainsString('use App\\Records\\LibRecord;', $requestContent);

        // Assert: Verify the record file was created in the src directory
        $recordPath = $this->tempDir.'/src/Records/LibRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class LibRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Records', $recordContent);
    }

    public function test_generates_correct_stub_content_without_record(): void
    {
        // Arrange: Prepare the directive execution without record
        $response = $this->service->run('forge:request test.content');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the request file was created with correct stub content
        $expectedPath = $this->tempDir.'/app/Requests/Test/ContentRequest.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Requests\\Test;', $content);
        $this->assertStringContainsString('class ContentRequest extends AbstractRequest', $content);
        $this->assertStringContainsString('use AndyDefer\\Actions\\Http\\Requests\\AbstractRequest;', $content);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Abstracts\\AbstractRecord;', $content);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Utils\\EmptyRecord;', $content);
        $this->assertStringContainsString('public function authorize(): bool', $content);
        $this->assertStringContainsString('public function rules(): array', $content);
        $this->assertStringContainsString('public function getRecord(): AbstractRecord', $content);
        $this->assertStringContainsString('return new EmptyRecord();', $content);
    }

    public function test_generates_correct_stub_content_with_record(): void
    {
        // Arrange: Prepare the directive execution with record
        $response = $this->service->run('forge:request test.content --r');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the request file was created with correct stub content
        $requestPath = $this->tempDir.'/app/Requests/Test/ContentRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);

        $this->assertStringContainsString('declare(strict_types=1);', $requestContent);
        $this->assertStringContainsString('namespace App\\Requests\\Test;', $requestContent);
        $this->assertStringContainsString('class ContentRequest extends AbstractRequest', $requestContent);
        $this->assertStringContainsString('use App\\Records\\Test\\ContentRecord;', $requestContent);
        $this->assertStringContainsString('return ContentRecord::from([ // TODO: Map request data to record properties ])', $requestContent);

        // Assert: Verify the record file was created with correct stub content
        $recordPath = $this->tempDir.'/app/Records/Test/ContentRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class ContentRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Records\\Test', $recordContent);
        $this->assertStringContainsString('extends AbstractRecord', $recordContent);
    }

    public function test_works_with_create_request_alias(): void
    {
        // Act: Execute the directive using the alias
        $response = $this->service->run('create-request alias-test');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created
        $expectedPath = $this->tempDir.'/app/Requests/AliasTestRequest.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_req_alias(): void
    {
        // Act: Execute the directive using the alias
        $response = $this->service->run('make-req req-test');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created
        $expectedPath = $this->tempDir.'/app/Requests/ReqTestRequest.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        // Act: Execute the directive successfully
        $response = $this->service->run('forge:request success-test');

        // Assert: Verify the success exit code
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        // Act: Execute the directive with invalid input
        $response = $this->service->run('forge:request invalid..name');

        // Assert: Verify the failure exit code
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
