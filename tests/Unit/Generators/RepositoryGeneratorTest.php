<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\AbstractGenerator;
use AndyDefer\DirectiveForge\Generators\RepositoryGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
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

    private function createPathInfo(string $className, string $subPath = '', array $segments = []): PathInfo
    {
        $segmentsCollection = new ScalarTypedCollection;
        if (! empty($segments)) {
            $segmentsCollection->add(...$segments);
        }

        return PathInfo::from([
            'className' => $className,
            'subPath' => $subPath,
            'segments' => $segmentsCollection,
        ]);
    }

    private function assertReplacementHasKey(ReplacementCollection $replacements, string $key, string $expectedValue): void
    {
        $array = $replacements->toAssociativeArray();
        $this->assertArrayHasKey($key, $array);
        $this->assertSame($expectedValue, $array[$key]);
    }

    private function assertReplacementNotHasKey(ReplacementCollection $replacements, string $key): void
    {
        $array = $replacements->toAssociativeArray();
        $this->assertArrayNotHasKey($key, $array);
    }

    public function test_get_replacements_with_simple_name(): void
    {
        // Arrange: Create path info with simple name
        $pathInfo = $this->createPathInfo('user', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify replacement structure
        $this->assertArrayHasKey('{{namespace}}', $array);
        $this->assertArrayHasKey('{{class}}', $array);
        $this->assertArrayHasKey('{{interface}}', $array);
        $this->assertSame('App\\Repositories', $array['{{namespace}}']);
        $this->assertSame('user', $array['{{class}}']);
        $this->assertSame('userInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_with_full_repository_name(): void
    {
        // Arrange: Create path info with full repository name
        $pathInfo = $this->createPathInfo('UserRepository', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify class and interface names
        $this->assertSame('UserRepository', $array['{{class}}']);
        $this->assertSame('UserInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        // Arrange: Create path info with subdirectory
        $pathInfo = $this->createPathInfo('user', 'Admin', ['Admin']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify namespace includes subdirectory
        $this->assertSame('App\\Repositories\\Admin', $array['{{namespace}}']);
        $this->assertSame('user', $array['{{class}}']);
        $this->assertSame('userInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_with_nested_subdirectories(): void
    {
        // Arrange: Create path info with nested subdirectories
        $pathInfo = $this->createPathInfo('order', 'Shop\\Checkout', ['Shop', 'Checkout']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify nested namespace
        $this->assertSame('App\\Repositories\\Shop\\Checkout', $array['{{namespace}}']);
        $this->assertSame('order', $array['{{class}}']);
        $this->assertSame('orderInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_preserves_kebab_case(): void
    {
        // Arrange: Create path info with kebab-case
        $pathInfo = $this->createPathInfo('user-profile', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify kebab-case preserved
        $this->assertSame('user-profile', $array['{{class}}']);
        $this->assertSame('user-profileInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_preserves_snake_case(): void
    {
        // Arrange: Create path info with snake_case
        $pathInfo = $this->createPathInfo('user_profile', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify snake_case preserved
        $this->assertSame('user_profile', $array['{{class}}']);
        $this->assertSame('user_profileInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_preserves_existing_pascal_case(): void
    {
        // Arrange: Create path info with PascalCase
        $pathInfo = $this->createPathInfo('ShowUser', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify PascalCase preserved
        $this->assertSame('ShowUser', $array['{{class}}']);
        $this->assertSame('ShowUserInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_with_model_name_in_subdirectory(): void
    {
        // Arrange: Create path info with model in subdirectory
        $pathInfo = $this->createPathInfo('product', 'Catalog\\Products', ['Catalog', 'Products']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify namespace and names
        $this->assertSame('App\\Repositories\\Catalog\\Products', $array['{{namespace}}']);
        $this->assertSame('product', $array['{{class}}']);
        $this->assertSame('productInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_complex_model_name(): void
    {
        // Arrange: Create path info with complex model name
        $pathInfo = $this->createPathInfo('email-template', 'Admin\\Settings', ['Admin', 'Settings']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify complex name preserved
        $this->assertSame('email-template', $array['{{class}}']);
        $this->assertSame('email-templateInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_with_numbers_in_name(): void
    {
        // Arrange: Create path info with numbers
        $pathInfo = $this->createPathInfo('user2fa', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify numbers preserved
        $this->assertSame('user2fa', $array['{{class}}']);
        $this->assertSame('user2faInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_with_underscores_and_hyphens_mixed(): void
    {
        // Arrange: Create path info with mixed separators
        $pathInfo = $this->createPathInfo('user-profile-data', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify mixed separators preserved
        $this->assertSame('user-profile-data', $array['{{class}}']);
        $this->assertSame('user-profile-dataInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_interface_name_derived_correctly_from_class(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('product-category', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify interface name derivation
        $this->assertSame('product-category', $array['{{class}}']);
        $this->assertSame('product-categoryInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_interface_name_for_single_word(): void
    {
        // Arrange: Create path info with single word
        $pathInfo = $this->createPathInfo('role', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify interface name for single word
        $this->assertSame('role', $array['{{class}}']);
        $this->assertSame('roleInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_with_uppercase_acronyms(): void
    {
        // Arrange: Create path info with uppercase acronyms
        $pathInfo = $this->createPathInfo('APIKey', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify acronyms preserved
        $this->assertSame('APIKey', $array['{{class}}']);
        $this->assertSame('APIKeyInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_with_mixed_case_acronyms(): void
    {
        // Arrange: Create path info with mixed case acronyms
        $pathInfo = $this->createPathInfo('user-XML-data', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify mixed case preserved
        $this->assertSame('user-XML-data', $array['{{class}}']);
        $this->assertSame('user-XML-dataInterface', $array['{{interface}}']);
    }

    public function test_get_replacements_with_deeply_nested_path(): void
    {
        // Arrange: Create path info with deeply nested path
        $pathInfo = $this->createPathInfo(
            'order-summary',
            'Api\\V2\\Shop\\Checkout\\Summary',
            ['Api', 'V2', 'Shop', 'Checkout', 'Summary']
        );

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify deeply nested namespace
        $this->assertSame('App\\Repositories\\Api\\V2\\Shop\\Checkout\\Summary', $array['{{namespace}}']);
        $this->assertSame('order-summary', $array['{{class}}']);
        $this->assertSame('order-summaryInterface', $array['{{interface}}']);
    }

    public function test_repository_generator_has_correct_type(): void
    {
        // Act: Get generator type
        $type = $this->generator->getType();

        // Assert: Verify type
        $this->assertSame('repository', $type->value);
    }

    public function test_get_replacements_returns_collection_with_correct_keys(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('test', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $keys = array_keys($replacements->toAssociativeArray());

        // Assert: Verify correct keys
        $this->assertContains('{{namespace}}', $keys);
        $this->assertContains('{{class}}', $keys);
        $this->assertContains('{{interface}}', $keys);
        $this->assertCount(3, $keys);
    }

    public function test_get_replacements_never_contains_extra_placeholders(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('test', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);

        // Assert: Verify no extra placeholders
        $this->assertReplacementNotHasKey($replacements, '{{signature}}');
        $this->assertReplacementNotHasKey($replacements, '{{description}}');
        $this->assertReplacementNotHasKey($replacements, '{{type}}');
        $this->assertReplacementNotHasKey($replacements, '{{item_type}}');
    }

    public function test_multiple_repository_generations_produce_consistent_results(): void
    {
        // Arrange: Create two identical path info objects
        $pathInfo1 = $this->createPathInfo('user-data', '', []);
        $pathInfo2 = $this->createPathInfo('user-data', '', []);

        // Act: Get replacements from both
        $replacements1 = $this->generator->getReplacements($pathInfo1);
        $replacements2 = $this->generator->getReplacements($pathInfo2);

        // Assert: Verify results are identical
        $this->assertSame(
            $replacements1->toAssociativeArray(),
            $replacements2->toAssociativeArray()
        );
    }

    public function test_interface_name_removes_repository_suffix_correctly(): void
    {
        // Arrange: Create path info with Repository suffix
        $pathInfo = $this->createPathInfo('UserRepository', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify Repository suffix removed for interface
        $this->assertSame('UserRepository', $array['{{class}}']);
        $this->assertSame('UserInterface', $array['{{interface}}']);
    }

    public function test_interface_name_with_multiple_words_removes_suffix(): void
    {
        // Arrange: Create path info with multi-word name
        $pathInfo = $this->createPathInfo('ProductCategoryRepository', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify suffix removed
        $this->assertSame('ProductCategoryRepository', $array['{{class}}']);
        $this->assertSame('ProductCategoryInterface', $array['{{interface}}']);
    }

    public function test_interface_name_preserves_case(): void
    {
        // Arrange: Create path info with acronyms
        $pathInfo = $this->createPathInfo('APIKeyRepository', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify case preserved
        $this->assertSame('APIKeyRepository', $array['{{class}}']);
        $this->assertSame('APIKeyInterface', $array['{{interface}}']);
    }

    public function test_generator_extends_abstract_generator(): void
    {
        // Assert: Verify inheritance
        $this->assertInstanceOf(AbstractGenerator::class, $this->generator);
    }

    public function test_generator_has_correct_config(): void
    {
        // Act: Get generator config
        $config = $this->generator->getType()->getConfig();

        // Assert: Verify config values
        $this->assertSame('repository', $config->type->value);
        $this->assertSame('/app/Repositories/', $config->basePath);
        $this->assertSame('App\\Repositories', $config->baseNamespace);
        $this->assertStringContainsString('repository.stub', $config->stubPath);
        $this->assertSame('Repository', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_generator_returns_correct_type(): void
    {
        // Act: Get generator type value
        $typeValue = $this->generator->getType()->value;

        // Assert: Verify type value
        $this->assertSame('repository', $typeValue);
    }
}
