<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Generators;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Services\DirectiveNamingService;
use AndyDefer\Directive\Services\SignatureValidationService;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Directives\MakeDirective;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective;
use AndyDefer\DirectiveForge\Directives\MakeTaskDirective;
use AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class FileCreationTest extends UnitTestCase
{
    use InteractsWithDirectives;

    private SignatureValidationService $signatureValidator;
    private DirectiveNamingService $namingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDirectiveTesting();

        $this->signatureValidator = new SignatureValidationService();
        $this->namingService = new DirectiveNamingService();
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

    // ==================== Directive Creation Tests ====================

    public function test_creates_directive_file(): void
    {
        // Arrange: Prepare directive name
        $directiveName = 'user-list';

        // Act: Run make-directive command
        $response = $this->registerAndRun('make-directive', [$directiveName]);
        $fullPath = $this->directiveTempDir . '/app/Directives/UserListDirective.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class UserListDirective', $content);
        $this->assertStringContainsString("return 'user-list'", $content);
        $this->assertStringContainsString('namespace App\\Directives', $content);
    }

    public function test_creates_directive_with_subdirectory(): void
    {
        // Arrange: Prepare directive name with subdirectory
        $directiveName = 'user/domain/hello-directive';

        // Act: Run make-directive command
        $response = $this->registerAndRun('make-directive', [$directiveName]);
        $fullPath = $this->directiveTempDir . '/app/Directives/User/Domain/HelloDirective.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class HelloDirective', $content);
        $this->assertStringContainsString("return 'hello'", $content);
        $this->assertStringContainsString('namespace App\\Directives\\User\\Domain', $content);
    }

    public function test_creates_directive_with_already_has_suffix(): void
    {
        // Arrange: Prepare directive name with suffix
        $directiveName = 'UserListDirective';

        // Act: Run make-directive command
        $response = $this->registerAndRun('make-directive', [$directiveName]);
        $fullPath = $this->directiveTempDir . '/app/Directives/UserListDirective.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and no duplicate suffix
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class UserListDirective', $content);
        $this->assertStringNotContainsString('UserListDirectiveDirective', $content);
    }

    // ==================== Action Creation Tests ====================

    public function test_creates_action_file(): void
    {
        // Arrange: Prepare action name
        $actionName = 'user/show';

        // Act: Run make-action command
        $response = $this->registerAndRun('make-action', [$actionName]);
        $fullPath = $this->directiveTempDir . '/app/Actions/User/ShowAction.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class ShowAction', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
    }

    public function test_creates_action_with_subdirectory(): void
    {
        // Arrange: Prepare action name with subdirectory
        $actionName = 'api/v1/users/show';

        // Act: Run make-action command
        $response = $this->registerAndRun('make-action', [$actionName]);
        $fullPath = $this->directiveTempDir . '/app/Actions/Api/V1/Users/ShowAction.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class ShowAction', $content);
        $this->assertStringContainsString('namespace App\\Actions\\Api\\V1\\Users', $content);
    }

    public function test_creates_action_with_already_has_suffix(): void
    {
        // Arrange: Prepare action name with suffix
        $actionName = 'ShowUserAction';

        // Act: Run make-action command
        $response = $this->registerAndRun('make-action', [$actionName]);
        $fullPath = $this->directiveTempDir . '/app/Actions/ShowUserAction.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and no duplicate suffix
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class ShowUserAction', $content);
        $this->assertStringNotContainsString('ShowUserActionAction', $content);
    }

    // ==================== Task Creation Tests ====================

    public function test_creates_task(): void
    {
        // Arrange: Prepare task name
        $taskName = 'send-welcome-email';

        // Act: Run make-task command
        $response = $this->registerAndRun('make-task', [$taskName]);
        $fullPath = $this->directiveTempDir . '/app/Tasks/SendWelcomeEmailTask.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
        $this->assertStringContainsString('extends AbstractTask', $content);
        $this->assertStringContainsString('protected function process(): void', $content);
    }

    public function test_creates_task_with_subdirectory(): void
    {
        // Arrange: Prepare task name with subdirectory
        $taskName = 'user/send-welcome-email';

        // Act: Run make-task command
        $response = $this->registerAndRun('make-task', [$taskName]);
        $fullPath = $this->directiveTempDir . '/app/Tasks/User/SendWelcomeEmailTask.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('namespace App\\Tasks\\User', $content);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
    }

    public function test_creates_task_with_already_has_suffix(): void
    {
        // Arrange: Prepare task name with suffix
        $taskName = 'SendWelcomeEmailTask';

        // Act: Run make-task command
        $response = $this->registerAndRun('make-task', [$taskName]);
        $fullPath = $this->directiveTempDir . '/app/Tasks/SendWelcomeEmailTask.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and no duplicate suffix
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringNotContainsString('SendWelcomeEmailTaskTask', $content);
    }

    // ==================== Repository Creation Tests ====================

    public function test_creates_repository(): void
    {
        // Arrange: Prepare repository name
        $repositoryName = 'user';

        // Act: Run make-repository command
        $response = $this->registerAndRun('make-repository', [$repositoryName]);
        $fullPath = $this->directiveTempDir . '/app/Repositories/UserRepository.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class UserRepository', $content);
        $this->assertStringContainsString('extends AbstractRepository', $content);
    }

    public function test_creates_repository_with_subdirectory(): void
    {
        // Arrange: Prepare repository name with subdirectory
        $repositoryName = 'admin/user';

        // Act: Run make-repository command
        $response = $this->registerAndRun('make-repository', [$repositoryName]);
        $fullPath = $this->directiveTempDir . '/app/Repositories/Admin/UserRepository.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('namespace App\\Repositories\\Admin', $content);
        $this->assertStringContainsString('class UserRepository', $content);
    }

    public function test_creates_repository_with_already_has_suffix(): void
    {
        // Arrange: Prepare repository name with suffix
        $repositoryName = 'UserRepository';

        // Act: Run make-repository command
        $response = $this->registerAndRun('make-repository', [$repositoryName]);
        $fullPath = $this->directiveTempDir . '/app/Repositories/UserRepository.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and no duplicate suffix
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringNotContainsString('UserRepositoryRepository', $content);
    }

    // ==================== Record Creation Tests ====================

    public function test_creates_record(): void
    {
        // Arrange: Prepare record name
        $recordName = 'user-data';

        // Act: Run make-record command
        $response = $this->registerAndRun('make-record', [$recordName]);
        $fullPath = $this->directiveTempDir . '/app/Records/UserDataRecord.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class UserDataRecord', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_creates_record_with_subdirectory(): void
    {
        // Arrange: Prepare record name with subdirectory
        $recordName = 'api/user-data';

        // Act: Run make-record command
        $response = $this->registerAndRun('make-record', [$recordName]);
        $fullPath = $this->directiveTempDir . '/app/Records/Api/UserDataRecord.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('namespace App\\Records\\Api', $content);
        $this->assertStringContainsString('class UserDataRecord', $content);
    }

    public function test_creates_record_with_already_has_suffix(): void
    {
        // Arrange: Prepare record name with suffix
        $recordName = 'UserDataRecord';

        // Act: Run make-record command
        $response = $this->registerAndRun('make-record', [$recordName]);
        $fullPath = $this->directiveTempDir . '/app/Records/UserDataRecord.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and no duplicate suffix
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringNotContainsString('UserDataRecordRecord', $content);
    }

    // ==================== Typed Collection Creation Tests ====================

    public function test_creates_typed_collection_with_string_type(): void
    {
        // Arrange: Prepare collection name and item type
        $collectionName = 'user-collection';
        $itemType = 'string';

        // Act: Run make-typed-collection command
        $response = $this->registerAndRun('make-typed-collection', [
            $collectionName,
            "--item-type={$itemType}",
        ]);
        $fullPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class UserCollection', $content);
        // ✅ Correction : vérifier AbstractTypedCollection au lieu de TypedCollection
        $this->assertStringContainsString('extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('@extends AbstractTypedCollection<string>', $content);
        $this->assertStringContainsString('parent::__construct(string::class)', $content);
    }

    public function test_creates_typed_collection_with_record_type(): void
    {
        // Arrange: Prepare collection name and record type
        $collectionName = 'user-collection';
        $itemType = 'UserRecord';

        // Act: Run make-typed-collection command
        $response = $this->registerAndRun('make-typed-collection', [
            $collectionName,
            "--item-type={$itemType}",
        ]);
        $fullPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        // ✅ Correction : vérifier AbstractTypedCollection au lieu de TypedCollection
        $this->assertStringContainsString('@extends AbstractTypedCollection<UserRecord>', $content);
        $this->assertStringContainsString('parent::__construct(UserRecord::class)', $content);
    }

    public function test_creates_typed_collection_with_subdirectory(): void
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
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('namespace App\\Collections\\Admin', $content);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringContainsString('extends AbstractTypedCollection', $content);
    }

    public function test_creates_typed_collection_with_already_has_suffix(): void
    {
        // Arrange: Prepare collection name with suffix
        $collectionName = 'UserCollection';
        $itemType = 'UserRecord';

        // Act: Run make-typed-collection command
        $response = $this->registerAndRun('make-typed-collection', [
            $collectionName,
            "--item-type={$itemType}",
        ]);
        $fullPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and no duplicate suffix
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringNotContainsString('UserCollectionCollection', $content);
    }

    public function test_typed_collection_requires_item_type(): void
    {
        // Arrange: Prepare collection name without item type
        $collectionName = 'string-collection';

        // Act: Run make-typed-collection command without item type
        $response = $this->registerAndRun('make-typed-collection', [$collectionName]);
        $fullPath = $this->directiveTempDir . '/app/Collections/StringCollection.php';

        // Assert: Verify error and no file creation
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Item type is required', $response->output);
        $this->assertFileDoesNotExist($fullPath);
    }

    // ==================== Error Handling Tests ====================

    public function test_prevents_duplicate_file_creation(): void
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

    public function test_validates_directive_name_format(): void
    {
        // Arrange: Prepare invalid directive name with @ symbol
        $invalidName = 'user@list';

        // Act: Run make-directive with invalid name
        $response = $this->registerAndRun('make-directive', [$invalidName]);

        // Assert: Verify error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Invalid directive name', $response->output);
    }

    public function test_requires_name_parameter(): void
    {
        // Act: Run make-directive without name
        $response = $this->registerAndRun('make-directive');

        // Assert: Verify error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    // ==================== Alias Tests ====================

    public function test_uses_alias_create_directive(): void
    {
        // Arrange: Register directive
        $this->registerDirective($this->getMakeDirective());

        // Act: Run using alias
        $response = $this->runDirective('create-directive', ['cache-clear']);
        $fullPath = $this->directiveTempDir . '/app/Directives/CacheClearDirective.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_act(): void
    {
        // Arrange: Register directive
        $this->registerDirective($this->getMakeActionDirective());

        // Act: Run using alias
        $response = $this->runDirective('make-act', ['user/profile']);
        $fullPath = $this->directiveTempDir . '/app/Actions/User/ProfileAction.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_job(): void
    {
        // Arrange: Register directive
        $this->registerDirective($this->getMakeTaskDirective());

        // Act: Run using alias
        $response = $this->runDirective('make-job', ['process-order']);
        $fullPath = $this->directiveTempDir . '/app/Tasks/ProcessOrderTask.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_repo(): void
    {
        // Arrange: Register directive
        $this->registerDirective($this->getMakeRepositoryDirective());

        // Act: Run using alias
        $response = $this->runDirective('make-repo', ['product']);
        $fullPath = $this->directiveTempDir . '/app/Repositories/ProductRepository.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_dto(): void
    {
        // Arrange: Register directive
        $this->registerDirective($this->getMakeRecordDirective());

        // Act: Run using alias
        $response = $this->runDirective('make-dto', ['product-data']);
        $fullPath = $this->directiveTempDir . '/app/Records/ProductDataRecord.php';

        // Assert: Verify success and file creation
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_collection(): void
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
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
    }

    // ==================== Nested Path Tests ====================

    public function test_creates_deeply_nested_directive(): void
    {
        // Arrange: Prepare deeply nested directive name
        $directiveName = 'api/v1/admin/user/list';

        // Act: Run make-directive command
        $response = $this->registerAndRun('make-directive', [$directiveName]);
        $fullPath = $this->directiveTempDir . '/app/Directives/Api/V1/Admin/User/ListDirective.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('namespace App\\Directives\\Api\\V1\\Admin\\User', $content);
        $this->assertStringContainsString('class ListDirective', $content);
    }

    public function test_creates_deeply_nested_action(): void
    {
        // Arrange: Prepare deeply nested action name
        $actionName = 'api/v2/shop/cart/add-item';

        // Act: Run make-action command
        $response = $this->registerAndRun('make-action', [$actionName]);
        $fullPath = $this->directiveTempDir . '/app/Actions/Api/V2/Shop/Cart/AddItemAction.php';
        $content = file_get_contents($fullPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('namespace App\\Actions\\Api\\V2\\Shop\\Cart', $content);
        $this->assertStringContainsString('class AddItemAction', $content);
    }

    // ==================== Multiple File Creation Tests ====================

    public function test_creates_multiple_files_in_same_test(): void
    {
        // Arrange: Register all directives
        $this->registerDirective($this->getMakeDirective());
        $this->registerDirective($this->getMakeActionDirective());
        $this->registerDirective($this->getMakeTaskDirective());

        // Act & Assert: Create directive
        $response1 = $this->runDirective('make-directive', ['test-one']);
        $this->assertSame(ExitCode::SUCCESS, $response1->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response1->output));
        $this->assertFileExists($this->directiveTempDir . '/app/Directives/TestOneDirective.php');

        // Act & Assert: Create action
        $response2 = $this->runDirective('make-action', ['test-two']);
        $this->assertSame(ExitCode::SUCCESS, $response2->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response2->output));
        $this->assertFileExists($this->directiveTempDir . '/app/Actions/TestTwoAction.php');

        // Act & Assert: Create task
        $response3 = $this->runDirective('make-task', ['test-three']);
        $this->assertSame(ExitCode::SUCCESS, $response3->exitCode);
        $this->assertStringContainsString('task created successfully!', strtolower($response3->output));
        $this->assertFileExists($this->directiveTempDir . '/app/Tasks/TestThreeTask.php');
    }
}
