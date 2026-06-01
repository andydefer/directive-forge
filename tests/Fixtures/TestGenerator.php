<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Fixtures;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Contracts\GeneratorInterface;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\Generators\AbstractGenerator;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

/**
 * Concrete implementation of AbstractGenerator for testing purposes.
 *
 * This fixture provides a testable generator with controlled behavior
 * for unit testing the AbstractGenerator functionality.
 *
 * @author Andy Defer
 */
final class TestGenerator extends AbstractGenerator implements GeneratorInterface
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
    }

    /**
     * {@inheritDoc}
     */
    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ReplacementCollection
    {
        $config = $this->type->getConfig();
        $className = $this->normalizeClassName($pathInfo->className, $config->suffix);
        $namespace = $pathInfo->getNamespace($config->baseNamespace);

        $collection = new ReplacementCollection;
        $collection->addReplacement('{{namespace}}', $namespace);
        $collection->addReplacement('{{class}}', $className);
        $collection->addReplacement('{{test}}', 'test_value');

        return $collection;
    }

    /**
     * Mock error method for testing.
     *
     * Wraps the interaction service's error method to allow
     * testing error handling in the AbstractGenerator.
     *
     * @param  string  $message  The error message to display
     */
    public function error(string $message): void
    {
        $this->interaction->error($message);
    }

    /**
     * Exposes the extractSignature method for testing.
     *
     * @param  string  $className  The normalized class name
     * @param  string  $suffix  The suffix to remove
     * @return string The extracted signature
     */
    public function testExtractSignature(string $className, string $suffix): string
    {
        return $this->extractSignature($className, $suffix);
    }

    /**
     * Exposes the normalizeClassName method for testing.
     *
     * @param  string  $className  The raw class name
     * @param  string  $suffix  The suffix to append
     * @return string The normalized class name
     */
    public function testNormalizeClassName(string $className, string $suffix): string
    {
        return $this->normalizeClassName($className, $suffix);
    }

    /**
     * Sets the generator type for testing different scenarios.
     *
     * @param  GeneratorType  $type  The generator type to set
     */
    public function setType(GeneratorType $type): void
    {
        $this->type = $type;
    }
}
