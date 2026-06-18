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
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class MakeRepositoryDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'make-repository {model}';
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
        $modelName = $this->argument('model');

        if ($modelName === null || $modelName === '') {
            $this->error('Model name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getLaravel();

            // 1. Déterminer les noms des classes
            $baseName = Str::studly($modelName);
            $recordName = $baseName.'Record';
            $filtersName = $baseName.'FiltersRecord';
            $repositoryName = $baseName.'Repository';

            // 2. Créer le Record avec make-record
            $recordArgs = new StringTypedCollection;
            $recordArgs->add(Str::kebab($baseName).'-record');
            $this->call(new DirectiveExecutionRecord('make-record', $recordArgs));

            // 3. Créer le FiltersRecord avec make-record
            $filtersArgs = new StringTypedCollection;
            $filtersArgs->add(Str::kebab($baseName).'-filters-record');
            $this->call(new DirectiveExecutionRecord('make-record', $filtersArgs));

            // 4. Créer le Repository
            $repositoryCreated = $this->createRepository($baseName, $recordName, $filtersName);
            if ($repositoryCreated === ExitCode::FAILURE) {
                return ExitCode::FAILURE;
            }

            $baseNamespace = $app['config']->get('directive-forge.namespace', 'App');

            $this->info('✅ Repository created successfully!');
            $this->line('   Repository: '.$baseNamespace.'\\Repositories\\'.$repositoryName);
            $this->line('   Record: '.$baseNamespace.'\\Records\\'.$recordName);
            $this->line('   Filters: '.$baseNamespace.'\\Records\\'.$filtersName);

            return ExitCode::SUCCESS;

        } catch (InvalidArgumentException $e) {
            $this->error('❌ '.$e->getMessage());

            return ExitCode::INVALID_ARGUMENT;
        } catch (Throwable $e) {
            $this->error('❌ '.$e->getMessage());

            return ExitCode::FAILURE;
        }
    }

    private function createRepository(string $baseName, string $recordName, string $filtersName): ExitCode
    {
        $app = $this->getLaravel();

        $context = $app->make(DirectiveForgeContext::class)
            ->setTypeDefinition(new TypeDefinitionRecord('repository', 'Repository', 'Repositories'));

        $generator = $app->make(GeneratorService::class);

        $name = Str::kebab($baseName).'-repository';
        $fileName = $context->normalizeFileName($name);
        $filePath = $context->createFilePath($fileName);
        $className = $filePath->getFileName();
        $namespace = $context->buildNamespace($filePath);

        if ($context->fileExists($fileName)) {
            $this->error('❌ Repository already exists: '.$className);

            return ExitCode::INVALID_ARGUMENT;
        }

        $stub = $context->loadStub('repository');

        $baseNamespace = $app['config']->get('directive-forge.namespace', 'App');
        $modelShortName = $baseName;
        $modelNamespace = $baseNamespace.'\\Models';

        $stub->replace(new ReplacementRecord('namespace', $namespace));
        $stub->replace(new ReplacementRecord('class', $className));
        $stub->replace(new ReplacementRecord('model_short', $modelShortName));
        $stub->replace(new ReplacementRecord('model_namespace', $modelNamespace));
        $stub->replace(new ReplacementRecord('record_class', $recordName));
        $stub->replace(new ReplacementRecord('filters_class', $filtersName));
        $stub->replace(new ReplacementRecord('base_namespace', $baseNamespace));

        $context->ensureDirectoryExists();

        $generatorContext = $generator->generate(
            $stub,
            $filePath,
            $context->getBaseDirectory()
        );

        if (! $generatorContext->isSuccess()) {
            $this->error('❌ Failed to create Repository: '.$generatorContext->getMessage());

            return ExitCode::FAILURE;
        }

        $this->line("   ✅ Repository created: {$className}");

        return ExitCode::SUCCESS;
    }
}
