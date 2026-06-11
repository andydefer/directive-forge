<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class MakeRepositoryDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DirectiveTestingService($this->app);
    }

    protected function tearDown(): void
    {
        $this->service->destroy();
        parent::tearDown();
    }

    public function test_get_signature_returns_make_repository(): void
    {
        $response = $this->service->run(MakeRepositoryDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_get_description_returns_description(): void
    {
        $response = $this->service->run(MakeRepositoryDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $this->service->registerDirective(MakeRepositoryDirective::class);

        $response = $this->service->runDirective('create-repository', ['test-alias']);
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $response = $this->service->runDirective('make-repo', ['test-alias-2']);
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeRepositoryDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('name', $response->output);
    }

    public function test_execute_creates_repository_file(): void
    {
        $repositoryName = 'user';

        $response = $this->service->run(MakeRepositoryDirective::class, [$repositoryName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Repositories/UserRepository.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserRepository', $content);
        $this->assertStringContainsString('extends AbstractRepository', $content);
    }

    public function test_execute_creates_repository_in_subdirectory(): void
    {
        $repositoryName = 'admin/user';

        $response = $this->service->run(MakeRepositoryDirective::class, [$repositoryName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Repositories/Admin/UserRepository.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Repositories\\Admin', $content);
        $this->assertStringContainsString('class UserRepository', $content);
    }

    public function test_execute_adds_repository_suffix_automatically(): void
    {
        $repositoryName = 'product';

        $response = $this->service->run(MakeRepositoryDirective::class, [$repositoryName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Repositories/ProductRepository.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProductRepository', $content);
    }

    public function test_execute_does_not_double_repository_suffix(): void
    {
        $repositoryName = 'UserRepository';

        $response = $this->service->run(MakeRepositoryDirective::class, [$repositoryName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Repositories/UserRepository.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserRepository', $content);
        $this->assertStringNotContainsString('UserRepositoryRepository', $content);
    }

    // ==================== Tests avec option --fully ====================

    public function test_execute_with_fully_option_creates_repository_record_and_filter_record(): void
    {
        $repositoryName = 'user';

        $response = $this->service->run(MakeRepositoryDirective::class, [$repositoryName, '--fully']);

        $tempDir = $this->service->getContext()->getTempDir();
        $repositoryPath = $tempDir . '/app/Repositories/UserRepository.php';
        $recordPath = $tempDir . '/app/Records/UserRecord.php';
        $filterRecordPath = $tempDir . '/app/Records/UserFilterRecord.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Fully created', $response->output);
        $this->assertFileExists($repositoryPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($filterRecordPath);
    }

    public function test_execute_with_fully_option_in_subdirectory(): void
    {
        $repositoryName = 'admin/user';

        $response = $this->service->run(MakeRepositoryDirective::class, [$repositoryName, '--fully']);

        $tempDir = $this->service->getContext()->getTempDir();
        $repositoryPath = $tempDir . '/app/Repositories/Admin/UserRepository.php';
        $recordPath = $tempDir . '/app/Records/Admin/UserRecord.php';
        $filterRecordPath = $tempDir . '/app/Records/Admin/UserFilterRecord.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Fully created', $response->output);
        $this->assertFileExists($repositoryPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($filterRecordPath);
    }

    public function test_execute_with_fully_option_preserves_naming_consistency(): void
    {
        $repositoryName = 'user-profile';

        $response = $this->service->run(MakeRepositoryDirective::class, [$repositoryName, '--fully']);

        $tempDir = $this->service->getContext()->getTempDir();
        $repositoryPath = $tempDir . '/app/Repositories/UserProfileRepository.php';
        $recordPath = $tempDir . '/app/Records/UserProfileRecord.php';
        $filterRecordPath = $tempDir . '/app/Records/UserProfileFilterRecord.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertFileExists($repositoryPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($filterRecordPath);
    }

    public function test_execute_with_fully_option_does_not_create_duplicate_files_on_second_run(): void
    {
        $repositoryName = 'test/duplicate';

        // First run - should succeed
        $firstResponse = $this->service->run(MakeRepositoryDirective::class, [$repositoryName, '--fully']);
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exit_code);

        // Second run - should fail because files already exist
        $secondResponse = $this->service->run(MakeRepositoryDirective::class, [$repositoryName, '--fully']);
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exit_code);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }

    public function test_execute_without_fully_option_does_not_create_records(): void
    {
        $repositoryName = 'user';

        $response = $this->service->run(MakeRepositoryDirective::class, [$repositoryName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $recordPath = $tempDir . '/app/Records/UserRecord.php';
        $filterRecordPath = $tempDir . '/app/Records/UserFilterRecord.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertFileExists($tempDir . '/app/Repositories/UserRepository.php');
        $this->assertFileDoesNotExist($recordPath);
        $this->assertFileDoesNotExist($filterRecordPath);
    }
}
