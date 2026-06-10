<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\ServiceGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class ServiceGeneratorTest extends UnitTestCase
{
    private ServiceGenerator $generator;

    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new ServiceGenerator($this->interaction);
    }

    private function createPathInfo(string $className, string $subPath = '', array $segments = []): PathInfo
    {
        // Arrange: Create segments collection
        $segmentsCollection = new ScalarTypedCollection;
        if (! empty($segments)) {
            $segmentsCollection->add(...$segments);
        }

        // Act & Assert: Create and return PathInfo
        return PathInfo::from([
            'className' => $className,
            'subPath' => $subPath,
            'segments' => $segmentsCollection,
        ]);
    }

    public function test_get_replacements_with_simple_name(): void
    {
        // Arrange: Create path info with simple service name
        $pathInfo = $this->createPathInfo('user-service', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify replacement structure
        $this->assertArrayHasKey('{{namespace}}', $array);
        $this->assertArrayHasKey('{{class}}', $array);
        $this->assertSame('App\\Services', $array['{{namespace}}']);
        $this->assertSame('user-service', $array['{{class}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        // Arrange: Create path info with subdirectory
        $pathInfo = $this->createPathInfo('user-service', 'Api\\V1', ['Api', 'V1']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify namespace includes subdirectory
        $this->assertSame('App\\Services\\Api\\V1', $array['{{namespace}}']);
        $this->assertSame('user-service', $array['{{class}}']);
    }

    public function test_get_replacements_with_nested_subdirectories(): void
    {
        // Arrange: Create path info with nested subdirectories
        $pathInfo = $this->createPathInfo('payment-service', 'Shop\\Checkout\\Payment', ['Shop', 'Checkout', 'Payment']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify deeply nested namespace
        $this->assertSame('App\\Services\\Shop\\Checkout\\Payment', $array['{{namespace}}']);
        $this->assertSame('payment-service', $array['{{class}}']);
    }

    public function test_generator_has_correct_type(): void
    {
        // Arrange: Get the generator instance

        // Act: Get the generator type
        $type = $this->generator->getType();

        // Assert: Verify the type is correct
        $this->assertSame('service', $type->value);
    }

    public function test_generator_has_correct_config(): void
    {
        // Arrange: Get the generator type
        $generatorType = $this->generator->getType();

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
        $this->assertSame('service', $config->type->value);
        $this->assertSame('/app/Services/', $config->basePath);
        $this->assertSame('App\\Services', $config->baseNamespace);
        $this->assertStringContainsString('service.stub', $config->stubPath);
        $this->assertSame('Service', $config->suffix);
        $this->assertFalse($config->requiresType);
    }

    public function test_get_replacements_preserves_kebab_case(): void
    {
        // Arrange: Create path info with kebab-case service name
        $pathInfo = $this->createPathInfo('user-profile-service', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify kebab-case is preserved
        $this->assertSame('user-profile-service', $array['{{class}}']);
    }

    public function test_get_replacements_preserves_snake_case(): void
    {
        // Arrange: Create path info with snake_case service name
        $pathInfo = $this->createPathInfo('user_profile_service', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify snake_case is preserved
        $this->assertSame('user_profile_service', $array['{{class}}']);
    }

    public function test_get_replacements_returns_collection_with_correct_keys(): void
    {
        // Arrange: Create path info with simple service name
        $pathInfo = $this->createPathInfo('test-service', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $keys = array_keys($replacements->toAssociativeArray());

        // Assert: Verify correct keys
        $this->assertContains('{{namespace}}', $keys);
        $this->assertContains('{{class}}', $keys);
        $this->assertCount(2, $keys);
    }
}
