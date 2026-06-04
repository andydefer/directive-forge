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

        // Arrange: Initialize the testing environment
        $this->initDirectiveTesting(bootLaravel: false);

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

    private function getMakeServiceDirective(): MakeServiceDirective
    {
        return new MakeServiceDirective($this->interaction);
    }

    private function getMakeRequestDirective(): MakeRequestDirective
    {
        return new MakeRequestDirective($this->interaction);
    }

    private function getMakeValueObjectDirective(): MakeValueObjectDirective
    {
        return new MakeValueObjectDirective($this->interaction);
    }

    private function getMakeConfigDirective(): MakeConfigDirective
    {
        return new MakeConfigDirective($this->interaction);
    }

    private function getMakeDataDirective(): MakeDataDirective
    {
        return new MakeDataDirective($this->interaction);
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
            $this->directiveTempDir . '/app/Services',
            $this->directiveTempDir . '/app/Http/Requests',
            $this->directiveTempDir . '/app/ValueObjects',
            $this->directiveTempDir . '/app/Configs',
            $this->directiveTempDir . '/app/Data',
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
            'make-service', 'create-service', 'make-svc' => $this->getMakeServiceDirective(),
            'make-request', 'create-request', 'make-req' => $this->getMakeRequestDirective(),
            'make-vo', 'create-vo', 'make-value-object' => $this->getMakeValueObjectDirective(),
            'make-config', 'create-config', 'make-cfg' => $this->getMakeConfigDirective(),
            'make-data', 'create-data', 'make-dto' => $this->getMakeDataDirective(),
            default => throw new \InvalidArgumentException("Unknown directive: {$signature}"),
        };

        $this->registerDirective($directive);

        return $this->runDirective($signature, $arguments);
    }

    // ==================== Directive Tests ====================

    public function test_make_directive_with_simple_name(): void
    {
        // Arrange
        $directiveName = 'user-list';

        // Act
        $response = $this->registerAndRun('make-directive', [$directiveName]);
        $fullPath = $this->directiveTempDir . '/app/Directives/UserListDirective.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
    }

    public function test_make_directive_with_subdirectory(): void
    {
        // Arrange
        $directiveName = 'user/domain/hello-directive';

        // Act
        $response = $this->registerAndRun('make-directive', [$directiveName]);
        $fullPath = $this->directiveTempDir . '/app/Directives/User/Domain/HelloDirective.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
    }

    public function test_make_directive_with_alias_create_directive(): void
    {
        // Arrange
        $this->registerDirective($this->getMakeDirective());

        // Act
        $response = $this->runDirective('create-directive', ['cache-clear']);
        $fullPath = $this->directiveTempDir . '/app/Directives/CacheClearDirective.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
    }

    public function test_make_directive_with_alias_make_cmd(): void
    {
        // Arrange
        $this->registerDirective($this->getMakeDirective());

        // Act
        $response = $this->runDirective('make-cmd', ['test-cmd']);
        $fullPath = $this->directiveTempDir . '/app/Directives/TestCmdDirective.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
    }

    // ==================== Action Tests ====================

    public function test_make_action_with_simple_name(): void
    {
        // Arrange
        $actionName = 'user/show';

        // Act
        $response = $this->registerAndRun('make-action', [$actionName]);
        $fullPath = $this->directiveTempDir . '/app/Actions/User/ShowAction.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
    }

    public function test_make_action_with_subdirectory(): void
    {
        // Arrange
        $actionName = 'api/v1/users/show';

        // Act
        $response = $this->registerAndRun('make-action', [$actionName]);
        $fullPath = $this->directiveTempDir . '/app/Actions/Api/V1/Users/ShowAction.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
    }

    public function test_make_action_with_alias_make_act(): void
    {
        // Arrange
        $this->registerDirective($this->getMakeActionDirective());

        // Act
        $response = $this->runDirective('make-act', ['user/profile']);
        $fullPath = $this->directiveTempDir . '/app/Actions/User/ProfileAction.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
    }

    // ==================== Task Tests ====================

    public function test_make_task(): void
    {
        // Arrange
        $taskName = 'send-welcome-email';

        // Act
        $response = $this->registerAndRun('make-task', [$taskName]);
        $fullPath = $this->directiveTempDir . '/app/Tasks/SendWelcomeEmailTask.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
    }

    public function test_make_task_with_subdirectory(): void
    {
        // Arrange
        $taskName = 'user/send-welcome-email';

        // Act
        $response = $this->registerAndRun('make-task', [$taskName]);
        $fullPath = $this->directiveTempDir . '/app/Tasks/User/SendWelcomeEmailTask.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
    }

    public function test_make_task_with_alias_make_job(): void
    {
        // Arrange
        $this->registerDirective($this->getMakeTaskDirective());

        // Act
        $response = $this->runDirective('make-job', ['process-order']);
        $fullPath = $this->directiveTempDir . '/app/Tasks/ProcessOrderTask.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('task created successfully!', strtolower($response->output));
    }

    // ==================== Repository Tests ====================

    public function test_make_repository(): void
    {
        // Arrange
        $repositoryName = 'user';

        // Act
        $response = $this->registerAndRun('make-repository', [$repositoryName]);
        $fullPath = $this->directiveTempDir . '/app/Repositories/UserRepository.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
    }

    public function test_make_repository_with_subdirectory(): void
    {
        // Arrange
        $repositoryName = 'admin/user';

        // Act
        $response = $this->registerAndRun('make-repository', [$repositoryName]);
        $fullPath = $this->directiveTempDir . '/app/Repositories/Admin/UserRepository.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
    }

    public function test_make_repository_with_alias_make_repo(): void
    {
        // Arrange
        $this->registerDirective($this->getMakeRepositoryDirective());

        // Act
        $response = $this->runDirective('make-repo', ['product']);
        $fullPath = $this->directiveTempDir . '/app/Repositories/ProductRepository.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
    }

    // ==================== Record Tests ====================

    public function test_make_record(): void
    {
        // Arrange
        $recordName = 'user-data';

        // Act
        $response = $this->registerAndRun('make-record', [$recordName]);
        $fullPath = $this->directiveTempDir . '/app/Records/UserDataRecord.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
    }

    public function test_make_record_with_subdirectory(): void
    {
        // Arrange
        $recordName = 'api/user-data';

        // Act
        $response = $this->registerAndRun('make-record', [$recordName]);
        $fullPath = $this->directiveTempDir . '/app/Records/Api/UserDataRecord.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
    }

    public function test_make_record_with_alias_make_dto(): void
    {
        // Arrange
        $this->registerDirective($this->getMakeRecordDirective());

        // Act
        $response = $this->runDirective('make-dto', ['product-data']);
        $fullPath = $this->directiveTempDir . '/app/Records/ProductDataRecord.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
    }

    // ==================== Typed Collection Tests ====================

    public function test_make_typed_collection(): void
    {
        // Arrange
        $collectionName = 'user-collection';
        $itemType = 'UserRecord';

        // Act
        $response = $this->registerAndRun('make-typed-collection', [
            $collectionName,
            "--item-type={$itemType}",
        ]);
        $fullPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('UserRecord', file_get_contents($fullPath));
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
    }

    public function test_make_typed_collection_with_subdirectory(): void
    {
        // Arrange
        $collectionName = 'admin/user-collection';
        $itemType = 'UserRecord';

        // Act
        $response = $this->registerAndRun('make-typed-collection', [
            $collectionName,
            "--item-type={$itemType}",
        ]);
        $fullPath = $this->directiveTempDir . '/app/Collections/Admin/UserCollection.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
    }

    public function test_make_typed_collection_with_alias_make_collection(): void
    {
        // Arrange
        $this->registerDirective($this->getMakeTypedCollectionDirective());

        // Act
        $response = $this->runDirective('make-collection', [
            'product-collection',
            '--item-type=ProductRecord',
        ]);
        $fullPath = $this->directiveTempDir . '/app/Collections/ProductCollection.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
    }

    public function test_make_typed_collection_requires_item_type(): void
    {
        // Arrange
        $collectionName = 'user-collection';

        // Act
        $response = $this->registerAndRun('make-typed-collection', [$collectionName]);
        $fullPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';

        // Assert
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Item type is required', $response->output);
        $this->assertFileDoesNotExist($fullPath);
    }

    // ==================== Service Tests ====================

    public function test_make_service(): void
    {
        // Arrange
        $serviceName = 'payment-processor';

        // Act
        $response = $this->registerAndRun('make-service', [$serviceName]);
        $fullPath = $this->directiveTempDir . '/app/Services/PaymentProcessorService.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('service created successfully!', strtolower($response->output));
    }

    public function test_make_service_with_subdirectory(): void
    {
        // Arrange
        $serviceName = 'api/payment-processor';

        // Act
        $response = $this->registerAndRun('make-service', [$serviceName]);
        $fullPath = $this->directiveTempDir . '/app/Services/Api/PaymentProcessorService.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('service created successfully!', strtolower($response->output));
    }

    public function test_make_service_with_alias_make_svc(): void
    {
        // Arrange
        $this->registerDirective($this->getMakeServiceDirective());

        // Act
        $response = $this->runDirective('make-svc', ['notification-sender']);
        $fullPath = $this->directiveTempDir . '/app/Services/NotificationSenderService.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('service created successfully!', strtolower($response->output));
    }

    // ==================== Request Tests ====================

    public function test_make_request(): void
    {
        // Arrange
        $requestName = 'StoreUserRequest';

        // Act
        $response = $this->registerAndRun('make-request', [$requestName]);
        $fullPath = $this->directiveTempDir . '/app/Http/Requests/StoreUserRequest.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('request created successfully!', strtolower($response->output));
    }

    public function test_make_request_with_subdirectory(): void
    {
        // Arrange
        $requestName = 'api/v1/StoreUserRequest';

        // Act
        $response = $this->registerAndRun('make-request', [$requestName]);
        $fullPath = $this->directiveTempDir . '/app/Http/Requests/Api/V1/StoreUserRequest.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('request created successfully!', strtolower($response->output));
    }

    public function test_make_request_with_alias_make_req(): void
    {
        // Arrange
        $this->registerDirective($this->getMakeRequestDirective());

        // Act
        $response = $this->runDirective('make-req', ['LoginRequest']);
        $fullPath = $this->directiveTempDir . '/app/Http/Requests/LoginRequest.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('request created successfully!', strtolower($response->output));
    }

    // ==================== Value Object Tests ====================

    public function test_make_vo(): void
    {
        // Arrange
        $voName = 'EmailAddressVO';

        // Act
        $response = $this->registerAndRun('make-vo', [$voName]);
        $fullPath = $this->directiveTempDir . '/app/ValueObjects/EmailAddressVO.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('value-object created successfully!', strtolower($response->output));
    }

    public function test_make_vo_with_subdirectory(): void
    {
        // Arrange
        $voName = 'User/EmailAddressVO';

        // Act
        $response = $this->registerAndRun('make-vo', [$voName]);
        $fullPath = $this->directiveTempDir . '/app/ValueObjects/User/EmailAddressVO.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('value-object created successfully!', strtolower($response->output));
    }

    // ==================== Config Tests ====================

    public function test_make_config(): void
    {
        // Arrange
        $configName = 'DatabaseConfig';

        // Act
        $response = $this->registerAndRun('make-config', [$configName]);
        $fullPath = $this->directiveTempDir . '/app/Configs/DatabaseConfig.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('config created successfully!', strtolower($response->output));
    }

    public function test_make_config_with_subdirectory(): void
    {
        // Arrange
        $configName = 'Database/MysqlConfig';

        // Act
        $response = $this->registerAndRun('make-config', [$configName]);
        $fullPath = $this->directiveTempDir . '/app/Configs/Database/MysqlConfig.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('config created successfully!', strtolower($response->output));
    }

    // ==================== Data DTO Tests ====================

    public function test_make_data(): void
    {
        // Arrange
        $dataName = 'user';

        // Act
        $response = $this->registerAndRun('make-data', [$dataName]);
        $fullPath = $this->directiveTempDir . '/app/Data/UserData.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('data created successfully!', strtolower($response->output));
    }

    public function test_make_data_with_subdirectory(): void
    {
        // Arrange
        $dataName = 'api/user';

        // Act
        $response = $this->registerAndRun('make-data', [$dataName]);
        $fullPath = $this->directiveTempDir . '/app/Data/Api/UserData.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('data created successfully!', strtolower($response->output));
    }

    public function test_make_data_with_alias_make_dto(): void
    {
        // Arrange
        $this->registerDirective($this->getMakeDataDirective());

        // Act
        $response = $this->runDirective('make-dto', ['product']);
        $fullPath = $this->directiveTempDir . '/app/Data/ProductData.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('data created successfully!', strtolower($response->output));
    }

    public function test_make_data_with_fully_option(): void
    {
        // Arrange
        $dataName = 'order';

        // Act
        $response = $this->registerAndRun('make-data', [$dataName, '--fully']);

        $dataPath = $this->directiveTempDir . '/app/Data/OrderData.php';
        $recordPath = $this->directiveTempDir . '/app/Records/OrderRecord.php';
        $collectionPath = $this->directiveTempDir . '/app/Collections/OrderDataCollection.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($dataPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($collectionPath);
        $this->assertStringContainsString('Fully created', $response->output);
    }

    // ==================== Error Handling Tests ====================

    public function test_prevent_duplicate_file_creation(): void
    {
        // Arrange
        $directiveName = 'user-list';

        // Act: First run
        $firstResponse = $this->registerAndRun('make-directive', [$directiveName]);

        // Assert: First creation succeeded
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exitCode);

        // Act: Second run
        $secondResponse = $this->registerAndRun('make-directive', [$directiveName]);

        // Assert: Failure message
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exitCode);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }

    public function test_invalid_directive_name(): void
    {
        // Arrange
        $invalidName = 'user@list';

        // Act
        $response = $this->registerAndRun('make-directive', [$invalidName]);

        // Assert
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Invalid directive name', $response->output);
    }

    public function test_missing_name_parameter(): void
    {
        // Act
        $response = $this->registerAndRun('make-directive');

        // Assert
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
        $this->registerDirective($this->getMakeServiceDirective());
        $this->registerDirective($this->getMakeRequestDirective());
        $this->registerDirective($this->getMakeValueObjectDirective());
        $this->registerDirective($this->getMakeConfigDirective());
        $this->registerDirective($this->getMakeDataDirective());

        // Act
        $response = $this->runDirective('--help');

        // Assert
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
        $this->registerDirective($this->getMakeServiceDirective());
        $this->registerDirective($this->getMakeRequestDirective());
        $this->registerDirective($this->getMakeValueObjectDirective());
        $this->registerDirective($this->getMakeConfigDirective());
        $this->registerDirective($this->getMakeDataDirective());

        // Act
        $response = $this->runDirective('--list');

        // Assert
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
        $this->assertStringContainsString('make-service', $response->output);
        $this->assertStringContainsString('create-service', $response->output);
        $this->assertStringContainsString('make-svc', $response->output);
        $this->assertStringContainsString('make-request', $response->output);
        $this->assertStringContainsString('create-request', $response->output);
        $this->assertStringContainsString('make-req', $response->output);
        $this->assertStringContainsString('make-vo', $response->output);
        $this->assertStringContainsString('create-vo', $response->output);
        $this->assertStringContainsString('make-value-object', $response->output);
        $this->assertStringContainsString('make-config', $response->output);
        $this->assertStringContainsString('create-config', $response->output);
        $this->assertStringContainsString('make-cfg', $response->output);
        $this->assertStringContainsString('make-data', $response->output);
        $this->assertStringContainsString('create-data', $response->output);
    }

    public function test_version_command_shows_version(): void
    {
        // Act
        $response = $this->runDirective('--version');

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('Laravel Directive', $response->output);
        $this->assertStringContainsString('Version:', $response->output);
    }
}
