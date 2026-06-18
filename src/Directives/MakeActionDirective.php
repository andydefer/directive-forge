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

final class MakeActionDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'make-action {name} {--supfile}';
    }

    public function getDescription(): string
    {
        return 'Create a new action class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-action');
        $aliases->add('make-act');

        return $aliases;
    }

    public function shouldBootLaravel(): bool
    {
        return true;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');
        $supfile = $this->option('supfile');

        if ($name === null || $name === '') {
            $this->error('Action name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getLaravel();

            $context = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('action', 'Action', 'Actions'));

            $generator = $app->make(GeneratorService::class);

            // Gestion des fichiers supplémentaires
            $baseName = preg_replace('/-?action$/i', '', $name);

            if ($supfile !== null && $supfile !== false) {
                // Cas -a : Créer une request avec record (make-request --r)
                if ($supfile === 'a') {
                    $requestName = $baseName.'-request';
                    $args = new StringTypedCollection;
                    $args->add($requestName);
                    $args->add('--r');  // Crée le record associé
                    $this->call(new DirectiveExecutionRecord('make-request', $args));
                }
                // Cas -r : Créer une request seule (sans record)
                elseif ($supfile === 'r') {
                    $requestName = $baseName.'-request';
                    $args = new StringTypedCollection;
                    $args->add($requestName);
                    $this->call(new DirectiveExecutionRecord('make-request', $args));
                }
            }

            // Création de l'action
            $suffix = $context->getSuffix();
            $nameWithSuffix = $name;
            if (! str_ends_with(strtolower($name), strtolower($suffix))) {
                $nameWithSuffix = $name.'-'.$suffix;
            }

            $fileName = $context->normalizeFileName($nameWithSuffix);
            $filePath = $context->createFilePath($fileName);
            $className = $filePath->getFileName();
            $namespace = $context->buildNamespace($filePath);

            if ($context->fileExists($fileName)) {
                $this->error('Action already exists: '.$context->getFullPath($fileName));

                return ExitCode::INVALID_ARGUMENT;
            }

            $stub = $context->loadStub('action');

            $stub->replace(new ReplacementRecord('namespace', $namespace));
            $stub->replace(new ReplacementRecord('class', $className));

            $context->ensureDirectoryExists();

            $generatorContext = $generator->generate(
                $stub,
                $filePath,
                $context->getBaseDirectory()
            );

            if ($generatorContext->isSuccess()) {
                $this->info('✅ Action created successfully!');
                $this->line('   Path: '.$generatorContext->getFullPath());
                $this->line('   Class: '.$namespace.'\\'.$className);
                $this->line('   Mode: '.$context->getMode());

                if ($supfile === 'a') {
                    $this->line('   ✅ Request + Record created successfully!');
                } elseif ($supfile === 'r') {
                    $this->line('   ✅ Request created successfully!');
                }

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
