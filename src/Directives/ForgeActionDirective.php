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

final class ForgeActionDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'forge:action {name} ::supfile->[r,request,a,all]=_';
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
        $name = $this->getArgument('name');

        if ($name === null || $name === '') {
            $this->error('Action name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getApplication();

            $context = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('action', 'Action', 'Actions'));

            $generator = $app->make(GeneratorService::class);

            $supfile = $this->getArgument('supfile');
            $baseName = preg_replace('/-?action$/i', '', $name);

            if ($supfile !== null) {
                $requestName = $baseName.'-request';
                $query = 'forge:request '.$requestName;

                if ($supfile === 'a' || $supfile === 'all') {
                    $query .= ' --r';
                }

                ob_start();
                $kernel = $this->getKernel();
                $exitCode = $kernel->runSignature($query);
                ob_get_clean();

                if ($exitCode === ExitCode::SUCCESS) {
                    if ($supfile === 'a' || $supfile === 'all') {
                        $this->line('   ✅ Request + Record created successfully!');
                    } else {
                        $this->line('   ✅ Request created successfully!');
                    }
                } else {
                    if ($supfile === 'a' || $supfile === 'all') {
                        $this->line('   ℹ️ Request + Record already exists, skipping creation');
                    } else {
                        $this->line('   ℹ️ Request already exists, skipping creation');
                    }
                }
            }

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

            $description = $this->getCustomDataItem('description', 'Action for '.$name);

            $stub = $context->loadStub('action');

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
                $this->info('✅ Action created successfully!');
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
