<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DirectiveForge\Contexts\DirectiveForgeContext;
use AndyDefer\DirectiveForge\Records\ReplacementRecord;
use AndyDefer\DirectiveForge\Records\TypeDefinitionRecord;
use AndyDefer\DirectiveForge\Services\GeneratorService;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\PhpServices\Services\FileSystemService;
use InvalidArgumentException;
use Throwable;

final class ForgeTaskDirective extends AbstractDirective
{
    private const TASK_TYPES = [
        'u' => ['name' => 'unique', 'abstract' => 'AbstractUniqueTask', 'folder' => 'UniqueTasks'],
        'unique' => ['name' => 'unique', 'abstract' => 'AbstractUniqueTask', 'folder' => 'UniqueTasks'],
        'r' => ['name' => 'recurring', 'abstract' => 'AbstractRecurringTask', 'folder' => 'RecurringTasks'],
        'recurring' => ['name' => 'recurring', 'abstract' => 'AbstractRecurringTask', 'folder' => 'RecurringTasks'],
    ];

    public function getSignature(): string
    {
        return 'forge:task {name} ::type->[u,unique,r,recurring]=*';
    }

    public function getDescription(): string
    {
        return 'Create a new task class (unique or recurring)';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-task');
        $aliases->add('make-job');

        return $aliases;
    }

    public function shouldBootLaravel(): bool
    {
        return true;
    }

    public function execute(): ExitCode
    {
        $name = $this->getArgument('name');
        $type = $this->getEnum('type');

        if ($name === null || $name === '') {
            $this->error('Task name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        if ($type === null || ! isset(self::TASK_TYPES[$type])) {
            $this->error('Task type is required. Use "u" or "unique" for unique tasks, "r" or "recurring" for recurring tasks.');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getApplication();

            $typeConfig = self::TASK_TYPES[$type];
            $typeName = $typeConfig['name'];
            $abstractClass = $typeConfig['abstract'];
            $folderName = $typeConfig['folder'];

            $cleanName = preg_replace('/-?task$/i', '', $name);
            $taskName = $cleanName.'-'.$typeName.'-task';

            $context = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('task', 'Task', 'Tasks'));

            $generator = $app->make(GeneratorService::class);

            $fileName = $context->normalizeFileName($taskName);
            $filePath = $context->createFilePath($fileName);

            $className = $filePath->getFileName();
            $baseNamespace = $app['config']->get('directive-forge.namespace', 'App');

            $namespace = $baseNamespace.'\\Tasks\\'.$folderName;
            if ($filePath->hasFolders()) {
                $namespace .= '\\'.$filePath->getNamespace();
            }

            $fullPath = $context->getBaseDirectory().DIRECTORY_SEPARATOR.$folderName.DIRECTORY_SEPARATOR.$filePath->getFilePath();

            $filesystem = $app->make(FileSystemService::class);
            $filesystem->ensureDirectoryExists(dirname($fullPath));

            if ($filesystem->exists($fullPath)) {
                $this->error('Task already exists: '.$fullPath);

                return ExitCode::INVALID_ARGUMENT;
            }

            $description = $this->getCustomDataItem('description', 'Task for '.$className);

            $stub = $context->loadStub('task');

            $stub->replace(new ReplacementRecord('namespace', $namespace));
            $stub->replace(new ReplacementRecord('class', $className));
            $stub->replace(new ReplacementRecord('abstract_class', $abstractClass));
            $stub->replace(new ReplacementRecord('description', $description));

            $generatorContext = $generator->generate(
                $stub,
                $filePath,
                $context->getBaseDirectory().DIRECTORY_SEPARATOR.$folderName
            );

            if ($generatorContext->isSuccess()) {
                $this->info('✅ '.ucfirst($typeName).' task created successfully!');
                $this->line('   Path: '.$generatorContext->getFullPath());
                $this->line('   Class: '.$namespace.'\\'.$className);
                $this->line('   Type: '.ucfirst($typeName));
                $this->line('   Mode: '.$context->getMode());

                return ExitCode::SUCCESS;
            }

            $this->error('❌ '.$generatorContext->getMessage());

            return ExitCode::FAILURE;

        } catch (InvalidArgumentException $e) {
            $this->error('❌ '.$e->getMessage());

            return ExitCode::INVALID_ARGUMENT;
        } catch (Throwable $e) {
            $this->error('❌ '.$e->getMessage());

            return ExitCode::FAILURE;
        }
    }
}
