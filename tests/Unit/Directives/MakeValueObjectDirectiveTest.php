<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeValueObjectDirective;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeValueObjectDirectiveTest extends UnitTestCase
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

    private function getDirective(): MakeValueObjectDirective
    {
        return new MakeValueObjectDirective($this->interaction);
    }

    private function registerAndRun(string $signature, array $arguments = []): DirectiveResponseRecord
    {
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        return $this->runDirective($signature, $arguments);
    }

    public function test_get_signature_returns_make_vo(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the signature
        $signature = $directive->getSignature();

        // Assert: Verify the signature is correct
        $this->assertSame('make-vo {name}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the description
        $description = $directive->getDescription();

        // Assert: Verify the description is correct
        $this->assertSame('Create a new value object class (VO)', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the aliases
        $aliases = $directive->getAliases();

        // Assert: Verify the aliases are correct
        $this->assertTrue($aliases->contains('create-vo'));
        $this->assertTrue($aliases->contains('make-value-object'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act: Run the directive without name argument
        $response = $this->registerAndRun('make-vo');

        // Assert: Verify invalid argument error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('name', $response->output);
    }

    public function test_execute_creates_vo_file(): void
    {
        // Arrange: Prepare VO name
        $voName = 'EmailAddressVO';

        // Act: Run the directive to create the VO file
        $response = $this->registerAndRun('make-vo', [$voName]);

        $expectedPath = $this->directiveTempDir . '/app/ValueObjects/EmailAddressVO.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('value-object created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class EmailAddressVO', $content);
        $this->assertStringContainsString('extends AbstractValueObject', $content);
        $this->assertStringContainsString('namespace App\\ValueObjects', $content);
    }

    public function test_execute_creates_vo_in_subdirectory(): void
    {
        // Arrange: Prepare VO name with subdirectory
        $voName = 'User/EmailAddressVO';

        // Act: Run the directive to create the VO file
        $response = $this->registerAndRun('make-vo', [$voName]);

        $expectedPath = $this->directiveTempDir . '/app/ValueObjects/User/EmailAddressVO.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and correct namespace
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('value-object created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\ValueObjects\\User', $content);
        $this->assertStringContainsString('class EmailAddressVO', $content);
    }

    public function test_execute_adds_vo_suffix_automatically(): void
    {
        // Arrange: Prepare VO name without suffix
        $voName = 'EmailAddress';

        // Act: Run the directive to create the VO file
        $response = $this->registerAndRun('make-vo', [$voName]);

        $expectedPath = $this->directiveTempDir . '/app/ValueObjects/EmailAddressVO.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix was added automatically
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('value-object created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class EmailAddressVO', $content);
    }

    public function test_prevents_duplicate_file_creation(): void
    {
        // Arrange: First creation
        $voName = 'DuplicateVO';

        // Act: First run (should succeed)
        $firstResponse = $this->registerAndRun('make-vo', [$voName]);

        // Assert: Verify first creation succeeded
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exitCode);
        $this->assertStringContainsString('value-object created successfully!', strtolower($firstResponse->output));

        // Act: Second run with same name (should fail)
        $secondResponse = $this->registerAndRun('make-vo', [$voName]);

        // Assert: Verify failure message
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exitCode);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }
}
