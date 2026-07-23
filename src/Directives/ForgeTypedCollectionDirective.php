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

final class ForgeTypedCollectionDirective extends AbstractDirective
{
    private const TYPE_MAP = [
        'vo' => ['folder' => 'ValueObjects', 'suffix' => 'vo'],
        'value-object' => ['folder' => 'ValueObjects', 'suffix' => 'vo'],
        'data' => ['folder' => 'Datas', 'suffix' => 'data'],
        'd' => ['folder' => 'Datas', 'suffix' => 'data'],
        'record' => ['folder' => 'Records', 'suffix' => 'record'],
        'r' => ['folder' => 'Records', 'suffix' => 'record'],
        'enum' => ['folder' => 'Enums', 'suffix' => 'enum'],
        'e' => ['folder' => 'Enums', 'suffix' => 'enum'],
    ];

    public function getSignature(): string
    {
        return 'forge:typed-collection {name} ::type->[vo,value-object,data,d,record,r,enum,e]=*';
    }

    public function getDescription(): string
    {
        return 'Create a typed collection for an item type (VO, Data, Record, or Enum)';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-collection');
        $aliases->add('make-collection');

        return $aliases;
    }

    public function shouldBootLaravel(): bool
    {
        return true;
    }

    public function execute(): ExitCode
    {
        $name = $this->getArgument('name');
        $type = $this->getEnum('type');

        if ($name === null || $name === '') {
            $this->error('Item name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        if ($type === null || ! isset(self::TYPE_MAP[$type])) {
            $this->error('Type is required. Use "vo", "value-object", "data", "d", "record", "r", "enum", or "e"');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $typeConfig = self::TYPE_MAP[$type];
            $suffix = $typeConfig['suffix'];

            $directive = 'forge:'.$suffix;
            $query = $directive.' '.$name;

            ob_start();
            $kernel = $this->getKernel();
            $exitCode = $kernel->runSignature($query);
            $output = ob_get_clean();

            if ($exitCode === ExitCode::SUCCESS) {
                $this->line('   ✅ '.ucfirst($suffix).' created successfully!');
            } else {
                $this->line('   ℹ️ '.ucfirst($suffix).' already exists, skipping creation');
            }

            $collectionCreated = $this->createCollection($name, $suffix);
            if ($collectionCreated === ExitCode::FAILURE) {
                return ExitCode::FAILURE;
            }

            $this->info('✅ Typed collection created successfully!');

            return ExitCode::SUCCESS;

        } catch (InvalidArgumentException $e) {
            $this->error('❌ '.$e->getMessage());

            return ExitCode::INVALID_ARGUMENT;
        } catch (Throwable $e) {
            $this->error('❌ '.$e->getMessage());

            return ExitCode::FAILURE;
        }
    }

    private function createCollection(string $name, string $suffix): ExitCode
    {
        $app = $this->getApplication();

        $baseName = $name;
        $hasSuffix = str_ends_with(strtolower($name), '-'.$suffix);

        if ($hasSuffix) {
            $baseName = substr($name, 0, -strlen($suffix) - 1);
        }

        $baseParts = explode('.', $baseName);
        $lastSegment = end($baseParts);

        [$typeName, $collectionClassName] = match ($suffix) {
            'vo' => [
                Str::studly($lastSegment).'VO',
                Str::studly($lastSegment).'VOCollection',
            ],
            'data' => [
                Str::studly($lastSegment).'Data',
                Str::studly($lastSegment).'DataCollection',
            ],
            'record' => [
                Str::studly($lastSegment).'Record',
                Str::studly($lastSegment).'RecordCollection',
            ],
            'enum' => [
                Str::studly($lastSegment).'Enum',
                Str::studly($lastSegment).'EnumCollection',
            ],
            default => [
                Str::studly($lastSegment).Str::studly($suffix),
                Str::studly($lastSegment).Str::studly($suffix).'Collection',
            ],
        };

        $collectionRaw = $baseName.'-'.$suffix.'-collection';

        $context = $app->make(DirectiveForgeContext::class)
            ->setTypeDefinition(new TypeDefinitionRecord('collection', 'Collection', 'Collections'));

        $filePath = new FilePathVO($collectionRaw);
        $folders = $filePath->getFolders()->toArray();

        $baseDir = $context->getBaseDirectory();
        $fullPath = $baseDir;
        if (! empty($folders)) {
            $fullPath .= '/'.implode('/', $folders);
        }
        $fullPath .= '/'.$collectionClassName.'.php';

        $namespace = $context->getBaseNamespace();
        if (! empty($folders)) {
            $namespace .= '\\'.implode('\\', $folders);
        }

        $filesystem = $app->make(FileSystemService::class);
        if ($filesystem->exists($fullPath)) {
            $this->error('❌ Collection already exists: '.$collectionClassName);

            return ExitCode::INVALID_ARGUMENT;
        }

        $typeFolder = $this->getTypeFolder($suffix);
        $baseNamespace = $app['config']->get('directive-forge.namespace', 'App');

        $folderParts = array_slice($baseParts, 0, -1);
        $typePath = implode('\\', array_map(fn ($p) => Str::studly($p), $folderParts));

        if (! empty($typePath)) {
            $typeFullClass = $baseNamespace.'\\'.$typeFolder.'\\'.$typePath.'\\'.$typeName;
        } else {
            $typeFullClass = $baseNamespace.'\\'.$typeFolder.'\\'.$typeName;
        }

        $description = $this->getCustomDataItem('description', 'Collection for '.$typeName);

        $stub = $context->loadStub('typed-collection');

        $stub->replace(new ReplacementRecord('namespace', $namespace));
        $stub->replace(new ReplacementRecord('class', $collectionClassName));
        $stub->replace(new ReplacementRecord('type', $typeFullClass));
        $stub->replace(new ReplacementRecord('type_short', $typeName));
        $stub->replace(new ReplacementRecord('description', $description));

        $filesystem->ensureDirectoryExists(dirname($fullPath));

        $content = $stub->getValue();
        $filesystem->put($fullPath, $content);

        $this->line("   ✅ Collection created: {$collectionClassName}");

        return ExitCode::SUCCESS;
    }

    private function getTypeFolder(string $suffix): string
    {
        return match ($suffix) {
            'vo' => 'ValueObjects',
            'data' => 'Datas',
            'record' => 'Records',
            'enum' => 'Enums',
            default => ucfirst($suffix).'s',
        };
    }
}
