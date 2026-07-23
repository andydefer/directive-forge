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

final class ForgeRepositoryDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'forge:repository {model}';
    }

    public function getDescription(): string
    {
        return 'Create a new repository with associated records for a model';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-repository');
        $aliases->add('make-repo');

        return $aliases;
    }

    public function shouldBootLaravel(): bool
    {
        return true;
    }

    public function execute(): ExitCode
    {
        $modelName = $this->getArgument('model');

        if ($modelName === null || $modelName === '') {
            $this->error('Model name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getApplication();

            $segments = explode('.', $modelName);
            $baseName = Str::studly(end($segments));

            $recordName = $baseName.'Record';
            $filtersName = $baseName.'FiltersRecord';
            $repositoryName = $baseName.'Repository';

            $recordPath = implode('.', array_slice($segments, 0, -1));
            $recordNameWithPath = $recordPath !== '' ? $recordPath.'.'.Str::kebab($baseName).'-record' : Str::kebab($baseName).'-record';
            $filtersNameWithPath = $recordPath !== '' ? $recordPath.'.'.Str::kebab($baseName).'-filters-record' : Str::kebab($baseName).'-filters-record';

            $description = $this->getCustomDataItem('description', 'Repository for '.$baseName);

            $recordQuery = 'forge:record '.$recordNameWithPath.' <description="'.$description.'">';
            $filtersQuery = 'forge:record '.$filtersNameWithPath.' <description="'.$description.'">';

            $kernel = $this->getKernel();

            ob_start();
            $exitCode = $kernel->runSignature($recordQuery);
            $output = ob_get_clean();

            if ($exitCode === ExitCode::SUCCESS) {
                $this->line('   ✅ Record created successfully!');
            } else {
                $this->line('   ℹ️ Record already exists, skipping creation');
            }

            ob_start();
            $exitCode = $kernel->runSignature($filtersQuery);
            $output = ob_get_clean();

            if ($exitCode === ExitCode::SUCCESS) {
                $this->line('   ✅ Filters record created successfully!');
            } else {
                $this->line('   ℹ️ Filters record already exists, skipping creation');
            }

            $context = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('repository', 'Repository', 'Repositories'));

            $generator = $app->make(GeneratorService::class);

            $nameWithPath = $recordPath !== '' ? $recordPath.'.'.Str::kebab($baseName).'-repository' : Str::kebab($baseName).'-repository';
            $fileName = $context->normalizeFileName($nameWithPath);
            $filePath = $context->createFilePath($fileName);
            $className = $filePath->getFileName();
            $namespace = $context->buildNamespace($filePath);

            if ($context->fileExists($fileName)) {
                $this->error('Repository already exists: '.$className);

                return ExitCode::INVALID_ARGUMENT;
            }

            $baseNamespace = $app['config']->get('directive-forge.namespace', 'App');

            $modelNamespace = $baseNamespace.'\\Models';
            if ($recordPath !== '') {
                $folderSegments = array_map(function ($segment) {
                    return Str::studly($segment);
                }, explode('.', $recordPath));
                $modelNamespace .= '\\'.implode('\\', $folderSegments);
            }

            $recordNamespace = $baseNamespace.'\\Records';
            if ($recordPath !== '') {
                $folderSegments = array_map(function ($segment) {
                    return Str::studly($segment);
                }, explode('.', $recordPath));
                $recordNamespace .= '\\'.implode('\\', $folderSegments);
            }

            $stub = $context->loadStub('repository');

            $stub->replace(new ReplacementRecord('namespace', $namespace));
            $stub->replace(new ReplacementRecord('class', $className));
            $stub->replace(new ReplacementRecord('model_short', $baseName));
            $stub->replace(new ReplacementRecord('model_namespace', $modelNamespace));
            $stub->replace(new ReplacementRecord('record_class', $recordName));
            $stub->replace(new ReplacementRecord('filters_class', $filtersName));
            $stub->replace(new ReplacementRecord('record_namespace', $recordNamespace));
            $stub->replace(new ReplacementRecord('base_namespace', $baseNamespace));
            $stub->replace(new ReplacementRecord('description', $description));

            $context->ensureDirectoryExists();

            $generatorContext = $generator->generate(
                $stub,
                $filePath,
                $context->getBaseDirectory()
            );

            if ($generatorContext->isSuccess()) {
                $this->info('✅ Repository created successfully!');
                $this->line('   Repository: '.$namespace.'\\'.$className);
                $this->line('   Model: '.$modelNamespace.'\\'.$baseName);
                $this->line('   Record: '.$recordNamespace.'\\'.$recordName);
                $this->line('   Filters: '.$recordNamespace.'\\'.$filtersName);

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
