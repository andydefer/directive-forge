<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class MakeRepositoryDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    private string $tempDir;

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

        // Créer les dossiers nécessaires
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
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Models');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Records');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Repositories');
    }

    public function test_creates_repository_with_app_namespace(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'App');

        $response = $this->service->run(MakeRepositoryDirective::class, [
            'user',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Repository created successfully', $response->output);

        $repoPath = $this->tempDir.'/app/Repositories/UserRepository.php';
        $this->assertFileExists($repoPath);

        $repoContent = file_get_contents($repoPath);
        $this->assertStringContainsString('namespace App\\Repositories;', $repoContent);
        $this->assertStringContainsString('use App\\Models\\User;', $repoContent);
        $this->assertStringContainsString('use App\\Records\\UserRecord;', $repoContent);
        $this->assertStringContainsString('use App\\Records\\UserFiltersRecord;', $repoContent);
    }

    public function test_creates_repository_with_custom_namespace(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'MyPackage');

        $response = $this->service->run(MakeRepositoryDirective::class, [
            'user',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $repoPath = $this->tempDir.'/app/Repositories/UserRepository.php';
        $this->assertFileExists($repoPath);

        $repoContent = file_get_contents($repoPath);
        $this->assertStringContainsString('namespace MyPackage\\Repositories;', $repoContent);
        $this->assertStringContainsString('use MyPackage\\Models\\User;', $repoContent);
        $this->assertStringContainsString('use MyPackage\\Records\\UserRecord;', $repoContent);
        $this->assertStringContainsString('use MyPackage\\Records\\UserFiltersRecord;', $repoContent);

        $recordPath = $this->tempDir.'/app/Records/UserRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('namespace MyPackage\\Records;', $recordContent);
    }

    public function test_creates_repository_with_vendor_namespace(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Vendor\\Package');

        $response = $this->service->run(MakeRepositoryDirective::class, [
            'product',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $repoPath = $this->tempDir.'/app/Repositories/ProductRepository.php';
        $this->assertFileExists($repoPath);

        $repoContent = file_get_contents($repoPath);
        $this->assertStringContainsString('namespace Vendor\\Package\\Repositories;', $repoContent);
        $this->assertStringContainsString('use Vendor\\Package\\Models\\Product;', $repoContent);
        $this->assertStringContainsString('use Vendor\\Package\\Records\\ProductRecord;', $repoContent);
        $this->assertStringContainsString('use Vendor\\Package\\Records\\ProductFiltersRecord;', $repoContent);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeRepositoryDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run(MakeRepositoryDirective::class, ['']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Model name is required', $response->output);
    }

    public function test_handles_kebab_case_model_name(): void
    {
        $response = $this->service->run(MakeRepositoryDirective::class, [
            'user-profile',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $repoPath = $this->tempDir.'/app/Repositories/UserProfileRepository.php';
        $this->assertFileExists($repoPath);

        $repoContent = file_get_contents($repoPath);
        $this->assertStringContainsString('class UserProfileRepository', $repoContent);
        $this->assertStringContainsString('parent::__construct(UserProfile::class, UserProfileRecord::class)', $repoContent);

        $recordPath = $this->tempDir.'/app/Records/UserProfileRecord.php';
        $this->assertFileExists($recordPath);

        $filtersPath = $this->tempDir.'/app/Records/UserProfileFiltersRecord.php';
        $this->assertFileExists($filtersPath);
    }

    public function test_creates_repository_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        $filesystem = new FileSystemService;
        $filesystem->ensureDirectoryExists($this->tempDir.'/src/Models');
        $filesystem->ensureDirectoryExists($this->tempDir.'/src/Records');
        $filesystem->ensureDirectoryExists($this->tempDir.'/src/Repositories');

        $response = $this->service->run(MakeRepositoryDirective::class, [
            'product',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $repoPath = $this->tempDir.'/src/Repositories/ProductRepository.php';
        $this->assertFileExists($repoPath);

        $repoContent = file_get_contents($repoPath);
        $this->assertStringContainsString('namespace App\\Repositories;', $repoContent);

        $recordPath = $this->tempDir.'/src/Records/ProductRecord.php';
        $this->assertFileExists($recordPath);

        $filtersPath = $this->tempDir.'/src/Records/ProductFiltersRecord.php';
        $this->assertFileExists($filtersPath);
    }
}
