<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeConfigDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class MakeConfigDirectiveTest extends IntegrationTestCase
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

    public function test_get_signature_returns_make_config(): void
    {
        $response = $this->service->run(MakeConfigDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
    }

    public function test_get_description_returns_description(): void
    {
        $response = $this->service->run(MakeConfigDirective::class, ['test']);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $this->service->registerDirective(MakeConfigDirective::class);

        $response = $this->service->runDirective('create-config', ['test-alias']);
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $response = $this->service->runDirective('make-cfg', ['test-alias-2']);
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeConfigDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('name', $response->output);
    }

    public function test_execute_creates_config_file(): void
    {
        $configName = 'DatabaseConfig';

        $response = $this->service->run(MakeConfigDirective::class, [$configName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Configs/DatabaseConfig.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('config created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class DatabaseConfig', $content);
        $this->assertStringContainsString('extends AbstractConfig', $content);
        $this->assertStringContainsString('namespace App\\Configs', $content);
    }

    public function test_execute_creates_config_in_subdirectory(): void
    {
        $configName = 'Database/DatabaseConfig';

        $response = $this->service->run(MakeConfigDirective::class, [$configName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Configs/Database/DatabaseConfig.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('config created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Configs\\Database', $content);
        $this->assertStringContainsString('class DatabaseConfig', $content);
    }

    public function test_execute_adds_config_suffix_automatically(): void
    {
        $configName = 'Database';

        $response = $this->service->run(MakeConfigDirective::class, [$configName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Configs/DatabaseConfig.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('config created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class DatabaseConfig', $content);
    }

    public function test_execute_does_not_double_config_suffix(): void
    {
        $configName = 'DatabaseConfig';

        $response = $this->service->run(MakeConfigDirective::class, [$configName]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Configs/DatabaseConfig.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('config created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class DatabaseConfig', $content);
        $this->assertStringNotContainsString('DatabaseConfigConfig', $content);
    }

    public function test_prevents_duplicate_file_creation(): void
    {
        $configName = 'DuplicateConfig';

        // First run - should succeed
        $firstResponse = $this->service->run(MakeConfigDirective::class, [$configName]);
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exitCode);
        $this->assertStringContainsString('config created successfully!', strtolower($firstResponse->output));

        // Second run - should fail because file already exists
        $secondResponse = $this->service->run(MakeConfigDirective::class, [$configName]);
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exitCode);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }
}
