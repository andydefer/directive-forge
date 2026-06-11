<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeDataDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class MakeDataDirectiveTest extends IntegrationTestCase
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

    public function test_get_signature_returns_make_data(): void
    {
        $response = $this->service->run(MakeDataDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_get_description_returns_description(): void
    {
        $response = $this->service->run(MakeDataDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $this->service->registerDirective(MakeDataDirective::class);

        $response = $this->service->runDirective('create-data', ['test-alias']);
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $response = $this->service->runDirective('make-dto', ['test-alias-2']);
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeDataDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('name', $response->output);
    }

    public function test_execute_creates_data_file(): void
    {
        $dataName = 'user';

        $response = $this->service->run(MakeDataDirective::class, [$dataName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Data/UserData.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('data created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserData', $content);
        $this->assertStringContainsString('extends AbstractData', $content);
        $this->assertStringContainsString('namespace App\\Data', $content);
    }

    public function test_execute_creates_data_in_subdirectory(): void
    {
        $dataName = 'user/profile';

        $response = $this->service->run(MakeDataDirective::class, [$dataName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Data/User/ProfileData.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('data created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Data\\User', $content);
        $this->assertStringContainsString('class ProfileData', $content);
    }

    public function test_execute_adds_data_suffix_automatically(): void
    {
        $dataName = 'product';

        $response = $this->service->run(MakeDataDirective::class, [$dataName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Data/ProductData.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('data created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProductData', $content);
    }

    public function test_execute_does_not_double_data_suffix(): void
    {
        $dataName = 'UserData';

        $response = $this->service->run(MakeDataDirective::class, [$dataName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Data/UserData.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('data created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserData', $content);
        $this->assertStringNotContainsString('UserDataData', $content);
    }

    // ==================== Tests avec option --fully ====================

    public function test_execute_with_fully_option_creates_data_record_and_collection(): void
    {
        $dataName = 'user';

        $response = $this->service->run(MakeDataDirective::class, [$dataName, '--fully']);

        $tempDir = $this->service->getContext()->getTempDir();
        $dataPath = $tempDir . '/app/Data/UserData.php';
        $recordPath = $tempDir . '/app/Records/UserRecord.php';
        $collectionPath = $tempDir . '/app/Collections/UserDataCollection.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Fully created', $response->output);
        $this->assertFileExists($dataPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($collectionPath);
    }

    public function test_execute_with_fully_option_in_subdirectory(): void
    {
        $dataName = 'user/profile';

        $response = $this->service->run(MakeDataDirective::class, [$dataName, '--fully']);

        $tempDir = $this->service->getContext()->getTempDir();
        $dataPath = $tempDir . '/app/Data/User/ProfileData.php';
        $recordPath = $tempDir . '/app/Records/User/ProfileRecord.php';
        $collectionPath = $tempDir . '/app/Collections/User/ProfileDataCollection.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Fully created', $response->output);
        $this->assertFileExists($dataPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($collectionPath);
    }

    public function test_execute_with_fully_option_preserves_naming_consistency(): void
    {
        $dataName = 'user-profile';

        $response = $this->service->run(MakeDataDirective::class, [$dataName, '--fully']);

        $tempDir = $this->service->getContext()->getTempDir();
        $dataPath = $tempDir . '/app/Data/UserProfileData.php';
        $recordPath = $tempDir . '/app/Records/UserProfileRecord.php';
        $collectionPath = $tempDir . '/app/Collections/UserProfileDataCollection.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertFileExists($dataPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($collectionPath);
    }

    public function test_execute_with_fully_option_does_not_create_duplicate_files_on_second_run(): void
    {
        $dataName = 'test/duplicate';

        // First run - should succeed
        $firstResponse = $this->service->run(MakeDataDirective::class, [$dataName, '--fully']);
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exit_code);

        // Second run - should fail because files already exist
        $secondResponse = $this->service->run(MakeDataDirective::class, [$dataName, '--fully']);
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exit_code);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }

    public function test_execute_without_fully_option_does_not_create_record_and_collection(): void
    {
        $dataName = 'user';

        $response = $this->service->run(MakeDataDirective::class, [$dataName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $recordPath = $tempDir . '/app/Records/UserRecord.php';
        $collectionPath = $tempDir . '/app/Collections/UserDataCollection.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertFileExists($tempDir . '/app/Data/UserData.php');
        $this->assertFileDoesNotExist($recordPath);
        $this->assertFileDoesNotExist($collectionPath);
    }
}
