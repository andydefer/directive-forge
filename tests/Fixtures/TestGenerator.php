<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Fixtures;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Contracts\GeneratorInterface;
use AndyDefer\DirectiveForge\Generators\AbstractGenerator;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

/**
 * Concrete implementation of AbstractGenerator for testing purposes
 */
final class TestGenerator extends AbstractGenerator implements GeneratorInterface
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
    }

    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): array
    {
        $config = $this->type->getConfig();
        $className = $this->normalizeClassName($pathInfo->className, $config->suffix);
        $namespace = $pathInfo->getNamespace($config->baseNamespace);

        return [
            '{{namespace}}' => $namespace,
            '{{class}}' => $className,
            '{{test}}' => 'test_value',
        ];
    }

    /**
     * Mock error method for testing
     */
    public function error(string $message): void
    {
        $this->interaction->error($message);
    }
}
