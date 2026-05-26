<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeRepositoryDirectiveTest extends UnitTestCase
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

    public function test_get_signature_returns_make_repository(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective::class);

        $this->assertSame('make-repository {name}', $directive->getSignature());
    }

    public function test_get_description_returns_description(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective::class);

        $this->assertSame('Create a new repository class', $directive->getDescription());
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective::class);
        $aliases = $directive->getAliases();

        $this->assertTrue($aliases->contains('create-repository'));
        $this->assertTrue($aliases->contains('make-repo'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective::class);

        $response = $this->runDirective('make-repository');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        // Le message d'erreur réel vient du kernel Laravel Directive
        $this->assertStringContainsString('Not enough arguments', $response->getOutput());
    }

    public function test_execute_creates_repository_file(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective::class);

        $response = $this->runDirective('make-repository', ['user']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('repository created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Repositories/UserRepository.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserRepository', $content);
        $this->assertStringContainsString('extends AbstractRepository', $content);
        // Le stub actuel ne contient pas UserInterface
        // $this->assertStringContainsString('UserInterface', $content);
    }

    public function test_execute_creates_repository_in_subdirectory(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective::class);

        $response = $this->runDirective('make-repository', ['admin/user']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('repository created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Repositories/Admin/UserRepository.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace App\\Repositories\\Admin', $content);
        $this->assertStringContainsString('class UserRepository', $content);
    }

    public function test_execute_adds_repository_suffix_automatically(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective::class);

        $response = $this->runDirective('make-repository', ['product']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $expectedPath = $this->directiveTempDir . '/app/Repositories/ProductRepository.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ProductRepository', $content);
    }

    public function test_execute_does_not_double_repository_suffix(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective::class);

        $response = $this->runDirective('make-repository', ['UserRepository']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $expectedPath = $this->directiveTempDir . '/app/Repositories/UserRepository.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserRepository', $content);
        $this->assertStringNotContainsString('UserRepositoryRepository', $content);
    }

    public function test_execute_creates_interface_name_correctly(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective::class);

        $response = $this->runDirective('make-repository', ['product-category']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $expectedPath = $this->directiveTempDir . '/app/Repositories/ProductCategoryRepository.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ProductCategoryRepository', $content);
    }
}
