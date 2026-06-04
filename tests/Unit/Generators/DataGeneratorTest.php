<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\DataGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class DataGeneratorTest extends UnitTestCase
{
    private DataGenerator $generator;
    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange: Create mock interaction service
        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new DataGenerator($this->interaction);
    }

    private function createPathInfo(string $className, string $subPath = '', array $segments = []): PathInfo
    {
        // Arrange: Create segments collection
        $segmentsCollection = new ScalarTypedCollection;
        if (!empty($segments)) {
            $segmentsCollection->add(...$segments);
        }

        // Arrange: Normalize class name to PascalCase
        $normalizedClassName = $this->toPascalCase($className);

        // Act & Assert: Create and return PathInfo
        return PathInfo::from([
            'className' => $normalizedClassName,
            'subPath' => $subPath,
            'segments' => $segmentsCollection,
        ]);
    }

    private function toPascalCase(string $string): string
    {
        // Arrange & Act: Convert string to PascalCase
        $string = str_replace(['-', '_'], ' ', $string);
        $string = ucwords($string);
        return str_replace(' ', '', $string);
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
        $this->assertSame('App\\Data', $array['{{namespace}}']);
        $this->assertSame('User', $array['{{class}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        // Arrange: Create path info with subdirectory
        $pathInfo = $this->createPathInfo('user', 'Api\\V1', ['Api', 'V1']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify namespace includes subdirectory
        $this->assertSame('App\\Data\\Api\\V1', $array['{{namespace}}']);
        $this->assertSame('User', $array['{{class}}']);
    }

    public function test_generator_has_correct_type(): void
    {
        // Arrange: Generator already created in setUp

        // Act: Get generator type
        $type = $this->generator->getType();

        // Assert: Verify the type is correct
        $this->assertSame('data', $type->value);
    }

    public function test_generator_has_correct_config(): void
    {
        // Arrange: Get generator type
        $generatorType = $this->generator->getType();

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
        $this->assertSame('data', $config->type->value);
        $this->assertSame('/app/Data/', $config->basePath);
        $this->assertSame('App\\Data', $config->baseNamespace);
        $this->assertStringContainsString('data.stub', $config->stubPath);
        $this->assertSame('Data', $config->suffix);
        $this->assertFalse($config->requiresType);
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
}
