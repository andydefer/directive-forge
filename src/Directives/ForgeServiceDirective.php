<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DirectiveForge\Contexts\DirectiveForgeContext;
use AndyDefer\DirectiveForge\Records\ReplacementRecord;
use AndyDefer\DirectiveForge\Records\TypeDefinitionRecord;
use AndyDefer\DirectiveForge\Services\GeneratorService;
use AndyDefer\DirectiveForge\ValueObjects\FilePathVO;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class ForgeServiceDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'forge:service {name} {--c}';
    }

    public function getDescription(): string
    {
        return 'Create a new service class with optional contract';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-service');
        $aliases->add('make-svc');

        return $aliases;
    }

    public function shouldBootLaravel(): bool
    {
        return true;
    }

    public function execute(): ExitCode
    {
        $name = preg_replace('/-?service$/i', '', $this->getArgument('name'));
        $createContract = $this->getFlag('c');

        if ($name === null || $name === '') {
            $this->error('Service name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getApplication();

            $segments = explode('.', $name);
            $lastSegment = end($segments);

            $serviceName = Str::studly($lastSegment).'Service';
            $interfaceName = Str::studly($lastSegment).'ServiceInterface';

            if ($createContract) {
                $interfaceQuery = 'forge:interface services.'.$name.'-service-interface <description="Contract for '.$serviceName.'">';

                $kernel = $this->getKernel();

                ob_start();
                $exitCode = $kernel->runSignature($interfaceQuery);
                $output = ob_get_clean();

                if ($exitCode === ExitCode::SUCCESS) {
                    $this->line('   ✅ Contract created successfully!');
                } else {
                    $this->line('   ℹ️ Contract already exists, skipping creation');
                }
            }

            $context = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('service', 'Service', 'Services'));

            $generator = $app->make(GeneratorService::class);

            $fileName = $context->normalizeFileName($name.'-service');
            $filePath = $context->createFilePath($fileName);
            $className = $filePath->getFileName();
            $namespace = $context->buildNamespace($filePath);

            if ($context->fileExists($fileName)) {
                $this->error('Service already exists: '.$context->getFullPath($fileName));

                return ExitCode::INVALID_ARGUMENT;
            }

            $description = $this->getCustomDataItem('description', 'Service class for '.$className);

            $stub = $context->loadStub('service');

            $stub->replace(new ReplacementRecord('namespace', $namespace));
            $stub->replace(new ReplacementRecord('class', $className));
            $stub->replace(new ReplacementRecord('description', $description));

            if ($createContract) {
                $baseNamespace = $app['config']->get('directive-forge.namespace', 'App');

                $contractFilePath = new FilePathVO($name.'-service-interface');
                $contractFolders = $contractFilePath->getFolders()->toArray();

                $contractNamespace = $baseNamespace.'\\Contracts\\Services';
                if (! empty($contractFolders)) {
                    $contractNamespace .= '\\'.implode('\\', $contractFolders);
                }

                $stub->replace(new ReplacementRecord('interface_namespace', $contractNamespace));
                $stub->replace(new ReplacementRecord('interface', $interfaceName));
                $stub->replace(new ReplacementRecord('interface_import', 'use '.$contractNamespace.'\\'.$interfaceName.';'));
                $stub->replace(new ReplacementRecord('implements', 'implements '.$interfaceName));
            } else {
                $stub->replace(new ReplacementRecord('interface_namespace', ''));
                $stub->replace(new ReplacementRecord('interface', ''));
                $stub->replace(new ReplacementRecord('interface_import', ''));
                $stub->replace(new ReplacementRecord('implements', ''));
            }

            $context->ensureDirectoryExists();

            $generatorContext = $generator->generate(
                $stub,
                $filePath,
                $context->getBaseDirectory()
            );

            if ($generatorContext->isSuccess()) {
                $this->info('✅ Service created successfully!');
                $this->line('   Path: '.$generatorContext->getFullPath());
                $this->line('   Class: '.$namespace.'\\'.$className);
                if ($createContract) {
                    $baseNamespace = $app['config']->get('directive-forge.namespace', 'App');

                    $contractFilePath = new FilePathVO($name.'-service-interface');
                    $contractFolders = $contractFilePath->getFolders()->toArray();

                    $contractNamespace = $baseNamespace.'\\Contracts\\Services';
                    if (! empty($contractFolders)) {
                        $contractNamespace .= '\\'.implode('\\', $contractFolders);
                    }
                    $this->line('   Contract: '.$contractNamespace.'\\'.$interfaceName);
                }
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
