<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeRepositoryDirectiveTest extends UnitTestCase
{
    use InteractsWithDirectives;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDirectiveTesting(bootLaravel: false);

        // Créer les répertoires nécessaires
        $this->createDirectories();

        // Enregistrer la directive MakeRecordDirective pour le test --fully
        $this->registerDirective(new MakeRecordDirective($this->interaction));
    }

    protected function tearDown(): void
    {
        $this->destroyDirectiveTesting();
        parent::tearDown();
    }

    private function getDirective(): MakeRepositoryDirective
    {
        return new MakeRepositoryDirective($this->interaction);
    }

    private function runMakeRepository(array $arguments = []): DirectiveResponseRecord
    {
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        return $this->runDirective(MakeRepositoryDirective::class, $arguments);
    }

    private function createDirectories(): void
    {
        $directories = [
            $this->directiveTempDir . '/app/Repositories',
            $this->directiveTempDir . '/app/Repositories/Admin',
            $this->directiveTempDir . '/app/Records',
            $this->directiveTempDir . '/app/Records/User',
            $this->directiveTempDir . '/app/Records/Admin',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    public function test_get_signature_returns_make_repository(): void
    {
        // Arrange
        $directive = $this->getDirective();

        // Act
        $signature = $directive->getSignature();

        // Assert
        $this->assertSame('make-repository {name} {--fully}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange
        $directive = $this->getDirective();

        // Act
        $description = $directive->getDescription();

        // Assert
        $this->assertSame('Create a new repository class (with --fully option to also create Record and FilterRecord)', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange
        $directive = $this->getDirective();

        // Act
        $aliases = $directive->getAliases();

        // Assert
        $this->assertTrue($aliases->contains('create-repository'));
        $this->assertTrue($aliases->contains('make-repo'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act
        $response = $this->runMakeRepository([]);

        // Assert
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('name', $response->output);
    }

    public function test_execute_creates_repository_file(): void
    {
        // Arrange
        $repositoryName = 'user';

        // Act
        $response = $this->runMakeRepository([$repositoryName]);

        $expectedPath = $this->directiveTempDir . '/app/Repositories/UserRepository.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserRepository', $content);
        $this->assertStringContainsString('extends AbstractRepository', $content);
    }

    public function test_execute_creates_repository_in_subdirectory(): void
    {
        // Arrange
        $repositoryName = 'admin/user';

        // Act
        $response = $this->runMakeRepository([$repositoryName]);

        $expectedPath = $this->directiveTempDir . '/app/Repositories/Admin/UserRepository.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Repositories\\Admin', $content);
        $this->assertStringContainsString('class UserRepository', $content);
    }

    public function test_execute_adds_repository_suffix_automatically(): void
    {
        // Arrange
        $repositoryName = 'product';

        // Act
        $response = $this->runMakeRepository([$repositoryName]);

        $expectedPath = $this->directiveTempDir . '/app/Repositories/ProductRepository.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProductRepository', $content);
    }

    public function test_execute_does_not_double_repository_suffix(): void
    {
        // Arrange
        $repositoryName = 'UserRepository';

        // Act
        $response = $this->runMakeRepository([$repositoryName]);

        $expectedPath = $this->directiveTempDir . '/app/Repositories/UserRepository.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserRepository', $content);
        $this->assertStringNotContainsString('UserRepositoryRepository', $content);
    }

    // ==================== Tests avec option --fully ====================

    public function test_execute_with_fully_option_creates_repository_record_and_filter_record(): void
    {
        // Arrange
        $repositoryName = 'user';

        // Act
        $response = $this->runMakeRepository([$repositoryName, '--fully']);

        $repositoryPath = $this->directiveTempDir . '/app/Repositories/UserRepository.php';
        $recordPath = $this->directiveTempDir . '/app/Records/UserRecord.php';
        $filterRecordPath = $this->directiveTempDir . '/app/Records/UserFilterRecord.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($repositoryPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($filterRecordPath);

        $repositoryContent = file_get_contents($repositoryPath);
        $recordContent = file_get_contents($recordPath);
        $filterRecordContent = file_get_contents($filterRecordPath);

        $this->assertStringContainsString('class UserRepository', $repositoryContent);
        $this->assertStringContainsString('class UserRecord', $recordContent);
        $this->assertStringContainsString('class UserFilterRecord', $filterRecordContent);
        $this->assertStringContainsString('extends AbstractRecord', $recordContent);
        $this->assertStringContainsString('extends AbstractRecord', $filterRecordContent);

        $this->assertStringContainsString('Fully created', $response->output);
    }

    public function test_execute_with_fully_option_in_subdirectory(): void
    {
        // Arrange
        $repositoryName = 'admin/user';

        // Act
        $response = $this->runMakeRepository([$repositoryName, '--fully']);

        $repositoryPath = $this->directiveTempDir . '/app/Repositories/Admin/UserRepository.php';
        $recordPath = $this->directiveTempDir . '/app/Records/Admin/UserRecord.php';
        $filterRecordPath = $this->directiveTempDir . '/app/Records/Admin/UserFilterRecord.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($repositoryPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($filterRecordPath);

        $repositoryContent = file_get_contents($repositoryPath);
        $recordContent = file_get_contents($recordPath);
        $filterRecordContent = file_get_contents($filterRecordPath);

        $this->assertStringContainsString('namespace App\\Repositories\\Admin', $repositoryContent);
        $this->assertStringContainsString('class UserRepository', $repositoryContent);
        $this->assertStringContainsString('namespace App\\Records\\Admin', $recordContent);
        $this->assertStringContainsString('class UserRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Records\\Admin', $filterRecordContent);
        $this->assertStringContainsString('class UserFilterRecord', $filterRecordContent);
    }

    public function test_execute_with_fully_option_preserves_naming_consistency(): void
    {
        // Arrange
        $repositoryName = 'user-profile';

        // Act
        $response = $this->runMakeRepository([$repositoryName, '--fully']);

        $repositoryPath = $this->directiveTempDir . '/app/Repositories/UserProfileRepository.php';
        $recordPath = $this->directiveTempDir . '/app/Records/UserProfileRecord.php';
        $filterRecordPath = $this->directiveTempDir . '/app/Records/UserProfileFilterRecord.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($repositoryPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($filterRecordPath);

        $this->assertStringContainsString('UserProfileRepository', file_get_contents($repositoryPath));
        $this->assertStringContainsString('UserProfileRecord', file_get_contents($recordPath));
        $this->assertStringContainsString('UserProfileFilterRecord', file_get_contents($filterRecordPath));
    }

    public function test_execute_with_fully_option_does_not_create_duplicate_files_on_second_run(): void
    {
        // Arrange
        $repositoryName = 'test/duplicate';

        // Act: First run
        $firstResponse = $this->runMakeRepository([$repositoryName, '--fully']);

        // Assert: First creation succeeded
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exitCode);

        // Act: Second run
        $secondResponse = $this->runMakeRepository([$repositoryName, '--fully']);

        // Assert: Second run fails because files already exist
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exitCode);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }

    public function test_execute_without_fully_option_does_not_create_records(): void
    {
        // Arrange
        $repositoryName = 'user';

        // Act
        $response = $this->runMakeRepository([$repositoryName]);

        $recordPath = $this->directiveTempDir . '/app/Records/UserRecord.php';
        $filterRecordPath = $this->directiveTempDir . '/app/Records/UserFilterRecord.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($this->directiveTempDir . '/app/Repositories/UserRepository.php');
        $this->assertFileDoesNotExist($recordPath);
        $this->assertFileDoesNotExist($filterRecordPath);
    }
}
