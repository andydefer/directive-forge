<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Enums;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Records\ReplacementRecord;
use AndyDefer\DirectiveForge\Generators\ActionGenerator;
use AndyDefer\DirectiveForge\Generators\ConfigGenerator;
use AndyDefer\DirectiveForge\Generators\DataGenerator;
use AndyDefer\DirectiveForge\Generators\DirectiveGenerator;
use AndyDefer\DirectiveForge\Generators\RecordGenerator;
use AndyDefer\DirectiveForge\Generators\RepositoryGenerator;
use AndyDefer\DirectiveForge\Generators\RequestGenerator;
use AndyDefer\DirectiveForge\Generators\ServiceGenerator;
use AndyDefer\DirectiveForge\Generators\TaskGenerator;
use AndyDefer\DirectiveForge\Generators\TypedCollectionGenerator;
use AndyDefer\DirectiveForge\Generators\ValueObjectGenerator;
use AndyDefer\DirectiveForge\Records\GeneratorConfig;

enum GeneratorType: string
{
    case DIRECTIVE = 'directive';
    case ACTION = 'action';
    case TASK = 'task';
    case REPOSITORY = 'repository';
    case RECORD = 'record';
    case TYPED_COLLECTION = 'typed-collection';
    case SERVICE = 'service';
    case REQUEST = 'request';
    case VALUE_OBJECT = 'value-object';
    case CONFIG = 'config';
    case DATA = 'data';

    public function getConfig(): GeneratorConfig
    {
        $baseConfig = [
            'type' => $this,
            'suffix' => $this->getSuffix(),
        ];

        $stubBasePath = dirname(__DIR__, 2).'/stubs/';

        $specificConfig = match ($this) {
            self::DIRECTIVE => [
                'basePath' => '/app/Directives/',
                'baseNamespace' => 'App\\Directives',
                'stubPath' => $stubBasePath.'directive.stub',
                'extraReplacements' => new ReplacementCollection([
                    new ReplacementRecord('{{date}}', date('Y-m-d H:i:s')),
                ]),
            ],
            self::ACTION => [
                'basePath' => '/app/Actions/',
                'baseNamespace' => 'App\\Actions',
                'stubPath' => $stubBasePath.'action.stub',
            ],
            self::TASK => [
                'basePath' => '/app/Tasks/',
                'baseNamespace' => 'App\\Tasks',
                'stubPath' => $stubBasePath.'task.stub',
            ],
            self::REPOSITORY => [
                'basePath' => '/app/Repositories/',
                'baseNamespace' => 'App\\Repositories',
                'stubPath' => $stubBasePath.'repository.stub',
            ],
            self::RECORD => [
                'basePath' => '/app/Records/',
                'baseNamespace' => 'App\\Records',
                'stubPath' => $stubBasePath.'record.stub',
            ],
            self::TYPED_COLLECTION => [
                'basePath' => '/app/Collections/',
                'baseNamespace' => 'App\\Collections',
                'stubPath' => $stubBasePath.'typed-collection.stub',
                'requiresType' => true,
            ],
            self::SERVICE => [
                'basePath' => '/app/Services/',
                'baseNamespace' => 'App\\Services',
                'stubPath' => $stubBasePath.'service.stub',
            ],
            self::REQUEST => [
                'basePath' => '/app/Http/Requests/',
                'baseNamespace' => 'App\\Http\\Requests',
                'stubPath' => $stubBasePath.'request.stub',
            ],
            self::VALUE_OBJECT => [
                'basePath' => '/app/ValueObjects/',
                'baseNamespace' => 'App\\ValueObjects',
                'stubPath' => $stubBasePath.'value-object.stub',
            ],
            self::CONFIG => [
                'basePath' => '/app/Configs/',
                'baseNamespace' => 'App\\Configs',
                'stubPath' => $stubBasePath.'config.stub',
            ],
            self::DATA => [
                'basePath' => '/app/Data/',
                'baseNamespace' => 'App\\Data',
                'stubPath' => $stubBasePath.'data.stub',
            ],
        };

        return GeneratorConfig::from(array_merge($baseConfig, $specificConfig));
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
            self::SERVICE => ServiceGenerator::class,
            self::REQUEST => RequestGenerator::class,
            self::VALUE_OBJECT => ValueObjectGenerator::class,
            self::CONFIG => ConfigGenerator::class,
            self::DATA => DataGenerator::class,
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
            self::SERVICE => 'make-service',
            self::REQUEST => 'make-request',
            self::VALUE_OBJECT => 'make-vo',
            self::CONFIG => 'make-config',
            self::DATA => 'make-data',
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
            self::SERVICE => 'Create a new service class',
            self::REQUEST => 'Create a new form request class',
            self::VALUE_OBJECT => 'Create a new value object class (VO)',
            self::CONFIG => 'Create a new configuration class',
            self::DATA => 'Create a new data DTO class (with --fully option for Record and Collection)',
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
            self::SERVICE => ['create-service', 'make-svc'],
            self::REQUEST => ['create-request', 'make-req'],
            self::VALUE_OBJECT => ['create-vo', 'make-value-object'],
            self::CONFIG => ['create-config', 'make-cfg'],
            self::DATA => ['create-data', 'make-dto'],
        };
    }

    private function getSuffix(): string
    {
        return match ($this) {
            self::DIRECTIVE => 'Directive',
            self::ACTION => 'Action',
            self::TASK => 'Task',
            self::REPOSITORY => 'Repository',
            self::RECORD => 'Record',
            self::TYPED_COLLECTION => 'Collection',
            self::SERVICE => 'Service',
            self::REQUEST => 'Request',
            self::VALUE_OBJECT => 'VO',
            self::CONFIG => 'Config',
            self::DATA => 'Data',
        };
    }
}
