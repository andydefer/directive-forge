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

final class MakeRequestDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'make-request {name} {--r}';
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
        $name = $this->argument('name');
        $createRecord = $this->option('r') ?? false;

        if ($name === null || $name === '') {
            $this->error('Request name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getLaravel();

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

            if ($createRecord) {
                $recordName = preg_replace('/-?request$/i', '', $name);
                $recordName = $recordName.'-record';

                $args = new StringTypedCollection;
                $args->add($recordName);

                $this->call(new DirectiveExecutionRecord('make-record', $args));
                $hasRecord = true;
            }

            $stub = $context->loadStub('request');

            $stub->replace(new ReplacementRecord('namespace', $namespace));
            $stub->replace(new ReplacementRecord('class', $className));

            if ($hasRecord) {
                $recordClassName = str_replace('Request', '', $className).'Record';

                $recordNamespace = str_replace('Requests', 'Records', $namespace);
                $recordFullClass = $recordNamespace.'\\'.$recordClassName;

                $stub->replace(new ReplacementRecord('record_class', $recordFullClass));
                $stub->replace(new ReplacementRecord('record_import', 'use '.$recordFullClass.';'));
                $stub->replace(new ReplacementRecord('record_return', $recordClassName.'::from([ // TODO: Map request data to record properties ])'));
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
