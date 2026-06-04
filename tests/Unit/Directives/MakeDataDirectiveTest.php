<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeDataDirective;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeDataDirectiveTest extends UnitTestCase
{
    use InteractsWithDirectives;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDirectiveTesting(bootLaravel: false);

        // Arrange: Create directories needed for tests
        $this->createDirectories();

        // Arrange: Register required directives for --fully option
        $this->registerDirective(new MakeRecordDirective($this->interaction));
        $this->registerDirective(new MakeTypedCollectionDirective($this->interaction));
    }

    protected function tearDown(): void
    {
        $this->destroyDirectiveTesting();
        parent::tearDown();
    }

    private function getDirective(): MakeDataDirective
    {
        return new MakeDataDirective($this->interaction);
    }

    private function runMakeData(array $arguments = []): DirectiveResponseRecord
    {
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        return $this->runDirective(MakeDataDirective::class, $arguments);
    }

    private function createDirectories(): void
    {
        $directories = [
            $this->directiveTempDir . '/app/Data',
            $this->directiveTempDir . '/app/Data/User',
            $this->directiveTempDir . '/app/Records',
            $this->directiveTempDir . '/app/Records/User',
            $this->directiveTempDir . '/app/Collections',
            $this->directiveTempDir . '/app/Collections/User',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    public function test_get_signature_returns_make_data(): void
    {
        // Arrange
        $directive = $this->getDirective();

        // Act
        $signature = $directive->getSignature();

        // Assert
        $this->assertSame('make-data {name} {--fully}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange
        $directive = $this->getDirective();

        // Act
        $description = $directive->getDescription();

        // Assert
        $this->assertSame('Create a new data DTO class (with --fully option to also create Record and TypedCollection)', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange
        $directive = $this->getDirective();

        // Act
        $aliases = $directive->getAliases();

        // Assert
        $this->assertTrue($aliases->contains('create-data'));
        $this->assertTrue($aliases->contains('make-dto'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act
        $response = $this->runMakeData([]);

        // Assert
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('name', $response->output);
    }

    public function test_execute_creates_data_file(): void
    {
        // Arrange
        $dataName = 'user';

        // Act
        $response = $this->runMakeData([$dataName]);

        $expectedPath = $this->directiveTempDir . '/app/Data/UserData.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('data created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserData', $content);
        $this->assertStringContainsString('extends AbstractData', $content);
        $this->assertStringContainsString('namespace App\\Data', $content);
    }

    public function test_execute_creates_data_in_subdirectory(): void
    {
        // Arrange
        $dataName = 'user/profile';

        // Act
        $response = $this->runMakeData([$dataName]);

        $expectedPath = $this->directiveTempDir . '/app/Data/User/ProfileData.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Data\\User', $content);
        $this->assertStringContainsString('class ProfileData', $content);
    }

    public function test_execute_adds_data_suffix_automatically(): void
    {
        // Arrange
        $dataName = 'product';

        // Act
        $response = $this->runMakeData([$dataName]);

        $expectedPath = $this->directiveTempDir . '/app/Data/ProductData.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProductData', $content);
    }

    public function test_execute_does_not_double_data_suffix(): void
    {
        // Arrange
        $dataName = 'UserData';

        // Act
        $response = $this->runMakeData([$dataName]);

        $expectedPath = $this->directiveTempDir . '/app/Data/UserData.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserData', $content);
        $this->assertStringNotContainsString('UserDataData', $content);
    }

    // ==================== Tests avec option --fully ====================

    public function test_execute_with_fully_option_creates_data_record_and_collection(): void
    {
        // Arrange
        $dataName = 'user';

        // Act
        $response = $this->runMakeData([$dataName, '--fully']);

        $dataPath = $this->directiveTempDir . '/app/Data/UserData.php';
        $recordPath = $this->directiveTempDir . '/app/Records/UserRecord.php';
        $collectionPath = $this->directiveTempDir . '/app/Collections/UserDataCollection.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($dataPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($collectionPath);

        $dataContent = file_get_contents($dataPath);
        $recordContent = file_get_contents($recordPath);
        $collectionContent = file_get_contents($collectionPath);

        $this->assertStringContainsString('class UserData', $dataContent);
        $this->assertStringContainsString('class UserRecord', $recordContent);
        $this->assertStringContainsString('extends AbstractRecord', $recordContent);
        $this->assertStringContainsString('class UserDataCollection', $collectionContent);
        $this->assertStringContainsString('extends AbstractTypedCollection', $collectionContent);
        // ✅ Correction : vérifier AbstractTypedCollection dans l'annotation
        $this->assertStringContainsString('@extends AbstractTypedCollection<UserData>', $collectionContent);

        $this->assertStringContainsString('Fully created', $response->output);
    }

    public function test_execute_with_fully_option_in_subdirectory(): void
    {
        // Arrange
        $dataName = 'user/profile';

        // Act
        $response = $this->runMakeData([$dataName, '--fully']);

        $dataPath = $this->directiveTempDir . '/app/Data/User/ProfileData.php';
        $recordPath = $this->directiveTempDir . '/app/Records/User/ProfileRecord.php';
        $collectionPath = $this->directiveTempDir . '/app/Collections/User/ProfileDataCollection.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($dataPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($collectionPath);

        $dataContent = file_get_contents($dataPath);
        $recordContent = file_get_contents($recordPath);
        $collectionContent = file_get_contents($collectionPath);

        $this->assertStringContainsString('namespace App\\Data\\User', $dataContent);
        $this->assertStringContainsString('class ProfileData', $dataContent);
        $this->assertStringContainsString('namespace App\\Records\\User', $recordContent);
        $this->assertStringContainsString('class ProfileRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Collections\\User', $collectionContent);
        $this->assertStringContainsString('class ProfileDataCollection', $collectionContent);
        $this->assertStringContainsString('extends AbstractTypedCollection', $collectionContent);
        $this->assertStringContainsString('@extends AbstractTypedCollection<ProfileData>', $collectionContent);
    }

    public function test_execute_with_fully_option_preserves_naming_consistency(): void
    {
        // Arrange
        $dataName = 'user-profile';

        // Act
        $response = $this->runMakeData([$dataName, '--fully']);

        $dataPath = $this->directiveTempDir . '/app/Data/UserProfileData.php';
        $recordPath = $this->directiveTempDir . '/app/Records/UserProfileRecord.php';
        $collectionPath = $this->directiveTempDir . '/app/Collections/UserProfileDataCollection.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($dataPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($collectionPath);

        $this->assertStringContainsString('UserProfileData', file_get_contents($dataPath));
        $this->assertStringContainsString('UserProfileRecord', file_get_contents($recordPath));
        $this->assertStringContainsString('UserProfileDataCollection', file_get_contents($collectionPath));
        $this->assertStringContainsString('extends AbstractTypedCollection', file_get_contents($collectionPath));
        $this->assertStringContainsString('@extends AbstractTypedCollection<UserProfileData>', file_get_contents($collectionPath));
    }

    public function test_execute_with_fully_option_does_not_create_duplicate_files_on_second_run(): void
    {
        // Arrange
        $dataName = 'test/duplicate';

        // Act: First run
        $firstResponse = $this->runMakeData([$dataName, '--fully']);

        // Assert: First creation succeeded
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exitCode);

        // Act: Second run
        $secondResponse = $this->runMakeData([$dataName, '--fully']);

        // Assert: Second run fails because files already exist
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exitCode);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }

    public function test_execute_without_fully_option_does_not_create_record_and_collection(): void
    {
        // Arrange
        $dataName = 'user';

        // Act
        $response = $this->runMakeData([$dataName]);

        $recordPath = $this->directiveTempDir . '/app/Records/UserRecord.php';
        $collectionPath = $this->directiveTempDir . '/app/Collections/UserDataCollection.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($this->directiveTempDir . '/app/Data/UserData.php');
        $this->assertFileDoesNotExist($recordPath);
        $this->assertFileDoesNotExist($collectionPath);
    }
}
