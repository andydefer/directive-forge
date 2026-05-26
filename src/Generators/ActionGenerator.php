<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

final class ActionGenerator extends AbstractGenerator
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->type = GeneratorType::ACTION;
    }

    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): array
    {
        $config = $this->type->getConfig();
        $className = $pathInfo->className;
        $namespace = $pathInfo->getNamespace($config->baseNamespace);

        // Générer le nom de la vue à partir du nom de la classe
        $view = str_replace('Action', '', $className);
        $view = preg_replace('/([a-z])([A-Z])/', '$1/$2', $view);
        $parts = explode('/', $view);
        if (count($parts) > 1) {
            $last = array_pop($parts);
            array_unshift($parts, $last);
            $view = implode('/', $parts);
        }

        return [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $className,
            '{{ view }}' => $view,
        ];
    }
}
