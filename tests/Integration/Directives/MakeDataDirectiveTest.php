<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeDataDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class MakeDataDirectiveTest extends IntegrationTestCase
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

    public function test_creates_data_successfully(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'user',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Data created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Datas/UserData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserData', $content);
        $this->assertStringContainsString('namespace App\\Datas', $content);
        $this->assertStringContainsString('extends AbstractData', $content);
    }

    public function test_creates_data_with_description(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'user-profile',
            '--description=User profile data transfer object',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Data created successfully', $response->output);

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
        $response = $this->service->run(MakeDataDirective::class, [
            'admin.user-profile',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Datas/Admin/UserProfileData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserProfileData', $content);
        $this->assertStringContainsString('namespace App\\Datas\\Admin', $content);
        $this->assertStringContainsString('extends AbstractData', $content);
    }

    public function test_creates_data_with_deep_subdirectories(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'api.v1.user.create',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Datas/Api/V1/User/CreateData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateData', $content);
        $this->assertStringContainsString('namespace App\\Datas\\Api\\V1\\User', $content);
        $this->assertStringContainsString('extends AbstractData', $content);
    }

    public function test_creates_data_with_suffix_already_present(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'user-data',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Datas/UserData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserData', $content);
        $this->assertStringContainsString('namespace App\\Datas', $content);
    }

    public function test_creates_data_with_suffix_in_subdirectory(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'admin.user-profile-data',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Datas/Admin/UserProfileData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserProfileData', $content);
        $this->assertStringContainsString('namespace App\\Datas\\Admin', $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeDataDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run(MakeDataDirective::class, ['']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Data name is required', $response->output);
    }

    public function test_returns_error_when_data_already_exists(): void
    {
        $existingPath = $this->tempDir.'/app/Datas/ExistingData.php';
        $this->filesystem->ensureDirectoryExists(dirname($existingPath));
        file_put_contents($existingPath, '<?php // Existing data');

        $response = $this->service->run(MakeDataDirective::class, ['existing']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Data already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        $response = $this->service->run(MakeDataDirective::class, ['invalid@name']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Custom\\Dto');

        $response = $this->service->run(MakeDataDirective::class, [
            'custom-data',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Datas/CustomData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CustomData', $content);
        $this->assertStringContainsString('namespace Custom\\Dto', $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'my-custom-data',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Datas/MyCustomData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomData', $content);
    }

    public function test_handles_camel_case_name(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'myCustomData',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Datas/MyCustomData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomData', $content);
    }

    public function test_handles_snake_case_name(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'my_custom_data',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_creates_data_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Datas', 0777, true);

        $response = $this->service->run(MakeDataDirective::class, [
            'lib-data',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/src/Datas/LibData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class LibData', $content);
        $this->assertStringContainsString('extends AbstractData', $content);
    }

    public function test_generates_correct_stub_content(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'test.content',
            '--description=My custom description',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Datas/Test/ContentData.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Datas\\Test;', $content);
        $this->assertStringContainsString('class ContentData extends AbstractData', $content);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Abstracts\\AbstractData;', $content);
        $this->assertStringContainsString('My custom description', $content);
    }

    public function test_works_with_create_data_alias(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'alias-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Datas/AliasTestData.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_dto_alias(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'dto-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Datas/DtoTestData.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'success-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run(MakeDataDirective::class, [
            'invalid..name',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
