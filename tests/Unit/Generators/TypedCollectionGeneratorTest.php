<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\AbstractGenerator;
use AndyDefer\DirectiveForge\Generators\TypedCollectionGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class TypedCollectionGeneratorTest extends UnitTestCase
{
    private TypedCollectionGenerator $generator;

    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new TypedCollectionGenerator($this->interaction);
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

    public function test_get_replacements_with_simple_name_and_string_type(): void
    {
        // Arrange: Create path info with simple name and string type
        $pathInfo = $this->createPathInfo('user-collection', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'string');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify replacement structure
        $this->assertArrayHasKey('{{namespace}}', $array);
        $this->assertArrayHasKey('{{class}}', $array);
        $this->assertArrayHasKey('{{type}}', $array);
        $this->assertSame('App\\Collections', $array['{{namespace}}']);
        $this->assertSame('user-collection', $array['{{class}}']);
        $this->assertSame('string', $array['{{type}}']);
    }

    public function test_get_replacements_with_simple_name_and_record_type(): void
    {
        // Arrange: Create path info with simple name and record type
        $pathInfo = $this->createPathInfo('user-collection', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify type is preserved
        $this->assertSame('user-collection', $array['{{class}}']);
        $this->assertSame('UserRecord', $array['{{type}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        // Arrange: Create path info with subdirectory
        $pathInfo = $this->createPathInfo('user-collection', 'Admin', ['Admin']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify namespace includes subdirectory
        $this->assertSame('App\\Collections\\Admin', $array['{{namespace}}']);
        $this->assertSame('user-collection', $array['{{class}}']);
        $this->assertSame('UserRecord', $array['{{type}}']);
    }

    public function test_get_replacements_with_nested_subdirectories(): void
    {
        // Arrange: Create path info with nested subdirectories
        $pathInfo = $this->createPathInfo('order-collection', 'Shop\\Checkout', ['Shop', 'Checkout']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'OrderItemRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify nested namespace
        $this->assertSame('App\\Collections\\Shop\\Checkout', $array['{{namespace}}']);
        $this->assertSame('order-collection', $array['{{class}}']);
        $this->assertSame('OrderItemRecord', $array['{{type}}']);
    }

    public function test_get_replacements_when_class_already_has_suffix(): void
    {
        // Arrange: Create path info with class that already has Collection suffix
        $pathInfo = $this->createPathInfo('UserCollection', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify class name preserved
        $this->assertSame('UserCollection', $array['{{class}}']);
        $this->assertStringEndsWith('Collection', $array['{{class}}']);
    }

    public function test_get_replacements_preserves_kebab_case(): void
    {
        // Arrange: Create path info with kebab-case
        $pathInfo = $this->createPathInfo('user-profile-collection', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'ProfileRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify kebab-case preserved
        $this->assertSame('user-profile-collection', $array['{{class}}']);
    }

    public function test_get_replacements_preserves_snake_case(): void
    {
        // Arrange: Create path info with snake_case
        $pathInfo = $this->createPathInfo('user_profile_collection', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'ProfileRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify snake_case preserved
        $this->assertSame('user_profile_collection', $array['{{class}}']);
    }

    public function test_get_replacements_preserves_existing_pascal_case(): void
    {
        // Arrange: Create path info with PascalCase
        $pathInfo = $this->createPathInfo('ShowUserCollection', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify PascalCase preserved
        $this->assertSame('ShowUserCollection', $array['{{class}}']);
    }

    public function test_get_replacements_with_numbers_in_name(): void
    {
        // Arrange: Create path info with numbers
        $pathInfo = $this->createPathInfo('user-2fa-collection', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'User2faRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify numbers preserved
        $this->assertSame('user-2fa-collection', $array['{{class}}']);
    }

    public function test_get_replacements_with_uppercase_acronyms(): void
    {
        // Arrange: Create path info with uppercase acronyms
        $pathInfo = $this->createPathInfo('API-key-collection', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'APIKeyRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify acronyms preserved
        $this->assertSame('API-key-collection', $array['{{class}}']);
    }

    public function test_get_replacements_with_mixed_case_acronyms(): void
    {
        // Arrange: Create path info with mixed case acronyms
        $pathInfo = $this->createPathInfo('user-XML-collection', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserXMLRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify mixed case preserved
        $this->assertSame('user-XML-collection', $array['{{class}}']);
    }

    public function test_get_replacements_with_complex_class_name(): void
    {
        // Arrange: Create path info with complex name
        $pathInfo = $this->createPathInfo('active-user-collection', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'ActiveUserRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify complex name preserved
        $this->assertSame('active-user-collection', $array['{{class}}']);
    }

    public function test_get_replacements_with_deeply_nested_path(): void
    {
        // Arrange: Create path info with deeply nested path
        $pathInfo = $this->createPathInfo(
            'order-collection',
            'Api\\V2\\Shop\\Checkout',
            ['Api', 'V2', 'Shop', 'Checkout']
        );

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'OrderRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify deeply nested namespace
        $this->assertSame('App\\Collections\\Api\\V2\\Shop\\Checkout', $array['{{namespace}}']);
        $this->assertSame('order-collection', $array['{{class}}']);
    }

    public function test_get_replacements_without_item_type_uses_default(): void
    {
        // Arrange: Create path info without item type
        $pathInfo = $this->createPathInfo('user-collection', '', []);

        // Act: Get replacements without item type
        $replacements = $this->generator->getReplacements($pathInfo, null, null);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify default type is string
        $this->assertSame('string', $array['{{type}}']);
    }

    public function test_get_replacements_with_empty_item_type_uses_default(): void
    {
        // Arrange: Create path info with empty item type
        $pathInfo = $this->createPathInfo('user-collection', '', []);

        // Act: Get replacements with empty item type
        $replacements = $this->generator->getReplacements($pathInfo, null, '');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify default type is string
        $this->assertSame('string', $array['{{type}}']);
    }

    public function test_get_replacements_with_fully_qualified_type_name(): void
    {
        // Arrange: Create path info with FQCN type
        $pathInfo = $this->createPathInfo('user-collection', '', []);

        // Act: Get replacements with FQCN
        $replacements = $this->generator->getReplacements($pathInfo, null, 'App\\Records\\UserRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify FQCN preserved
        $this->assertSame('App\\Records\\UserRecord', $array['{{type}}']);
    }

    public function test_generator_has_correct_type(): void
    {
        // Act: Get generator type
        $type = $this->generator->getType();

        // Assert: Verify type
        $this->assertSame('typed-collection', $type->value);
    }

    public function test_get_replacements_returns_collection_with_correct_keys(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('test', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'string');
        $keys = array_keys($replacements->toAssociativeArray());

        // Assert: Verify correct keys
        $this->assertContains('{{namespace}}', $keys);
        $this->assertContains('{{class}}', $keys);
        $this->assertContains('{{type}}', $keys);
        $this->assertCount(3, $keys);
    }

    public function test_get_replacements_never_contains_extra_placeholders(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('test', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'string');

        // Assert: Verify no extra placeholders
        $this->assertReplacementNotHasKey($replacements, '{{signature}}');
        $this->assertReplacementNotHasKey($replacements, '{{description}}');
        $this->assertReplacementNotHasKey($replacements, '{{interface}}');
    }

    public function test_multiple_collection_generations_produce_consistent_results(): void
    {
        // Arrange: Create two identical path info objects
        $pathInfo1 = $this->createPathInfo('my-collection', '', []);
        $pathInfo2 = $this->createPathInfo('my-collection', '', []);

        // Act: Get replacements from both
        $replacements1 = $this->generator->getReplacements($pathInfo1, null, 'MyRecord');
        $replacements2 = $this->generator->getReplacements($pathInfo2, null, 'MyRecord');

        // Assert: Verify results are identical
        $this->assertSame(
            $replacements1->toAssociativeArray(),
            $replacements2->toAssociativeArray()
        );
    }

    public function test_get_replacements_with_single_word_name(): void
    {
        // Arrange: Create path info with single word
        $pathInfo = $this->createPathInfo('user', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify single word preserved
        $this->assertSame('user', $array['{{class}}']);
    }

    public function test_get_replacements_with_single_word_already_has_suffix(): void
    {
        // Arrange: Create path info with single word that has suffix
        $pathInfo = $this->createPathInfo('UserCollection', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify class name preserved
        $this->assertSame('UserCollection', $array['{{class}}']);
    }

    public function test_get_replacements_with_php_primitive_type(): void
    {
        // Arrange: Create path info with PHP primitive type
        $pathInfo = $this->createPathInfo('int-collection', '', []);

        // Act: Get replacements with int type
        $replacements = $this->generator->getReplacements($pathInfo, null, 'int');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify int type
        $this->assertSame('int', $array['{{type}}']);
        $this->assertSame('int-collection', $array['{{class}}']);
    }

    public function test_get_replacements_with_array_type(): void
    {
        // Arrange: Create path info with array type
        $pathInfo = $this->createPathInfo('array-collection', '', []);

        // Act: Get replacements with array type
        $replacements = $this->generator->getReplacements($pathInfo, null, 'array');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify array type
        $this->assertSame('array', $array['{{type}}']);
        $this->assertSame('array-collection', $array['{{class}}']);
    }

    public function test_get_replacements_with_nullable_type(): void
    {
        // Arrange: Create path info with nullable type
        $pathInfo = $this->createPathInfo('optional-collection', '', []);

        // Act: Get replacements with nullable type
        $replacements = $this->generator->getReplacements($pathInfo, null, '?UserRecord');
        $array = $replacements->toAssociativeArray();

        // Assert: Verify nullable type preserved
        $this->assertSame('?UserRecord', $array['{{type}}']);
        $this->assertSame('optional-collection', $array['{{class}}']);
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
        $this->assertSame('typed-collection', $config->type->value);
        $this->assertSame('/app/Collections/', $config->basePath);
        $this->assertSame('App\\Collections', $config->baseNamespace);
        $this->assertSame('Collection', $config->suffix);
        $this->assertTrue($config->requiresType);
    }
}
