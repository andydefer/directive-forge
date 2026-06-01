<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Services\DirectiveNamingService;
use AndyDefer\Directive\Services\SignatureValidationService;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\DirectiveForgeServiceProvider;
use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Directives\MakeDirective;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective;
use AndyDefer\DirectiveForge\Directives\MakeTaskDirective;
use AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class DirectiveForgeCompleteIntegrationTest extends UnitTestCase
{
    use InteractsWithDirectives;

    private SignatureValidationService $signatureValidator;
    private DirectiveNamingService $namingService;

    protected function getPackageProviders($app): array
    {
        return [
            DirectiveForgeServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize the testing environment
        $this->initDirectiveTesting();

        $this->signatureValidator = new SignatureValidationService();
        $this->namingService = new DirectiveNamingService();

        $this->createDirectories();
    }

    protected function tearDown(): void
    {
        $this->destroyDirectiveTesting();
        parent::tearDown();
    }

    private function getMakeDirective(): MakeDirective
    {
        return new MakeDirective(
            $this->interaction,
            $this->signatureValidator,
            $this->namingService
        );
    }

    private function getMakeActionDirective(): MakeActionDirective
    {
        return new MakeActionDirective($this->interaction);
    }

    private function getMakeTaskDirective(): MakeTaskDirective
    {
        return new MakeTaskDirective($this->interaction);
    }

    private function getMakeRepositoryDirective(): MakeRepositoryDirective
    {
        return new MakeRepositoryDirective($this->interaction);
    }

    private function getMakeRecordDirective(): MakeRecordDirective
    {
        return new MakeRecordDirective($this->interaction);
    }

    private function getMakeTypedCollectionDirective(): MakeTypedCollectionDirective
    {
        return new MakeTypedCollectionDirective($this->interaction);
    }

    private function createDirectories(): void
    {
        $directories = [
            $this->directiveTempDir . '/app/Directives',
            $this->directiveTempDir . '/app/Actions',
            $this->directiveTempDir . '/app/Tasks',
            $this->directiveTempDir . '/app/Repositories',
            $this->directiveTempDir . '/app/Records',
            $this->directiveTempDir . '/app/Collections',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    private function registerAndRun(string $signature, array $arguments = []): DirectiveResponseRecord
    {
        $directive = match ($signature) {
            'make-directive', 'create-directive', 'make-cmd' => $this->getMakeDirective(),
            'make-action', 'create-action', 'make-act' => $this->getMakeActionDirective(),
            'make-task', 'create-task', 'make-job' => $this->getMakeTaskDirective(),
            'make-repository', 'create-repository', 'make-repo' => $this->getMakeRepositoryDirective(),
            'make-record', 'create-record', 'make-dto' => $this->getMakeRecordDirective(),
            'make-typed-collection', 'create-collection', 'make-collection' => $this->getMakeTypedCollectionDirective(),
            default => throw new \InvalidArgumentException("Unknown directive: {$signature}"),
        };

        $this->registerDirective($directive);

        return $this->runDirective($signature, $arguments);
    }

    // ==================== Directive Tests ====================

    public function test_make_directive_with_simple_name(): void
    {
        // Arrange: Prepare directive name
        $directiveName = 'user-list';

        // Act: Run make-directive command
        $response = $this->registerAndRun('make-directive', [$directiveName]);
        $fullPath = $this->directiveTempDir . '/app/Directives/UserListDirective.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
    }

    public function test_make_directive_with_subdirectory(): void
    {
        // Arrange: Prepare directive name with subdirectory
        $directiveName = 'user/domain/hello-directive';

        // Act: Run make-directive command
        $response = $this->registerAndRun('make-directive', [$directiveName]);
        $fullPath = $this->directiveTempDir . '/app/Directives/User/Domain/HelloDirective.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
    }

    public function test_make_directive_with_alias_create_directive(): void
    {
        // Arrange: Register directive
        $this->registerDirective($this->getMakeDirective());

        // Act: Run using alias
        $response = $this->runDirective('create-directive', ['cache-clear']);
        $fullPath = $this->directiveTempDir . '/app/Directives/CacheClearDirective.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
    }

    public function test_make_directive_with_alias_make_cmd(): void
    {
        // Arrange: Register directive
        $this->registerDirective($this->getMakeDirective());

        // Act: Run using alias
        $response = $this->runDirective('make-cmd', ['test-cmd']);
        $fullPath = $this->directiveTempDir . '/app/Directives/TestCmdDirective.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
    }

    // ==================== Action Tests ====================

    public function test_make_action_with_simple_name(): void
    {
        // Arrange: Prepare action name
        $actionName = 'user/show';

        // Act: Run make-action command
        $response = $this->registerAndRun('make-action', [$actionName]);
        $fullPath = $this->directiveTempDir . '/app/Actions/User/ShowAction.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
    }

    public function test_make_action_with_subdirectory(): void
    {
        // Arrange: Prepare action name with subdirectory
        $actionName = 'api/v1/users/show';

        // Act: Run make-action command
        $response = $this->registerAndRun('make-action', [$actionName]);
        $fullPath = $this->directiveTempDir . '/app/Actions/Api/V1/Users/ShowAction.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
    }

    public function test_make_action_with_alias_make_act(): void
    {
        // Arrange: Register directive
        $this->registerDirective($this->getMakeActionDirective());

        // Act: Run using alias
        $response = $this->runDirective('make-act', ['user/profile']);
        $fullPath = $this->directiveTempDir . '/app/Actions/User/ProfileAction.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
    }

    // ==================== Task Tests ====================

    public function test_make_task(): void
    {
        // Arrange: Prepare task name
        $taskName = 'send-welcome-email';

        // Act: Run make-task command
        $response = $this->registerAndRun('make-task', [$taskName]);
        $fullPath = $this->directiveTempDir . '/app/Tasks/SendWelcomeEmailTask.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
    }

    public function test_make_task_with_subdirectory(): void
    {
        // Arrange: Prepare task name with subdirectory
        $taskName = 'user/send-welcome-email';

        // Act: Run make-task command
        $response = $this->registerAndRun('make-task', [$taskName]);
        $fullPath = $this->directiveTempDir . '/app/Tasks/User/SendWelcomeEmailTask.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
    }

    public function test_make_task_with_alias_make_job(): void
    {
        // Arrange: Register directive
        $this->registerDirective($this->getMakeTaskDirective());

        // Act: Run using alias
        $response = $this->runDirective('make-job', ['process-order']);
        $fullPath = $this->directiveTempDir . '/app/Tasks/ProcessOrderTask.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
    }

    // ==================== Repository Tests ====================

    public function test_make_repository(): void
    {
        // Arrange: Prepare repository name
        $repositoryName = 'user';

        // Act: Run make-repository command
        $response = $this->registerAndRun('make-repository', [$repositoryName]);
        $fullPath = $this->directiveTempDir . '/app/Repositories/UserRepository.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
    }

    public function test_make_repository_with_subdirectory(): void
    {
        // Arrange: Prepare repository name with subdirectory
        $repositoryName = 'admin/user';

        // Act: Run make-repository command
        $response = $this->registerAndRun('make-repository', [$repositoryName]);
        $fullPath = $this->directiveTempDir . '/app/Repositories/Admin/UserRepository.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
    }

    public function test_make_repository_with_alias_make_repo(): void
    {
        // Arrange: Register directive
        $this->registerDirective($this->getMakeRepositoryDirective());

        // Act: Run using alias
        $response = $this->runDirective('make-repo', ['product']);
        $fullPath = $this->directiveTempDir . '/app/Repositories/ProductRepository.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
    }

    // ==================== Record Tests ====================

    public function test_make_record(): void
    {
        // Arrange: Prepare record name
        $recordName = 'user-data';

        // Act: Run make-record command
        $response = $this->registerAndRun('make-record', [$recordName]);
        $fullPath = $this->directiveTempDir . '/app/Records/UserDataRecord.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
    }

    public function test_make_record_with_subdirectory(): void
    {
        // Arrange: Prepare record name with subdirectory
        $recordName = 'api/user-data';

        // Act: Run make-record command
        $response = $this->registerAndRun('make-record', [$recordName]);
        $fullPath = $this->directiveTempDir . '/app/Records/Api/UserDataRecord.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
    }

    public function test_make_record_with_alias_make_dto(): void
    {
        // Arrange: Register directive
        $this->registerDirective($this->getMakeRecordDirective());

        // Act: Run using alias
        $response = $this->runDirective('make-dto', ['product-data']);
        $fullPath = $this->directiveTempDir . '/app/Records/ProductDataRecord.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
    }

    // ==================== Typed Collection Tests ====================

    public function test_make_typed_collection(): void
    {
        // Arrange: Prepare collection name and item type
        $collectionName = 'user-collection';
        $itemType = 'UserRecord';

        // Act: Run make-typed-collection command
        $response = $this->registerAndRun('make-typed-collection', [
            $collectionName,
            "--item-type={$itemType}",
        ]);
        $fullPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('UserRecord', file_get_contents($fullPath));
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
    }

    public function test_make_typed_collection_with_subdirectory(): void
    {
        // Arrange: Prepare collection name with subdirectory
        $collectionName = 'admin/user-collection';
        $itemType = 'UserRecord';

        // Act: Run make-typed-collection command
        $response = $this->registerAndRun('make-typed-collection', [
            $collectionName,
            "--item-type={$itemType}",
        ]);
        $fullPath = $this->directiveTempDir . '/app/Collections/Admin/UserCollection.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
    }

    public function test_make_typed_collection_with_alias_make_collection(): void
    {
        // Arrange: Register directive
        $this->registerDirective($this->getMakeTypedCollectionDirective());

        // Act: Run using alias
        $response = $this->runDirective('make-collection', [
            'product-collection',
            '--item-type=ProductRecord',
        ]);
        $fullPath = $this->directiveTempDir . '/app/Collections/ProductCollection.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
    }

    public function test_make_typed_collection_requires_item_type(): void
    {
        // Arrange: Prepare collection name without item type
        $collectionName = 'user-collection';

        // Act: Run make-typed-collection command without item type
        $response = $this->registerAndRun('make-typed-collection', [$collectionName]);
        $fullPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';

        // Assert: Verify error and no file creation
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Item type is required', $response->output);
        $this->assertFileDoesNotExist($fullPath);
    }

    // ==================== Error Handling Tests ====================

    public function test_prevent_duplicate_file_creation(): void
    {
        // Arrange: First creation
        $directiveName = 'user-list';

        // Act: First run (should succeed)
        $firstResponse = $this->registerAndRun('make-directive', [$directiveName]);

        // Assert: Verify first creation succeeded
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($firstResponse->output));

        // Act: Second run with same name (should fail)
        $secondResponse = $this->registerAndRun('make-directive', [$directiveName]);

        // Assert: Verify failure message
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exitCode);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }

    public function test_invalid_directive_name(): void
    {
        // Arrange: Prepare invalid directive name with @ symbol
        $invalidName = 'user@list';

        // Act: Run make-directive with invalid name
        $response = $this->registerAndRun('make-directive', [$invalidName]);

        // Assert: Verify error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Invalid directive name', $response->output);
    }

    public function test_missing_name_parameter(): void
    {
        // Act: Run make-directive without name
        $response = $this->registerAndRun('make-directive');

        // Assert: Verify error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    // ==================== System Commands Tests ====================

    public function test_help_command_shows_system_commands(): void
    {
        // Arrange: Register all directives
        $this->registerDirective($this->getMakeDirective());
        $this->registerDirective($this->getMakeActionDirective());
        $this->registerDirective($this->getMakeTaskDirective());
        $this->registerDirective($this->getMakeRepositoryDirective());
        $this->registerDirective($this->getMakeRecordDirective());
        $this->registerDirective($this->getMakeTypedCollectionDirective());

        // Act: Run help command
        $response = $this->runDirective('--help');

        // Assert: Verify help contains system commands
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('--list', $response->output);
        $this->assertStringContainsString('--help', $response->output);
        $this->assertStringContainsString('--version', $response->output);
    }

    public function test_list_command_shows_all_directives(): void
    {
        // Arrange: Register all directives
        $this->registerDirective($this->getMakeDirective());
        $this->registerDirective($this->getMakeActionDirective());
        $this->registerDirective($this->getMakeTaskDirective());
        $this->registerDirective($this->getMakeRepositoryDirective());
        $this->registerDirective($this->getMakeRecordDirective());
        $this->registerDirective($this->getMakeTypedCollectionDirective());

        // Act: Run list command
        $response = $this->runDirective('--list');

        // Assert: Verify list contains all directives
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertStringContainsString('make-directive', $response->output);
        $this->assertStringContainsString('create-directive', $response->output);
        $this->assertStringContainsString('make-cmd', $response->output);

        $this->assertStringContainsString('make-action', $response->output);
        $this->assertStringContainsString('create-action', $response->output);
        $this->assertStringContainsString('make-act', $response->output);

        $this->assertStringContainsString('make-task', $response->output);
        $this->assertStringContainsString('create-task', $response->output);
        $this->assertStringContainsString('make-job', $response->output);

        $this->assertStringContainsString('make-repository', $response->output);
        $this->assertStringContainsString('create-repository', $response->output);
        $this->assertStringContainsString('make-repo', $response->output);

        $this->assertStringContainsString('make-record', $response->output);
        $this->assertStringContainsString('create-record', $response->output);
        $this->assertStringContainsString('make-dto', $response->output);

        $this->assertStringContainsString('make-typed-collection', $response->output);
        $this->assertStringContainsString('create-collection', $response->output);
        $this->assertStringContainsString('make-collection', $response->output);
    }

    public function test_version_command_shows_version(): void
    {
        // Act: Run version command
        $response = $this->runDirective('--version');

        // Assert: Verify version output
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('Laravel Directive', $response->output);
        $this->assertStringContainsString('Version:', $response->output);
    }
}
