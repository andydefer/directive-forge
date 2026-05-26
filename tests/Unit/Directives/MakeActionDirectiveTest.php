<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeActionDirectiveTest extends UnitTestCase
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

    public function test_get_signature_returns_make_action(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $this->assertSame('make-action {name} {--type=api}', $directive->getSignature());
    }

    public function test_get_description_returns_description(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $this->assertSame('Create a new action class', $directive->getDescription());
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);
        $aliases = $directive->getAliases();

        $this->assertTrue($aliases->contains('create-action'));
        $this->assertTrue($aliases->contains('make-act'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $response = $this->runDirective('make-action');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        $this->assertStringContainsString('Not enough arguments (missing: "name")', $response->getOutput());
    }

    public function test_execute_creates_action_with_api_type(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['user/show', '--type=api']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('action created successfully!', strtolower($response->getOutput()));

        // Le fichier est créé dans User/ShowAction.php (avec le sous-dossier User)
        $expectedPath = $this->directiveTempDir . '/app/Actions/User/ShowAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ShowAction', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
        $this->assertStringContainsString('JsonResponse', $content);
    }

    public function test_execute_creates_action_with_web_type(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['admin/dashboard', '--type=web']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('action created successfully!', strtolower($response->getOutput()));

        // Le fichier est créé dans Admin/DashboardAction.php
        $expectedPath = $this->directiveTempDir . '/app/Actions/Admin/DashboardAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class DashboardAction', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
        $this->assertStringContainsString('InertiaResponse', $content);
    }

    public function test_execute_creates_action_in_subdirectory(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['api/v1/users/show', '--type=api']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('action created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Actions/Api/V1/Users/ShowAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace App\\Actions\\Api\\V1\\Users', $content);
        $this->assertStringContainsString('class ShowAction', $content);
    }

    public function test_execute_rejects_invalid_type(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['test', '--type=invalid']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        $this->assertStringContainsString('Invalid type', $response->getOutput());
    }

    public function test_execute_uses_default_api_type_when_not_specified(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['user/profile']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('action created successfully!', strtolower($response->getOutput()));

        // Le fichier est créé dans User/ProfileAction.php (avec le sous-dossier User)
        $expectedPath = $this->directiveTempDir . '/app/Actions/User/ProfileAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ProfileAction', $content);
    }

    public function test_execute_adds_action_suffix_automatically(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['user/user-show']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $expectedPath = $this->directiveTempDir . '/app/Actions/User/UserShowAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserShowAction', $content);
    }

    public function test_execute_does_not_double_action_suffix(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['ShowUserAction']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $expectedPath = $this->directiveTempDir . '/app/Actions/ShowUserAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ShowUserAction', $content);
        $this->assertStringNotContainsString('ShowUserActionAction', $content);
    }
}
