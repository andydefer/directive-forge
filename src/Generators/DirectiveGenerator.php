<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Services\DirectiveNamingService;
use AndyDefer\Directive\Services\SignatureValidationService;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

/**
 * Generator for creating Directive classes.
 *
 * Creates Laravel Directive compatible classes with proper signatures,
 * validation, and naming conventions.
 *
 * @author Andy Defer
 */
final class DirectiveGenerator extends AbstractGenerator
{
    private SignatureValidationService $signatureValidator;

    private DirectiveNamingService $namingService;

    public function __construct(
        DirectiveInteractionService $interaction,
        SignatureValidationService $signatureValidator,
        DirectiveNamingService $namingService,
    ) {
        parent::__construct($interaction);
        $this->type = GeneratorType::DIRECTIVE;
        $this->signatureValidator = $signatureValidator;
        $this->namingService = $namingService;
    }

    /**
     * {@inheritDoc}
     */
    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ReplacementCollection
    {
        $config = $this->type->getConfig();
        $className = $pathInfo->className;
        $namespace = $pathInfo->getNamespace($config->baseNamespace);
        $signature = $this->extractSignature($className, $config->suffix);

        $collection = new ReplacementCollection;
        $collection->addReplacement('{{namespace}}', $namespace)
            ->addReplacement('{{signature}}', $signature)
            ->addReplacement('{{class}}', $className)
            ->addReplacement('{{description}}', "Description for {$signature}")
            ->addReplacement('{{date}}', date('Y-m-d H:i:s'));

        return $collection;
    }

    /**
     * Validates a directive name against the signature validation rules.
     *
     * Checks if the directive name follows the required format:
     * - Starts with a letter
     * - Contains only letters, numbers, and hyphens
     * - No consecutive or trailing hyphens
     *
     * @param  string  $name  The directive name to validate
     * @return bool True if the directive name is valid, false otherwise
     */
    public function validate(string $name): bool
    {
        $baseName = basename($name);
        $validation = $this->signatureValidator->validate($baseName);

        if (! $validation->isValid) {
            $this->interaction->error($validation->error ?? 'Invalid directive name format');

            return false;
        }

        return true;
    }
}
