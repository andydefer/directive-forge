<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Directives\MakeConfigDirective;
use AndyDefer\DirectiveForge\Directives\MakeDataDirective;
use AndyDefer\DirectiveForge\Directives\MakeDirective;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective;
use AndyDefer\DirectiveForge\Directives\MakeRequestDirective;
use AndyDefer\DirectiveForge\Directives\MakeServiceDirective;
use AndyDefer\DirectiveForge\Directives\MakeTaskDirective;
use AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective;
use AndyDefer\DirectiveForge\Directives\MakeValueObjectDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class DirectiveForgeCompleteIntegrationTest extends IntegrationTestCase
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

    // ==================== Directive Tests ====================

    public function test_make_directive_with_simple_name(): void
    {
        $response = $this->service->run(MakeDirective::class, ['user-list-2']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_directive_with_subdirectory(): void
    {
        $response = $this->service->run(MakeDirective::class, ['user/domain/hello-directive']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_directive_with_alias_create_directive(): void
    {
        // Enregistrer la directive une fois pour tester les alias
        $this->service->registerDirective(MakeDirective::class);

        $response = $this->service->runDirective('create-directive', ['cache-clear']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_directive_with_alias_make_cmd(): void
    {
        $this->service->registerDirective(MakeDirective::class);

        $response = $this->service->runDirective('make-cmd', ['test-cmd']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    // ==================== Action Tests ====================

    public function test_make_action_with_simple_name(): void
    {
        $response = $this->service->run(MakeActionDirective::class, ['user/show']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_action_with_subdirectory(): void
    {
        $response = $this->service->run(MakeActionDirective::class, ['api/v1/users/show']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_action_with_alias_make_act(): void
    {
        $this->service->registerDirective(MakeActionDirective::class);

        $response = $this->service->runDirective('make-act', ['user/profile']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    // ==================== Task Tests ====================

    public function test_make_task(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, ['send-welcome-email']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_task_with_subdirectory(): void
    {
        $response = $this->service->run(MakeTaskDirective::class, ['user/send-welcome-email']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_task_with_alias_make_job(): void
    {
        $this->service->registerDirective(MakeTaskDirective::class);

        $response = $this->service->runDirective('make-job', ['process-order']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    // ==================== Repository Tests ====================

    public function test_make_repository(): void
    {
        $response = $this->service->run(MakeRepositoryDirective::class, ['user']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_repository_with_subdirectory(): void
    {
        $response = $this->service->run(MakeRepositoryDirective::class, ['admin/user']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_repository_with_alias_make_repo(): void
    {
        $this->service->registerDirective(MakeRepositoryDirective::class);

        $response = $this->service->runDirective('make-repo', ['product']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    // ==================== Record Tests ====================

    public function test_make_record(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, ['user-data']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_record_with_subdirectory(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, ['api/user-data']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_record_with_alias_make_dto(): void
    {
        $this->service->registerDirective(MakeRecordDirective::class);

        $response = $this->service->runDirective('make-dto', ['product-data']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    // ==================== Typed Collection Tests ====================

    public function test_make_typed_collection(): void
    {
        $response = $this->service->run(MakeTypedCollectionDirective::class, ['user-collection', '--item-type=UserRecord']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_typed_collection_with_subdirectory(): void
    {
        $response = $this->service->run(MakeTypedCollectionDirective::class, ['admin/user-collection', '--item-type=UserRecord']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_typed_collection_with_alias_make_collection(): void
    {
        $this->service->registerDirective(MakeTypedCollectionDirective::class);

        $response = $this->service->runDirective('make-collection', ['product-collection', '--item-type=ProductRecord']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_typed_collection_requires_item_type(): void
    {
        $response = $this->service->run(MakeTypedCollectionDirective::class, ['user-collection']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }

    // ==================== Service Tests ====================

    public function test_make_service(): void
    {
        $response = $this->service->run(MakeServiceDirective::class, ['payment-processor']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_service_with_subdirectory(): void
    {
        $response = $this->service->run(MakeServiceDirective::class, ['api/payment-processor']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_service_with_alias_make_svc(): void
    {
        $this->service->registerDirective(MakeServiceDirective::class);

        $response = $this->service->runDirective('make-svc', ['notification-sender']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    // ==================== Request Tests ====================

    public function test_make_request(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, ['StoreUserRequest']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_request_with_subdirectory(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, ['api/v1/StoreUserRequest']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_request_with_alias_make_req(): void
    {
        $this->service->registerDirective(MakeRequestDirective::class);

        $response = $this->service->runDirective('make-req', ['LoginRequest']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    // ==================== Value Object Tests ====================

    public function test_make_vo(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, ['EmailAddressVO']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_vo_with_subdirectory(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, ['User/EmailAddressVO']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    // ==================== Config Tests ====================

    public function test_make_config(): void
    {
        $response = $this->service->run(MakeConfigDirective::class, ['DatabaseConfig']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_config_with_subdirectory(): void
    {
        $response = $this->service->run(MakeConfigDirective::class, ['Database/MysqlConfig']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    // ==================== Data DTO Tests ====================

    public function test_make_data(): void
    {
        $response = $this->service->run(MakeDataDirective::class, ['user']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_data_with_subdirectory(): void
    {
        $response = $this->service->run(MakeDataDirective::class, ['api/user']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_data_with_alias_make_dto(): void
    {
        $this->service->registerDirective(MakeDataDirective::class);

        $response = $this->service->runDirective('make-dto', ['product']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_make_data_with_fully_option(): void
    {
        $response = $this->service->run(MakeDataDirective::class, ['order', '--fully']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    // ==================== Error Handling Tests ====================

    public function test_prevent_duplicate_file_creation(): void
    {
        // First run - should succeed
        $firstResponse = $this->service->run(MakeDirective::class, ['user-list']);
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exit_code);

        // Second run - should fail because file already exists
        $secondResponse = $this->service->run(MakeDirective::class, ['user-list']);
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exit_code);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }

    public function test_invalid_directive_name(): void
    {
        $response = $this->service->run(MakeDirective::class, ['user@list']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Invalid directive name', $response->output);
    }

    public function test_missing_name_parameter(): void
    {
        $response = $this->service->run(MakeDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    // ==================== System Commands Tests ====================

    public function test_help_command_shows_system_commands(): void
    {
        $response = $this->service->runDirective('--help');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('--list', $response->output);
        $this->assertStringContainsString('--help', $response->output);
        $this->assertStringContainsString('--version', $response->output);
    }

    public function test_version_command_shows_version(): void
    {
        $response = $this->service->runDirective('--version');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Laravel Directive', $response->output);
        $this->assertStringContainsString('Version:', $response->output);
    }
}
