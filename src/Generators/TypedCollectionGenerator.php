<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

/**
 * Generator for creating TypedCollection classes.
 *
 * Creates type-safe collection classes that extend TypedCollection,
 * providing strict type validation and collection-specific methods.
 *
 * @author Andy Defer
 */
final class TypedCollectionGenerator extends AbstractGenerator
{
    private const DEFAULT_ITEM_TYPE = 'string';

    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->type = GeneratorType::TYPED_COLLECTION;
    }

    /**
     * {@inheritDoc}
     */
    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ReplacementCollection
    {
        $config = $this->type->getConfig();
        $className = $pathInfo->className;
        $namespace = $pathInfo->getNamespace($config->baseNamespace);
        $itemTypeValue = $this->normalizeItemType($itemType);

        $collection = new ReplacementCollection;
        $collection
            ->addReplacement('{{namespace}}', $namespace)
            ->addReplacement('{{class}}', $className)
            ->addReplacement('{{type}}', $itemTypeValue);

        return $collection;
    }

    /**
     * Normalizes the item type for the typed collection.
     *
     * Converts the provided item type to a valid PHP type hint.
     * Defaults to 'string' when no type is provided.
     *
     * @param  string|null  $itemType  The raw item type (e.g., 'User', 'int', 'string')
     * @return string The normalized PHP type hint
     */
    private function normalizeItemType(?string $itemType): string
    {
        if ($itemType === null || $itemType === '') {
            return self::DEFAULT_ITEM_TYPE;
        }

        // Convert to valid PHP type hint
        return match (strtolower($itemType)) {
            'int', 'integer' => 'int',
            'bool', 'boolean' => 'bool',
            'float', 'double' => 'float',
            default => $itemType,
        };
    }
}
