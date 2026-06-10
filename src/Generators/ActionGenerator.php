<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

/**
 * Generator for creating Action classes.
 *
 * Creates action classes for encapsulating business logic following the
 * Action Domain Pattern.
 *
 * @author Andy Defer
 */
final class ActionGenerator extends AbstractGenerator
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->type = GeneratorType::ACTION;
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
            ->addReplacement('{{ namespace }}', $namespace)
            ->addReplacement('{{ class }}', $className);

        return $collection;
    }
}
