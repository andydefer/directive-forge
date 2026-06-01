<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\AbstractGenerator;
use AndyDefer\DirectiveForge\Generators\RecordGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class RecordGeneratorTest extends UnitTestCase
{
    private RecordGenerator $generator;

    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new RecordGenerator($this->interaction);
    }

    private function createPathInfo(string $className, string $subPath = '', array $segments = []): PathInfo
    {
        $segmentsCollection = new ScalarTypedCollection;
        $segmentsCollection->add(...$segments);

        return PathInfo::from([
            'className' => $className,
            'subPath' => $subPath,
            'segments' => $segmentsCollection,
        ]);
    }

    private function assertReplacementNotHasKey(ReplacementCollection $replacements, string $key): void
    {
        $array = $replacements->toAssociativeArray();
        $this->assertArrayNotHasKey($key, $array);
    }

    public function test_get_replacements_with_simple_name(): void
    {
        // Arrange: Create path info with simple name
        $pathInfo = $this->createPathInfo('user-data', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify replacement structure
        $this->assertArrayHasKey('{{namespace}}', $array);
        $this->assertArrayHasKey('{{class}}', $array);
        $this->assertSame('App\\Records', $array['{{namespace}}']);
        $this->assertSame('user-data', $array['{{class}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        // Arrange: Create path info with subdirectory
        $pathInfo = $this->createPathInfo('user-data', 'Api\\V1\\Users', ['Api', 'V1', 'Users']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify namespace includes subdirectory
        $this->assertSame('App\\Records\\Api\\V1\\Users', $array['{{namespace}}']);
        $this->assertSame('user-data', $array['{{class}}']);
    }

    public function test_get_replacements_with_nested_subdirectories(): void
    {
        // Arrange: Create path info with nested subdirectories
        $pathInfo = $this->createPathInfo('order-item', 'Shop\\Checkout\\Items', ['Shop', 'Checkout', 'Items']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify nested namespace
        $this->assertSame('App\\Records\\Shop\\Checkout\\Items', $array['{{namespace}}']);
        $this->assertSame('order-item', $array['{{class}}']);
    }

    public function test_get_replacements_when_class_already_has_suffix(): void
    {
        // Arrange: Create path info with class that already has Record suffix
        $pathInfo = $this->createPathInfo('UserDataRecord', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify class name preserved
        $this->assertSame('UserDataRecord', $array['{{class}}']);
        $this->assertStringEndsWith('Record', $array['{{class}}']);
    }

    public function test_get_replacements_preserves_kebab_case(): void
    {
        // Arrange: Create path info with kebab-case
        $pathInfo = $this->createPathInfo('user-profile-data', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify kebab-case preserved
        $this->assertSame('user-profile-data', $array['{{class}}']);
    }

    public function test_get_replacements_preserves_snake_case(): void
    {
        // Arrange: Create path info with snake_case
        $pathInfo = $this->createPathInfo('user_profile_data', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify snake_case preserved
        $this->assertSame('user_profile_data', $array['{{class}}']);
    }

    public function test_get_replacements_preserves_existing_pascal_case(): void
    {
        // Arrange: Create path info with PascalCase
        $pathInfo = $this->createPathInfo('ShowUserData', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify PascalCase preserved
        $this->assertSame('ShowUserData', $array['{{class}}']);
    }

    public function test_get_replacements_handles_single_word_class_name(): void
    {
        // Arrange: Create path info with single word
        $pathInfo = $this->createPathInfo('config', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify single word preserved
        $this->assertSame('config', $array['{{class}}']);
    }

    public function test_get_replacements_handles_single_word_with_suffix(): void
    {
        // Arrange: Create path info with single word that has suffix
        $pathInfo = $this->createPathInfo('ConfigRecord', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify class name preserved
        $this->assertSame('ConfigRecord', $array['{{class}}']);
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
        $this->assertSame('App\\Records\\Api\\V2\\Shop\\Checkout\\Summary', $array['{{namespace}}']);
        $this->assertSame('order-summary', $array['{{class}}']);
    }

    public function test_get_replacements_with_numbers_in_class_name(): void
    {
        // Arrange: Create path info with numbers in class name
        $pathInfo = $this->createPathInfo('user-2fa-data', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify numbers preserved
        $this->assertSame('user-2fa-data', $array['{{class}}']);
    }

    public function test_get_replacements_with_uppercase_in_class_name(): void
    {
        // Arrange: Create path info with uppercase
        $pathInfo = $this->createPathInfo('UserAPIKey', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify uppercase preserved
        $this->assertSame('UserAPIKey', $array['{{class}}']);
    }

    public function test_get_replacements_with_mixed_case_class_name(): void
    {
        // Arrange: Create path info with mixed case
        $pathInfo = $this->createPathInfo('user-API-key', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify mixed case preserved
        $this->assertSame('user-API-key', $array['{{class}}']);
    }

    public function test_generator_has_correct_type(): void
    {
        // Act: Get generator type
        $type = $this->generator->getType();

        // Assert: Verify type
        $this->assertSame('record', $type->value);
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
        $this->assertCount(2, $keys);
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
        $this->assertReplacementNotHasKey($replacements, '{{interface}}');
    }

    public function test_multiple_record_generations_produce_consistent_results(): void
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
        $this->assertSame('record', $config->type->value);
        $this->assertSame('/app/Records/', $config->basePath);
        $this->assertSame('App\\Records', $config->baseNamespace);
        $this->assertStringContainsString('record.stub', $config->stubPath);
        $this->assertSame('Record', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_generator_returns_correct_type(): void
    {
        // Act: Get generator type value
        $typeValue = $this->generator->getType()->value;

        // Assert: Verify type value
        $this->assertSame('record', $typeValue);
    }
}
