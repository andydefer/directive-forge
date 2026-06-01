<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Services\DirectiveNamingService;
use AndyDefer\Directive\Services\SignatureValidationService;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeDirective;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeDirectiveTest extends UnitTestCase
{
    use InteractsWithDirectives;

    private SignatureValidationService $signatureValidator;

    private DirectiveNamingService $namingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDirectiveTesting();

        // Initialize services needed for the directive
        $this->signatureValidator = new SignatureValidationService;
        $this->namingService = new DirectiveNamingService;
    }

    protected function tearDown(): void
    {
        $this->destroyDirectiveTesting();
        parent::tearDown();
    }

    private function getDirective(): MakeDirective
    {
        return new MakeDirective(
            $this->interaction,
            $this->signatureValidator,
            $this->namingService
        );
    }

    private function registerAndRun(string $signature, array $arguments = []): DirectiveResponseRecord
    {
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        return $this->runDirective($signature, $arguments);
    }

    public function test_get_signature_returns_make_directive(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the signature
        $signature = $directive->getSignature();

        // Assert: Verify the signature is correct
        $this->assertSame('make-directive {name}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the description
        $description = $directive->getDescription();

        // Assert: Verify the description is correct
        $this->assertSame('Create a new directive class', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the aliases
        $aliases = $directive->getAliases();

        // Assert: Verify the aliases are correct
        $this->assertTrue($aliases->contains('create-directive'));
        $this->assertTrue($aliases->contains('make-cmd'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act: Run the directive without name argument
        $response = $this->registerAndRun('make-directive');

        // Assert: Verify invalid argument error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_execute_creates_file_with_correct_replacements(): void
    {
        // Arrange: Prepare directive name
        $directiveName = 'user-list';

        // Act: Run the directive to create the directive file
        $response = $this->registerAndRun('make-directive', [$directiveName]);

        $expectedPath = $this->directiveTempDir . '/app/Directives/UserListDirective.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserListDirective', $content);
        $this->assertStringContainsString("return 'user-list'", $content);
        $this->assertStringContainsString('namespace App\\Directives;', $content);
    }

    public function test_execute_creates_file_in_subdirectory(): void
    {
        // Arrange: Prepare directive name with subdirectories
        $directiveName = 'user/domain/hello-directive';

        // Act: Run the directive to create the directive file
        $response = $this->registerAndRun('make-directive', [$directiveName]);

        $expectedPath = $this->directiveTempDir . '/app/Directives/User/Domain/HelloDirective.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and correct namespace
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertDirectoryExists($this->directiveTempDir . '/app/Directives/User/Domain');
        $this->assertStringContainsString('namespace App\\Directives\\User\\Domain;', $content);
        $this->assertStringContainsString('class HelloDirective', $content);
        $this->assertStringContainsString("return 'hello'", $content);
    }

    public function test_execute_adds_directive_suffix_automatically(): void
    {
        // Arrange: Prepare directive name without suffix
        $directiveName = 'hello';

        // Act: Run the directive to create the directive file
        $response = $this->registerAndRun('make-directive', [$directiveName]);

        $expectedPath = $this->directiveTempDir . '/app/Directives/HelloDirective.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix was added automatically
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class HelloDirective', $content);
        $this->assertStringNotContainsString('HelloDirectiveDirective', $content);
        $this->assertStringContainsString("return 'hello'", $content);
    }

    public function test_execute_does_not_double_directive_suffix(): void
    {
        // Arrange: Prepare directive name that already has suffix
        $directiveName = 'hello-directive';

        // Act: Run the directive to create the directive file
        $response = $this->registerAndRun('make-directive', [$directiveName]);

        $expectedPath = $this->directiveTempDir . '/app/Directives/HelloDirective.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix is not duplicated
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class HelloDirective', $content);
        $this->assertStringNotContainsString('HelloDirectiveDirective', $content);
        $this->assertStringContainsString("return 'hello'", $content);
    }

    public function test_execute_rejects_invalid_directive_name(): void
    {
        // Arrange: Prepare invalid directive name with @ symbol
        $invalidName = 'user@list';

        // Act: Run the directive with invalid name
        $response = $this->registerAndRun('make-directive', [$invalidName]);

        // Assert: Verify invalid argument error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Invalid directive name', $response->output);
    }
}
