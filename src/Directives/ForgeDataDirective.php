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

final class ForgeDataDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'forge:data {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new data DTO class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-data');
        $aliases->add('make-data');

        return $aliases;
    }

    public function shouldBootLaravel(): bool
    {
        return true;
    }

    public function execute(): ExitCode
    {
        // Récupération de l'argument
        $name = $this->getArgument('name');

        if ($name === null || $name === '') {
            $this->error('Data name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            // Récupération de l'application
            $app = $this->getApplication();

            $context = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('data', 'Data', 'Datas'));

            $generator = $app->make(GeneratorService::class);

            $fileName = $context->normalizeFileName($name);
            $filePath = $context->createFilePath($fileName);
            $className = $filePath->getFileName();
            $namespace = $context->buildNamespace($filePath);

            if ($context->fileExists($fileName)) {
                $this->error('Data already exists: '.$context->getFullPath($fileName));

                return ExitCode::INVALID_ARGUMENT;
            }

            // Récupération de la description depuis les tags personnalisés
            $description = $this->getCustomDataItem('description', 'Data DTO for '.$name);

            $stub = $context->loadStub('data');

            $stub->replace(new ReplacementRecord('namespace', $namespace));
            $stub->replace(new ReplacementRecord('class', $className));
            $stub->replace(new ReplacementRecord('description', $description));

            $context->ensureDirectoryExists();

            $generatorContext = $generator->generate(
                $stub,
                $filePath,
                $context->getBaseDirectory()
            );

            if ($generatorContext->isSuccess()) {
                $this->info('✅ Data created successfully!');
                $this->line('   Path: '.$generatorContext->getFullPath());
                $this->line('   Class: '.$namespace.'\\'.$className);
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
