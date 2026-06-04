<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeServiceDirective;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeServiceDirectiveTest extends UnitTestCase
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

    private function getDirective(): MakeServiceDirective
    {
        return new MakeServiceDirective($this->interaction);
    }

    private function registerAndRun(string $signature, array $arguments = []): DirectiveResponseRecord
    {
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        return $this->runDirective($signature, $arguments);
    }

    public function test_get_signature_returns_make_service(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the signature
        $signature = $directive->getSignature();

        // Assert: Verify the signature is correct
        $this->assertSame('make-service {name}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the description
        $description = $directive->getDescription();

        // Assert: Verify the description is correct
        $this->assertSame('Create a new service class', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the aliases
        $aliases = $directive->getAliases();

        // Assert: Verify the aliases are correct
        $this->assertTrue($aliases->contains('create-service'));
        $this->assertTrue($aliases->contains('make-svc'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act: Run the directive without name argument
        $response = $this->registerAndRun('make-service');

        // Assert: Verify invalid argument error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        // Le message peut être "Not enough arguments (missing: "name")" 
        // ou "Service name is required" selon la couche qui intercepte
        $this->assertStringContainsString('name', $response->output);
        $this->assertStringContainsString('argument', strtolower($response->output));
    }

    public function test_execute_creates_service_file(): void
    {
        // Arrange: Prepare service name
        $serviceName = 'user-service';

        // Act: Run the directive to create the service file
        $response = $this->registerAndRun('make-service', [$serviceName]);

        $expectedPath = $this->directiveTempDir . '/app/Services/UserService.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('service created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserService', $content);
        $this->assertStringContainsString('namespace App\\Services', $content);
        $this->assertStringContainsString('final class UserService', $content);
    }

    public function test_execute_creates_service_in_subdirectory(): void
    {
        // Arrange: Prepare service name with subdirectory
        $serviceName = 'api/user-service';

        // Act: Run the directive to create the service file
        $response = $this->registerAndRun('make-service', [$serviceName]);

        $expectedPath = $this->directiveTempDir . '/app/Services/Api/UserService.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and correct namespace
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Services\\Api', $content);
        $this->assertStringContainsString('class UserService', $content);
    }

    public function test_execute_adds_service_suffix_automatically(): void
    {
        // Arrange: Prepare service name without suffix
        $serviceName = 'payment';

        // Act: Run the directive to create the service file
        $response = $this->registerAndRun('make-service', [$serviceName]);

        $expectedPath = $this->directiveTempDir . '/app/Services/PaymentService.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix was added automatically
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class PaymentService', $content);
    }

    public function test_execute_does_not_double_service_suffix(): void
    {
        // Arrange: Prepare service name that already has suffix
        $serviceName = 'UserService';

        // Act: Run the directive to create the service file
        $response = $this->registerAndRun('make-service', [$serviceName]);

        $expectedPath = $this->directiveTempDir . '/app/Services/UserService.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix is not duplicated
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserService', $content);
        $this->assertStringNotContainsString('UserServiceService', $content);
    }

    public function test_prevents_duplicate_file_creation(): void
    {
        // Arrange: First creation
        $serviceName = 'duplicate-service';

        // Act: First run (should succeed)
        $firstResponse = $this->registerAndRun('make-service', [$serviceName]);

        // Assert: Verify first creation succeeded
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exitCode);

        // Act: Second run with same name (should fail)
        $secondResponse = $this->registerAndRun('make-service', [$serviceName]);

        // Assert: Verify failure message
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exitCode);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }
}
