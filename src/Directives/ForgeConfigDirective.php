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
use InvalidArgumentException;
use Throwable;

final class ForgeConfigDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'forge:config {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new config class with its interface';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-config');
        $aliases->add('make-config-class');

        return $aliases;
    }

    public function shouldBootLaravel(): bool
    {
        return true;
    }

    public function execute(): ExitCode
    {
        $name = $this->getArgument('name');

        if ($name === null || $name === '') {
            $this->error('Config name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getApplication();

            $implContext = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('config', 'Config', 'Configs'));

            $generator = $app->make(GeneratorService::class);

            $suffix = $implContext->getSuffix();
            $nameWithSuffix = $name;
            if (! str_ends_with(strtolower($name), strtolower($suffix))) {
                $nameWithSuffix = $name.'-'.$suffix;
            }

            $fileName = $implContext->normalizeFileName($nameWithSuffix);
            $filePath = $implContext->createFilePath($fileName);
            $className = $filePath->getFileName();
            $namespace = $implContext->buildNamespace($filePath);

            $description = $this->getCustomDataItem('description', 'Config for '.$name);

            $segments = $filePath->getSegments()->toArray();
            $baseName = $filePath->getBaseName();
            $folderSegments = array_slice($segments, 0, -1);

            $interfacePathParts = $folderSegments;
            array_unshift($interfacePathParts, 'Configs');

            $interfaceDottedPath = implode('.', $interfacePathParts);
            $interfaceFullName = $interfaceDottedPath !== ''
                ? $interfaceDottedPath.'.'.$baseName.'-interface'
                : $baseName.'-interface';

            $this->call('forge:interface '.$interfaceFullName.' <description="'.$description.'">');

            $interfaceContext = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('interface', 'Interface', 'Contracts'));

            $interfaceFilePath = $interfaceContext->createFilePath($interfaceFullName);
            $interfaceClassName = $interfaceFilePath->getFileName();
            $interfaceNamespace = $interfaceContext->buildNamespace($interfaceFilePath);

            if ($implContext->fileExists($fileName)) {
                $this->error('Config already exists: '.$implContext->getFullPath($fileName));

                return ExitCode::INVALID_ARGUMENT;
            }

            $stub = $implContext->loadStub('config');

            $stub->replace(new ReplacementRecord('namespace', $namespace));
            $stub->replace(new ReplacementRecord('class', $className));
            $stub->replace(new ReplacementRecord('interface_namespace', $interfaceNamespace));
            $stub->replace(new ReplacementRecord('interface', $interfaceClassName));
            $stub->replace(new ReplacementRecord('description', $description));

            $implContext->ensureDirectoryExists();

            $generatorContext = $generator->generate(
                $stub,
                $filePath,
                $implContext->getBaseDirectory()
            );

            if ($generatorContext->isSuccess()) {
                $this->info('✅ Config created successfully!');
                $this->line('   Interface: '.$interfaceNamespace.'\\'.$interfaceClassName);
                $this->line('   Class: '.$namespace.'\\'.$className);
                $this->line('   Mode: '.$implContext->getMode());

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
