<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRequestDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class MakeRequestDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    private string $tempDir;

    private FileSystemService $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DirectiveTestingService($this->app);
        $this->service->registerDirective(MakeRecordDirective::class);

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
        $response = $this->service->run(MakeRequestDirective::class, [
            'create-user',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Request created successfully', $response->output);

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
        $response = $this->service->run(MakeRequestDirective::class, [
            'create-user',
            '--r',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Request created successfully', $response->output);

        $requestPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class CreateUserRequest', $requestContent);
        $this->assertStringContainsString('namespace App\\Requests', $requestContent);
        $this->assertStringContainsString('extends AbstractRequest', $requestContent);
        $this->assertStringContainsString('use App\\Records\\CreateUserRecord;', $requestContent);
        $this->assertStringContainsString('return CreateUserRecord::from([ // TODO: Map request data to record properties ])', $requestContent);

        $recordPath = $this->tempDir.'/app/Records/CreateUserRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class CreateUserRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Records', $recordContent);
        $this->assertStringContainsString('extends AbstractRecord', $recordContent);
    }

    public function test_creates_request_with_subdirectories(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, [
            'admin.user-create',
            '--r',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $requestPath = $this->tempDir.'/app/Requests/Admin/UserCreateRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class UserCreateRequest', $requestContent);
        $this->assertStringContainsString('namespace App\\Requests\\Admin', $requestContent);
        $this->assertStringContainsString('use App\\Records\\Admin\\UserCreateRecord;', $requestContent);

        $recordPath = $this->tempDir.'/app/Records/Admin/UserCreateRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class UserCreateRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Records\\Admin', $recordContent);
    }

    public function test_creates_request_with_deep_subdirectories(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, [
            'api.v1.user.create',
            '--r',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $requestPath = $this->tempDir.'/app/Requests/Api/V1/User/CreateRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class CreateRequest', $requestContent);
        $this->assertStringContainsString('namespace App\\Requests\\Api\\V1\\User', $requestContent);
        $this->assertStringContainsString('use App\\Records\\Api\\V1\\User\\CreateRecord;', $requestContent);

        $recordPath = $this->tempDir.'/app/Records/Api/V1/User/CreateRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class CreateRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Records\\Api\\V1\\User', $recordContent);
    }

    public function test_creates_request_with_suffix_already_present(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, [
            'create-user-request',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateUserRequest', $content);
        $this->assertStringContainsString('namespace App\\Requests', $content);
    }

    public function test_creates_request_with_suffix_already_present_and_record(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, [
            'create-user-request',
            '--r',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $requestPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class CreateUserRequest', $requestContent);
        $this->assertStringContainsString('use App\\Records\\CreateUserRecord;', $requestContent);

        $recordPath = $this->tempDir.'/app/Records/CreateUserRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class CreateUserRecord', $recordContent);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, ['']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Request name is required', $response->output);
    }

    public function test_returns_error_when_request_already_exists(): void
    {
        $this->service->run(MakeRequestDirective::class, ['existing']);

        $response = $this->service->run(MakeRequestDirective::class, ['existing']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Request already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, ['invalid@name']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Custom');

        $response = $this->service->run(MakeRequestDirective::class, [
            'custom-request',
            '--r',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $requestPath = $this->tempDir.'/app/Requests/CustomRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class CustomRequest', $requestContent);
        $this->assertStringContainsString('namespace Custom\\Requests', $requestContent);
        $this->assertStringContainsString('use Custom\\Records\\CustomRecord;', $requestContent);

        $recordPath = $this->tempDir.'/app/Records/CustomRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class CustomRecord', $recordContent);
        $this->assertStringContainsString('namespace Custom\\Records', $recordContent);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, [
            'my-custom-request',
            '--r',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $requestPath = $this->tempDir.'/app/Requests/MyCustomRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class MyCustomRequest', $requestContent);
        $this->assertStringContainsString('use App\\Records\\MyCustomRecord;', $requestContent);

        $recordPath = $this->tempDir.'/app/Records/MyCustomRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class MyCustomRecord', $recordContent);
    }

    public function test_handles_camel_case_name(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, [
            'myCustomRequest',
            '--r',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $requestPath = $this->tempDir.'/app/Requests/MyCustomRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class MyCustomRequest', $requestContent);
        $this->assertStringContainsString('use App\\Records\\MyCustomRecord;', $requestContent);

        $recordPath = $this->tempDir.'/app/Records/MyCustomRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class MyCustomRecord', $recordContent);
    }

    public function test_handles_snake_case_name(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, [
            'my_custom_request',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_creates_request_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Requests', 0777, true);
        mkdir($this->tempDir.'/src/Records', 0777, true);

        $response = $this->service->run(MakeRequestDirective::class, [
            'lib-request',
            '--r',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $requestPath = $this->tempDir.'/src/Requests/LibRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class LibRequest', $requestContent);
        $this->assertStringContainsString('namespace App\\Requests', $requestContent);
        $this->assertStringContainsString('use App\\Records\\LibRecord;', $requestContent);

        $recordPath = $this->tempDir.'/src/Records/LibRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class LibRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Records', $recordContent);
    }

    public function test_generates_correct_stub_content_without_record(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, [
            'test.content',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

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
        $response = $this->service->run(MakeRequestDirective::class, [
            'test.content',
            '--r',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $requestPath = $this->tempDir.'/app/Requests/Test/ContentRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);

        $this->assertStringContainsString('declare(strict_types=1);', $requestContent);
        $this->assertStringContainsString('namespace App\\Requests\\Test;', $requestContent);
        $this->assertStringContainsString('class ContentRequest extends AbstractRequest', $requestContent);
        $this->assertStringContainsString('use App\\Records\\Test\\ContentRecord;', $requestContent);
        $this->assertStringContainsString('return ContentRecord::from([ // TODO: Map request data to record properties ])', $requestContent);

        $recordPath = $this->tempDir.'/app/Records/Test/ContentRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class ContentRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Records\\Test', $recordContent);
        $this->assertStringContainsString('extends AbstractRecord', $recordContent);
    }

    public function test_works_with_create_request_alias(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, [
            'alias-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Requests/AliasTestRequest.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_req_alias(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, [
            'req-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Requests/ReqTestRequest.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, [
            'success-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, [
            'invalid..name',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
