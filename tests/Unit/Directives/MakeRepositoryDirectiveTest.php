<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective;
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

    private function getDirective(): MakeRepositoryDirective
    {
        return new MakeRepositoryDirective($this->interaction);
    }

    private function registerAndRun(string $signature, array $arguments = []): DirectiveResponseRecord
    {
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        return $this->runDirective($signature, $arguments);
    }

    public function test_get_signature_returns_make_repository(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the signature
        $signature = $directive->getSignature();

        // Assert: Verify the signature is correct
        $this->assertSame('make-repository {name}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the description
        $description = $directive->getDescription();

        // Assert: Verify the description is correct
        $this->assertSame('Create a new repository class', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the aliases
        $aliases = $directive->getAliases();

        // Assert: Verify the aliases are correct
        $this->assertTrue($aliases->contains('create-repository'));
        $this->assertTrue($aliases->contains('make-repo'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act: Run the directive without name argument
        $response = $this->registerAndRun('make-repository');

        // Assert: Verify invalid argument error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_execute_creates_repository_file(): void
    {
        // Arrange: Prepare repository name
        $repositoryName = 'user';

        // Act: Run the directive to create the repository file
        $response = $this->registerAndRun('make-repository', [$repositoryName]);

        $expectedPath = $this->directiveTempDir.'/app/Repositories/UserRepository.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserRepository', $content);
        $this->assertStringContainsString('extends AbstractRepository', $content);
    }

    public function test_execute_creates_repository_in_subdirectory(): void
    {
        // Arrange: Prepare repository name with subdirectories
        $repositoryName = 'admin/user';

        // Act: Run the directive to create the repository file
        $response = $this->registerAndRun('make-repository', [$repositoryName]);

        $expectedPath = $this->directiveTempDir.'/app/Repositories/Admin/UserRepository.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and correct namespace
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('repository created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Repositories\\Admin', $content);
        $this->assertStringContainsString('class UserRepository', $content);
    }

    public function test_execute_adds_repository_suffix_automatically(): void
    {
        // Arrange: Prepare repository name without suffix
        $repositoryName = 'product';

        // Act: Run the directive to create the repository file
        $response = $this->registerAndRun('make-repository', [$repositoryName]);

        $expectedPath = $this->directiveTempDir.'/app/Repositories/ProductRepository.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix was added automatically
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProductRepository', $content);
    }

    public function test_execute_does_not_double_repository_suffix(): void
    {
        // Arrange: Prepare repository name that already has suffix
        $repositoryName = 'UserRepository';

        // Act: Run the directive to create the repository file
        $response = $this->registerAndRun('make-repository', [$repositoryName]);

        $expectedPath = $this->directiveTempDir.'/app/Repositories/UserRepository.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix is not duplicated
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserRepository', $content);
        $this->assertStringNotContainsString('UserRepositoryRepository', $content);
    }

    public function test_execute_creates_interface_name_correctly(): void
    {
        // Arrange: Prepare repository name
        $repositoryName = 'product-category';

        // Act: Run the directive to create the repository file
        $response = $this->registerAndRun('make-repository', [$repositoryName]);

        $expectedPath = $this->directiveTempDir.'/app/Repositories/ProductCategoryRepository.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify interface name is generated correctly
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProductCategoryRepository', $content);
    }
}
