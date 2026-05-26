<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\ValueObjects;

use AndyDefer\Records\AbstractRecord;

final class PathInfo extends AbstractRecord
{
    public function __construct(
        public readonly string $className,
        public readonly string $subPath,
        public readonly array $segments,
    ) {}

    public function getFullClassName(): string
    {
        return $this->subPath
            ? $this->subPath . '\\' . $this->className
            : $this->className;
    }

    public function getFilePath(string $basePath): string
    {
        $path = getcwd() . $basePath;

        if ($this->subPath) {
            // Convertir les backslashes en slashes pour le chemin de fichier
            $subPathParts = str_replace('\\', '/', $this->subPath);
            $path .= $subPathParts . '/';
            return $path . $this->className . '.php';
        }

        return $path . $this->className . '.php';
    }

    public function getNamespace(string $baseNamespace): string
    {
        if (!$this->subPath) {
            return $baseNamespace;
        }

        return $baseNamespace . '\\' . str_replace('/', '\\', $this->subPath);
    }
}
