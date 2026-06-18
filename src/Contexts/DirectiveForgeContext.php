<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Contexts;

use AndyDefer\DirectiveForge\Configs\ForgeConfig;
use AndyDefer\DirectiveForge\Enums\ExtensionType;
use AndyDefer\DirectiveForge\Records\TypeDefinitionRecord;
use AndyDefer\DirectiveForge\Services\StubLoaderService;
use AndyDefer\DirectiveForge\ValueObjects\FilePathVO;
use AndyDefer\DirectiveForge\ValueObjects\StubVO;
use AndyDefer\PhpServices\Services\FileSystemService;
use Illuminate\Contracts\Container\Container;

final class DirectiveForgeContext
{
    private ?TypeDefinitionRecord $typeDefinition = null;

    public function __construct(
        private readonly Container $container
    ) {}

    public function setTypeDefinition(TypeDefinitionRecord $typeDefinition): self
    {
        $this->typeDefinition = $typeDefinition;

        return $this;
    }

    public function getTypeDefinition(): TypeDefinitionRecord
    {
        if ($this->typeDefinition === null) {
            throw new \RuntimeException('TypeDefinition not set. Call setTypeDefinition() first.');
        }

        return $this->typeDefinition;
    }

    public function getType(): string
    {
        return $this->getTypeDefinition()->type;
    }

    public function getSuffix(): string
    {
        return $this->getTypeDefinition()->suffix;
    }

    public function getPlural(): string
    {
        return $this->getTypeDefinition()->plural;
    }

    public function getConfig(): ForgeConfig
    {
        return $this->container->make(ForgeConfig::class);
    }

    private function getFilesystem(): FileSystemService
    {
        return $this->container->make(FileSystemService::class);
    }

    private function getStubLoader(): StubLoaderService
    {
        return $this->container->make(StubLoaderService::class);
    }

    public function getBaseDirectory(): string
    {
        $mode = $this->getConfig()->getMode();
        $path = $mode === 'app' ? 'app' : 'src';

        return getcwd().'/'.$path.'/'.$this->getPlural();
    }

    public function getBaseNamespace(): string
    {
        return $this->getConfig()->getNamespace().'\\'.$this->getPlural();
    }

    public function getMode(): string
    {
        return $this->getConfig()->getMode();
    }

    public function createFilePath(string $name): FilePathVO
    {
        return new FilePathVO($name, ExtensionType::from($this->getConfig()->getExtension()));
    }

    public function loadStub(string $name): StubVO
    {
        $path = $this->getConfig()->getStubPath($name);

        return $this->getStubLoader()->load($path);
    }

    public function fileExists(string $name): bool
    {
        $path = $this->getFullPath($name);

        return $this->getFilesystem()->exists($path);
    }

    public function getFullPath(string $name): string
    {
        $filePath = $this->createFilePath($name);

        return $this->getBaseDirectory().'/'.$filePath->getFilePath();
    }

    public function ensureDirectoryExists(): void
    {
        $this->getFilesystem()->ensureDirectoryExists($this->getBaseDirectory());
    }

    public function normalizeFileName(string $name): string
    {
        if (! str_ends_with(strtolower($name), $this->getType())) {
            return $name.'-'.$this->getType();
        }

        return $name;
    }

    public function buildNamespace(FilePathVO $filePath): string
    {
        $namespace = $this->getBaseNamespace();

        if ($filePath->hasFolders()) {
            $namespace .= '\\'.$filePath->getNamespace();
        }

        return $namespace;
    }
}
