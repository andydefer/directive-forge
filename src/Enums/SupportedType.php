<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Enums;

use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Directives\MakeConfigDirective;
use AndyDefer\DirectiveForge\Directives\MakeDataDirective;
use AndyDefer\DirectiveForge\Directives\MakeDirective;
use AndyDefer\DirectiveForge\Directives\MakeInterfaceDirective;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective;
use AndyDefer\DirectiveForge\Directives\MakeRequestDirective;
use AndyDefer\DirectiveForge\Directives\MakeServiceDirective;
use AndyDefer\DirectiveForge\Directives\MakeTaskDirective;
use AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective;
use AndyDefer\DirectiveForge\Directives\MakeValueObjectDirective;

enum SupportedType: string
{
    case ACTION = 'action';
    case CONFIG = 'config';
    case DATA = 'data';
    case DIRECTIVE = 'directive';
    case INTERFACE = 'interface';
    case RECORD = 'record';
    case REPOSITORY = 'repository';
    case REQUEST = 'request';
    case SERVICE = 'service';
    case TASK = 'task';
    case TYPED_COLLECTION = 'typed-collection';
    case VALUE_OBJECT = 'value-object';

    public function getDirectiveClass(): string
    {
        return match ($this) {
            self::ACTION => MakeActionDirective::class,
            self::CONFIG => MakeConfigDirective::class,
            self::DATA => MakeDataDirective::class,
            self::DIRECTIVE => MakeDirective::class,
            self::INTERFACE => MakeInterfaceDirective::class,
            self::RECORD => MakeRecordDirective::class,
            self::REPOSITORY => MakeRepositoryDirective::class,
            self::REQUEST => MakeRequestDirective::class,
            self::SERVICE => MakeServiceDirective::class,
            self::TASK => MakeTaskDirective::class,
            self::TYPED_COLLECTION => MakeTypedCollectionDirective::class,
            self::VALUE_OBJECT => MakeValueObjectDirective::class,
        };
    }

    public function getFolder(): string
    {
        return match ($this) {
            self::ACTION => 'Actions',
            self::CONFIG => 'Configs',
            self::DATA => 'Datas',
            self::DIRECTIVE => 'Directives',
            self::INTERFACE => 'Contracts',  // ← Toujours Contracts
            self::RECORD => 'Records',
            self::REPOSITORY => 'Repositories',
            self::REQUEST => 'Requests',
            self::SERVICE => 'Services',
            self::TASK => 'Tasks',
            self::TYPED_COLLECTION => 'Collections',
            self::VALUE_OBJECT => 'ValueObjects',
        };
    }

    public function getCommand(): string
    {
        return match ($this) {
            self::ACTION => 'make-action',
            self::CONFIG => 'make-config',
            self::DATA => 'make-data',
            self::DIRECTIVE => 'make-directive',
            self::INTERFACE => 'make-interface',
            self::RECORD => 'make-record',
            self::REPOSITORY => 'make-repository',
            self::REQUEST => 'make-request',
            self::SERVICE => 'make-service',
            self::TASK => 'make-task',
            self::TYPED_COLLECTION => 'make-typed-collection',
            self::VALUE_OBJECT => 'make-value-object',
        };
    }

    public function getStubName(): string
    {
        return match ($this) {
            self::ACTION => 'action',
            self::CONFIG => 'config',
            self::DATA => 'data',
            self::DIRECTIVE => 'directive',
            self::INTERFACE => 'interface',
            self::RECORD => 'record',
            self::REPOSITORY => 'repository',
            self::REQUEST => 'request',
            self::SERVICE => 'service',
            self::TASK => 'task',
            self::TYPED_COLLECTION => 'typed-collection',
            self::VALUE_OBJECT => 'value-object',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ACTION => 'Create a new action class',
            self::CONFIG => 'Create a new config class',
            self::DATA => 'Create a new data class',
            self::DIRECTIVE => 'Create a new directive class',
            self::INTERFACE => 'Create a new interface',
            self::RECORD => 'Create a new record class',
            self::REPOSITORY => 'Create a new repository class',
            self::REQUEST => 'Create a new request class',
            self::SERVICE => 'Create a new service class',
            self::TASK => 'Create a new task class',
            self::TYPED_COLLECTION => 'Create a new typed collection class',
            self::VALUE_OBJECT => 'Create a new value object class',
        };
    }

    public static function fromCommand(string $command): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->getCommand() === $command) {
                return $case;
            }
        }

        return null;
    }

    public static function fromFolder(string $folder): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->getFolder() === $folder) {
                return $case;
            }
        }

        return null;
    }

    public static function fromType(string $type): self
    {
        $type = strtolower($type);

        return match ($type) {
            'action', 'actions' => self::ACTION,
            'config', 'configs' => self::CONFIG,
            'data', 'datas' => self::DATA,
            'directive', 'directives' => self::DIRECTIVE,
            'interface', 'interfaces', 'contract', 'contracts' => self::INTERFACE,
            'record', 'records' => self::RECORD,
            'repository', 'repositories' => self::REPOSITORY,
            'request', 'requests' => self::REQUEST,
            'service', 'services' => self::SERVICE,
            'task', 'tasks' => self::TASK,
            'typed-collection', 'typed-collections' => self::TYPED_COLLECTION,
            'valueobject', 'valueobjects', 'vo', 'vos' => self::VALUE_OBJECT,  // ← AJOUT
            default => self::DATA,
        };
    }
}
