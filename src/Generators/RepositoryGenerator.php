<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

final class RepositoryGenerator extends AbstractGenerator
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->type = GeneratorType::REPOSITORY;
    }

    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): array
    {
        $config = $this->type->getConfig();
        $className = $pathInfo->className;
        $namespace = $pathInfo->getNamespace($config->baseNamespace);

        // Enlever le suffixe Repository pour créer l'interface
        $interfaceName = str_replace('Repository', '', $className) . 'Interface';

        return [
            '{{namespace}}' => $namespace,
            '{{class}}' => $className,
            '{{interface}}' => $interfaceName,
        ];
    }
}
