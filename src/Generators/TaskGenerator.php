<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Collections\ReplacementCollection;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

/**
 * Generator for creating Task classes.
 *
 * Creates Task classes for encapsulating background jobs or
 * long-running operations that can be queued or executed asynchronously.
 *
 * @author Andy Defer
 */
final class TaskGenerator extends AbstractGenerator
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->type = GeneratorType::TASK;
    }

    /**
     * {@inheritDoc}
     */
    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ReplacementCollection
    {
        $config = $this->type->getConfig();
        $className = $pathInfo->className;
        $namespace = $pathInfo->getNamespace($config->baseNamespace);
        $description = $this->generateDescription($className);

        $collection = new ReplacementCollection;
        $collection
            ->addReplacement('{{namespace}}', $namespace)
            ->addReplacement('{{class}}', $className)
            ->addReplacement('{{description}}', $description);

        return $collection;
    }

    /**
     * Generates a human-readable description from the task class name.
     *
     * Converts PascalCase to a space-separated sentence.
     * Example: 'SendWelcomeEmailTask' -> 'Task for Send Welcome Email'
     *
     * @param  string  $className  The task class name (e.g., 'SendWelcomeEmailTask')
     * @return string The generated description
     */
    private function generateDescription(string $className): string
    {
        // Remove 'Task' suffix
        $baseName = str_replace('Task', '', $className);

        // Convert PascalCase to words
        $words = preg_split('/(?=[A-Z])/', $baseName, -1, PREG_SPLIT_NO_EMPTY);
        $name = implode(' ', $words);

        return "Task for {$name}";
    }
}
