<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class MakeRecordDirectiveTest extends IntegrationTestCase
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

    public function test_get_signature_returns_make_record(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_get_description_returns_description(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $this->service->registerDirective(MakeRecordDirective::class);

        $response = $this->service->runDirective('create-record', ['test-alias']);
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $response = $this->service->runDirective('make-dto', ['test-alias-2']);
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeRecordDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_execute_creates_record_file(): void
    {
        $recordName = 'user-data';

        $response = $this->service->run(MakeRecordDirective::class, [$recordName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Records/UserDataRecord.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserDataRecord', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_execute_creates_record_in_subdirectory(): void
    {
        $recordName = 'api/user-data';

        $response = $this->service->run(MakeRecordDirective::class, [$recordName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Records/Api/UserDataRecord.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Records\\Api', $content);
        $this->assertStringContainsString('class UserDataRecord', $content);
    }

    public function test_execute_adds_record_suffix_automatically(): void
    {
        $recordName = 'product-data';

        $response = $this->service->run(MakeRecordDirective::class, [$recordName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Records/ProductDataRecord.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProductDataRecord', $content);
    }

    public function test_execute_does_not_double_record_suffix(): void
    {
        $recordName = 'UserDataRecord';

        $response = $this->service->run(MakeRecordDirective::class, [$recordName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Records/UserDataRecord.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserDataRecord', $content);
        $this->assertStringNotContainsString('UserDataRecordRecord', $content);
    }

    public function test_execute_converts_kebab_case_to_pascal_case(): void
    {
        $recordName = 'user-profile-data';

        $response = $this->service->run(MakeRecordDirective::class, [$recordName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Records/UserProfileDataRecord.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserProfileDataRecord', $content);
    }
}
