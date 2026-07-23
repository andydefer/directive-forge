<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Traits\FileCreator;
use AndyDefer\DirectiveForge\Collections\ReplacementCollection;
use AndyDefer\DirectiveForge\Contracts\GeneratorInterface;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\Records\GeneratorConfig;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

/**
 * Abstract base class for all code generators.
 *
 * Provides common functionality for generating PHP files from stubs,
 * including class name normalization, stub loading, and file creation.
 *
 * @author Andy Defer
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    use FileCreator;

    protected GeneratorType $type;

    protected DirectiveInteractionService $interaction;

    public function __construct(DirectiveInteractionService $interaction)
    {
        $this->interaction = $interaction;
        $this->initFileCreator();
    }

    public function getType(): GeneratorType
    {
        return $this->type;
    }

    public function generate(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ExitCode
    {
        $config = $this->type->getConfig();

        // Normalize class name BEFORE using it
        $normalizedClassName = $this->normalizeClassName($pathInfo->className, $config->suffix);

        // Create normalized PathInfo using from() for hydration
        $normalizedPathInfo = PathInfo::from([
            'className' => $normalizedClassName,
            'subPath' => $pathInfo->subPath,
            'segments' => $pathInfo->segments,
        ]);

        // Get the full stub path (with type suffix if needed)
        $stubPath = $this->resolveStubPath($config, $type);

        // Create destination path with normalized name
        $destinationPath = $normalizedPathInfo->getFilePath($config->basePath);

        // Get replacements as ReplacementCollection
        $replacementCollection = $this->getReplacements($normalizedPathInfo, $type, $itemType);

        // Convert to associative array for file creation
        $replacements = $replacementCollection->toAssociativeArray();

        // Merge extra replacements from config
        if ($config->extraReplacements !== null && $config->extraReplacements->isNotEmpty()) {
            $replacements = array_merge($replacements, $config->extraReplacements->toAssociativeArray());
        }

        // Ensure directory exists
        $this->ensureDirectoryExists(dirname($destinationPath));

        // Create file
        if (! $this->createFile($stubPath, $destinationPath, $replacements)) {
            return ExitCode::FAILURE;
        }

        // Display success messages
        $this->displaySuccessMessage($normalizedPathInfo, $config, $type, $itemType, $destinationPath);

        return ExitCode::SUCCESS;
    }

    abstract public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ReplacementCollection;

    /**
     * Normalizes a class name by converting to PascalCase and adding suffix.
     *
     * @param  string  $className  The raw class name
     * @param  string  $suffix  The suffix to append (e.g., 'Directive', 'Action')
     * @return string The normalized class name
     */
    protected function normalizeClassName(string $className, string $suffix): string
    {
        $pascalCase = $this->toPascalCase($className);

        if (! str_ends_with($pascalCase, $suffix)) {
            $pascalCase .= $suffix;
        }

        return $pascalCase;
    }

    /**
     * Extracts the base signature from a class name by removing suffix.
     *
     * @param  string  $className  The normalized class name
     * @param  string  $suffix  The suffix to remove
     * @return string The kebab-case signature
     */
    protected function extractSignature(string $className, string $suffix): string
    {
        $baseName = preg_replace("/{$suffix}$/", '', $className);

        return $this->toKebabCase($baseName);
    }

    /**
     * Resolves the stub path, handling type-specific stubs if supported.
     *
     * @param  GeneratorConfig  $config  The generator configuration
     * @param  string|null  $type  The optional type variant (e.g., 'default', 'custom')
     * @return string The resolved stub file path
     */
    private function resolveStubPath(GeneratorConfig $config, ?string $type = null): string
    {
        $stubPath = $config->stubPath;

        if ($config->supportsType && $type !== null) {
            $stubPath = str_replace('.stub', ".{$type}.stub", $stubPath);
        }

        return $stubPath;
    }

    /**
     * Displays success message after file generation.
     *
     * @param  PathInfo  $pathInfo  The normalized path information
     * @param  GeneratorConfig  $config  The generator configuration
     * @param  string|null  $type  The type variant used
     * @param  string|null  $itemType  The item type for typed collections
     * @param  string  $destinationPath  The absolute path to the created file
     */
    private function displaySuccessMessage(
        PathInfo $pathInfo,
        GeneratorConfig $config,
        ?string $type,
        ?string $itemType,
        string $destinationPath
    ): void {
        $this->interaction->info("✅ {$config->type->value} created successfully!");
        $this->interaction->line("   Class: {$pathInfo->getNamespace($config->baseNamespace)}\\{$pathInfo->className}");
        $this->interaction->line("   Path: {$destinationPath}");

        if ($type !== null) {
            $this->interaction->line("   Type: {$type}");
        }

        if ($itemType !== null) {
            $this->interaction->line("   Item Type: {$itemType}");
        }
    }

    /**
     * Displays an error message.
     *
     * @param  string  $message  The error message to display
     */
    protected function error(string $message): void
    {
        $this->interaction->error($message);
    }
}
