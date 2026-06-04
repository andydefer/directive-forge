<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

/**
 * Generator for creating Form Request classes.
 *
 * Creates Laravel Form Request classes that extend AbstractRequest,
 * with EmptyRecord by default for simple use cases.
 *
 * @author Andy Defer
 */
final class RequestGenerator extends AbstractGenerator
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->type = GeneratorType::REQUEST;
    }

    /**
     * {@inheritDoc}
     */
    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ReplacementCollection
    {
        $config = $this->type->getConfig();
        $className = $pathInfo->className;
        $namespace = $pathInfo->getNamespace($config->baseNamespace);
        $recordClass = $this->generateRecordClassName($className);

        $collection = new ReplacementCollection;
        $collection
            ->addReplacement('{{namespace}}', $namespace)
            ->addReplacement('{{class}}', $className)
            ->addReplacement('{{recordClass}}', $recordClass);

        return $collection;
    }

    /**
     * Generates the Record class name from the Request class name.
     *
     * Example: 'StoreUserRequest' -> 'StoreUserRecord'
     *
     * @param string $className The request class name
     * @return string The corresponding record class name
     */
    private function generateRecordClassName(string $className): string
    {
        // Remove 'Request' suffix
        $baseName = str_replace('Request', '', $className);

        // Add 'Record' suffix
        return $baseName . 'Record';
    }
}
