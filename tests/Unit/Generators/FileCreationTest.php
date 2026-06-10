<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Generators;

use AndyDefer\Directive\Configs\EnvDirectiveNamingConfig;
use AndyDefer\Directive\Configs\EnvSignatureValidationConfig;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Services\DirectiveNamingService;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\Directive\Services\SignatureValidationService;
use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Directives\MakeDirective;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective;
use AndyDefer\DirectiveForge\Directives\MakeTaskDirective;
use AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class FileCreationTest extends IntegrationTestCase
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

    private function registerAndRun(string $signature, array $arguments = []): DirectiveResponseRecord
    {
        $directiveClass = match ($signature) {
            'make-directive', 'create-directive', 'make-cmd' => MakeDirective::class,
            'make-action', 'create-action', 'make-act' => MakeActionDirective::class,
            'make-task', 'create-task', 'make-job' => MakeTaskDirective::class,
            'make-repository', 'create-repository', 'make-repo' => MakeRepositoryDirective::class,
            'make-record', 'create-record', 'make-dto' => MakeRecordDirective::class,
            'make-typed-collection', 'create-collection', 'make-collection' => MakeTypedCollectionDirective::class,
            default => throw new \InvalidArgumentException("Unknown directive: {$signature}"),
        };

        return $this->service->run($directiveClass, $arguments);
    }

    // ==================== Directive Creation Tests ====================

    public function test_creates_directive_file(): void
    {
        $directiveName = 'user-list';

        $response = $this->registerAndRun('make-directive', [$directiveName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Directives/UserListDirective.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class UserListDirective', $content);
        $this->assertStringContainsString("return 'user-list'", $content);
        $this->assertStringContainsString('namespace App\\Directives', $content);
    }

    public function test_creates_directive_with_subdirectory(): void
    {
        $directiveName = 'user/domain/hello-directive';

        $response = $this->registerAndRun('make-directive', [$directiveName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Directives/User/Domain/HelloDirective.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class HelloDirective', $content);
        $this->assertStringContainsString("return 'hello'", $content);
        $this->assertStringContainsString('namespace App\\Directives\\User\\Domain', $content);
    }

    public function test_creates_directive_with_already_has_suffix(): void
    {
        $directiveName = 'UserListDirective';

        $response = $this->registerAndRun('make-directive', [$directiveName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Directives/UserListDirective.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class UserListDirective', $content);
        $this->assertStringNotContainsString('UserListDirectiveDirective', $content);
    }

    // ==================== Action Creation Tests ====================

    public function test_creates_action_file(): void
    {
        $actionName = 'user/show';

        $response = $this->registerAndRun('make-action', [$actionName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Actions/User/ShowAction.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class ShowAction', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
    }

    public function test_creates_action_with_subdirectory(): void
    {
        $actionName = 'api/v1/users/show';

        $response = $this->registerAndRun('make-action', [$actionName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Actions/Api/V1/Users/ShowAction.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class ShowAction', $content);
        $this->assertStringContainsString('namespace App\\Actions\\Api\\V1\\Users', $content);
    }

    public function test_creates_action_with_already_has_suffix(): void
    {
        $actionName = 'ShowUserAction';

        $response = $this->registerAndRun('make-action', [$actionName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Actions/ShowUserAction.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class ShowUserAction', $content);
        $this->assertStringNotContainsString('ShowUserActionAction', $content);
    }

    // ==================== Task Creation Tests ====================

    public function test_creates_task(): void
    {
        $taskName = 'send-welcome-email';

        $response = $this->registerAndRun('make-task', [$taskName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Tasks/SendWelcomeEmailTask.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
        $this->assertStringContainsString('extends AbstractTask', $content);
        $this->assertStringContainsString('protected function process(): void', $content);
    }

    public function test_creates_task_with_subdirectory(): void
    {
        $taskName = 'user/send-welcome-email';

        $response = $this->registerAndRun('make-task', [$taskName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Tasks/User/SendWelcomeEmailTask.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('namespace App\\Tasks\\User', $content);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
    }

    public function test_creates_task_with_already_has_suffix(): void
    {
        $taskName = 'SendWelcomeEmailTask';

        $response = $this->registerAndRun('make-task', [$taskName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Tasks/SendWelcomeEmailTask.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringNotContainsString('SendWelcomeEmailTaskTask', $content);
    }

    // ==================== Repository Creation Tests ====================

    public function test_creates_repository(): void
    {
        $repositoryName = 'user';

        $response = $this->registerAndRun('make-repository', [$repositoryName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Repositories/UserRepository.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class UserRepository', $content);
        $this->assertStringContainsString('extends AbstractRepository', $content);
    }

    public function test_creates_repository_with_subdirectory(): void
    {
        $repositoryName = 'admin/user';

        $response = $this->registerAndRun('make-repository', [$repositoryName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Repositories/Admin/UserRepository.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('namespace App\\Repositories\\Admin', $content);
        $this->assertStringContainsString('class UserRepository', $content);
    }

    public function test_creates_repository_with_already_has_suffix(): void
    {
        $repositoryName = 'UserRepository';

        $response = $this->registerAndRun('make-repository', [$repositoryName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Repositories/UserRepository.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringNotContainsString('UserRepositoryRepository', $content);
    }

    // ==================== Record Creation Tests ====================

    public function test_creates_record(): void
    {
        $recordName = 'user-data';

        $response = $this->registerAndRun('make-record', [$recordName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Records/UserDataRecord.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class UserDataRecord', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_creates_record_with_subdirectory(): void
    {
        $recordName = 'api/user-data';

        $response = $this->registerAndRun('make-record', [$recordName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Records/Api/UserDataRecord.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('namespace App\\Records\\Api', $content);
        $this->assertStringContainsString('class UserDataRecord', $content);
    }

    public function test_creates_record_with_already_has_suffix(): void
    {
        $recordName = 'UserDataRecord';

        $response = $this->registerAndRun('make-record', [$recordName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Records/UserDataRecord.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringNotContainsString('UserDataRecordRecord', $content);
    }

    // ==================== Typed Collection Creation Tests ====================

    public function test_creates_typed_collection_with_string_type(): void
    {
        $collectionName = 'user-collection';
        $itemType = 'string';

        $response = $this->registerAndRun('make-typed-collection', [
            $collectionName,
            "--item-type={$itemType}",
        ]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Collections/UserCollection.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringContainsString('extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('@extends AbstractTypedCollection<string>', $content);
        $this->assertStringContainsString('parent::__construct(string::class)', $content);
    }

    public function test_creates_typed_collection_with_record_type(): void
    {
        $collectionName = 'user-collection';
        $itemType = 'UserRecord';

        $response = $this->registerAndRun('make-typed-collection', [
            $collectionName,
            "--item-type={$itemType}",
        ]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Collections/UserCollection.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('@extends AbstractTypedCollection<UserRecord>', $content);
        $this->assertStringContainsString('parent::__construct(UserRecord::class)', $content);
    }

    public function test_creates_typed_collection_with_subdirectory(): void
    {
        $collectionName = 'admin/user-collection';
        $itemType = 'UserRecord';

        $response = $this->registerAndRun('make-typed-collection', [
            $collectionName,
            "--item-type={$itemType}",
        ]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Collections/Admin/UserCollection.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('namespace App\\Collections\\Admin', $content);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringContainsString('extends AbstractTypedCollection', $content);
    }

    public function test_creates_typed_collection_with_already_has_suffix(): void
    {
        $collectionName = 'UserCollection';
        $itemType = 'UserRecord';

        $response = $this->registerAndRun('make-typed-collection', [
            $collectionName,
            "--item-type={$itemType}",
        ]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Collections/UserCollection.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringNotContainsString('UserCollectionCollection', $content);
    }

    public function test_typed_collection_requires_item_type(): void
    {
        $collectionName = 'string-collection';

        $response = $this->registerAndRun('make-typed-collection', [$collectionName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Collections/StringCollection.php';

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Item type is required', $response->output);
        $this->assertFileDoesNotExist($fullPath);
    }

    // ==================== Error Handling Tests ====================

    public function test_prevents_duplicate_file_creation(): void
    {
        $directiveName = 'user-list';

        $firstResponse = $this->registerAndRun('make-directive', [$directiveName]);
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($firstResponse->output));

        $secondResponse = $this->registerAndRun('make-directive', [$directiveName]);
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exitCode);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }

    public function test_validates_directive_name_format(): void
    {
        $invalidName = 'user@list';

        $response = $this->registerAndRun('make-directive', [$invalidName]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Invalid directive name', $response->output);
    }

    public function test_requires_name_parameter(): void
    {
        $response = $this->registerAndRun('make-directive');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    // ==================== Alias Tests ====================

    public function test_uses_alias_create_directive(): void
    {
        $response = $this->service->run(MakeDirective::class, ['cache-clear']);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Directives/CacheClearDirective.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_act(): void
    {
        $response = $this->service->run(MakeActionDirective::class, ['user/profile']);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Actions/User/ProfileAction.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_job(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, ['process-order']);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Tasks/ProcessOrderTask.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_repo(): void
    {
        $response = $this->service->run(MakeRepositoryDirective::class, ['product']);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Repositories/ProductRepository.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_dto(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, ['product-data']);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Records/ProductDataRecord.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_collection(): void
    {
        $response = $this->service->run(MakeTypedCollectionDirective::class, [
            'product-collection',
            '--item-type=ProductRecord',
        ]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Collections/ProductCollection.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
    }

    // ==================== Nested Path Tests ====================

    public function test_creates_deeply_nested_directive(): void
    {
        $directiveName = 'api/v1/admin/user/list';

        $response = $this->registerAndRun('make-directive', [$directiveName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Directives/Api/V1/Admin/User/ListDirective.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('namespace App\\Directives\\Api\\V1\\Admin\\User', $content);
        $this->assertStringContainsString('class ListDirective', $content);
    }

    public function test_creates_deeply_nested_action(): void
    {
        $actionName = 'api/v2/shop/cart/add-item';

        $response = $this->registerAndRun('make-action', [$actionName]);
        $tempDir = $this->service->getContext()->getTempDir();
        $fullPath = $tempDir . '/app/Actions/Api/V2/Shop/Cart/AddItemAction.php';
        $content = file_get_contents($fullPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('namespace App\\Actions\\Api\\V2\\Shop\\Cart', $content);
        $this->assertStringContainsString('class AddItemAction', $content);
    }

    // ==================== Multiple File Creation Tests ====================

    public function test_creates_multiple_files_in_same_test(): void
    {
        $response1 = $this->service->run(MakeDirective::class, ['test-one']);
        $this->assertSame(ExitCode::SUCCESS, $response1->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response1->output));
        $this->assertFileExists($this->service->getContext()->getTempDir() . '/app/Directives/TestOneDirective.php');

        $response2 = $this->service->run(MakeActionDirective::class, ['test-two']);
        $this->assertSame(ExitCode::SUCCESS, $response2->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response2->output));
        $this->assertFileExists($this->service->getContext()->getTempDir() . '/app/Actions/TestTwoAction.php');

        $response3 = $this->service->run(MakeTaskDirective::class, ['test-three']);
        $this->assertSame(ExitCode::SUCCESS, $response3->exitCode);
        $this->assertStringContainsString('task created successfully!', strtolower($response3->output));
        $this->assertFileExists($this->service->getContext()->getTempDir() . '/app/Tasks/TestThreeTask.php');
    }
}
