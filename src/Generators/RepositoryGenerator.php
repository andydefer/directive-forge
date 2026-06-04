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
 * Creates Repository classes with corresponding Record and FilterRecord.
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

        // Extraire le nom de base (supprimer le suffixe Repository)
        $baseName = str_replace('Repository', '', $className);

        // Normaliser le nom de base en PascalCase (pour gérer les tirets dans les noms de dossiers)
        // Mais le className est déjà passé par toPascalCase dans extractPathInfo
        // Donc on garde le baseName tel quel, il est déjà en PascalCase

        // Pour les records, on utilise le baseName normalisé
        $recordClassName = $baseName . 'Record';
        $filterRecordClassName = $baseName . 'FilterRecord';

        $collection = new ReplacementCollection;
        $collection
            ->addReplacement('{{namespace}}', $namespace)
            ->addReplacement('{{class}}', $className)
            ->addReplacement('{{recordClass}}', $recordClassName)
            ->addReplacement('{{filterRecordClass}}', $filterRecordClassName);

        return $collection;
    }
}
