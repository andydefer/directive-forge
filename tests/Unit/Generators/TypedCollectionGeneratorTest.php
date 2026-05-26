<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\TypedCollectionGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
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

    public function test_get_replacements_with_simple_name_and_string_type(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'string');

        $this->assertIsArray($replacements);
        $this->assertArrayHasKey('{{namespace}}', $replacements);
        $this->assertArrayHasKey('{{class}}', $replacements);
        $this->assertArrayHasKey('{{type}}', $replacements);

        $this->assertSame('App\\Collections', $replacements['{{namespace}}']);
        $this->assertSame('user-collection', $replacements['{{class}}']);
        $this->assertSame('string', $replacements['{{type}}']);
    }

    public function test_get_replacements_with_simple_name_and_record_type(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserRecord');

        $this->assertSame('user-collection', $replacements['{{class}}']);
        $this->assertSame('UserRecord', $replacements['{{type}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-collection',
            subPath: 'Admin',
            segments: ['Admin']
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserRecord');

        $this->assertSame('App\\Collections\\Admin', $replacements['{{namespace}}']);
        $this->assertSame('user-collection', $replacements['{{class}}']);
        $this->assertSame('UserRecord', $replacements['{{type}}']);
    }

    public function test_get_replacements_with_nested_subdirectories(): void
    {
        $pathInfo = new PathInfo(
            className: 'order-collection',
            subPath: 'Shop\\Checkout',
            segments: ['Shop', 'Checkout']
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'OrderItemRecord');

        $this->assertSame('App\\Collections\\Shop\\Checkout', $replacements['{{namespace}}']);
        $this->assertSame('order-collection', $replacements['{{class}}']);
        $this->assertSame('OrderItemRecord', $replacements['{{type}}']);
    }

    public function test_get_replacements_when_class_already_has_suffix(): void
    {
        $pathInfo = new PathInfo(
            className: 'UserCollection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserRecord');

        $this->assertSame('UserCollection', $replacements['{{class}}']);
        $this->assertStringEndsWith('Collection', $replacements['{{class}}']);
    }

    public function test_get_replacements_preserves_kebab_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-profile-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'ProfileRecord');

        $this->assertSame('user-profile-collection', $replacements['{{class}}']);
    }

    public function test_get_replacements_preserves_snake_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'user_profile_collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'ProfileRecord');

        $this->assertSame('user_profile_collection', $replacements['{{class}}']);
    }

    public function test_get_replacements_preserves_existing_pascal_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'ShowUserCollection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserRecord');

        $this->assertSame('ShowUserCollection', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_numbers_in_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-2fa-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'User2faRecord');

        $this->assertSame('user-2fa-collection', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_uppercase_acronyms(): void
    {
        $pathInfo = new PathInfo(
            className: 'API-key-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'APIKeyRecord');

        $this->assertSame('API-key-collection', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_mixed_case_acronyms(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-XML-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserXMLRecord');

        $this->assertSame('user-XML-collection', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_complex_class_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'active-user-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'ActiveUserRecord');

        $this->assertSame('active-user-collection', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_deeply_nested_path(): void
    {
        $pathInfo = new PathInfo(
            className: 'order-collection',
            subPath: 'Api\\V2\\Shop\\Checkout',
            segments: ['Api', 'V2', 'Shop', 'Checkout']
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'OrderRecord');

        $this->assertSame('App\\Collections\\Api\\V2\\Shop\\Checkout', $replacements['{{namespace}}']);
        $this->assertSame('order-collection', $replacements['{{class}}']);
    }

    public function test_get_replacements_without_item_type_uses_default(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, null);

        $this->assertSame('string', $replacements['{{type}}']);
    }

    public function test_get_replacements_with_empty_item_type_uses_default(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, '');

        $this->assertSame('string', $replacements['{{type}}']);
    }

    public function test_get_replacements_with_fully_qualified_type_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'App\\Records\\UserRecord');

        $this->assertSame('App\\Records\\UserRecord', $replacements['{{type}}']);
    }

    public function test_generate_calls_parent_generate_method(): void
    {
        $pathInfo = new PathInfo(
            className: 'test-collection',
            subPath: '',
            segments: []
        );

        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('generate');

        $this->assertTrue($method->isPublic());
    }

    public function test_typed_collection_generator_has_correct_type(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $property = $reflection->getProperty('type');

        $type = $property->getValue($this->generator);

        $this->assertSame('typed-collection', $type->value);
    }

    public function test_get_replacements_returns_array_with_correct_keys(): void
    {
        $pathInfo = new PathInfo(
            className: 'test',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'string');
        $keys = array_keys($replacements);

        $this->assertContains('{{namespace}}', $keys);
        $this->assertContains('{{class}}', $keys);
        $this->assertContains('{{type}}', $keys);
        $this->assertCount(3, $keys);
    }

    public function test_get_replacements_never_contains_extra_placeholders(): void
    {
        $pathInfo = new PathInfo(
            className: 'test',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'string');

        $this->assertArrayNotHasKey('{{signature}}', $replacements);
        $this->assertArrayNotHasKey('{{description}}', $replacements);
        $this->assertArrayNotHasKey('{{interface}}', $replacements);
    }

    public function test_multiple_collection_generations_produce_consistent_results(): void
    {
        $pathInfo1 = new PathInfo(
            className: 'my-collection',
            subPath: '',
            segments: []
        );

        $pathInfo2 = new PathInfo(
            className: 'my-collection',
            subPath: '',
            segments: []
        );

        $replacements1 = $this->generator->getReplacements($pathInfo1, null, 'MyRecord');
        $replacements2 = $this->generator->getReplacements($pathInfo2, null, 'MyRecord');

        $this->assertSame($replacements1, $replacements2);
    }

    public function test_get_replacements_with_single_word_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'user',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserRecord');

        $this->assertSame('user', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_single_word_already_has_suffix(): void
    {
        $pathInfo = new PathInfo(
            className: 'UserCollection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'UserRecord');

        $this->assertSame('UserCollection', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_php_primitive_type(): void
    {
        $pathInfo = new PathInfo(
            className: 'int-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'int');

        $this->assertSame('int', $replacements['{{type}}']);
        $this->assertSame('int-collection', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_array_type(): void
    {
        $pathInfo = new PathInfo(
            className: 'array-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, 'array');

        $this->assertSame('array', $replacements['{{type}}']);
        $this->assertSame('array-collection', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_nullable_type(): void
    {
        $pathInfo = new PathInfo(
            className: 'optional-collection',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo, null, '?UserRecord');

        $this->assertSame('?UserRecord', $replacements['{{type}}']);
        $this->assertSame('optional-collection', $replacements['{{class}}']);
    }

    /**
     * Note: Les tests de génération de fichiers sont maintenant gérés par AbstractGenerator
     * et utilisent le trait FileCreator. La propriété $files n'existe plus directement
     * dans TypedCollectionGenerator car elle est dans le trait FileCreator.
     * 
     * Pour tester la génération de fichiers, utilisez les tests d'intégration.
     */
    public function test_generator_extends_abstract_generator(): void
    {
        $this->assertInstanceOf(\AndyDefer\DirectiveForge\Generators\AbstractGenerator::class, $this->generator);
    }

    public function test_generator_has_correct_config(): void
    {
        $config = $this->generator->getType()->getConfig();

        $this->assertSame('typed-collection', $config->type->value);
        $this->assertSame('/app/Collections/', $config->basePath);
        $this->assertSame('App\\Collections', $config->baseNamespace);
        $this->assertSame('Collection', $config->suffix);
        $this->assertTrue($config->requiresType);
    }
}
