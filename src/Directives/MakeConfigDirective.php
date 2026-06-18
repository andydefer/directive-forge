<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveExecutionRecord;
use AndyDefer\Directive\Records\ReplacementRecord;
use AndyDefer\DirectiveForge\Contexts\DirectiveForgeContext;
use AndyDefer\DirectiveForge\Records\TypeDefinitionRecord;
use AndyDefer\DirectiveForge\Services\GeneratorService;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use InvalidArgumentException;
use Throwable;

final class MakeConfigDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'make-config {name}';
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
        $name = $this->argument('name');

        if ($name === null || $name === '') {
            $this->error('Config name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getLaravel();

            // ✅ Contexte pour l'implémentation
            $implContext = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('config', 'Config', 'Configs'));

            $generator = $app->make(GeneratorService::class);

            // ✅ Normaliser le nom avec suffixe si nécessaire
            $suffix = $implContext->getSuffix();  // 'Config'
            $nameWithSuffix = $name;
            if (! str_ends_with(strtolower($name), strtolower($suffix))) {
                $nameWithSuffix = $name.'-'.$suffix;
            }

            // ✅ Nom pour l'implémentation
            $fileName = $implContext->normalizeFileName($nameWithSuffix);
            $filePath = $implContext->createFilePath($fileName);
            $className = $filePath->getFileName();
            $namespace = $implContext->buildNamespace($filePath);

            // ✅ Nom pour l'interface
            $plural = $implContext->getPlural();  // 'Configs'
            $interfaceName = strtolower($plural.'.'.$nameWithSuffix.'-interface');
            $description = $this->argument('description') ?? 'Config for '.$name;

            // Appeler make-interface
            $args = new StringTypedCollection;
            $args->add($interfaceName);
            $args->add($description);

            $this->call(new DirectiveExecutionRecord('make-interface', $args));

            // ✅ Créer l'implémentation
            if ($implContext->fileExists($fileName)) {
                $this->error('Config already exists: '.$implContext->getFullPath($fileName));

                return ExitCode::INVALID_ARGUMENT;
            }

            $interfaceClassName = $className.'Interface';
            $interfaceNamespace = str_replace('\\Configs', '\\Contracts\\Configs', $namespace);

            $stub = $implContext->loadStub('config');

            $stub->replace(new ReplacementRecord('namespace', $namespace));
            $stub->replace(new ReplacementRecord('class', $className));
            $stub->replace(new ReplacementRecord('interface_namespace', $interfaceNamespace));
            $stub->replace(new ReplacementRecord('interface', $interfaceClassName));
            $stub->replace(new ReplacementRecord('description', 'Config for '.$name));

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
