<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

/**
 * Generator for creating Repository classes.
 *
 * Creates Repository classes with corresponding Interface definitions
 * following the Repository pattern for database abstraction.
 *
 * @author Andy Defer
 */
final class RepositoryGenerator extends AbstractGenerator
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->type = GeneratorType::REPOSITORY;
    }

    /**
     * {@inheritDoc}
     */
    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ReplacementCollection
    {
        $config = $this->type->getConfig();
        $className = $pathInfo->className;
        $namespace = $pathInfo->getNamespace($config->baseNamespace);

        // Remove 'Repository' suffix to create interface name
        $interfaceName = $this->generateInterfaceName($className);

        $collection = new ReplacementCollection;
        $collection
            ->addReplacement('{{namespace}}', $namespace)
            ->addReplacement('{{class}}', $className)
            ->addReplacement('{{interface}}', $interfaceName);

        return $collection;
    }

    /**
     * Generates the interface name from the repository class name.
     *
     * Example: 'UserRepository' -> 'UserInterface'
     *
     * @param  string  $className  The repository class name (e.g., 'UserRepository')
     * @return string The corresponding interface name (e.g., 'UserInterface')
     */
    private function generateInterfaceName(string $className): string
    {
        return str_replace('Repository', '', $className).'Interface';
    }
}
