<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\ValueObjects;

use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\GeneratorConfig;

final class GeneratorConfigTest extends UnitTestCase
{
    public function test_constructor_creates_valid_object(): void
    {
        $config = new GeneratorConfig(
            type: GeneratorType::DIRECTIVE,
            basePath: '/app/Test/',
            baseNamespace: 'App\\Test',
            stubPath: '/stubs/test.stub',
            suffix: 'Test',
            requiresType: false,
            supportsType: false,
            extraReplacements: ['{{extra}}' => 'value'],
        );

        $this->assertInstanceOf(GeneratorConfig::class, $config);
        $this->assertSame(GeneratorType::DIRECTIVE, $config->type);
        $this->assertSame('/app/Test/', $config->basePath);
        $this->assertSame('App\\Test', $config->baseNamespace);
        $this->assertSame('/stubs/test.stub', $config->stubPath);
        $this->assertSame('Test', $config->suffix);
        $this->assertFalse($config->requiresType);
        $this->assertFalse($config->supportsType);
        $this->assertSame(['{{extra}}' => 'value'], $config->extraReplacements);
    }

    public function test_constructor_with_default_values(): void
    {
        $config = new GeneratorConfig(
            type: GeneratorType::ACTION,
            basePath: '/app/Actions/',
            baseNamespace: 'App\\Actions',
            stubPath: '/stubs/action.stub',
            suffix: 'Action',
        );

        $this->assertSame(GeneratorType::ACTION, $config->type);
        $this->assertSame('/app/Actions/', $config->basePath);
        $this->assertSame('App\\Actions', $config->baseNamespace);
        $this->assertSame('/stubs/action.stub', $config->stubPath);
        $this->assertSame('Action', $config->suffix);
        $this->assertFalse($config->requiresType);
        $this->assertFalse($config->supportsType);
        $this->assertSame([], $config->extraReplacements);
    }

    public function test_constructor_with_supports_type_true(): void
    {
        $config = new GeneratorConfig(
            type: GeneratorType::ACTION,
            basePath: '/app/Actions/',
            baseNamespace: 'App\\Actions',
            stubPath: '/stubs/action.stub',
            suffix: 'Action',
            supportsType: true,
        );

        $this->assertTrue($config->supportsType);
    }

    public function test_constructor_with_requires_type_true(): void
    {
        $config = new GeneratorConfig(
            type: GeneratorType::TYPED_COLLECTION,
            basePath: '/app/Collections/',
            baseNamespace: 'App\\Collections',
            stubPath: '/stubs/typed-collection.stub',
            suffix: 'Collection',
            requiresType: true,
        );

        $this->assertTrue($config->requiresType);
    }

    public function test_constructor_with_extra_replacements(): void
    {
        $extraReplacements = [
            '{{date}}' => '2024-01-01',
            '{{author}}' => 'John Doe',
            '{{version}}' => '1.0.0',
        ];

        $config = new GeneratorConfig(
            type: GeneratorType::DIRECTIVE,
            basePath: '/app/Directives/',
            baseNamespace: 'App\\Directives',
            stubPath: '/stubs/directive.stub',
            suffix: 'Directive',
            extraReplacements: $extraReplacements,
        );

        $this->assertCount(3, $config->extraReplacements);
        $this->assertSame('2024-01-01', $config->extraReplacements['{{date}}']);
        $this->assertSame('John Doe', $config->extraReplacements['{{author}}']);
        $this->assertSame('1.0.0', $config->extraReplacements['{{version}}']);
    }

    public function test_different_generator_types_have_different_configs(): void
    {
        $directiveConfig = GeneratorType::DIRECTIVE->getConfig();
        $actionConfig = GeneratorType::ACTION->getConfig();
        $taskConfig = GeneratorType::TASK->getConfig();

        $this->assertNotSame($directiveConfig->basePath, $actionConfig->basePath);
        $this->assertNotSame($actionConfig->basePath, $taskConfig->basePath);
        $this->assertNotSame($directiveConfig->suffix, $actionConfig->suffix);
        $this->assertNotSame($actionConfig->suffix, $taskConfig->suffix);
    }

    public function test_config_from_enum_is_immutable(): void
    {
        $config1 = GeneratorType::DIRECTIVE->getConfig();
        $config2 = GeneratorType::DIRECTIVE->getConfig();

        $this->assertNotSame($config1, $config2);
        $this->assertEquals($config1, $config2);
    }

    public function test_config_contains_correct_base_path_for_directive(): void
    {
        $config = GeneratorType::DIRECTIVE->getConfig();

        $this->assertSame('/app/Directives/', $config->basePath);
    }

    public function test_config_contains_correct_base_path_for_action(): void
    {
        $config = GeneratorType::ACTION->getConfig();

        $this->assertSame('/app/Actions/', $config->basePath);
    }

    public function test_config_contains_correct_base_path_for_task(): void
    {
        $config = GeneratorType::TASK->getConfig();

        $this->assertSame('/app/Tasks/', $config->basePath);
    }

    public function test_config_contains_correct_base_path_for_repository(): void
    {
        $config = GeneratorType::REPOSITORY->getConfig();

        $this->assertSame('/app/Repositories/', $config->basePath);
    }

    public function test_config_contains_correct_base_path_for_record(): void
    {
        $config = GeneratorType::RECORD->getConfig();

        $this->assertSame('/app/Records/', $config->basePath);
    }

