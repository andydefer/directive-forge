<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
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

    public function test_get_signature_returns_make_record(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRecordDirective::class);

        $this->assertSame('make-record {name}', $directive->getSignature());
    }

    public function test_get_description_returns_description(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRecordDirective::class);

        $this->assertSame('Create a new record class', $directive->getDescription());
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRecordDirective::class);
        $aliases = $directive->getAliases();

        $this->assertTrue($aliases->contains('create-record'));
        $this->assertTrue($aliases->contains('make-dto'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRecordDirective::class);

        $response = $this->runDirective('make-record');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        // Le message d'erreur réel vient du kernel Laravel Directive
        $this->assertStringContainsString('Not enough arguments', $response->getOutput());
    }

    public function test_execute_creates_record_file(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRecordDirective::class);

        $response = $this->runDirective('make-record', ['user-data']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('record created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Records/UserDataRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserDataRecord', $content);
        $this->assertStringContainsString('extends AbstractRecord', $content);
    }

    public function test_execute_creates_record_in_subdirectory(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRecordDirective::class);

        $response = $this->runDirective('make-record', ['api/user-data']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('record created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Records/Api/UserDataRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace App\\Records\\Api', $content);
        $this->assertStringContainsString('class UserDataRecord', $content);
    }

    public function test_execute_adds_record_suffix_automatically(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRecordDirective::class);

        $response = $this->runDirective('make-record', ['product-data']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $expectedPath = $this->directiveTempDir . '/app/Records/ProductDataRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ProductDataRecord', $content);
    }

    public function test_execute_does_not_double_record_suffix(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRecordDirective::class);

        $response = $this->runDirective('make-record', ['UserDataRecord']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $expectedPath = $this->directiveTempDir . '/app/Records/UserDataRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserDataRecord', $content);
        $this->assertStringNotContainsString('UserDataRecordRecord', $content);
    }

    public function test_execute_converts_kebab_case_to_pascal_case(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRecordDirective::class);

        $response = $this->runDirective('make-record', ['user-profile-data']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $expectedPath = $this->directiveTempDir . '/app/Records/UserProfileDataRecord.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserProfileDataRecord', $content);
    }
}
