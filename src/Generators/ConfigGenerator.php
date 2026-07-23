<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Collections\ReplacementCollection;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

/**
 * Generator for creating Configuration classes.
 *
 * Creates Config classes that extend AbstractConfig with no properties,
 * only methods returning configuration values from environment variables.
 *
 * @author Andy Defer
 */
final class ConfigGenerator extends AbstractGenerator
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->type = GeneratorType::CONFIG;
    }

    /**
     * {@inheritDoc}
     */
    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ReplacementCollection
    {
        $config = $this->type->getConfig();
        $className = $pathInfo->className;
        $namespace = $pathInfo->getNamespace($config->baseNamespace);

        $collection = new ReplacementCollection;
        $collection
            ->addReplacement('{{namespace}}', $namespace)
            ->addReplacement('{{class}}', $className)
            ->addReplacement('{{date}}', date('Y-m-d H:i:s'));

        return $collection;
    }
}
