<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\ConfigGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class ConfigGeneratorTest extends UnitTestCase
{
    private ConfigGenerator $generator;
    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new ConfigGenerator($this->interaction);
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
        // Arrange: Create path info with simple config name
        $pathInfo = $this->createPathInfo('DatabaseConfig', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify replacement structure
        $this->assertArrayHasKey('{{namespace}}', $array);
        $this->assertArrayHasKey('{{class}}', $array);
        $this->assertArrayHasKey('{{date}}', $array);
        $this->assertSame('App\\Configs', $array['{{namespace}}']);
        $this->assertSame('DatabaseConfig', $array['{{class}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        // Arrange: Create path info with subdirectory
        $pathInfo = $this->createPathInfo('DatabaseConfig', 'Database', ['Database']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify namespace includes subdirectory
        $this->assertSame('App\\Configs\\Database', $array['{{namespace}}']);
        $this->assertSame('DatabaseConfig', $array['{{class}}']);
    }

    public function test_generator_has_correct_type(): void
    {
        // Arrange: Get the generator instance

        // Act: Get the generator type
        $type = $this->generator->getType();

        // Assert: Verify the type is correct
        $this->assertSame('config', $type->value);
    }

    public function test_generator_has_correct_config(): void
    {
        // Arrange: Get the generator type
        $generatorType = $this->generator->getType();

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
        $this->assertSame('config', $config->type->value);
        $this->assertSame('/app/Configs/', $config->basePath);
        $this->assertSame('App\\Configs', $config->baseNamespace);
        $this->assertStringContainsString('config.stub', $config->stubPath);
        $this->assertSame('Config', $config->suffix);
        $this->assertFalse($config->requiresType);
    }
}
