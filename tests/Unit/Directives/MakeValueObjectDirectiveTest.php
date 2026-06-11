<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeValueObjectDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class MakeValueObjectDirectiveTest extends IntegrationTestCase
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

    public function test_get_signature_returns_make_vo(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_get_description_returns_description(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $this->service->registerDirective(MakeValueObjectDirective::class);

        $response = $this->service->runDirective('create-vo', ['test-alias']);
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $response = $this->service->runDirective('make-value-object', ['test-alias-2']);
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeValueObjectDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('name', $response->output);
    }

    public function test_execute_creates_vo_file(): void
    {
        $voName = 'EmailAddressVO';

        $response = $this->service->run(MakeValueObjectDirective::class, [$voName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/ValueObjects/EmailAddressVO.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('value-object created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class EmailAddressVO', $content);
        $this->assertStringContainsString('extends AbstractValueObject', $content);
        $this->assertStringContainsString('namespace App\\ValueObjects', $content);
    }

    public function test_execute_creates_vo_in_subdirectory(): void
    {
        $voName = 'User/EmailAddressVO';

        $response = $this->service->run(MakeValueObjectDirective::class, [$voName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/ValueObjects/User/EmailAddressVO.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('value-object created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\ValueObjects\\User', $content);
        $this->assertStringContainsString('class EmailAddressVO', $content);
    }

    public function test_execute_adds_vo_suffix_automatically(): void
    {
        $voName = 'EmailAddress';

        $response = $this->service->run(MakeValueObjectDirective::class, [$voName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/ValueObjects/EmailAddressVO.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('value-object created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class EmailAddressVO', $content);
    }

    public function test_prevents_duplicate_file_creation(): void
    {
        $voName = 'DuplicateVO';

        // First run - should succeed
        $firstResponse = $this->service->run(MakeValueObjectDirective::class, [$voName]);
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exit_code);
        $this->assertStringContainsString('value-object created successfully!', strtolower($firstResponse->output));

        // Second run - should fail because file already exists
        $secondResponse = $this->service->run(MakeValueObjectDirective::class, [$voName]);
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exit_code);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }
}
