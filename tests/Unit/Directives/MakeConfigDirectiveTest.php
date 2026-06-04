<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeConfigDirective;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeConfigDirectiveTest extends UnitTestCase
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

    private function getDirective(): MakeConfigDirective
    {
        return new MakeConfigDirective($this->interaction);
    }

    private function registerAndRun(string $signature, array $arguments = []): DirectiveResponseRecord
    {
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        return $this->runDirective($signature, $arguments);
    }

    public function test_get_signature_returns_make_config(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the signature
        $signature = $directive->getSignature();

        // Assert: Verify the signature is correct
        $this->assertSame('make-config {name}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the description
        $description = $directive->getDescription();

        // Assert: Verify the description is correct
        $this->assertSame('Create a new configuration class', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the aliases
        $aliases = $directive->getAliases();

        // Assert: Verify the aliases are correct
        $this->assertTrue($aliases->contains('create-config'));
        $this->assertTrue($aliases->contains('make-cfg'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act: Run the directive without name argument
        $response = $this->registerAndRun('make-config');

        // Assert: Verify invalid argument error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('name', $response->output);
    }

    public function test_execute_creates_config_file(): void
    {
        // Arrange: Prepare config name
        $configName = 'DatabaseConfig';

        // Act: Run the directive to create the config file
        $response = $this->registerAndRun('make-config', [$configName]);

        $expectedPath = $this->directiveTempDir . '/app/Configs/DatabaseConfig.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('config created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class DatabaseConfig', $content);
        $this->assertStringContainsString('extends AbstractConfig', $content);
        $this->assertStringContainsString('namespace App\\Configs', $content);
    }

    public function test_execute_creates_config_in_subdirectory(): void
    {
        // Arrange: Prepare config name with subdirectory
        $configName = 'Database/DatabaseConfig';

        // Act: Run the directive to create the config file
        $response = $this->registerAndRun('make-config', [$configName]);

        $expectedPath = $this->directiveTempDir . '/app/Configs/Database/DatabaseConfig.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and correct namespace
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('config created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Configs\\Database', $content);
        $this->assertStringContainsString('class DatabaseConfig', $content);
    }

    public function test_execute_adds_config_suffix_automatically(): void
    {
        // Arrange: Prepare config name without suffix
        $configName = 'Database';

        // Act: Run the directive to create the config file
        $response = $this->registerAndRun('make-config', [$configName]);

        $expectedPath = $this->directiveTempDir . '/app/Configs/DatabaseConfig.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix was added automatically
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('config created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class DatabaseConfig', $content);
    }

    public function test_execute_does_not_double_config_suffix(): void
    {
        // Arrange: Prepare config name that already has suffix
        $configName = 'DatabaseConfig';

        // Act: Run the directive to create the config file
        $response = $this->registerAndRun('make-config', [$configName]);

        $expectedPath = $this->directiveTempDir . '/app/Configs/DatabaseConfig.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix is not duplicated
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('config created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class DatabaseConfig', $content);
        $this->assertStringNotContainsString('DatabaseConfigConfig', $content);
    }

    public function test_prevents_duplicate_file_creation(): void
    {
        // Arrange: First creation
        $configName = 'DuplicateConfig';

        // Act: First run (should succeed)
        $firstResponse = $this->registerAndRun('make-config', [$configName]);

        // Assert: Verify first creation succeeded
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exitCode);
        $this->assertStringContainsString('config created successfully!', strtolower($firstResponse->output));

        // Act: Second run with same name (should fail)
        $secondResponse = $this->registerAndRun('make-config', [$configName]);

        // Assert: Verify failure message
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exitCode);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }
}
