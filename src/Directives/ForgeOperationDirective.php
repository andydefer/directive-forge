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
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class ForgeOperationDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'forge:operation {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new operation class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-operation');
        $aliases->add('make-op');

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
            $this->error('Operation name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getApplication();

            $baseName = $name;
            $hasOperationSuffix = str_ends_with(strtolower($name), '-operation') || str_ends_with(strtolower($name), 'operation');

            if ($hasOperationSuffix) {
                $baseName = preg_replace('/-?operation$/i', '', $name);
            }

            $className = Str::studly($baseName).'Operation';
            $fileNameKebab = Str::kebab($baseName).'-operation';

            $context = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('operation', 'Operation', 'Operations'));

            $generator = $app->make(GeneratorService::class);

            $normalizedFileName = $context->normalizeFileName($fileNameKebab);
            $filePath = $context->createFilePath($normalizedFileName);
            $class = $filePath->getFileName();
            $namespace = $context->buildNamespace($filePath);

            if ($context->fileExists($normalizedFileName)) {
                $this->error('Operation already exists: '.$context->getFullPath($normalizedFileName));

                return ExitCode::INVALID_ARGUMENT;
            }

            $description = $this->getCustomDataItem('description', 'Operation for '.$className);

            $stub = $context->loadStub('operation');

            $stub->replace(new ReplacementRecord('namespace', $namespace));
            $stub->replace(new ReplacementRecord('class', $class));
            $stub->replace(new ReplacementRecord('description', $description));

            $context->ensureDirectoryExists();

            $generatorContext = $generator->generate(
                $stub,
                $filePath,
                $context->getBaseDirectory()
            );

            if ($generatorContext->isSuccess()) {
                $this->info('✅ Operation created successfully!');
                $this->line('   Path: '.$generatorContext->getFullPath());
                $this->line('   Class: '.$namespace.'\\'.$class);
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
