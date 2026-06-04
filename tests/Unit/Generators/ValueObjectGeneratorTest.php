<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\ValueObjectGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class ValueObjectGeneratorTest extends UnitTestCase
{
    private ValueObjectGenerator $generator;
    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new ValueObjectGenerator($this->interaction);
    }

    private function createPathInfo(string $className, string $subPath = '', array $segments = []): PathInfo
    {
        $segmentsCollection = new ScalarTypedCollection;
        if (!empty($segments)) {
            $segmentsCollection->add(...$segments);
        }

        return PathInfo::from([
            'className' => $className,
            'subPath' => $subPath,
            'segments' => $segmentsCollection,
        ]);
    }

    public function test_get_replacements_with_simple_name(): void
    {
        // Arrange: Create path info with simple VO name
        $pathInfo = $this->createPathInfo('EmailAddressVO', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify replacement structure
        $this->assertArrayHasKey('{{namespace}}', $array);
        $this->assertArrayHasKey('{{class}}', $array);
        $this->assertArrayHasKey('{{date}}', $array);
        $this->assertSame('App\\ValueObjects', $array['{{namespace}}']);
        $this->assertSame('EmailAddressVO', $array['{{class}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        // Arrange: Create path info with subdirectory
        $pathInfo = $this->createPathInfo('EmailAddressVO', 'User', ['User']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify namespace includes subdirectory
        $this->assertSame('App\\ValueObjects\\User', $array['{{namespace}}']);
        $this->assertSame('EmailAddressVO', $array['{{class}}']);
    }

    public function test_generator_has_correct_type(): void
    {
        // Arrange: Get the generator instance

        // Act: Get the generator type
        $type = $this->generator->getType();

        // Assert: Verify the type is correct
        $this->assertSame('value-object', $type->value);
    }

    public function test_generator_has_correct_config(): void
    {
        // Arrange: Get the generator type
        $generatorType = $this->generator->getType();

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
        $this->assertSame('value-object', $config->type->value);
        $this->assertSame('/app/ValueObjects/', $config->basePath);
        $this->assertSame('App\\ValueObjects', $config->baseNamespace);
        $this->assertStringContainsString('value-object.stub', $config->stubPath);
        $this->assertSame('VO', $config->suffix);
        $this->assertFalse($config->requiresType);
    }
}
