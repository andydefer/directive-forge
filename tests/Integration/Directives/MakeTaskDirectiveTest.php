<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeTaskDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class MakeTaskDirectiveTest extends IntegrationTestCase
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

    public function test_creates_task_successfully(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'clear-cache',
            '--description=Clear application cache',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Task created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Tasks/ClearCacheTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ClearCacheTask', $content);
        $this->assertStringContainsString('namespace App\\Tasks', $content);
        $this->assertStringContainsString('extends AbstractTask', $content);
        $this->assertStringContainsString("'clear-cache'", $content);
        $this->assertStringContainsString("'Clear application cache'", $content);
        $this->assertStringContainsString('protected function before(): void', $content);
        $this->assertStringContainsString('protected function after(bool $success, ?string $error = null): void', $content);
    }

    public function test_creates_task_with_subdirectories(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'admin.clear-cache',
            '--description=Clear cache from admin',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/Admin/ClearCacheTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ClearCacheTask', $content);
        $this->assertStringContainsString('namespace App\\Tasks\\Admin', $content);
        $this->assertStringContainsString("'admin.clear-cache'", $content);
        $this->assertStringContainsString("'Clear cache from admin'", $content);
    }

    public function test_creates_task_with_deep_subdirectories(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'api.v1.cache.clear',
            '--description=Clear API cache',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/Api/V1/Cache/ClearTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ClearTask', $content);
        $this->assertStringContainsString('namespace App\\Tasks\\Api\\V1\\Cache', $content);
        $this->assertStringContainsString("'api.v1.cache.clear'", $content);
    }

    public function test_creates_task_with_suffix_already_present(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'clear-cache-task',
            '--description=Clear cache',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/ClearCacheTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ClearCacheTask', $content);
        $this->assertStringContainsString('namespace App\\Tasks', $content);
        $this->assertStringContainsString("'clear-cache-task'", $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, ['']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Task name is required', $response->output);
    }

    public function test_returns_error_when_task_already_exists(): void
    {
        $existingPath = $this->tempDir.'/app/Tasks/ExistingTask.php';
        $this->filesystem->ensureDirectoryExists(dirname($existingPath));
        file_put_contents($existingPath, '<?php // Existing task');

        $response = $this->service->run(MakeTaskDirective::class, ['existing']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Task already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, ['invalid@name']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_rejects_snake_case_name(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'my_custom_task',
            '--description=My custom task',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Custom');

        $response = $this->service->run(MakeTaskDirective::class, [
            'custom-task',
            '--description=Custom task',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/CustomTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CustomTask', $content);
        $this->assertStringContainsString('namespace Custom\\Tasks', $content);
    }

    public function test_uses_default_description_when_not_provided(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'default-desc',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/DefaultDescTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString("'Description of the task'", $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'my-custom-task',
            '--description=My custom task',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/MyCustomTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomTask', $content);
        $this->assertStringContainsString("'my-custom-task'", $content);
    }

    public function test_handles_camel_case_name(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'myCustomTask',
            '--description=My custom task',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/MyCustomTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomTask', $content);
        $this->assertStringContainsString("'mycustomtask'", $content);
    }

    public function test_creates_task_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Tasks', 0777, true);

        $response = $this->service->run(MakeTaskDirective::class, [
            'lib-task',
            '--description=Library task',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/src/Tasks/LibTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class LibTask', $content);
        $this->assertStringContainsString('extends AbstractTask', $content);
    }

    public function test_generates_correct_stub_content(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'test.content',
            '--description=My custom description',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/Test/ContentTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Tasks\\Test;', $content);
        $this->assertStringContainsString('class ContentTask extends AbstractTask', $content);
        $this->assertStringContainsString("'test.content'", $content);
        $this->assertStringContainsString("'My custom description'", $content);
        $this->assertStringContainsString('use AndyDefer\\Task\\AbstractTask;', $content);
        $this->assertStringContainsString('use AndyDefer\\Task\\Records\\TaskConfigRecord;', $content);
        $this->assertStringContainsString('use AndyDefer\\Task\\ValueObjects\\CounterVO;', $content);
        $this->assertStringContainsString('use AndyDefer\\Task\\ValueObjects\\TaskSignatureVO;', $content);
        $this->assertStringContainsString('protected function before(): void', $content);
        $this->assertStringContainsString('protected function after(bool $success, ?string $error = null): void', $content);
        $this->assertStringContainsString('protected function process(): void', $content);
        $this->assertStringContainsString("\$this->info('Task executed successfully!');", $content);
    }

    public function test_works_with_create_task_alias(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'alias-test',
            '--description=Alias test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/AliasTestTask.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_job_alias(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'job-test',
            '--description=Job test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/JobTestTask.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'success-test',
            '--description=Success test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, [
            'invalid..name',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
