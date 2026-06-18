<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class MakeRecordDirectiveTest extends IntegrationTestCase
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

    public function test_creates_record_successfully(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, [
            'user',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Record created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Records/UserRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserRecord', $content);
        $this->assertStringContainsString('namespace App\\Records', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_creates_record_with_subdirectories(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, [
            'admin.user-profile',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Records/Admin/UserProfileRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserProfileRecord', $content);
        $this->assertStringContainsString('namespace App\\Records\\Admin', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_creates_record_with_deep_subdirectories(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, [
            'api.v1.user.create',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Records/Api/V1/User/CreateRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateRecord', $content);
        $this->assertStringContainsString('namespace App\\Records\\Api\\V1\\User', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_creates_record_with_suffix_already_present(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, [
            'user-record',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Records/UserRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserRecord', $content);
        $this->assertStringContainsString('namespace App\\Records', $content);
    }

    public function test_creates_record_with_suffix_in_subdirectory(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, [
            'admin.user-profile-record',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Records/Admin/UserProfileRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserProfileRecord', $content);
        $this->assertStringContainsString('namespace App\\Records\\Admin', $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, ['']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Record name is required', $response->output);
    }

    public function test_returns_error_when_record_already_exists(): void
    {
        $existingPath = $this->tempDir.'/app/Records/ExistingRecord.php';
        $this->filesystem->ensureDirectoryExists(dirname($existingPath));
        file_put_contents($existingPath, '<?php // Existing record');

        $response = $this->service->run(MakeRecordDirective::class, ['existing']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Record already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, ['invalid@name']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Custom\\Dto');

        $response = $this->service->run(MakeRecordDirective::class, [
            'custom-record',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Records/CustomRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CustomRecord', $content);
        $this->assertStringContainsString('namespace Custom\\Dto', $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, [
            'my-custom-record',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Records/MyCustomRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomRecord', $content);
    }

    public function test_handles_camel_case_name(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, [
            'myCustomRecord',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Records/MyCustomRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomRecord', $content);
    }

    public function test_handles_snake_case_name(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, [
            'my_custom_record',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_creates_record_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Records', 0777, true);

        $response = $this->service->run(MakeRecordDirective::class, [
            'lib-record',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/src/Records/LibRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class LibRecord', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_generates_correct_stub_content(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, [
            'test.content',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

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
        $response = $this->service->run(MakeRecordDirective::class, [
            'alias-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Records/AliasTestRecord.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_dto_alias(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, [
            'dto-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Records/DtoTestRecord.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, [
            'success-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, [
            'invalid..name',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
