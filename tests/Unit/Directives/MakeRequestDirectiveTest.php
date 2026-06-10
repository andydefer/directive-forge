<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeRequestDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class MakeRequestDirectiveTest extends IntegrationTestCase
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

    public function test_get_signature_returns_make_request(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
    }

    public function test_get_description_returns_description(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $this->service->registerDirective(MakeRequestDirective::class);

        $response = $this->service->runDirective('create-request', ['test-alias']);
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $response = $this->service->runDirective('make-req', ['test-alias-2']);
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeRequestDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('name', $response->output);
    }

    public function test_execute_creates_request_file(): void
    {
        $requestName = 'StoreUserRequest';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Http/Requests/StoreUserRequest.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('request created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class StoreUserRequest', $content);
        $this->assertStringContainsString('extends AbstractRequest', $content);
        $this->assertStringContainsString('namespace App\\Http\\Requests', $content);
    }

    public function test_execute_creates_request_with_empty_record(): void
    {
        $requestName = 'UpdateUserRequest';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Http/Requests/UpdateUserRequest.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Utils\\EmptyRecord;', $content);
        $this->assertStringContainsString('return new EmptyRecord();', $content);
    }

    public function test_execute_creates_request_in_subdirectory(): void
    {
        $requestName = 'api/v1/StoreUserRequest';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Http/Requests/Api/V1/StoreUserRequest.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('request created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Http\\Requests\\Api\\V1', $content);
        $this->assertStringContainsString('class StoreUserRequest', $content);
    }

    public function test_execute_adds_request_suffix_automatically(): void
    {
        $requestName = 'Login';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Http/Requests/LoginRequest.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('request created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class LoginRequest', $content);
    }

    public function test_execute_does_not_double_request_suffix(): void
    {
        $requestName = 'StoreUserRequest';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Http/Requests/StoreUserRequest.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('request created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class StoreUserRequest', $content);
        $this->assertStringNotContainsString('StoreUserRequestRequest', $content);
    }

    public function test_request_has_authorize_method_returns_true(): void
    {
        $requestName = 'PublicRequest';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Http/Requests/PublicRequest.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('request created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('public function authorize(): bool', $content);
        $this->assertStringContainsString('return true;', $content);
    }

    public function test_request_has_rules_method_returns_empty_array(): void
    {
        $requestName = 'EmptyRulesRequest';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Http/Requests/EmptyRulesRequest.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('request created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('public function rules(): array', $content);
        $this->assertStringContainsString('return [];', $content);
    }

    public function test_prevents_duplicate_request_creation(): void
    {
        $requestName = 'DuplicateRequest';

        // First run - should succeed
        $firstResponse = $this->service->run(MakeRequestDirective::class, [$requestName]);
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exitCode);
        $this->assertStringContainsString('request created successfully!', strtolower($firstResponse->output));

        // Second run - should fail because file already exists
        $secondResponse = $this->service->run(MakeRequestDirective::class, [$requestName]);
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exitCode);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }

    public function test_creates_request_with_kebab_case_name(): void
    {
        $requestName = 'store-user-request';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Http/Requests/StoreUserRequest.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('request created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class StoreUserRequest', $content);
    }

    public function test_creates_request_with_snake_case_name(): void
    {
        $requestName = 'store_user_request';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Http/Requests/StoreUserRequest.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('request created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class StoreUserRequest', $content);
    }

    // ==================== Tests avec option --fully ====================

    public function test_execute_with_fully_option_creates_request_and_record(): void
    {
        $requestName = 'User/StoreUserRequest';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName, '--fully']);

        $tempDir = $this->service->getContext()->getTempDir();
        $requestPath = $tempDir . '/app/Http/Requests/User/StoreUserRequest.php';
        $recordPath = $tempDir . '/app/Records/User/StoreUserRecord.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('Fully created', $response->output);
        $this->assertFileExists($requestPath);
        $this->assertFileExists($recordPath);
    }

    public function test_execute_with_fully_option_in_subdirectory(): void
    {
        $requestName = 'api/v1/StoreUserRequest';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName, '--fully']);

        $tempDir = $this->service->getContext()->getTempDir();
        $requestPath = $tempDir . '/app/Http/Requests/Api/V1/StoreUserRequest.php';
        $recordPath = $tempDir . '/app/Records/Api/V1/StoreUserRecord.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('Fully created', $response->output);
        $this->assertFileExists($requestPath);
        $this->assertFileExists($recordPath);
    }

    public function test_execute_with_fully_option_preserves_naming_consistency(): void
    {
        $requestName = 'user-profile/update-email-request';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName, '--fully']);

        $tempDir = $this->service->getContext()->getTempDir();
        $requestPath = $tempDir . '/app/Http/Requests/UserProfile/UpdateEmailRequest.php';
        $recordPath = $tempDir . '/app/Records/UserProfile/UpdateEmailRecord.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($requestPath);
        $this->assertFileExists($recordPath);
    }

    public function test_execute_without_fully_option_does_not_create_record(): void
    {
        $requestName = 'User/StoreUserRequest';

        $response = $this->service->run(MakeRequestDirective::class, [$requestName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $recordPath = $tempDir . '/app/Records/User/StoreUserRecord.php';

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($tempDir . '/app/Http/Requests/User/StoreUserRequest.php');
        $this->assertFileDoesNotExist($recordPath);
    }
}
