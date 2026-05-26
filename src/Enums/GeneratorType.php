<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Enums;

use AndyDefer\DirectiveForge\Generators\ActionGenerator;
use AndyDefer\DirectiveForge\Generators\DirectiveGenerator;
use AndyDefer\DirectiveForge\Generators\RecordGenerator;
use AndyDefer\DirectiveForge\Generators\RepositoryGenerator;
use AndyDefer\DirectiveForge\Generators\TaskGenerator;
use AndyDefer\DirectiveForge\Generators\TypedCollectionGenerator;
use AndyDefer\DirectiveForge\ValueObjects\GeneratorConfig;

enum GeneratorType: string
{
    case DIRECTIVE = 'directive';
    case ACTION = 'action';
    case TASK = 'task';
    case REPOSITORY = 'repository';
    case RECORD = 'record';
    case TYPED_COLLECTION = 'typed-collection';

    public function getConfig(): GeneratorConfig
    {
        return match ($this) {
            self::DIRECTIVE => new GeneratorConfig(
                type: $this,
                basePath: '/app/Directives/',
                baseNamespace: 'App\\Directives',
                stubPath: __DIR__ . '/../../stubs/directive.stub',
                suffix: 'Directive',
                extraReplacements: ['{{date}}' => date('Y-m-d H:i:s')],
            ),
            self::ACTION => new GeneratorConfig(
                type: $this,
                basePath: '/app/Actions/',
                baseNamespace: 'App\\Actions',
                stubPath: __DIR__ . '/../../stubs/action.stub',
                suffix: 'Action',
                supportsType: true,
            ),
            self::TASK => new GeneratorConfig(
                type: $this,
                basePath: '/app/Tasks/',
                baseNamespace: 'App\\Tasks',
                stubPath: __DIR__ . '/../../stubs/task.stub',
                suffix: 'Task',
            ),
            self::REPOSITORY => new GeneratorConfig(
                type: $this,
                basePath: '/app/Repositories/',
                baseNamespace: 'App\\Repositories',
                stubPath: __DIR__ . '/../../stubs/repository.stub',
                suffix: 'Repository',
            ),
            self::RECORD => new GeneratorConfig(
                type: $this,
                basePath: '/app/Records/',
                baseNamespace: 'App\\Records',
                stubPath: __DIR__ . '/../../stubs/record.stub',
                suffix: 'Record',
            ),
            self::TYPED_COLLECTION => new GeneratorConfig(
                type: $this,
                basePath: '/app/Collections/',
                baseNamespace: 'App\\Collections',
                stubPath: __DIR__ . '/../../stubs/typed-collection.stub',
                suffix: 'Collection',
                requiresType: true,
            ),
        };
    }

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
}
