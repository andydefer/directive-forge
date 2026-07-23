<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class ForgeTaskDirectiveTest extends IntegrationTestCase
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

    public function test_creates_unique_task_successfully(): void
    {
        $response = $this->service->run('forge:task clear-cache u <description="Clear application cache">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Unique task created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Tasks/UniqueTasks/ClearCacheUniqueTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ClearCacheUniqueTask', $content);
        $this->assertStringContainsString('namespace App\\Tasks\\UniqueTasks', $content);
        $this->assertStringContainsString('extends AbstractUniqueTask', $content);
        $this->assertStringContainsString('Clear application cache', $content);
    }

    public function test_creates_recurring_task_successfully(): void
    {
        $response = $this->service->run('forge:task clear-cache r <description="Clear application cache recurring">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Recurring task created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Tasks/RecurringTasks/ClearCacheRecurringTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ClearCacheRecurringTask', $content);
        $this->assertStringContainsString('namespace App\\Tasks\\RecurringTasks', $content);
        $this->assertStringContainsString('extends AbstractRecurringTask', $content);
        $this->assertStringContainsString('Clear application cache recurring', $content);
    }

    public function test_creates_unique_task_with_unique_keyword(): void
    {
        $response = $this->service->run('forge:task clear-cache unique <description="Clear application cache">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Unique task created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Tasks/UniqueTasks/ClearCacheUniqueTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('extends AbstractUniqueTask', $content);
    }

    public function test_creates_recurring_task_with_recurring_keyword(): void
    {
        $response = $this->service->run('forge:task clear-cache recurring <description="Clear application cache recurring">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Recurring task created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Tasks/RecurringTasks/ClearCacheRecurringTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('extends AbstractRecurringTask', $content);
    }

    public function test_creates_unique_task_with_subdirectories(): void
    {
        $response = $this->service->run('forge:task admin.clear-cache u <description="Clear cache from admin">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/UniqueTasks/Admin/ClearCacheUniqueTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ClearCacheUniqueTask', $content);
        $this->assertStringContainsString('namespace App\\Tasks\\UniqueTasks\\Admin', $content);
        $this->assertStringContainsString('Clear cache from admin', $content);
    }

    public function test_creates_recurring_task_with_subdirectories(): void
    {
        $response = $this->service->run('forge:task admin.clear-cache r <description="Clear cache from admin recurring">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/RecurringTasks/Admin/ClearCacheRecurringTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ClearCacheRecurringTask', $content);
        $this->assertStringContainsString('namespace App\\Tasks\\RecurringTasks\\Admin', $content);
        $this->assertStringContainsString('Clear cache from admin recurring', $content);
    }

    public function test_creates_unique_task_with_deep_subdirectories(): void
    {
        $response = $this->service->run('forge:task api.v1.cache.clear u <description="Clear API cache">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/UniqueTasks/Api/V1/Cache/ClearUniqueTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ClearUniqueTask', $content);
        $this->assertStringContainsString('namespace App\\Tasks\\UniqueTasks\\Api\\V1\\Cache', $content);
    }

    public function test_returns_error_when_type_missing(): void
    {
        $response = $this->service->run('forge:task clear-cache');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Task type is required', $response->output);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run('forge:task');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Task name is required', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run('forge:task  u');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Task name is required', $response->output);
    }

    public function test_returns_error_when_task_already_exists(): void
    {
        $this->service->run('forge:task existing u');

        $response = $this->service->run('forge:task existing u');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Task already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        $response = $this->service->run('forge:task invalid@name u');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_rejects_snake_case_name(): void
    {
        $response = $this->service->run('forge:task my_custom_task u <description="My custom task">');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Custom');

        $response = $this->service->run('forge:task custom-task u <description="Custom task">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/UniqueTasks/CustomUniqueTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CustomUniqueTask', $content);
        $this->assertStringContainsString('namespace Custom\\Tasks\\UniqueTasks', $content);
    }

    public function test_uses_default_description_when_not_provided(): void
    {
        $response = $this->service->run('forge:task default-desc u');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/UniqueTasks/DefaultDescUniqueTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('Task for DefaultDescUniqueTask', $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run('forge:task my-custom-task u <description="My custom task">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/UniqueTasks/MyCustomUniqueTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomUniqueTask', $content);
    }

    public function test_handles_camel_case_name(): void
    {
        $response = $this->service->run('forge:task myCustomTask u <description="My custom task">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/UniqueTasks/MyCustomUniqueTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomUniqueTask', $content);
    }

    public function test_creates_task_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Tasks/UniqueTasks', 0777, true);

        $response = $this->service->run('forge:task lib-task u <description="Library task">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/src/Tasks/UniqueTasks/LibUniqueTask.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class LibUniqueTask', $content);
        $this->assertStringContainsString('extends AbstractUniqueTask', $content);
    }

    public function test_works_with_create_task_alias(): void
    {
        $response = $this->service->run('create-task alias-test u <description="Alias test">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/UniqueTasks/AliasTestUniqueTask.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_job_alias(): void
    {
        $response = $this->service->run('make-job job-test u <description="Job test">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Tasks/UniqueTasks/JobTestUniqueTask.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run('forge:task success-test u <description="Success test">');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run('forge:task invalid..name u');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
