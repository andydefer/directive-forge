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

        // Normaliser le className en PascalCase comme le fait BaseDirective::extractPathInfo
        $normalizedClassName = $this->toPascalCase($className);

        return PathInfo::from([
            'className' => $normalizedClassName,
            'subPath' => $subPath,
            'segments' => $segmentsCollection,
        ]);
    }

    private function toPascalCase(string $string): string
    {
        $string = str_replace(['-', '_'], ' ', $string);
        $string = ucwords($string);

        return str_replace(' ', '', $string);
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
        // Arrange: Create path info with simple name (sera normalisé en 'User')
        $pathInfo = $this->createPathInfo('user', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify replacement structure
        $this->assertArrayHasKey('{{namespace}}', $array);
        $this->assertArrayHasKey('{{class}}', $array);
        $this->assertArrayHasKey('{{recordClass}}', $array);
        $this->assertArrayHasKey('{{filterRecordClass}}', $array);
        $this->assertSame('App\\Repositories', $array['{{namespace}}']);
        $this->assertSame('User', $array['{{class}}']);
        $this->assertSame('UserRecord', $array['{{recordClass}}']);
        $this->assertSame('UserFilterRecord', $array['{{filterRecordClass}}']);
    }

    public function test_get_replacements_with_full_repository_name(): void
    {
        // Arrange: Create path info with full repository name
        $pathInfo = $this->createPathInfo('UserRepository', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify class and record names
        $this->assertSame('UserRepository', $array['{{class}}']);
        $this->assertSame('UserRecord', $array['{{recordClass}}']);
        $this->assertSame('UserFilterRecord', $array['{{filterRecordClass}}']);
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
        $this->assertSame('User', $array['{{class}}']);
        $this->assertSame('UserRecord', $array['{{recordClass}}']);
        $this->assertSame('UserFilterRecord', $array['{{filterRecordClass}}']);
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
        $this->assertSame('Order', $array['{{class}}']);
        $this->assertSame('OrderRecord', $array['{{recordClass}}']);
        $this->assertSame('OrderFilterRecord', $array['{{filterRecordClass}}']);
    }

    public function test_get_replacements_preserves_kebab_case(): void
    {
        // Arrange: Create path info with kebab-case
        $pathInfo = $this->createPathInfo('user-profile', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify kebab-case preserved for class, but PascalCase for records
        $this->assertSame('UserProfile', $array['{{class}}']);
        $this->assertSame('UserProfileRecord', $array['{{recordClass}}']);
        $this->assertSame('UserProfileFilterRecord', $array['{{filterRecordClass}}']);
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
        $this->assertContains('{{recordClass}}', $keys);
        $this->assertContains('{{filterRecordClass}}', $keys);
        $this->assertCount(4, $keys);
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
