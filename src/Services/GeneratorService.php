<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Services;

use AndyDefer\DirectiveForge\Contexts\GeneratorContext;
use AndyDefer\DirectiveForge\ValueObjects\FilePathVO;
use AndyDefer\DirectiveForge\ValueObjects\StubVO;
use AndyDefer\DirectiveForge\ValueObjects\UnixTimestampVO;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;

/**
 * Service de génération de fichiers à partir de stubs.
 *
 * Combine un StubVO (contenu + remplacements) et un FilePathVO (chemin)
 * pour créer un fichier sur le disque.
 *
 * @example
 * $stub = new StubVO('Hello {{name}}!');
 * $stub->replace(new ReplacementRecord('name', 'John'));
 *
 * $path = new FilePathVO('greetings.hello');
 *
 * $generator = new GeneratorService(new FileSystemService());
 * $context = $generator->generate($stub, $path, '/app/output');
 */
final class GeneratorService
{
    public function __construct(
        private readonly FileSystemInterface $filesystem
    ) {}

    /**
     * Génère un fichier à partir d'un stub et d'un chemin.
     */
    public function generate(
        StubVO $stub,
        FilePathVO $path,
        string $baseDirectory
    ): GeneratorContext {
        $context = new GeneratorContext;
        $context->setStub($stub);
        $context->setPath($path);
        $context->setBaseDirectory($baseDirectory);

        // 1. Construire le chemin complet
        $fullPath = $this->buildFullPath($baseDirectory, $path);
        $context->setFullPath($fullPath);

        // 2. Vérifier si le fichier existe déjà
        if ($this->filesystem->exists($fullPath)) {
            $context->setError(sprintf('File already exists: %s', $fullPath));
            $context->setSuccess(false);
            $context->setEndTime(new UnixTimestampVO);

            return $context;
        }

        // 3. Rendre le contenu
        $content = $stub->getValue();
        $context->setContent($content);

        // 4. Créer le répertoire parent
        try {
            $this->filesystem->ensureDirectoryExists(dirname($fullPath));
        } catch (\RuntimeException $e) {
            $context->setError(sprintf('Cannot create directory: %s', $e->getMessage()));
            $context->setSuccess(false);
            $context->setEndTime(new UnixTimestampVO);

            return $context;
        }

        // 5. Écrire le fichier
        try {
            $bytes = $this->filesystem->put($fullPath, $content);

            if ($bytes === false) {
                $context->setError(sprintf('Cannot write file: %s', $fullPath));
                $context->setSuccess(false);
                $context->setEndTime(new UnixTimestampVO);

                return $context;
            }
        } catch (\RuntimeException $e) {
            $context->setError(sprintf('Cannot write file: %s', $e->getMessage()));
            $context->setSuccess(false);
            $context->setEndTime(new UnixTimestampVO);

            return $context;
        }

        // 6. Retourner le contexte de succès
        $context->setSuccess(true);
        $context->setBytes($bytes);
        $context->setEndTime(new UnixTimestampVO);

        return $context;
    }

    /**
     * Génère plusieurs fichiers à partir de stubs.
     *
     * @param  array<array{stub: StubVO, path: FilePathVO}>  $items
     * @return array<GeneratorContext>
     */
    public function generateMany(array $items, string $baseDirectory): array
    {
        $contexts = [];

        foreach ($items as $item) {
            $contexts[] = $this->generate(
                $item['stub'],
                $item['path'],
                $baseDirectory
            );
        }

        return $contexts;
    }

    /**
     * Vérifie si un fichier peut être généré sans l'écrire.
     */
    public function dryRun(
        StubVO $stub,
        FilePathVO $path,
        string $baseDirectory
    ): GeneratorContext {
        $context = new GeneratorContext;
        $context->setStub($stub);
        $context->setPath($path);
        $context->setBaseDirectory($baseDirectory);

        // 1. Construire le chemin complet
        $fullPath = $this->buildFullPath($baseDirectory, $path);
        $context->setFullPath($fullPath);

        // 2. Vérifier si le fichier existe déjà
        if ($this->filesystem->exists($fullPath)) {
            $context->setError(sprintf('File would be overwritten: %s', $fullPath));
            $context->setSuccess(false);
            $context->setEndTime(new UnixTimestampVO);

            return $context;
        }

        // 3. Rendre le contenu
        $content = $stub->getValue();
        $context->setContent($content);

        // 4. Succès (simulé)
        $context->setSuccess(true);
        $context->setBytes(strlen($content));
        $context->setEndTime(new UnixTimestampVO);

        return $context;
    }

    /**
     * Vérifie si un fichier peut être écrit.
     */
    public function canWrite(FilePathVO $path, string $baseDirectory): bool
    {
        $fullPath = $this->buildFullPath($baseDirectory, $path);
        $directory = dirname($fullPath);

        // Vérifier si le répertoire parent est accessible en écriture
        if ($this->filesystem->exists($directory)) {
            return $this->filesystem->isWritable($directory);
        }

        // Vérifier récursivement
        $current = $directory;
        while ($current !== '' && $current !== '/' && $current !== '.') {
            if ($this->filesystem->exists($current)) {
                return $this->filesystem->isWritable($current);
            }
            $current = dirname($current);
        }

        return false;
    }

    /**
     * Construit le chemin complet.
     */
    private function buildFullPath(string $baseDirectory, FilePathVO $path): string
    {
        $base = rtrim($baseDirectory, '/\\');
        $file = $path->getFilePath();

        return $base.DIRECTORY_SEPARATOR.$file;
    }
}
