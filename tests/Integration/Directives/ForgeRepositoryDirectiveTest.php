<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class ForgeRepositoryDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    private string $tempDir;

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

        $filesystem = new FileSystemService;
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Models');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Models/Profiles');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Records');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Records/Profiles');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Repositories');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Repositories/Profiles');
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

    public function test_creates_repository_with_app_namespace(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'App');

        $response = $this->service->run('forge:repository user');

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

    public function test_creates_repository_with_subdirectory(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'App');

        $response = $this->service->run('forge:repository profiles.user-profile');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Repository created successfully', $response->output);

        $repoPath = $this->tempDir.'/app/Repositories/Profiles/UserProfileRepository.php';
        $this->assertFileExists($repoPath);

        $repoContent = file_get_contents($repoPath);
        $this->assertStringContainsString('namespace App\\Repositories\\Profiles;', $repoContent);
        $this->assertStringContainsString('use App\\Models\\Profiles\\UserProfile;', $repoContent);
        $this->assertStringContainsString('use App\\Records\\Profiles\\UserProfileRecord;', $repoContent);
        $this->assertStringContainsString('use App\\Records\\Profiles\\UserProfileFiltersRecord;', $repoContent);

        $recordPath = $this->tempDir.'/app/Records/Profiles/UserProfileRecord.php';
        $this->assertFileExists($recordPath);

        $filtersPath = $this->tempDir.'/app/Records/Profiles/UserProfileFiltersRecord.php';
        $this->assertFileExists($filtersPath);
    }

    public function test_creates_repository_with_deep_subdirectory(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'App');

        $response = $this->service->run('forge:repository api.v1.user.profile');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $repoPath = $this->tempDir.'/app/Repositories/Api/V1/User/ProfileRepository.php';
        $this->assertFileExists($repoPath);

        $repoContent = file_get_contents($repoPath);
        $this->assertStringContainsString('namespace App\\Repositories\\Api\\V1\\User;', $repoContent);
        $this->assertStringContainsString('use App\\Models\\Api\\V1\\User\\Profile;', $repoContent);
        $this->assertStringContainsString('use App\\Records\\Api\\V1\\User\\ProfileRecord;', $repoContent);
        $this->assertStringContainsString('use App\\Records\\Api\\V1\\User\\ProfileFiltersRecord;', $repoContent);

        $recordPath = $this->tempDir.'/app/Records/Api/V1/User/ProfileRecord.php';
        $this->assertFileExists($recordPath);

        $filtersPath = $this->tempDir.'/app/Records/Api/V1/User/ProfileFiltersRecord.php';
        $this->assertFileExists($filtersPath);
    }

    public function test_creates_repository_with_custom_namespace(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'MyPackage');

        $response = $this->service->run('forge:repository profiles.user-profile');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $repoPath = $this->tempDir.'/app/Repositories/Profiles/UserProfileRepository.php';
        $this->assertFileExists($repoPath);

        $repoContent = file_get_contents($repoPath);
        $this->assertStringContainsString('namespace MyPackage\\Repositories\\Profiles;', $repoContent);
        $this->assertStringContainsString('use MyPackage\\Models\\Profiles\\UserProfile;', $repoContent);
        $this->assertStringContainsString('use MyPackage\\Records\\Profiles\\UserProfileRecord;', $repoContent);
        $this->assertStringContainsString('use MyPackage\\Records\\Profiles\\UserProfileFiltersRecord;', $repoContent);
    }

    public function test_creates_repository_with_vendor_namespace(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Vendor\\Package');

        $response = $this->service->run('forge:repository profiles.user-profile');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $repoPath = $this->tempDir.'/app/Repositories/Profiles/UserProfileRepository.php';
        $this->assertFileExists($repoPath);

        $repoContent = file_get_contents($repoPath);
        $this->assertStringContainsString('namespace Vendor\\Package\\Repositories\\Profiles;', $repoContent);
        $this->assertStringContainsString('use Vendor\\Package\\Models\\Profiles\\UserProfile;', $repoContent);
        $this->assertStringContainsString('use Vendor\\Package\\Records\\Profiles\\UserProfileRecord;', $repoContent);
        $this->assertStringContainsString('use Vendor\\Package\\Records\\Profiles\\UserProfileFiltersRecord;', $repoContent);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run('forge:repository');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Model name is required', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run('forge:repository ');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Model name is required', $response->output);
    }

    public function test_handles_kebab_case_model_name(): void
    {
        $response = $this->service->run('forge:repository user-profile');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $repoPath = $this->tempDir.'/app/Repositories/UserProfileRepository.php';
        $this->assertFileExists($repoPath);

        $repoContent = file_get_contents($repoPath);
        $this->assertStringContainsString('class UserProfileRepository', $repoContent);

        $recordPath = $this->tempDir.'/app/Records/UserProfileRecord.php';
        $this->assertFileExists($recordPath);

        $filtersPath = $this->tempDir.'/app/Records/UserProfileFiltersRecord.php';
        $this->assertFileExists($filtersPath);
    }

    public function test_handles_kebab_case_with_subdirectory(): void
    {
        $response = $this->service->run('forge:repository profiles.user-profile');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $repoPath = $this->tempDir.'/app/Repositories/Profiles/UserProfileRepository.php';
        $this->assertFileExists($repoPath);

        $repoContent = file_get_contents($repoPath);
        $this->assertStringContainsString('class UserProfileRepository', $repoContent);

        $recordPath = $this->tempDir.'/app/Records/Profiles/UserProfileRecord.php';
        $this->assertFileExists($recordPath);

        $filtersPath = $this->tempDir.'/app/Records/Profiles/UserProfileFiltersRecord.php';
        $this->assertFileExists($filtersPath);
    }

    public function test_creates_repository_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        $filesystem = new FileSystemService;
        $filesystem->ensureDirectoryExists($this->tempDir.'/src/Models/Profiles');
        $filesystem->ensureDirectoryExists($this->tempDir.'/src/Records/Profiles');
        $filesystem->ensureDirectoryExists($this->tempDir.'/src/Repositories/Profiles');

        $response = $this->service->run('forge:repository profiles.user-profile');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $repoPath = $this->tempDir.'/src/Repositories/Profiles/UserProfileRepository.php';
        $this->assertFileExists($repoPath);

        $repoContent = file_get_contents($repoPath);
        $this->assertStringContainsString('namespace App\\Repositories\\Profiles;', $repoContent);

        $recordPath = $this->tempDir.'/src/Records/Profiles/UserProfileRecord.php';
        $this->assertFileExists($recordPath);

        $filtersPath = $this->tempDir.'/src/Records/Profiles/UserProfileFiltersRecord.php';
        $this->assertFileExists($filtersPath);
    }

    public function test_works_with_create_repository_alias(): void
    {
        $response = $this->service->run('create-repository profiles.user-profile');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $repoPath = $this->tempDir.'/app/Repositories/Profiles/UserProfileRepository.php';
        $this->assertFileExists($repoPath);
    }

    public function test_works_with_make_repo_alias(): void
    {
        $response = $this->service->run('make-repo profiles.user-profile');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $repoPath = $this->tempDir.'/app/Repositories/Profiles/UserProfileRepository.php';
        $this->assertFileExists($repoPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run('forge:repository user');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run('forge:repository invalid..name');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