    public function test_config_contains_correct_base_path_for_typed_collection(): void
    {
        $config = GeneratorType::TYPED_COLLECTION->getConfig();

        $this->assertSame('/app/Collections/', $config->basePath);
    }

    public function test_config_contains_correct_base_namespace(): void
    {
        $directiveConfig = GeneratorType::DIRECTIVE->getConfig();
        $actionConfig = GeneratorType::ACTION->getConfig();

        $this->assertSame('App\\Directives', $directiveConfig->baseNamespace);
        $this->assertSame('App\\Actions', $actionConfig->baseNamespace);
    }

    public function test_config_contains_correct_suffix(): void
    {
        $this->assertSame('Directive', GeneratorType::DIRECTIVE->getConfig()->suffix);
        $this->assertSame('Action', GeneratorType::ACTION->getConfig()->suffix);
        $this->assertSame('Task', GeneratorType::TASK->getConfig()->suffix);
        $this->assertSame('Repository', GeneratorType::REPOSITORY->getConfig()->suffix);
        $this->assertSame('Record', GeneratorType::RECORD->getConfig()->suffix);
        $this->assertSame('Collection', GeneratorType::TYPED_COLLECTION->getConfig()->suffix);
    }

    public function test_config_contains_correct_stub_path(): void
    {
        $directiveConfig = GeneratorType::DIRECTIVE->getConfig();
        $actionConfig = GeneratorType::ACTION->getConfig();

        $this->assertStringContainsString('directive.stub', $directiveConfig->stubPath);
        $this->assertStringContainsString('action.stub', $actionConfig->stubPath);
    }

    public function test_only_action_supports_type(): void
    {
        $directiveConfig = GeneratorType::DIRECTIVE->getConfig();
        $actionConfig = GeneratorType::ACTION->getConfig();
        $taskConfig = GeneratorType::TASK->getConfig();
        $repositoryConfig = GeneratorType::REPOSITORY->getConfig();
        $recordConfig = GeneratorType::RECORD->getConfig();
        $collectionConfig = GeneratorType::TYPED_COLLECTION->getConfig();

        $this->assertFalse($directiveConfig->supportsType);
        $this->assertTrue($actionConfig->supportsType);
        $this->assertFalse($taskConfig->supportsType);
        $this->assertFalse($repositoryConfig->supportsType);
        $this->assertFalse($recordConfig->supportsType);
        $this->assertFalse($collectionConfig->supportsType);
    }

    public function test_only_typed_collection_requires_type(): void
    {
        $directiveConfig = GeneratorType::DIRECTIVE->getConfig();
        $actionConfig = GeneratorType::ACTION->getConfig();
        $taskConfig = GeneratorType::TASK->getConfig();
        $repositoryConfig = GeneratorType::REPOSITORY->getConfig();
        $recordConfig = GeneratorType::RECORD->getConfig();
        $collectionConfig = GeneratorType::TYPED_COLLECTION->getConfig();

        $this->assertFalse($directiveConfig->requiresType);
        $this->assertFalse($actionConfig->requiresType);
        $this->assertFalse($taskConfig->requiresType);
        $this->assertFalse($repositoryConfig->requiresType);
        $this->assertFalse($recordConfig->requiresType);
        $this->assertTrue($collectionConfig->requiresType);
    }

    public function test_directive_config_has_extra_replacements(): void
    {
        $config = GeneratorType::DIRECTIVE->getConfig();

        $this->assertArrayHasKey('{{date}}', $config->extraReplacements);
        $this->assertNotEmpty($config->extraReplacements['{{date}}']);
    }

    public function test_action_config_has_no_extra_replacements_by_default(): void
    {
        $config = GeneratorType::ACTION->getConfig();

        $this->assertEmpty($config->extraReplacements);
    }

    public function test_config_properties_are_readonly(): void
    {
        $config = new GeneratorConfig(
            type: GeneratorType::DIRECTIVE,
            basePath: '/app/Test/',
            baseNamespace: 'App\\Test',
            stubPath: '/stubs/test.stub',
            suffix: 'Test',
        );

        // All properties should be readonly (not writable)
        $reflection = new \ReflectionClass($config);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} should be readonly");
        }
    }

    public function test_config_with_empty_extra_replacements(): void
    {
        $config = new GeneratorConfig(
            type: GeneratorType::DIRECTIVE,
            basePath: '/app/Directives/',
            baseNamespace: 'App\\Directives',
            stubPath: '/stubs/directive.stub',
            suffix: 'Directive',
            extraReplacements: [],
        );

        $this->assertIsArray($config->extraReplacements);
        $this->assertEmpty($config->extraReplacements);
    }

    public function test_config_with_large_extra_replacements(): void
    {
        $largeReplacements = [];
        for ($i = 0; $i < 100; $i++) {
            $largeReplacements["{{placeholder_{$i}}}"] = "value_{$i}";
        }

        $config = new GeneratorConfig(
            type: GeneratorType::DIRECTIVE,
            basePath: '/app/Directives/',
            baseNamespace: 'App\\Directives',
            stubPath: '/stubs/directive.stub',
            suffix: 'Directive',
            extraReplacements: $largeReplacements,
        );

        $this->assertCount(100, $config->extraReplacements);
        $this->assertSame('value_50', $config->extraReplacements['{{placeholder_50}}']);
    }
}
