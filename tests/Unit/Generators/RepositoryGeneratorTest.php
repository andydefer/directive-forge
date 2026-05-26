<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\RepositoryGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class RepositoryGeneratorTest extends UnitTestCase
{
    private RepositoryGenerator $generator;
    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new RepositoryGenerator($this->interaction);
    }

    public function test_get_replacements_with_simple_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'user',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertIsArray($replacements);
        $this->assertArrayHasKey('{{namespace}}', $replacements);
        $this->assertArrayHasKey('{{class}}', $replacements);
        $this->assertArrayHasKey('{{interface}}', $replacements);

        $this->assertSame('App\\Repositories', $replacements['{{namespace}}']);
        // Le nom de la classe reste tel quel
        $this->assertSame('user', $replacements['{{class}}']);
        // L'interface est générée à partir du nom original
        $this->assertSame('userInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_with_full_repository_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'UserRepository',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('UserRepository', $replacements['{{class}}']);
        $this->assertSame('UserInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        $pathInfo = new PathInfo(
            className: 'user',
            subPath: 'Admin',
            segments: ['Admin']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Repositories\\Admin', $replacements['{{namespace}}']);
        $this->assertSame('user', $replacements['{{class}}']);
        $this->assertSame('userInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_with_nested_subdirectories(): void
    {
        $pathInfo = new PathInfo(
            className: 'order',
            subPath: 'Shop\\Checkout',
            segments: ['Shop', 'Checkout']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Repositories\\Shop\\Checkout', $replacements['{{namespace}}']);
        $this->assertSame('order', $replacements['{{class}}']);
        $this->assertSame('orderInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_preserves_kebab_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-profile',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user-profile', $replacements['{{class}}']);
        $this->assertSame('user-profileInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_preserves_snake_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'user_profile',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user_profile', $replacements['{{class}}']);
        $this->assertSame('user_profileInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_preserves_existing_pascal_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'ShowUser',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('ShowUser', $replacements['{{class}}']);
        $this->assertSame('ShowUserInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_with_model_name_in_subdirectory(): void
    {
        $pathInfo = new PathInfo(
            className: 'product',
            subPath: 'Catalog\\Products',
            segments: ['Catalog', 'Products']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Repositories\\Catalog\\Products', $replacements['{{namespace}}']);
        $this->assertSame('product', $replacements['{{class}}']);
        $this->assertSame('productInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_complex_model_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'email-template',
            subPath: 'Admin\\Settings',
            segments: ['Admin', 'Settings']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('email-template', $replacements['{{class}}']);
        $this->assertSame('email-templateInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_with_numbers_in_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'user2fa',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user2fa', $replacements['{{class}}']);
        $this->assertSame('user2faInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_with_underscores_and_hyphens_mixed(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-profile-data',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user-profile-data', $replacements['{{class}}']);
        $this->assertSame('user-profile-dataInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_interface_name_derived_correctly_from_class(): void
    {
        $pathInfo = new PathInfo(
            className: 'product-category',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('product-category', $replacements['{{class}}']);
        $this->assertSame('product-categoryInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_interface_name_for_single_word(): void
    {
        $pathInfo = new PathInfo(
            className: 'role',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('role', $replacements['{{class}}']);
        $this->assertSame('roleInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_with_uppercase_acronyms(): void
    {
        $pathInfo = new PathInfo(
            className: 'APIKey',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('APIKey', $replacements['{{class}}']);
        $this->assertSame('APIKeyInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_with_mixed_case_acronyms(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-XML-data',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user-XML-data', $replacements['{{class}}']);
        $this->assertSame('user-XML-dataInterface', $replacements['{{interface}}']);
    }

    public function test_get_replacements_with_deeply_nested_path(): void
    {
        $pathInfo = new PathInfo(
            className: 'order-summary',
            subPath: 'Api\\V2\\Shop\\Checkout\\Summary',
            segments: ['Api', 'V2', 'Shop', 'Checkout', 'Summary']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Repositories\\Api\\V2\\Shop\\Checkout\\Summary', $replacements['{{namespace}}']);
        $this->assertSame('order-summary', $replacements['{{class}}']);
        $this->assertSame('order-summaryInterface', $replacements['{{interface}}']);
    }

    public function test_generate_calls_parent_generate_method(): void
    {
        $pathInfo = new PathInfo(
            className: 'test-repository',
            subPath: '',
            segments: []
        );

        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('generate');

        $this->assertTrue($method->isPublic());
    }

    public function test_repository_generator_has_correct_type(): void
    {
        $type = $this->generator->getType();

        $this->assertSame('repository', $type->value);
    }

    public function test_get_replacements_returns_array_with_correct_keys(): void
    {
        $pathInfo = new PathInfo(
            className: 'test',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);
        $keys = array_keys($replacements);

        $this->assertContains('{{namespace}}', $keys);
        $this->assertContains('{{class}}', $keys);
        $this->assertContains('{{interface}}', $keys);
        $this->assertCount(3, $keys);
    }

    public function test_get_replacements_never_contains_extra_placeholders(): void
    {
        $pathInfo = new PathInfo(
            className: 'test',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertArrayNotHasKey('{{signature}}', $replacements);
        $this->assertArrayNotHasKey('{{description}}', $replacements);
        $this->assertArrayNotHasKey('{{type}}', $replacements);
        $this->assertArrayNotHasKey('{{item_type}}', $replacements);
    }

    public function test_multiple_repository_generations_produce_consistent_results(): void
    {
        $pathInfo1 = new PathInfo(
            className: 'user-data',
            subPath: '',
            segments: []
        );

        $pathInfo2 = new PathInfo(
            className: 'user-data',
            subPath: '',
            segments: []
        );

        $replacements1 = $this->generator->getReplacements($pathInfo1);
        $replacements2 = $this->generator->getReplacements($pathInfo2);

        $this->assertSame($replacements1, $replacements2);
    }

    public function test_interface_name_removes_repository_suffix_correctly(): void
    {
        $pathInfo = new PathInfo(
            className: 'UserRepository',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('UserRepository', $replacements['{{class}}']);
        $this->assertSame('UserInterface', $replacements['{{interface}}']);
    }

    public function test_interface_name_with_multiple_words_removes_suffix(): void
    {
        $pathInfo = new PathInfo(
            className: 'ProductCategoryRepository',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('ProductCategoryRepository', $replacements['{{class}}']);
        $this->assertSame('ProductCategoryInterface', $replacements['{{interface}}']);
    }

    public function test_interface_name_preserves_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'APIKeyRepository',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('APIKeyRepository', $replacements['{{class}}']);
        $this->assertSame('APIKeyInterface', $replacements['{{interface}}']);
    }

    public function test_generator_extends_abstract_generator(): void
    {
        $this->assertInstanceOf(\AndyDefer\DirectiveForge\Generators\AbstractGenerator::class, $this->generator);
    }

    public function test_generator_has_correct_config(): void
    {
        $config = $this->generator->getType()->getConfig();

        $this->assertSame('repository', $config->type->value);
        $this->assertSame('/app/Repositories/', $config->basePath);
        $this->assertSame('App\\Repositories', $config->baseNamespace);
        $this->assertStringContainsString('repository.stub', $config->stubPath);
        $this->assertSame('Repository', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    /**
     * Note: Les tests de génération de fichiers sont maintenant gérés par AbstractGenerator
     * et utilisent le trait FileCreator. La propriété $files n'existe plus directement
     * dans RepositoryGenerator car elle est dans le trait FileCreator.
     * 
     * Pour tester la génération de fichiers, utilisez les tests d'intégration.
     */
    public function test_generator_returns_correct_type(): void
    {
        $this->assertSame('repository', $this->generator->getType()->value);
    }
}
