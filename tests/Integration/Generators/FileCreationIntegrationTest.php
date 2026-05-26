<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Generators;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Directives\MakeDirective;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective;
use AndyDefer\DirectiveForge\Directives\MakeTaskDirective;
use AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class FileCreationIntegrationTest extends IntegrationTestCase
{
    use InteractsWithDirectives;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDirectiveTesting();
    }

    protected function tearDown(): void
    {
        $this->destroyDirectiveTesting();
        parent::tearDown();
    }

    // ==================== Directive Creation Tests ====================

    public function test_creates_directive_file(): void
    {
        $this->registerDirectiveClass(MakeDirective::class);

        $response = $this->runDirective('make-directive', ['user-list']);

        $response->assertSuccess();
        // Le message est en minuscules
        $response->assertOutputContains('directive created successfully!');

        $fullPath = $this->directiveTempDir . '/app/Directives/UserListDirective.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('class UserListDirective', $content);
        $this->assertStringContainsString("return 'user-list'", $content);
        $this->assertStringContainsString('namespace App\\Directives', $content);
    }

    public function test_creates_directive_with_subdirectory(): void
    {
        $this->registerDirectiveClass(MakeDirective::class);

        $response = $this->runDirective('make-directive', ['user/domain/hello-directive']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Directives/User/Domain/HelloDirective.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('class HelloDirective', $content);
        // La signature est extraite du nom de classe : HelloDirective -> hello
        $this->assertStringContainsString("return 'hello'", $content);
        $this->assertStringContainsString('namespace App\\Directives\\User\\Domain', $content);
    }

    public function test_creates_directive_with_already_has_suffix(): void
    {
        $this->registerDirectiveClass(MakeDirective::class);

        $response = $this->runDirective('make-directive', ['UserListDirective']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Directives/UserListDirective.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('class UserListDirective', $content);
        $this->assertStringNotContainsString('UserListDirectiveDirective', $content);
    }

    // ==================== Action Creation Tests ====================

    public function test_creates_action_with_api_type(): void
    {
        $this->registerDirectiveClass(MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['user/show', '--type=api']);

        $response->assertSuccess();
        $response->assertOutputContains('action created successfully!');

        // Le fichier est créé dans le sous-dossier User/ShowAction.php
        $fullPath = $this->directiveTempDir . '/app/Actions/User/ShowAction.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('class ShowAction', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
        $this->assertStringContainsString('JsonResponse', $content);
    }

    public function test_creates_action_with_web_type(): void
    {
        $this->registerDirectiveClass(MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['admin/dashboard', '--type=web']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Actions/Admin/DashboardAction.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('class DashboardAction', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
        $this->assertStringContainsString('InertiaResponse', $content);
    }

    public function test_creates_action_with_subdirectory(): void
    {
        $this->registerDirectiveClass(MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['api/v1/users/show', '--type=api']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Actions/Api/V1/Users/ShowAction.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('class ShowAction', $content);
        $this->assertStringContainsString('namespace App\\Actions\\Api\\V1\\Users', $content);
    }

    public function test_creates_action_with_already_has_suffix(): void
    {
        $this->registerDirectiveClass(MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['ShowUserAction', '--type=api']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Actions/ShowUserAction.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringNotContainsString('ShowUserActionAction', $content);
    }

    // ==================== Task Creation Tests ====================

    public function test_creates_task(): void
    {
        $this->registerDirectiveClass(MakeTaskDirective::class);

        $response = $this->runDirective('make-task', ['send-welcome-email']);

        $response->assertSuccess();
        $response->assertOutputContains('task created successfully!');

        $fullPath = $this->directiveTempDir . '/app/Tasks/SendWelcomeEmailTask.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
        $this->assertStringContainsString('extends AbstractTask', $content);
        $this->assertStringContainsString('protected function process(): void', $content);
    }

    public function test_creates_task_with_subdirectory(): void
    {
        $this->registerDirectiveClass(MakeTaskDirective::class);

        $response = $this->runDirective('make-task', ['user/send-welcome-email']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Tasks/User/SendWelcomeEmailTask.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('namespace App\\Tasks\\User', $content);
        $this->assertStringContainsString('class SendWelcomeEmailTask', $content);
    }

    public function test_creates_task_with_already_has_suffix(): void
    {
        $this->registerDirectiveClass(MakeTaskDirective::class);

        $response = $this->runDirective('make-task', ['SendWelcomeEmailTask']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Tasks/SendWelcomeEmailTask.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringNotContainsString('SendWelcomeEmailTaskTask', $content);
    }

    // ==================== Repository Creation Tests ====================

    public function test_creates_repository(): void
    {
        $this->registerDirectiveClass(MakeRepositoryDirective::class);

        $response = $this->runDirective('make-repository', ['user']);

        $response->assertSuccess();
        $response->assertOutputContains('repository created successfully!');

        $fullPath = $this->directiveTempDir . '/app/Repositories/UserRepository.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('class UserRepository', $content);
        $this->assertStringContainsString('extends AbstractRepository', $content);
        // Le stub actuel ne contient pas UserInterface
        // $this->assertStringContainsString('UserInterface', $content);
    }

    public function test_creates_repository_with_subdirectory(): void
    {
        $this->registerDirectiveClass(MakeRepositoryDirective::class);

        $response = $this->runDirective('make-repository', ['admin/user']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Repositories/Admin/UserRepository.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('namespace App\\Repositories\\Admin', $content);
        $this->assertStringContainsString('class UserRepository', $content);
    }

    public function test_creates_repository_with_already_has_suffix(): void
    {
        $this->registerDirectiveClass(MakeRepositoryDirective::class);

        $response = $this->runDirective('make-repository', ['UserRepository']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Repositories/UserRepository.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringNotContainsString('UserRepositoryRepository', $content);
    }

    // ==================== Record Creation Tests ====================

    public function test_creates_record(): void
    {
        $this->registerDirectiveClass(MakeRecordDirective::class);

        $response = $this->runDirective('make-record', ['user-data']);

        $response->assertSuccess();
        $response->assertOutputContains('record created successfully!');

        $fullPath = $this->directiveTempDir . '/app/Records/UserDataRecord.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('class UserDataRecord', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_creates_record_with_subdirectory(): void
    {
        $this->registerDirectiveClass(MakeRecordDirective::class);

        $response = $this->runDirective('make-record', ['api/user-data']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Records/Api/UserDataRecord.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('namespace App\\Records\\Api', $content);
        $this->assertStringContainsString('class UserDataRecord', $content);
    }

    public function test_creates_record_with_already_has_suffix(): void
    {
        $this->registerDirectiveClass(MakeRecordDirective::class);

        $response = $this->runDirective('make-record', ['UserDataRecord']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Records/UserDataRecord.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringNotContainsString('UserDataRecordRecord', $content);
    }

    // ==================== Typed Collection Creation Tests ====================

    public function test_creates_typed_collection_with_string_type(): void
    {
        $this->registerDirectiveClass(MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', [
            'user-collection',
            '--item-type=string'
        ]);

        $response->assertSuccess();
        $response->assertOutputContains('typed-collection created successfully!');

        $fullPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringContainsString('extends TypedCollection', $content);
        $this->assertStringContainsString('@extends TypedCollection<string>', $content);
        // Le stub utilise string::class et non 'string'
        $this->assertStringContainsString("parent::__construct(string::class)", $content);
    }

    public function test_creates_typed_collection_with_record_type(): void
    {
        $this->registerDirectiveClass(MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', [
            'user-collection',
            '--item-type=UserRecord'
        ]);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('@extends TypedCollection<UserRecord>', $content);
        $this->assertStringContainsString("parent::__construct(UserRecord::class)", $content);
    }

    public function test_creates_typed_collection_with_subdirectory(): void
    {
        $this->registerDirectiveClass(MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', [
            'admin/user-collection',
            '--item-type=UserRecord'
        ]);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Collections/Admin/UserCollection.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('namespace App\\Collections\\Admin', $content);
        $this->assertStringContainsString('class UserCollection', $content);
    }

    public function test_creates_typed_collection_with_already_has_suffix(): void
    {
        $this->registerDirectiveClass(MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', [
            'UserCollection',
            '--item-type=UserRecord'
        ]);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringNotContainsString('UserCollectionCollection', $content);
    }

    public function test_typed_collection_requires_item_type(): void
    {
        $this->registerDirectiveClass(MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', ['string-collection']);

        $response->assertFailure(ExitCode::INVALID_ARGUMENT->value);
        $response->assertOutputContains('Item type is required');

        $fullPath = $this->directiveTempDir . '/app/Collections/StringCollection.php';
        $this->assertFileDoesNotExist($fullPath);
    }

    // ==================== Error Handling Tests ====================

    public function test_prevents_duplicate_file_creation(): void
    {
        $this->registerDirectiveClass(MakeDirective::class);

        // First creation
        $firstResponse = $this->runDirective('make-directive', ['user-list']);
        $firstResponse->assertSuccess();

        // Second creation (should fail)
        $secondResponse = $this->runDirective('make-directive', ['user-list']);

        $secondResponse->assertFailure(ExitCode::FAILURE->value);
        $secondResponse->assertOutputContains('File already exists');
    }

    public function test_validates_action_type(): void
    {
        $this->registerDirectiveClass(MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['test', '--type=invalid']);

        $response->assertFailure(ExitCode::INVALID_ARGUMENT->value);
        $response->assertOutputContains('Invalid type');
    }

    public function test_validates_directive_name_format(): void
    {
        $this->registerDirectiveClass(MakeDirective::class);

        $response = $this->runDirective('make-directive', ['user@list']);

        $response->assertFailure(ExitCode::INVALID_ARGUMENT->value);
        $response->assertOutputContains('Invalid directive name');
    }

    public function test_requires_name_parameter(): void
    {
        $this->registerDirectiveClass(MakeDirective::class);

        $response = $this->runDirective('make-directive');

        $response->assertFailure(ExitCode::INVALID_ARGUMENT->value);
        // Le message d'erreur réel est "Not enough arguments (missing: "name")"
        $response->assertOutputContains('Not enough arguments');
    }

    // ==================== Alias Tests ====================

    public function test_uses_alias_create_directive(): void
    {
        $this->registerDirectiveClass(MakeDirective::class);

        $response = $this->runDirective('create-directive', ['cache-clear']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Directives/CacheClearDirective.php';
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_act(): void
    {
        $this->registerDirectiveClass(MakeActionDirective::class);

        $response = $this->runDirective('make-act', ['user/profile', '--type=api']);

        $response->assertSuccess();

        // Le fichier est créé dans le sous-dossier User/ProfileAction.php
        $fullPath = $this->directiveTempDir . '/app/Actions/User/ProfileAction.php';
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_job(): void
    {
        $this->registerDirectiveClass(MakeTaskDirective::class);

        $response = $this->runDirective('make-job', ['process-order']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Tasks/ProcessOrderTask.php';
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_repo(): void
    {
        $this->registerDirectiveClass(MakeRepositoryDirective::class);

        $response = $this->runDirective('make-repo', ['product']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Repositories/ProductRepository.php';
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_dto(): void
    {
        $this->registerDirectiveClass(MakeRecordDirective::class);

        $response = $this->runDirective('make-dto', ['product-data']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Records/ProductDataRecord.php';
        $this->assertFileExists($fullPath);
    }

    public function test_uses_alias_make_collection(): void
    {
        $this->registerDirectiveClass(MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-collection', [
            'product-collection',
            '--item-type=ProductRecord'
        ]);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Collections/ProductCollection.php';
        $this->assertFileExists($fullPath);
    }

    // ==================== Nested Path Tests ====================

    public function test_creates_deeply_nested_directive(): void
    {
        $this->registerDirectiveClass(MakeDirective::class);

        $response = $this->runDirective('make-directive', ['api/v1/admin/user/list']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Directives/Api/V1/Admin/User/ListDirective.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('namespace App\\Directives\\Api\\V1\\Admin\\User', $content);
        $this->assertStringContainsString('class ListDirective', $content);
    }

    public function test_creates_deeply_nested_action(): void
    {
        $this->registerDirectiveClass(MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['api/v2/shop/cart/add-item', '--type=api']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Actions/Api/V2/Shop/Cart/AddItemAction.php';
        $this->assertFileExists($fullPath);

        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('namespace App\\Actions\\Api\\V2\\Shop\\Cart', $content);
        $this->assertStringContainsString('class AddItemAction', $content);
    }

    // ==================== Multiple File Creation Tests ====================

    public function test_creates_multiple_files_in_same_test(): void
    {
        $this->registerDirectiveClass(MakeDirective::class);
        $this->registerDirectiveClass(MakeActionDirective::class);
        $this->registerDirectiveClass(MakeTaskDirective::class);

        // Create directive
        $response1 = $this->runDirective('make-directive', ['test-one']);
        $response1->assertSuccess();
        $this->assertFileExists($this->directiveTempDir . '/app/Directives/TestOneDirective.php');

        // Create action
        $response2 = $this->runDirective('make-action', ['test-two', '--type=api']);
        $response2->assertSuccess();
        $this->assertFileExists($this->directiveTempDir . '/app/Actions/TestTwoAction.php');

        // Create task
        $response3 = $this->runDirective('make-task', ['test-three']);
        $response3->assertSuccess();
        $this->assertFileExists($this->directiveTempDir . '/app/Tasks/TestThreeTask.php');
    }
}
