<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\ValueObjects;

use AndyDefer\DirectiveForge\Records\PathInfoRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;

final class PathInfo extends AbstractValueObject
{
    public function __construct(
        public readonly string $className,
        public readonly string $subPath,
        public readonly ScalarTypedCollection $segments,
    ) {}

    public function getFullClassName(): string
    {
        return $this->subPath
            ? $this->subPath.'\\'.$this->className
            : $this->className;
    }

    public function getFilePath(string $basePath): string
    {
        $path = getcwd().$basePath;

        if ($this->subPath) {
            $subPathParts = str_replace('\\', '/', $this->subPath);
            $path .= $subPathParts.'/';

            return $path.$this->className.'.php';
        }

        return $path.$this->className.'.php';
    }

    public function getNamespace(string $baseNamespace): string
    {
        if (! $this->subPath) {
            return $baseNamespace;
        }

        return $baseNamespace.'\\'.str_replace('/', '\\', $this->subPath);
    }

    /**
     * Returns the value object as a record.
     */
    public function getValue(): PathInfoRecord
    {
        return new PathInfoRecord(
            className: $this->className,
            subPath: $this->subPath,
            segments: $this->segments,
        );
    }
}
