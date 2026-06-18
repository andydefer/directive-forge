<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class MakeDirectiveTest extends IntegrationTestCase
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

    public function test_creates_file_successfully(): void
    {
        $response = $this->service->run(MakeDirective::class, [
            'test-command',
            '--description=Test description',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Directive created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Directives/TestCommandDirective.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class TestCommandDirective', $content);
        $this->assertStringContainsString("return 'test-command'", $content);
        $this->assertStringContainsString("return 'Test description'", $content);
        $this->assertStringContainsString('namespace App\\Directives', $content);
    }

    public function test_creates_file_with_subdirectories(): void
    {
        $response = $this->service->run(MakeDirective::class, [
            'users.hello-world',
            '--description=Hello world command',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Directives/Users/HelloWorldDirective.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class HelloWorldDirective', $content);
        $this->assertStringContainsString("return 'users.hello-world'", $content);
        $this->assertStringContainsString("return 'Hello world command'", $content);
        $this->assertStringContainsString('namespace App\\Directives\\Users', $content);
    }

    public function test_creates_file_with_deep_subdirectories(): void
    {
        $response = $this->service->run(MakeDirective::class, [
            'api.v1.admin.user-management',
            '--description=Admin user management',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Directives/Api/V1/Admin/UserManagementDirective.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserManagementDirective', $content);
        $this->assertStringContainsString("return 'api.v1.admin.user-management'", $content);
        $this->assertStringContainsString('namespace App\\Directives\\Api\\V1\\Admin', $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_returns_error_when_file_already_exists(): void
    {
        $existingPath = $this->tempDir.'/app/Directives/ExistingDirective.php';
        $this->filesystem->ensureDirectoryExists(dirname($existingPath));
        file_put_contents($existingPath, '<?php // Existing file');

        $response = $this->service->run(MakeDirective::class, ['existing']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Directive already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        $response = $this->service->run(MakeDirective::class, ['invalid@name']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run(MakeDirective::class, ['']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Directive name is required', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Hello');

        $response = $this->service->run(MakeDirective::class, [
            'hello-command',
            '--description=Hello description',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Directives/HelloCommandDirective.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace Hello\\Directives', $content);
    }

    public function test_uses_default_description_when_not_provided(): void
    {
        $response = $this->service->run(MakeDirective::class, [
            'default-desc',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Directives/DefaultDescDirective.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString("return 'Description of the directive'", $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run(MakeDirective::class, [
            'my-custom-command',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Directives/MyCustomCommandDirective.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomCommandDirective', $content);
        $this->assertStringContainsString("return 'my-custom-command'", $content);
    }

    public function test_handles_camel_case_name(): void
    {
        $response = $this->service->run(MakeDirective::class, [
            'myCustomCommand',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Directives/MyCustomCommandDirective.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomCommandDirective', $content);
        $this->assertStringContainsString("return 'mycustomcommand'", $content);
    }

    public function test_creates_file_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Directives', 0777, true);

        $response = $this->service->run(MakeDirective::class, [
            'lib-command',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/src/Directives/LibCommandDirective.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class LibCommandDirective', $content);
    }

    public function test_generates_correct_stub_content(): void
    {
        $response = $this->service->run(MakeDirective::class, [
            'test.content',
            '--description=My custom description',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Directives/Test/ContentDirective.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Directives\\Test;', $content);
        $this->assertStringContainsString('class ContentDirective extends AbstractDirective', $content);
        $this->assertStringContainsString("return 'test.content'", $content);
        $this->assertStringContainsString("return 'My custom description'", $content);
        $this->assertStringContainsString('use AndyDefer\\Directive\\AbstractDirective;', $content);
        $this->assertStringContainsString('use AndyDefer\\Directive\\Enums\\ExitCode;', $content);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Collections\\Utility\\StringTypedCollection;', $content);
        $this->assertStringContainsString('public function shouldBootLaravel(): bool', $content);
        $this->assertStringContainsString('public function execute(): ExitCode', $content);
        $this->assertStringContainsString("\$this->info('Directive executed successfully!');", $content);
    }

    public function test_works_with_create_directive_alias(): void
    {
        $response = $this->service->run(MakeDirective::class, [
            'alias-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Directives/AliasTestDirective.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_cmd_alias(): void
    {
        $response = $this->service->run(MakeDirective::class, [
            'cmd-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Directives/CmdTestDirective.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run(MakeDirective::class, [
            'success-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run(MakeDirective::class, [
            'invalid..name',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
