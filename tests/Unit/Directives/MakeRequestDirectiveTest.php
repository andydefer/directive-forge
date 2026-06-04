<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeRequestDirective;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeRequestDirectiveTest extends UnitTestCase
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

    private function getDirective(): MakeRequestDirective
    {
        return new MakeRequestDirective($this->interaction);
    }

    private function registerAndRun(string $signature, array $arguments = []): DirectiveResponseRecord
    {
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        return $this->runDirective($signature, $arguments);
    }

    public function test_get_signature_returns_make_request(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the signature
        $signature = $directive->getSignature();

        // Assert: Verify the signature is correct
        $this->assertSame('make-request {name}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the description
        $description = $directive->getDescription();

        // Assert: Verify the description is correct
        $this->assertSame('Create a new form request class', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the aliases
        $aliases = $directive->getAliases();

        // Assert: Verify the aliases are correct
        $this->assertTrue($aliases->contains('create-request'));
        $this->assertTrue($aliases->contains('make-req'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act: Run the directive without name argument
        $response = $this->registerAndRun('make-request');

        // Assert: Verify invalid argument error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        // Le message peut être "Not enough arguments (missing: "name")" 
        // ou "Request name is required" selon la couche qui intercepte
        $this->assertStringContainsString('name', $response->output);
        $this->assertStringContainsString('argument', strtolower($response->output));
    }

    public function test_execute_creates_request_file(): void
    {
        // Arrange: Prepare request name
        $requestName = 'StoreUserRequest';

        // Act: Run the directive to create the request file
        $response = $this->registerAndRun('make-request', [$requestName]);

        $expectedPath = $this->directiveTempDir . '/app/Http/Requests/StoreUserRequest.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('request created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class StoreUserRequest', $content);
        $this->assertStringContainsString('extends AbstractRequest', $content);
        $this->assertStringContainsString('namespace App\\Http\\Requests', $content);
    }

    public function test_execute_creates_request_with_empty_record(): void
    {
        // Arrange: Prepare request name
        $requestName = 'UpdateUserRequest';

        // Act: Run the directive to create the request file
        $response = $this->registerAndRun('make-request', [$requestName]);

        $expectedPath = $this->directiveTempDir . '/app/Http/Requests/UpdateUserRequest.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and file content contains EmptyRecord
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Utils\\EmptyRecord;', $content);
        $this->assertStringContainsString('return new EmptyRecord();', $content);
    }

    public function test_execute_creates_request_in_subdirectory(): void
    {
        // Arrange: Prepare request name with subdirectory
        $requestName = 'api/v1/StoreUserRequest';

        // Act: Run the directive to create the request file
        $response = $this->registerAndRun('make-request', [$requestName]);

        $expectedPath = $this->directiveTempDir . '/app/Http/Requests/Api/V1/StoreUserRequest.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and correct namespace
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Http\\Requests\\Api\\V1', $content);
        $this->assertStringContainsString('class StoreUserRequest', $content);
    }

    public function test_execute_adds_request_suffix_automatically(): void
    {
        // Arrange: Prepare request name without suffix
        $requestName = 'Login';

        // Act: Run the directive to create the request file
        $response = $this->registerAndRun('make-request', [$requestName]);

        $expectedPath = $this->directiveTempDir . '/app/Http/Requests/LoginRequest.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix was added automatically
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class LoginRequest', $content);
    }

    public function test_execute_does_not_double_request_suffix(): void
    {
        // Arrange: Prepare request name that already has suffix
        $requestName = 'StoreUserRequest';

        // Act: Run the directive to create the request file
        $response = $this->registerAndRun('make-request', [$requestName]);

        $expectedPath = $this->directiveTempDir . '/app/Http/Requests/StoreUserRequest.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix is not duplicated
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class StoreUserRequest', $content);
        $this->assertStringNotContainsString('StoreUserRequestRequest', $content);
    }

    public function test_request_has_authorize_method_returns_true(): void
    {
        // Arrange: Prepare request name
        $requestName = 'PublicRequest';

        // Act: Run the directive to create the request file
        $response = $this->registerAndRun('make-request', [$requestName]);

        $expectedPath = $this->directiveTempDir . '/app/Http/Requests/PublicRequest.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify authorize method returns true
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('public function authorize(): bool', $content);
        $this->assertStringContainsString('return true;', $content);
    }

    public function test_request_has_rules_method_returns_empty_array(): void
    {
        // Arrange: Prepare request name
        $requestName = 'EmptyRulesRequest';

        // Act: Run the directive to create the request file
        $response = $this->registerAndRun('make-request', [$requestName]);

        $expectedPath = $this->directiveTempDir . '/app/Http/Requests/EmptyRulesRequest.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify rules method returns empty array
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('public function rules(): array', $content);
        $this->assertStringContainsString('return [];', $content);
    }

    public function test_prevents_duplicate_request_creation(): void
    {
        // Arrange: First creation
        $requestName = 'DuplicateRequest';

        // Act: First run (should succeed)
        $firstResponse = $this->registerAndRun('make-request', [$requestName]);

        // Assert: Verify first creation succeeded
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exitCode);

        // Act: Second run with same name (should fail)
        $secondResponse = $this->registerAndRun('make-request', [$requestName]);

        // Assert: Verify failure message
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exitCode);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }

    public function test_creates_request_with_kebab_case_name(): void
    {
        // Arrange: Prepare kebab-case request name
        $requestName = 'store-user-request';

        // Act: Run the directive to create the request file
        $response = $this->registerAndRun('make-request', [$requestName]);

        $expectedPath = $this->directiveTempDir . '/app/Http/Requests/StoreUserRequest.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify conversion to PascalCase
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class StoreUserRequest', $content);
    }

    public function test_creates_request_with_snake_case_name(): void
    {
        // Arrange: Prepare snake_case request name
        $requestName = 'store_user_request';

        // Act: Run the directive to create the request file
        $response = $this->registerAndRun('make-request', [$requestName]);

        $expectedPath = $this->directiveTempDir . '/app/Http/Requests/StoreUserRequest.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify conversion to PascalCase
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class StoreUserRequest', $content);
    }
}
