<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class ForgeActionDirectiveTest extends IntegrationTestCase
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

    public function test_creates_action_successfully(): void
    {
        // Arrange: Prepare the directive execution
        $response = $this->service->run('forge:action create-user');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Action created successfully', $response->output);

        // Assert: Verify the file was created with correct content
        $expectedPath = $this->tempDir.'/app/Actions/CreateUserAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateUserAction', $content);
        $this->assertStringContainsString('namespace App\\Actions', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
    }

    public function test_creates_action_with_subdirectories(): void
    {
        // Arrange: Prepare the directive execution with subdirectory
        $response = $this->service->run('forge:action admin.update-user');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the correct subdirectory
        $expectedPath = $this->tempDir.'/app/Actions/Admin/UpdateUserAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UpdateUserAction', $content);
        $this->assertStringContainsString('namespace App\\Actions\\Admin', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
    }

    public function test_creates_action_with_deep_subdirectories(): void
    {
        // Arrange: Prepare the directive execution with deep subdirectory
        $response = $this->service->run('forge:action api.v1.user.create');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the correct deep subdirectory
        $expectedPath = $this->tempDir.'/app/Actions/Api/V1/User/CreateAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateAction', $content);
        $this->assertStringContainsString('namespace App\\Actions\\Api\\V1\\User', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
    }

    public function test_creates_action_with_suffix_already_present(): void
    {
        // Arrange: Prepare the directive execution with name already containing suffix
        $response = $this->service->run('forge:action create-user-action');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created without duplicating suffix
        $expectedPath = $this->tempDir.'/app/Actions/CreateUserAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateUserAction', $content);
        $this->assertStringContainsString('namespace App\\Actions', $content);
    }

    public function test_creates_action_with_suffix_in_subdirectory(): void
    {
        // Arrange: Prepare the directive execution with subdirectory and suffix
        $response = $this->service->run('forge:action admin.create-user-action');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the correct subdirectory
        $expectedPath = $this->tempDir.'/app/Actions/Admin/CreateUserAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateUserAction', $content);
        $this->assertStringContainsString('namespace App\\Actions\\Admin', $content);
    }

    public function test_creates_action_with_enum_r(): void
    {
        // Arrange: Prepare the directive execution with enum r (just the value)
        $response = $this->service->run('forge:action create-user r');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the action was created
        $actionPath = $this->tempDir.'/app/Actions/CreateUserAction.php';
        $this->assertFileExists($actionPath);

        // Assert: Verify the request was created (without record)
        $requestPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class CreateUserRequest', $requestContent);
        $this->assertStringContainsString('namespace App\\Requests', $requestContent);

        // Assert: Verify the record does NOT exist
        $recordPath = $this->tempDir.'/app/Records/CreateUserRecord.php';
        $this->assertFileDoesNotExist($recordPath);
    }

    public function test_creates_action_with_enum_request(): void
    {
        // Arrange: Prepare the directive execution with enum request (just the value)
        $response = $this->service->run('forge:action create-user request');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the action was created
        $actionPath = $this->tempDir.'/app/Actions/CreateUserAction.php';
        $this->assertFileExists($actionPath);

        // Assert: Verify the request was created (without record)
        $requestPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        // Assert: Verify the record does NOT exist
        $recordPath = $this->tempDir.'/app/Records/CreateUserRecord.php';
        $this->assertFileDoesNotExist($recordPath);
    }

    public function test_creates_action_with_enum_a(): void
    {
        // Arrange: Prepare the directive execution with enum a (just the value)
        $response = $this->service->run('forge:action create-user a');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the action was created
        $actionPath = $this->tempDir.'/app/Actions/CreateUserAction.php';
        $this->assertFileExists($actionPath);

        // Assert: Verify the request was created
        $requestPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        // Assert: Verify the record was created
        $recordPath = $this->tempDir.'/app/Records/CreateUserRecord.php';
        $this->assertFileExists($recordPath);

        // Assert: Verify the request imports the record
        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('use App\\Records\\CreateUserRecord;', $requestContent);
        $this->assertStringContainsString('return CreateUserRecord::from([ // TODO: Map request data to record properties ])', $requestContent);
    }

    public function test_creates_action_with_enum_all(): void
    {
        // Arrange: Prepare the directive execution with enum all (just the value)
        $response = $this->service->run('forge:action create-user all');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the action was created
        $actionPath = $this->tempDir.'/app/Actions/CreateUserAction.php';
        $this->assertFileExists($actionPath);

        // Assert: Verify the request was created
        $requestPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        // Assert: Verify the record was created
        $recordPath = $this->tempDir.'/app/Records/CreateUserRecord.php';
        $this->assertFileExists($recordPath);

        // Assert: Verify the request imports the record
        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('use App\\Records\\CreateUserRecord;', $requestContent);
    }

    public function test_creates_action_with_enum_a_and_subdirectories(): void
    {
        // Arrange: Prepare the directive execution with enum a and subdirectory
        $response = $this->service->run('forge:action admin.create-user a');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the action
        $actionPath = $this->tempDir.'/app/Actions/Admin/CreateUserAction.php';
        $this->assertFileExists($actionPath);

        // Assert: Verify the request
        $requestPath = $this->tempDir.'/app/Requests/Admin/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        // Assert: Verify the record
        $recordPath = $this->tempDir.'/app/Records/Admin/CreateUserRecord.php';
        $this->assertFileExists($recordPath);

        // Assert: Verify the request imports the record
        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('use App\\Records\\Admin\\CreateUserRecord;', $requestContent);
    }

    public function test_creates_action_with_enum_r_and_subdirectories(): void
    {
        // Arrange: Prepare the directive execution with enum r and subdirectory
        $response = $this->service->run('forge:action admin.create-user r');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the action
        $actionPath = $this->tempDir.'/app/Actions/Admin/CreateUserAction.php';
        $this->assertFileExists($actionPath);

        // Assert: Verify the request
        $requestPath = $this->tempDir.'/app/Requests/Admin/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        // Assert: Verify the record does NOT exist
        $recordPath = $this->tempDir.'/app/Records/Admin/CreateUserRecord.php';
        $this->assertFileDoesNotExist($recordPath);
    }

    public function test_returns_error_when_name_missing(): void
    {
        // Act: Execute the directive without providing a name
        $response = $this->service->run('forge:action');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Action name is required', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        // Act: Execute the directive with an empty name
        $response = $this->service->run('forge:action ');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Action name is required', $response->output);
    }

    public function test_returns_error_when_action_already_exists(): void
    {
        // Arrange: Create an existing action
        $this->service->run('forge:action existing');

        // Act: Attempt to create an action that already exists
        $response = $this->service->run('forge:action existing');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Action already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        // Act: Attempt to create an action with invalid characters
        $response = $this->service->run('forge:action invalid@name');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        // Arrange: Change the namespace configuration
        $this->app['config']->set('directive-forge.namespace', 'Custom');

        // Act: Execute the directive with custom namespace
        $response = $this->service->run('forge:action custom-action');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with the custom namespace
        $expectedPath = $this->tempDir.'/app/Actions/CustomAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CustomAction', $content);
        $this->assertStringContainsString('namespace Custom\\Actions', $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        // Arrange: Prepare the directive execution with kebab case
        $response = $this->service->run('forge:action my-custom-action');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with PascalCase class name
        $expectedPath = $this->tempDir.'/app/Actions/MyCustomAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomAction', $content);
    }

    public function test_handles_camel_case_name(): void
    {
        // Arrange: Prepare the directive execution with camel case
        $response = $this->service->run('forge:action myCustomAction');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with the same name
        $expectedPath = $this->tempDir.'/app/Actions/MyCustomAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomAction', $content);
    }

    public function test_handles_snake_case_name(): void
    {
        // Act: Attempt to create an action with snake case (invalid)
        $response = $this->service->run('forge:action my_custom_action');

        // Assert: Verify the error response
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_creates_action_in_src_when_mode_library(): void
    {
        // Arrange: Change the mode to library
        $this->app['config']->set('directive-forge.mode', 'library');

        // Create the src directory structure
        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Actions', 0777, true);

        // Act: Execute the directive in library mode
        $response = $this->service->run('forge:action lib-action');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created in the src directory
        $expectedPath = $this->tempDir.'/src/Actions/LibAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class LibAction', $content);
        $this->assertStringContainsString('namespace App\\Actions', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
    }

    public function test_generates_correct_stub_content(): void
    {
        // Arrange: Prepare the directive execution
        $response = $this->service->run('forge:action test.content');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created with correct stub content
        $expectedPath = $this->tempDir.'/app/Actions/Test/ContentAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Actions\\Test;', $content);
        $this->assertStringContainsString('class ContentAction extends AbstractAction', $content);
        $this->assertStringContainsString('use AndyDefer\\Actions\\Actions\\AbstractAction;', $content);
        $this->assertStringContainsString('use AndyDefer\\Actions\\Http\\ResponseFactory;', $content);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Abstracts\\AbstractRecord;', $content);
        $this->assertStringContainsString('protected function handle(AbstractRecord $request): ResponseFactory', $content);
        $this->assertStringContainsString('return ResponseFactory::noContent();', $content);
    }

    public function test_works_with_create_action_alias(): void
    {
        // Act: Execute the directive using the alias
        $response = $this->service->run('create-action alias-test');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created
        $expectedPath = $this->tempDir.'/app/Actions/AliasTestAction.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_act_alias(): void
    {
        // Act: Execute the directive using the alias
        $response = $this->service->run('make-act act-test');

        // Assert: Verify the directive executed successfully
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Assert: Verify the file was created
        $expectedPath = $this->tempDir.'/app/Actions/ActTestAction.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        // Act: Execute the directive successfully
        $response = $this->service->run('forge:action success-test');

        // Assert: Verify the success exit code
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        // Act: Execute the directive with invalid input
        $response = $this->service->run('forge:action invalid..name');

        // Assert: Verify the failure exit code
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
