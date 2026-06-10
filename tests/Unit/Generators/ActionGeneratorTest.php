<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\AbstractGenerator;
use AndyDefer\DirectiveForge\Generators\ActionGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class ActionGeneratorTest extends UnitTestCase
{
    private ActionGenerator $generator;

    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new ActionGenerator($this->interaction);
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
        // Arrange
        $pathInfo = $this->createPathInfo('show-user', '', []);

        // Act
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert
        $this->assertArrayHasKey('{{ namespace }}', $array);
        $this->assertArrayHasKey('{{ class }}', $array);
        $this->assertSame('App\\Actions', $array['{{ namespace }}']);
        $this->assertSame('show-user', $array['{{ class }}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        // Arrange
        $pathInfo = $this->createPathInfo('show-user', 'Api\\V1\\Users', ['Api', 'V1', 'Users']);

        // Act
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert
        $this->assertSame('App\\Actions\\Api\\V1\\Users', $array['{{ namespace }}']);
        $this->assertSame('show-user', $array['{{ class }}']);
    }

    public function test_action_already_has_suffix(): void
    {
        // Arrange
        $pathInfo = $this->createPathInfo('ShowUserAction', '', []);

        // Act
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert
        $this->assertSame('ShowUserAction', $array['{{ class }}']);
    }

    public function test_get_replacements_returns_collection_with_correct_keys(): void
    {
        // Arrange
        $pathInfo = $this->createPathInfo('test', '', []);

        // Act
        $replacements = $this->generator->getReplacements($pathInfo);
        $keys = array_keys($replacements->toAssociativeArray());

        // Assert
        $this->assertContains('{{ namespace }}', $keys);
        $this->assertContains('{{ class }}', $keys);
        $this->assertCount(2, $keys);
    }

    public function test_get_replacements_never_contains_extra_placeholders(): void
    {
        // Arrange
        $pathInfo = $this->createPathInfo('test', '', []);

        // Act
        $replacements = $this->generator->getReplacements($pathInfo);

        // Assert
        $this->assertReplacementNotHasKey($replacements, '{{signature}}');
        $this->assertReplacementNotHasKey($replacements, '{{description}}');
        $this->assertReplacementNotHasKey($replacements, '{{type}}');
        $this->assertReplacementNotHasKey($replacements, '{{interface}}');
        $this->assertReplacementNotHasKey($replacements, '{{view}}');
        $this->assertReplacementNotHasKey($replacements, '{{item_type}}');
    }

    public function test_generator_extends_abstract_generator(): void
    {
        // Assert
        $this->assertInstanceOf(AbstractGenerator::class, $this->generator);
    }

    public function test_generator_has_correct_type(): void
    {
        // Act
        $type = $this->generator->getType();

        // Assert
        $this->assertSame('action', $type->value);
    }

    public function test_generator_has_correct_config(): void
    {
        // Act
        $config = $this->generator->getType()->getConfig();

        // Assert
        $this->assertSame('action', $config->type->value);
        $this->assertSame('/app/Actions/', $config->basePath);
        $this->assertSame('App\\Actions', $config->baseNamespace);
        $this->assertStringContainsString('action.stub', $config->stubPath);
        $this->assertSame('Action', $config->suffix);
        $this->assertFalse($config->requiresType);
    }

    public function test_generator_returns_correct_type(): void
    {
        // Act
        $typeValue = $this->generator->getType()->value;

        // Assert
        $this->assertSame('action', $typeValue);
    }
}
