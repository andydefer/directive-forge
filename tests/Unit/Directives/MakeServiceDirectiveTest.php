<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeServiceDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class MakeServiceDirectiveTest extends IntegrationTestCase
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

    public function test_get_signature_returns_make_service(): void
    {
        $response = $this->service->run(MakeServiceDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_get_description_returns_description(): void
    {
        $response = $this->service->run(MakeServiceDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $this->service->registerDirective(MakeServiceDirective::class);

        $response = $this->service->runDirective('create-service', ['test-alias']);
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $response = $this->service->runDirective('make-svc', ['test-alias-2']);
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeServiceDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('name', $response->output);
        $this->assertStringContainsString('argument', strtolower($response->output));
    }

    public function test_execute_creates_service_file(): void
    {
        $serviceName = 'user-service';

        $response = $this->service->run(MakeServiceDirective::class, [$serviceName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Services/UserService.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('service created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserService', $content);
        $this->assertStringContainsString('namespace App\\Services', $content);
        $this->assertStringContainsString('final class UserService', $content);
    }

    public function test_execute_creates_service_in_subdirectory(): void
    {
        $serviceName = 'api/user-service';

        $response = $this->service->run(MakeServiceDirective::class, [$serviceName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Services/Api/UserService.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('service created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Services\\Api', $content);
        $this->assertStringContainsString('class UserService', $content);
    }

    public function test_execute_adds_service_suffix_automatically(): void
    {
        $serviceName = 'payment';

        $response = $this->service->run(MakeServiceDirective::class, [$serviceName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Services/PaymentService.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('service created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class PaymentService', $content);
    }

    public function test_execute_does_not_double_service_suffix(): void
    {
        $serviceName = 'UserService';

        $response = $this->service->run(MakeServiceDirective::class, [$serviceName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Services/UserService.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('service created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserService', $content);
        $this->assertStringNotContainsString('UserServiceService', $content);
    }

    public function test_prevents_duplicate_file_creation(): void
    {
        $serviceName = 'duplicate-service';

        // First run - should succeed
        $firstResponse = $this->service->run(MakeServiceDirective::class, [$serviceName]);
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exit_code);
        $this->assertStringContainsString('service created successfully!', strtolower($firstResponse->output));

        // Second run - should fail because file already exists
        $secondResponse = $this->service->run(MakeServiceDirective::class, [$serviceName]);
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exit_code);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }
}
