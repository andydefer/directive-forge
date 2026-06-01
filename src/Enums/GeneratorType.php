<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Enums;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Records\ReplacementRecord;
use AndyDefer\DirectiveForge\Generators\ActionGenerator;
use AndyDefer\DirectiveForge\Generators\DirectiveGenerator;
use AndyDefer\DirectiveForge\Generators\RecordGenerator;
use AndyDefer\DirectiveForge\Generators\RepositoryGenerator;
use AndyDefer\DirectiveForge\Generators\TaskGenerator;
use AndyDefer\DirectiveForge\Generators\TypedCollectionGenerator;
use AndyDefer\DirectiveForge\Records\GeneratorConfig;

/**
 * Enumeration of available code generator types.
 *
 * Defines all supported generation types, their configurations,
 * and associated metadata for the Forge package.
 *
 * @author Andy Defer
 */
enum GeneratorType: string
{
    case DIRECTIVE = 'directive';
    case ACTION = 'action';
    case TASK = 'task';
    case REPOSITORY = 'repository';
    case RECORD = 'record';
    case TYPED_COLLECTION = 'typed-collection';

    /**
     * Returns the generator configuration for this type.
     *
     * @return GeneratorConfig The configuration record
     */
    public function getConfig(): GeneratorConfig
    {
        $baseConfig = [
            'type' => $this,
            'suffix' => $this->getSuffix(),
        ];

        $specificConfig = match ($this) {
            self::DIRECTIVE => [
                'basePath' => '/app/Directives/',
                'baseNamespace' => 'App\\Directives',
                'stubPath' => __DIR__ . '/../../stubs/directive.stub',
                'extraReplacements' => [
                    new ReplacementRecord('{{date}}', date('Y-m-d H:i:s')),
                ],
            ],
            self::ACTION => [
                'basePath' => '/app/Actions/',
                'baseNamespace' => 'App\\Actions',
                'stubPath' => __DIR__ . '/../../stubs/action.stub',
                // ✅ SUPPRESSION de 'supportsType' et 'requiresType'
            ],
            self::TASK => [
                'basePath' => '/app/Tasks/',
                'baseNamespace' => 'App\\Tasks',
                'stubPath' => __DIR__ . '/../../stubs/task.stub',
            ],
            self::REPOSITORY => [
                'basePath' => '/app/Repositories/',
                'baseNamespace' => 'App\\Repositories',
                'stubPath' => __DIR__ . '/../../stubs/repository.stub',
            ],
            self::RECORD => [
                'basePath' => '/app/Records/',
                'baseNamespace' => 'App\\Records',
                'stubPath' => __DIR__ . '/../../stubs/record.stub',
            ],
            self::TYPED_COLLECTION => [
                'basePath' => '/app/Collections/',
                'baseNamespace' => 'App\\Collections',
                'stubPath' => __DIR__ . '/../../stubs/typed-collection.stub',
                'requiresType' => true,
            ],
        };

        return GeneratorConfig::from(array_merge($baseConfig, $specificConfig));
    }

    /**
     * Returns the fully qualified generator class name for this type.
     *
     * @return string The generator class FQCN
     */
    public function getGeneratorClass(): string
    {
        return match ($this) {
            self::DIRECTIVE => DirectiveGenerator::class,
            self::ACTION => ActionGenerator::class,
            self::TASK => TaskGenerator::class,
            self::REPOSITORY => RepositoryGenerator::class,
            self::RECORD => RecordGenerator::class,
            self::TYPED_COLLECTION => TypedCollectionGenerator::class,
        };
    }

    /**
     * Returns the CLI signature for generating this type.
     *
     * @return string The command signature (e.g., 'make-directive')
     */
    public function getSignature(): string
    {
        return match ($this) {
            self::DIRECTIVE => 'make-directive',
            self::ACTION => 'make-action',
            self::TASK => 'make-task',
            self::REPOSITORY => 'make-repository',
            self::RECORD => 'make-record',
            self::TYPED_COLLECTION => 'make-typed-collection',
        };
    }

    /**
     * Returns the CLI description for this generator type.
     *
     * @return string The human-readable description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::DIRECTIVE => 'Create a new directive class',
            self::ACTION => 'Create a new action class',
            self::TASK => 'Create a new task class',
            self::REPOSITORY => 'Create a new repository class',
            self::RECORD => 'Create a new record class',
            self::TYPED_COLLECTION => 'Create a new typed collection class',
        };
    }

    /**
     * Returns CLI aliases for this generator type.
     *
     * @return array<string> Array of alias names
     */
    public function getAliases(): array
    {
        return match ($this) {
            self::DIRECTIVE => ['create-directive', 'make-cmd'],
            self::ACTION => ['create-action', 'make-act'],
            self::TASK => ['create-task', 'make-job'],
            self::REPOSITORY => ['create-repository', 'make-repo'],
            self::RECORD => ['create-record', 'make-dto'],
            self::TYPED_COLLECTION => ['create-collection', 'make-collection'],
        };
    }

    /**
     * Returns the class suffix for this generator type.
     *
     * @return string The suffix to append to class names
     */
    private function getSuffix(): string
    {
        return match ($this) {
            self::DIRECTIVE => 'Directive',
            self::ACTION => 'Action',
            self::TASK => 'Task',
            self::REPOSITORY => 'Repository',
            self::RECORD => 'Record',
            self::TYPED_COLLECTION => 'Collection',
        };
    }
}
