<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Services;

use AndyDefer\DirectiveForge\ValueObjects\StubVO;
use AndyDefer\PhpServices\Services\FileSystemService;

final class StubLoaderService
{
    public function __construct(
        private readonly FileSystemService $filesystem
    ) {}

    public function load(string $path): StubVO
    {
        if (! $this->filesystem->exists($path)) {
            throw new \RuntimeException(sprintf('Stub file not found: %s', $path));
        }

        $content = $this->filesystem->get($path);

        return new StubVO($content);
    }

    public function loadFromString(string $content): StubVO
    {
        return new StubVO($content);
    }
}
