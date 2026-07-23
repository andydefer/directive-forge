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

final class ForgeEnumDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'forge:enum {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new enum class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-enum');

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
            $this->error('Enum name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getApplication();

            $baseName = $name;
            $hasEnumSuffix = str_ends_with(strtolower($name), '-enum');

            if ($hasEnumSuffix) {
                $baseName = substr($name, 0, -5);
            }

            $fileNameKebab = Str::kebab($baseName);

            $context = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('enum', 'Enum', 'Enums'));

            $generator = $app->make(GeneratorService::class);

            // Supprimer le suffixe -enum ajouté par normalizeFileName
            $normalizedFileName = str_replace('-enum', '', $context->normalizeFileName($fileNameKebab));
            $filePath = $context->createFilePath($normalizedFileName);
            $className = $filePath->getFileName();
            $namespace = $context->buildNamespace($filePath);

            if ($context->fileExists($normalizedFileName)) {
                $this->error('Enum already exists: '.$context->getFullPath($normalizedFileName));

                return ExitCode::INVALID_ARGUMENT;
            }

            $description = $this->getCustomDataItem('description', 'Enum for '.$className);

            $stub = $context->loadStub('enum');

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
                $this->info('✅ Enum created successfully!');
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
