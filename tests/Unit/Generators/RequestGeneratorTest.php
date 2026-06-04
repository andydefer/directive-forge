<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\RequestGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class RequestGeneratorTest extends UnitTestCase
{
    private RequestGenerator $generator;
    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new RequestGenerator($this->interaction);
    }

    private function createPathInfo(string $className, string $subPath = '', array $segments = []): PathInfo
    {
        // Arrange: Create segments collection
        $segmentsCollection = new ScalarTypedCollection;
        if (!empty($segments)) {
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
        // Arrange: Create path info with simple name
        $pathInfo = $this->createPathInfo('StoreUserRequest', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify replacement structure
        $this->assertArrayHasKey('{{namespace}}', $array);
        $this->assertArrayHasKey('{{class}}', $array);
        $this->assertArrayHasKey('{{recordClass}}', $array);
        $this->assertSame('App\\Http\\Requests', $array['{{namespace}}']);
        $this->assertSame('StoreUserRequest', $array['{{class}}']);
        $this->assertSame('StoreUserRecord', $array['{{recordClass}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        // Arrange: Create path info with subdirectory
        $pathInfo = $this->createPathInfo('StoreUserRequest', 'Api\\V1', ['Api', 'V1']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify namespace includes subdirectory
        $this->assertSame('App\\Http\\Requests\\Api\\V1', $array['{{namespace}}']);
        $this->assertSame('StoreUserRequest', $array['{{class}}']);
        $this->assertSame('StoreUserRecord', $array['{{recordClass}}']);
    }

    public function test_get_replacements_generates_record_class_name_correctly(): void
    {
        // Arrange: Create path info with request name
        $pathInfo = $this->createPathInfo('UpdateProfileRequest', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify record class name generation
        $this->assertSame('UpdateProfileRecord', $array['{{recordClass}}']);
    }

    public function test_get_replacements_removes_request_suffix_for_record(): void
    {
        // Arrange: Create path info with request name ending with Request
        $pathInfo = $this->createPathInfo('LoginRequest', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify Request suffix is removed and Record suffix is added
        $this->assertSame('LoginRecord', $array['{{recordClass}}']);
    }

    public function test_generator_has_correct_type(): void
    {
        // Arrange: Get the generator instance

        // Act: Get the generator type
        $type = $this->generator->getType();

        // Assert: Verify the type is correct
        $this->assertSame('request', $type->value);
    }

    public function test_generator_has_correct_config(): void
    {
        // Arrange: Get the generator type
        $generatorType = $this->generator->getType();

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
        $this->assertSame('request', $config->type->value);
        $this->assertSame('/app/Http/Requests/', $config->basePath);
        $this->assertSame('App\\Http\\Requests', $config->baseNamespace);
        $this->assertStringContainsString('request.stub', $config->stubPath);
        $this->assertSame('Request', $config->suffix);
        $this->assertFalse($config->requiresType);
    }
}
