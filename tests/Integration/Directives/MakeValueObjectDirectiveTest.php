<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeValueObjectDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class MakeValueObjectDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DirectiveTestingService($this->app);
        $this->service->registerDirective(MakeValueObjectDirective::class);

        $this->tempDir = $this->service->getTempDir();

        $this->app['config']->set('directive-forge.mode', 'app');
        $this->app['config']->set('directive-forge.namespace', 'App');
        $this->app['config']->set('directive-forge.extension', 'php');
        $this->app['config']->set('directive-forge.directory_permission', 0755);

        $this->createDirectories();
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

    private function createDirectories(): void
    {
        $filesystem = new FileSystemService;
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/ValueObjects');
    }

    public function test_creates_value_object_successfully(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, [
            'user-vo',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Value Object created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/ValueObjects/UserVO.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserVO extends AbstractValueObject', $content);
        $this->assertStringContainsString('namespace App\\ValueObjects;', $content);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Abstracts\\AbstractValueObject;', $content);
    }

    public function test_creates_value_object_with_subdirectories(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, [
            'posts.user-vo',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/ValueObjects/Posts/UserVO.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserVO extends AbstractValueObject', $content);
        $this->assertStringContainsString('namespace App\\ValueObjects\\Posts;', $content);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Abstracts\\AbstractValueObject;', $content);
    }

    public function test_creates_value_object_with_deep_subdirectories(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, [
            'api.v1.posts.user-vo',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/ValueObjects/Api/V1/Posts/UserVO.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserVO extends AbstractValueObject', $content);
        $this->assertStringContainsString('namespace App\\ValueObjects\\Api\\V1\\Posts;', $content);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Abstracts\\AbstractValueObject;', $content);
    }

    public function test_creates_value_object_with_custom_namespace(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'MyPackage');

        $response = $this->service->run(MakeValueObjectDirective::class, [
            'user-vo',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/ValueObjects/UserVO.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace MyPackage\\ValueObjects;', $content);
        $this->assertStringContainsString('class UserVO extends AbstractValueObject', $content);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, ['']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Value Object name is required', $response->output);
    }

    public function test_returns_error_when_value_object_already_exists(): void
    {
        // Créer une première fois
        $this->service->run(MakeValueObjectDirective::class, ['user-vo']);

        // Essayer de recréer
        $response = $this->service->run(MakeValueObjectDirective::class, ['user-vo']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Value Object already exists', $response->output);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, [
            'my-custom-vo',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/ValueObjects/MyCustomVO.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomVO extends AbstractValueObject', $content);
        $this->assertStringContainsString('namespace App\\ValueObjects;', $content);
    }

    public function test_handles_snake_case_name(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, [
            'my_custom_vo',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_creates_value_object_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        $filesystem = new FileSystemService;
        $filesystem->ensureDirectoryExists($this->tempDir.'/src/ValueObjects');

        $response = $this->service->run(MakeValueObjectDirective::class, [
            'user-vo',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/src/ValueObjects/UserVO.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace App\\ValueObjects;', $content);
        $this->assertStringContainsString('class UserVO extends AbstractValueObject', $content);
    }

    public function test_creates_value_object_with_vo_suffix_already_present(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, [
            'user-vo',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/ValueObjects/UserVO.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserVO extends AbstractValueObject', $content);
        $this->assertStringContainsString('namespace App\\ValueObjects;', $content);
    }

    public function test_creates_value_object_without_vo_suffix(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, [
            'user',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Le nom devrait être automatiquement complété avec -vo
        $expectedPath = $this->tempDir.'/app/ValueObjects/UserVO.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserVO extends AbstractValueObject', $content);
        $this->assertStringContainsString('namespace App\\ValueObjects;', $content);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, [
            'success-test-vo',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, [
            'invalid..name',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }

    public function test_works_with_create_vo_alias(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, [
            'alias-test-vo',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/ValueObjects/AliasTestVO.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_value_object_alias(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, [
            'alias-test-2-vo',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/ValueObjects/AliasTest2VO.php';
        $this->assertFileExists($expectedPath);
    }
}
