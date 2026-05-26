<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeDirectiveTest extends UnitTestCase
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

    public function test_get_signature_returns_make_directive(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $this->assertSame('make-directive {name}', $directive->getSignature());
    }

    public function test_get_description_returns_description(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $this->assertSame('Create a new directive class', $directive->getDescription());
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);
        $aliases = $directive->getAliases();

        $this->assertTrue($aliases->contains('create-directive'));
        $this->assertTrue($aliases->contains('make-cmd'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $response = $this->runDirective('make-directive');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        // Le message d'erreur réel vient du kernel Laravel Directive
        $this->assertStringContainsString('Not enough arguments', $response->getOutput());
    }

    public function test_execute_creates_file_with_correct_replacements(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $response = $this->runDirective('make-directive', ['user-list']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('directive created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Directives/UserListDirective.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserListDirective', $content);
        $this->assertStringContainsString("return 'user-list'", $content);
        $this->assertStringContainsString('namespace App\\Directives;', $content);
    }

    public function test_execute_creates_file_in_subdirectory(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $response = $this->runDirective('make-directive', ['user/domain/hello-directive']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('directive created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Directives/User/Domain/HelloDirective.php';
        $this->assertFileExists($expectedPath);
        $this->assertDirectoryExists($this->directiveTempDir . '/app/Directives/User/Domain');

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace App\\Directives\\User\\Domain;', $content);
        $this->assertStringContainsString('class HelloDirective', $content);
        // La signature est extraite du nom de classe HelloDirective -> hello
        $this->assertStringContainsString("return 'hello'", $content);
    }

    public function test_execute_adds_directive_suffix_automatically(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $response = $this->runDirective('make-directive', ['hello']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('directive created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Directives/HelloDirective.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class HelloDirective', $content);
        $this->assertStringNotContainsString('HelloDirectiveDirective', $content);
        // hello -> HelloDirective, signature = hello
        $this->assertStringContainsString("return 'hello'", $content);
    }

    public function test_execute_does_not_double_directive_suffix(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $response = $this->runDirective('make-directive', ['hello-directive']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('directive created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Directives/HelloDirective.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class HelloDirective', $content);
        $this->assertStringNotContainsString('HelloDirectiveDirective', $content);
        // hello-directive -> HelloDirective, signature extraite = hello
        $this->assertStringContainsString("return 'hello'", $content);
    }

    public function test_execute_rejects_invalid_directive_name(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $response = $this->runDirective('make-directive', ['user@list']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        $this->assertStringContainsString('Invalid directive name', $response->getOutput());
    }
}
