<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeRecordDirectiveTest extends UnitTestCase
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

    private function getDirective(): MakeRecordDirective
    {
        return new MakeRecordDirective($this->interaction);
    }

    private function registerAndRun(string $signature, array $arguments = []): DirectiveResponseRecord
    {
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        return $this->runDirective($signature, $arguments);
    }

    public function test_get_signature_returns_make_record(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the signature
        $signature = $directive->getSignature();

        // Assert: Verify the signature is correct
        $this->assertSame('make-record {name}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the description
        $description = $directive->getDescription();

        // Assert: Verify the description is correct
        $this->assertSame('Create a new record class', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the aliases
        $aliases = $directive->getAliases();

        // Assert: Verify the aliases are correct
        $this->assertTrue($aliases->contains('create-record'));
        $this->assertTrue($aliases->contains('make-dto'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act: Run the directive without name argument
        $response = $this->registerAndRun('make-record');

        // Assert: Verify invalid argument error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_execute_creates_record_file(): void
    {
        // Arrange: Prepare record name
        $recordName = 'user-data';

        // Act: Run the directive to create the record file
        $response = $this->registerAndRun('make-record', [$recordName]);

        $expectedPath = $this->directiveTempDir.'/app/Records/UserDataRecord.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserDataRecord', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_execute_creates_record_in_subdirectory(): void
    {
        // Arrange: Prepare record name with subdirectories
        $recordName = 'api/user-data';

        // Act: Run the directive to create the record file
        $response = $this->registerAndRun('make-record', [$recordName]);

        $expectedPath = $this->directiveTempDir.'/app/Records/Api/UserDataRecord.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and correct namespace
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('record created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Records\\Api', $content);
        $this->assertStringContainsString('class UserDataRecord', $content);
    }

    public function test_execute_adds_record_suffix_automatically(): void
    {
        // Arrange: Prepare record name without suffix
        $recordName = 'product-data';

        // Act: Run the directive to create the record file
        $response = $this->registerAndRun('make-record', [$recordName]);

        $expectedPath = $this->directiveTempDir.'/app/Records/ProductDataRecord.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix was added automatically
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProductDataRecord', $content);
    }

    public function test_execute_does_not_double_record_suffix(): void
    {
        // Arrange: Prepare record name that already has suffix
        $recordName = 'UserDataRecord';

        // Act: Run the directive to create the record file
        $response = $this->registerAndRun('make-record', [$recordName]);

        $expectedPath = $this->directiveTempDir.'/app/Records/UserDataRecord.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix is not duplicated
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserDataRecord', $content);
        $this->assertStringNotContainsString('UserDataRecordRecord', $content);
    }

    public function test_execute_converts_kebab_case_to_pascal_case(): void
    {
        // Arrange: Prepare kebab-case record name
        $recordName = 'user-profile-data';

        // Act: Run the directive to create the record file
        $response = $this->registerAndRun('make-record', [$recordName]);

        $expectedPath = $this->directiveTempDir.'/app/Records/UserProfileDataRecord.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify conversion to PascalCase
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserProfileDataRecord', $content);
    }
}
