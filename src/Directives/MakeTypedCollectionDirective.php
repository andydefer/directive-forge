<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveExecutionRecord;
use AndyDefer\Directive\Records\ReplacementRecord;
use AndyDefer\DirectiveForge\Contexts\DirectiveForgeContext;
use AndyDefer\DirectiveForge\Enums\SupportedType;
use AndyDefer\DirectiveForge\Records\TypeDefinitionRecord;
use AndyDefer\DirectiveForge\ValueObjects\FilePathVO;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\PhpServices\Services\FileSystemService;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class MakeTypedCollectionDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'make-typed-collection {item}';
    }

    public function getDescription(): string
    {
        return 'Create a typed collection for an item type';
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
        $item = $this->argument('item');

        if ($item === null || $item === '') {
            $this->error('Item type is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        try {
            $app = $this->getLaravel();

            // Extraire le suffixe (tout après le dernier tiret)
            $parts = explode('-', $item);
            $suffix = end($parts);

            // 1. Créer le type avec make-{suffix}
            $directive = 'make-'.$suffix;
            $args = new StringTypedCollection;
            $args->add($item);

            $this->call(new DirectiveExecutionRecord($directive, $args));

            // 2. Créer la collection
            $collectionCreated = $this->createCollection($item, $suffix);
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

    private function createCollection(string $item, string $suffix): ExitCode
    {
        $app = $this->getLaravel();

        // Extraire le nom de base (sans le suffixe)
        $baseName = substr($item, 0, -strlen($suffix) - 1);

        // 🔧 CORRECTION : Extraire le dernier segment pour le nom du type
        $baseParts = explode('.', $baseName);
        $lastSegment = end($baseParts);

        // 🔧 CORRECTION : Construire le nom de la collection correctement
        // Pour les sous-dossiers, on garde la structure mais on utilise le bon nom
        if ($suffix === 'vo') {
            $typeName = Str::studly($lastSegment).'VO';
            $collectionClassName = Str::studly($lastSegment).'VOCollection';
        } else {
            $typeName = Str::studly($lastSegment).Str::studly($suffix);
            $collectionClassName = Str::studly($lastSegment).Str::studly($suffix).'Collection';
        }

        // 🔧 CORRECTION : Construire le chemin avec FilePathVO
        // Pour les dossiers, on utilise le baseName complet
        $collectionRaw = $baseName.'-'.$suffix.'-collection';

        $context = $app->make(DirectiveForgeContext::class)
            ->setTypeDefinition(new TypeDefinitionRecord('collection', 'Collection', 'Collections'));

        // Utiliser FilePathVO pour extraire les dossiers
        $filePath = new FilePathVO($collectionRaw);
        $folders = $filePath->getFolders()->toArray();

        // Construire le chemin complet
        $baseDir = $context->getBaseDirectory();
        $fullPath = $baseDir;
        if (! empty($folders)) {
            $fullPath .= '/'.implode('/', $folders);
        }
        // 🔧 CORRECTION : Utiliser le bon nom de classe pour le fichier
        $fullPath .= '/'.$collectionClassName.'.php';

        // Construire le namespace
        $namespace = $context->getBaseNamespace();
        if (! empty($folders)) {
            $namespace .= '\\'.implode('\\', $folders);
        }

        // Vérifier si le fichier existe
        $filesystem = $app->make(FileSystemService::class);
        if ($filesystem->exists($fullPath)) {
            $this->error('❌ Collection already exists: '.$collectionClassName);

            return ExitCode::INVALID_ARGUMENT;
        }

        $typeFolder = $this->getTypeFolder($suffix);
        $baseNamespace = $app['config']->get('directive-forge.namespace', 'App');

        // Dossiers du type (tous sauf le dernier segment)
        $folderParts = array_slice($baseParts, 0, -1);
        $typePath = implode('\\', array_map(fn ($p) => Str::studly($p), $folderParts));

        // Construire le FQCN du type
        if (! empty($typePath)) {
            $typeFullClass = $baseNamespace.'\\'.$typeFolder.'\\'.$typePath.'\\'.$typeName;
        } else {
            $typeFullClass = $baseNamespace.'\\'.$typeFolder.'\\'.$typeName;
        }

        $stub = $context->loadStub('typed-collection');

        $stub->replace(new ReplacementRecord('namespace', $namespace));
        $stub->replace(new ReplacementRecord('class', $collectionClassName));
        $stub->replace(new ReplacementRecord('type', $typeFullClass));
        $stub->replace(new ReplacementRecord('type_short', $typeName));

        $filesystem->ensureDirectoryExists(dirname($fullPath));

        $content = $stub->getValue();
        $filesystem->put($fullPath, $content);

        $this->line("   ✅ Collection created: {$collectionClassName}");

        return ExitCode::SUCCESS;
    }

    private function getTypeFolder(string $suffix): string
    {
        $type = SupportedType::fromType($suffix);

        return $type->getFolder();
    }
}
