<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DirectiveForge\Contexts\DirectiveForgeContext;
use AndyDefer\DirectiveForge\Records\ReplacementRecord;
use AndyDefer\DirectiveForge\Records\TypeDefinitionRecord;
use AndyDefer\DirectiveForge\ValueObjects\FilePathVO;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\PhpServices\Services\FileSystemService;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class ForgeValueObjectDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'forge:vo {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new value object class (VO)';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-vo');
        $aliases->add('make-value-object');

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
            $this->error('Value Object name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getApplication();

            $baseName = $name;
            $hasVoSuffix = str_ends_with(strtolower($name), '-vo');

            if ($hasVoSuffix) {
                $baseName = substr($name, 0, -3);
            }

            $filePath = new FilePathVO($name);
            $folders = $filePath->getFolders()->toArray();

            $segments = explode('.', $baseName);
            $lastSegment = end($segments);
            $className = Str::studly($lastSegment).'VO';

            $context = $app->make(DirectiveForgeContext::class)
                ->setTypeDefinition(new TypeDefinitionRecord('vo', 'VO', 'ValueObjects'));

            $baseDir = $context->getBaseDirectory();
            $fullPath = $baseDir;
            if (! empty($folders)) {
                $fullPath .= '/'.implode('/', $folders);
            }
            $fullPath .= '/'.$className.'.php';

            $namespace = $context->getBaseNamespace();
            if (! empty($folders)) {
                $namespace .= '\\'.implode('\\', $folders);
            }

            $description = $this->getCustomDataItem('description', 'Value Object for '.$className);

            $filesystem = $app->make(FileSystemService::class);
            if ($filesystem->exists($fullPath)) {
                $this->error('Value Object already exists: '.$fullPath);

                return ExitCode::INVALID_ARGUMENT;
            }

            $stub = $context->loadStub('value-object');

            $stub->replace(new ReplacementRecord('namespace', $namespace));
            $stub->replace(new ReplacementRecord('class', $className));
            $stub->replace(new ReplacementRecord('description', $description));

            $filesystem->ensureDirectoryExists(dirname($fullPath));

            $content = $stub->getValue();
            $filesystem->put($fullPath, $content);

            $this->info('✅ Value Object created successfully!');
            $this->line('   Path: '.$fullPath);
            $this->line('   Class: '.$namespace.'\\'.$className);
            $this->line('   Mode: '.$context->getMode());

            return ExitCode::SUCCESS;

        } catch (InvalidArgumentException $e) {
            $this->error('❌ '.$e->getMessage());

            return ExitCode::INVALID_ARGUMENT;
        } catch (Throwable $e) {
            $this->error('❌ '.$e->getMessage());

            return ExitCode::FAILURE;
        }
    }
}
