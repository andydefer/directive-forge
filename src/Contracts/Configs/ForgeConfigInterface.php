<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Contracts\Configs;

interface ForgeConfigInterface
{
    public function getMode(): string;

    public function getNamespace(): string;

    public function getExtension(): string;

    public function getDirectoryPermission(): int;

    public function getStubPath(string $name): string;

    public function getAvailableStubs(): array;
}
