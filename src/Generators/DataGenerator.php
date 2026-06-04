<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

/**
 * Generator for creating Data DTO classes.
 *
 * Creates Data DTOs that extend AbstractData with immutable properties,
 * following the principle that all properties must be concepts (Value Objects,
 * Enums, other Data, or concrete TypedCollections).
 *
 * @author Andy Defer
 */
final class DataGenerator extends AbstractGenerator
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->type = GeneratorType::DATA;
    }

    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ReplacementCollection
    {
        $config = $this->type->getConfig();
        $className = $pathInfo->className;
        $namespace = $pathInfo->getNamespace($config->baseNamespace);

        $collection = new ReplacementCollection;
        $collection
            ->addReplacement('{{namespace}}', $namespace)
            ->addReplacement('{{class}}', $className);

        return $collection;
    }
}
