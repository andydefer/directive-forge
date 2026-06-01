<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Records;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\Records\GeneratorConfig;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class GeneratorConfigTest extends UnitTestCase
{
    private function createReplacementCollection(array $replacements = []): ReplacementCollection
    {
        $collection = new ReplacementCollection();
        foreach ($replacements as $placeholder => $value) {
            $collection->addReplacement($placeholder, $value);
        }

        return $collection;
    }

    private function assertReplacementCollectionEquals(array $expected, ReplacementCollection $actual): void
    {
        $this->assertSame($expected, $actual->toAssociativeArray());
    }

    public function test_constructor_creates_valid_object(): void
    {
        // Arrange: Create extra replacements collection
        $extraReplacements = $this->createReplacementCollection(['{{extra}}' => 'value']);

        // Act: Create config
        $config = new GeneratorConfig(
            type: GeneratorType::DIRECTIVE,
            basePath: '/app/Test/',
            baseNamespace: 'App\\Test',
            stubPath: '/stubs/test.stub',
            suffix: 'Test',
            requiresType: false,
            supportsType: false,
            extraReplacements: $extraReplacements,
        );

        // Assert: Verify config properties
        $this->assertInstanceOf(GeneratorConfig::class, $config);
        $this->assertSame(GeneratorType::DIRECTIVE, $config->type);
        $this->assertSame('/app/Test/', $config->basePath);
        $this->assertSame('App\\Test', $config->baseNamespace);
        $this->assertSame('/stubs/test.stub', $config->stubPath);
        $this->assertSame('Test', $config->suffix);
        $this->assertFalse($config->requiresType);
        $this->assertFalse($config->supportsType);
        $this->assertReplacementCollectionEquals(['{{extra}}' => 'value'], $config->extraReplacements);
    }

    public function test_constructor_with_default_values(): void
    {
        // Act: Create config with default values
        $config = new GeneratorConfig(
            type: GeneratorType::ACTION,
            basePath: '/app/Actions/',
            baseNamespace: 'App\\Actions',
            stubPath: '/stubs/action.stub',
            suffix: 'Action',
        );

        // Assert: Verify default values
        $this->assertSame(GeneratorType::ACTION, $config->type);
        $this->assertSame('/app/Actions/', $config->basePath);
        $this->assertSame('App\\Actions', $config->baseNamespace);
        $this->assertSame('/stubs/action.stub', $config->stubPath);
        $this->assertSame('Action', $config->suffix);
        $this->assertFalse($config->requiresType);
        $this->assertFalse($config->supportsType);
        $this->assertTrue($config->extraReplacements->isEmpty());
    }

    public function test_constructor_with_supports_type_true(): void
    {
        // Act: Create config with supportsType true
        $config = new GeneratorConfig(
            type: GeneratorType::ACTION,
            basePath: '/app/Actions/',
            baseNamespace: 'App\\Actions',
            stubPath: '/stubs/action.stub',
            suffix: 'Action',
            supportsType: true,
        );

        // Assert: Verify supportsType
        $this->assertTrue($config->supportsType);
    }

    public function test_constructor_with_requires_type_true(): void
    {
        // Act: Create config with requiresType true
        $config = new GeneratorConfig(
            type: GeneratorType::TYPED_COLLECTION,
            basePath: '/app/Collections/',
            baseNamespace: 'App\\Collections',
            stubPath: '/stubs/typed-collection.stub',
            suffix: 'Collection',
            requiresType: true,
        );

        // Assert: Verify requiresType
        $this->assertTrue($config->requiresType);
    }

    public function test_constructor_with_extra_replacements(): void
    {
        // Arrange: Create extra replacements
        $extraReplacements = $this->createReplacementCollection([
            '{{date}}' => '2024-01-01',
            '{{author}}' => 'John Doe',
            '{{version}}' => '1.0.0',
        ]);

        // Act: Create config
        $config = new GeneratorConfig(
            type: GeneratorType::DIRECTIVE,
            basePath: '/app/Directives/',
            baseNamespace: 'App\\Directives',
            stubPath: '/stubs/directive.stub',
            suffix: 'Directive',
            extraReplacements: $extraReplacements,
        );

        // Assert: Verify extra replacements
        $this->assertCount(3, $config->extraReplacements);
        $this->assertReplacementCollectionEquals(
            ['{{date}}' => '2024-01-01', '{{author}}' => 'John Doe', '{{version}}' => '1.0.0'],
            $config->extraReplacements
        );
    }

    public function test_different_generator_types_have_different_configs(): void
    {
        // Act: Get configs for different types
        $directiveConfig = GeneratorType::DIRECTIVE->getConfig();
        $actionConfig = GeneratorType::ACTION->getConfig();
        $taskConfig = GeneratorType::TASK->getConfig();

        // Assert: Verify configs are different
        $this->assertNotSame($directiveConfig->basePath, $actionConfig->basePath);
        $this->assertNotSame($actionConfig->basePath, $taskConfig->basePath);
        $this->assertNotSame($directiveConfig->suffix, $actionConfig->suffix);
        $this->assertNotSame($actionConfig->suffix, $taskConfig->suffix);
    }

    public function test_config_from_enum_is_immutable(): void
    {
        // Act: Get config twice
        $config1 = GeneratorType::DIRECTIVE->getConfig();
        $config2 = GeneratorType::DIRECTIVE->getConfig();

        // Assert: Different instances but equal values
        $this->assertNotSame($config1, $config2);
        $this->assertEquals($config1, $config2);
    }

    public function test_config_contains_correct_base_path_for_directive(): void
    {
        // Act: Get directive config
        $config = GeneratorType::DIRECTIVE->getConfig();

        // Assert: Verify base path
        $this->assertSame('/app/Directives/', $config->basePath);
    }

    public function test_config_contains_correct_base_path_for_action(): void
    {
        // Act: Get action config
        $config = GeneratorType::ACTION->getConfig();

        // Assert: Verify base path
        $this->assertSame('/app/Actions/', $config->basePath);
    }

    public function test_config_contains_correct_base_path_for_task(): void
    {
        // Act: Get task config
        $config = GeneratorType::TASK->getConfig();

        // Assert: Verify base path
        $this->assertSame('/app/Tasks/', $config->basePath);
    }

    public function test_config_contains_correct_base_path_for_repository(): void
    {
        // Act: Get repository config
        $config = GeneratorType::REPOSITORY->getConfig();

        // Assert: Verify base path
        $this->assertSame('/app/Repositories/', $config->basePath);
    }

    public function test_config_contains_correct_base_path_for_record(): void
    {
        // Act: Get record config
        $config = GeneratorType::RECORD->getConfig();

        // Assert: Verify base path
        $this->assertSame('/app/Records/', $config->basePath);
    }

    public function test_config_contains_correct_base_path_for_typed_collection(): void
    {
        // Act: Get typed collection config
        $config = GeneratorType::TYPED_COLLECTION->getConfig();

        // Assert: Verify base path
        $this->assertSame('/app/Collections/', $config->basePath);
    }

    public function test_config_contains_correct_base_namespace(): void
    {
        // Act: Get configs
        $directiveConfig = GeneratorType::DIRECTIVE->getConfig();
        $actionConfig = GeneratorType::ACTION->getConfig();

        // Assert: Verify base namespaces
        $this->assertSame('App\\Directives', $directiveConfig->baseNamespace);
        $this->assertSame('App\\Actions', $actionConfig->baseNamespace);
    }

    public function test_config_contains_correct_suffix(): void
    {
        // Assert: Verify suffixes for all types
        $this->assertSame('Directive', GeneratorType::DIRECTIVE->getConfig()->suffix);
        $this->assertSame('Action', GeneratorType::ACTION->getConfig()->suffix);
        $this->assertSame('Task', GeneratorType::TASK->getConfig()->suffix);
        $this->assertSame('Repository', GeneratorType::REPOSITORY->getConfig()->suffix);
        $this->assertSame('Record', GeneratorType::RECORD->getConfig()->suffix);
        $this->assertSame('Collection', GeneratorType::TYPED_COLLECTION->getConfig()->suffix);
    }

    public function test_config_contains_correct_stub_path(): void
    {
        // Act: Get configs
        $directiveConfig = GeneratorType::DIRECTIVE->getConfig();
        $actionConfig = GeneratorType::ACTION->getConfig();

        // Assert: Verify stub paths
        $this->assertStringContainsString('directive.stub', $directiveConfig->stubPath);
        $this->assertStringContainsString('action.stub', $actionConfig->stubPath);
    }

    public function test_no_generator_supports_type(): void
    {
        // Act: Get all configs
        $directiveConfig = GeneratorType::DIRECTIVE->getConfig();
        $actionConfig = GeneratorType::ACTION->getConfig();
        $taskConfig = GeneratorType::TASK->getConfig();
        $repositoryConfig = GeneratorType::REPOSITORY->getConfig();
        $recordConfig = GeneratorType::RECORD->getConfig();
        $collectionConfig = GeneratorType::TYPED_COLLECTION->getConfig();

        // Assert: No generator supports type
        $this->assertFalse($directiveConfig->supportsType);
        $this->assertFalse($actionConfig->supportsType);
        $this->assertFalse($taskConfig->supportsType);
        $this->assertFalse($repositoryConfig->supportsType);
        $this->assertFalse($recordConfig->supportsType);
        $this->assertFalse($collectionConfig->supportsType);
    }

    public function test_only_typed_collection_requires_type(): void
    {
        // Act: Get all configs
        $directiveConfig = GeneratorType::DIRECTIVE->getConfig();
        $actionConfig = GeneratorType::ACTION->getConfig();
        $taskConfig = GeneratorType::TASK->getConfig();
        $repositoryConfig = GeneratorType::REPOSITORY->getConfig();
        $recordConfig = GeneratorType::RECORD->getConfig();
        $collectionConfig = GeneratorType::TYPED_COLLECTION->getConfig();

        // Assert: Only typed collection requires type
        $this->assertFalse($directiveConfig->requiresType);
        $this->assertFalse($actionConfig->requiresType);
        $this->assertFalse($taskConfig->requiresType);
        $this->assertFalse($repositoryConfig->requiresType);
        $this->assertFalse($recordConfig->requiresType);
        $this->assertTrue($collectionConfig->requiresType);
    }

    public function test_directive_config_has_extra_replacements(): void
    {
        // Act: Get directive config
        $config = GeneratorType::DIRECTIVE->getConfig();

        // Assert: Verify extra replacements contain date
        $replacements = $config->extraReplacements->toAssociativeArray();
        $this->assertArrayHasKey('{{date}}', $replacements);
        $this->assertNotEmpty($replacements['{{date}}']);
    }

    public function test_action_config_has_no_extra_replacements_by_default(): void
    {
        // Act: Get action config
        $config = GeneratorType::ACTION->getConfig();

        // Assert: Verify no extra replacements
        $this->assertTrue($config->extraReplacements->isEmpty());
    }

    public function test_config_properties_are_readonly(): void
    {
        // Arrange: Create config
        $config = new GeneratorConfig(
            type: GeneratorType::DIRECTIVE,
            basePath: '/app/Test/',
            baseNamespace: 'App\\Test',
            stubPath: '/stubs/test.stub',
            suffix: 'Test',
        );

        // Assert: All properties are readonly
        $reflection = new \ReflectionClass($config);
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} should be readonly");
        }
    }

    public function test_config_with_empty_extra_replacements(): void
    {
        // Arrange: Create config with empty extra replacements
        $emptyReplacements = new ReplacementCollection();

        // Act: Create config
        $config = new GeneratorConfig(
            type: GeneratorType::DIRECTIVE,
            basePath: '/app/Directives/',
            baseNamespace: 'App\\Directives',
            stubPath: '/stubs/directive.stub',
            suffix: 'Directive',
            extraReplacements: $emptyReplacements,
        );

        // Assert: Verify empty extra replacements
        $this->assertTrue($config->extraReplacements->isEmpty());
    }

    public function test_config_with_large_extra_replacements(): void
    {
        // Arrange: Create large replacements collection
        $largeReplacements = new ReplacementCollection();
        for ($i = 0; $i < 100; $i++) {
            $largeReplacements->addReplacement("{{placeholder_{$i}}}", "value_{$i}");
        }

        // Act: Create config
        $config = new GeneratorConfig(
            type: GeneratorType::DIRECTIVE,
            basePath: '/app/Directives/',
            baseNamespace: 'App\\Directives',
            stubPath: '/stubs/directive.stub',
            suffix: 'Directive',
            extraReplacements: $largeReplacements,
        );

        // Assert: Verify large replacements
        $this->assertCount(100, $config->extraReplacements);

        $replacements = $config->extraReplacements->toAssociativeArray();
        $this->assertArrayHasKey('{{placeholder_50}}', $replacements);
        $this->assertSame('value_50', $replacements['{{placeholder_50}}']);
    }

    public function test_config_from_enum_returns_correct_extra_replacements(): void
    {
        // Act: Get directive config from enum
        $config = GeneratorType::DIRECTIVE->getConfig();

        // Assert: Verify extra replacements structure
        $replacements = $config->extraReplacements->toAssociativeArray();
        $this->assertIsArray($replacements);
        $this->assertCount(1, $replacements);
        $this->assertArrayHasKey('{{date}}', $replacements);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $replacements['{{date}}']);
    }
}
