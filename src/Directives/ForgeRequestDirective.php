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

final class ForgeRequestDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'forge:request {name} {--r}';
    }

    public function getDescription(): string
    {
        return 'Create a new request class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-request');
        $aliases->add('make-req');

        return $aliases;
    }

    public function shouldBootLaravel(): bool
    {
        return true;
    }

    public function execute(): ExitCode
    {
        $name = $this->getArgument('name');
        $createRecord = $this->getArgument('r') ?? false;

        if ($name === null || $name === '') {
            $this->error('Request name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getApplication();

            $context = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('request', 'Request', 'Requests'));

            $generator = $app->make(GeneratorService::class);

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
                $this->error('Request already exists: '.$context->getFullPath($fileName));

                return ExitCode::INVALID_ARGUMENT;
            }

            $hasRecord = false;
            $recordClassName = null;
            $recordFullClass = null;
            $recordNamespace = null;

            if ($createRecord) {
                $baseName = preg_replace('/-?request$/i', '', $name);
                $recordName = $baseName.'-record';

                $recordQuery = 'forge:record '.$recordName;

                $kernel = $this->getKernel();

                ob_start();
                $exitCode = $kernel->runSignature($recordQuery);
                $output = ob_get_clean();

                if ($exitCode === ExitCode::SUCCESS) {
                    $this->line('   ✅ Record created successfully!');
                } else {
                    $this->line('   ℹ️ Record already exists, skipping creation');
                }

                $hasRecord = true;

                $recordContext = $app->make(DirectiveForgeContext::class)
                    ->setTypeDefinition(new TypeDefinitionRecord('record', 'Record', 'Records'));

                $recordFileName = $recordContext->normalizeFileName($recordName);
                $recordFilePath = $recordContext->createFilePath($recordFileName);
                $recordClassName = $recordFilePath->getFileName();

                $recordNamespace = str_replace('Requests', 'Records', $namespace);
                $recordFullClass = $recordNamespace.'\\'.$recordClassName;
            }

            $description = $this->getCustomDataItem('description', 'Request for '.$name);

            $stub = $context->loadStub('request');

            $stub->replace(new ReplacementRecord('namespace', $namespace));
            $stub->replace(new ReplacementRecord('class', $className));
            $stub->replace(new ReplacementRecord('description', $description));

            if ($hasRecord) {
                $stub->replace(new ReplacementRecord('record_class', $recordFullClass));
                $stub->replace(new ReplacementRecord('record_import', 'use '.$recordFullClass.';'));
                $stub->replace(new ReplacementRecord('record_return', $recordClassName.'::from([ /** TODO: Map request data to record properties */])'));
            } else {
                $stub->replace(new ReplacementRecord('record_class', 'EmptyRecord'));
                $stub->replace(new ReplacementRecord('record_import', 'use AndyDefer\\DomainStructures\\Utils\\EmptyRecord;'));
                $stub->replace(new ReplacementRecord('record_return', 'new EmptyRecord();'));
            }

            $context->ensureDirectoryExists();

            $generatorContext = $generator->generate(
                $stub,
                $filePath,
                $context->getBaseDirectory()
            );

            if ($generatorContext->isSuccess()) {
                $this->info('✅ Request created successfully!');
                $this->line('   Path: '.$generatorContext->getFullPath());
                $this->line('   Class: '.$namespace.'\\'.$className);
                $this->line('   Record: '.($hasRecord ? $recordClassName : 'EmptyRecord'));
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
