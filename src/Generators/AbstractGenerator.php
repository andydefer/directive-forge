<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Traits\FileCreator;
use AndyDefer\DirectiveForge\Contracts\GeneratorInterface;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

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

        // Normaliser le nom de la classe AVANT de l'utiliser
        $normalizedClassName = $this->normalizeClassName($pathInfo->className, $config->suffix);

        // Créer un nouveau PathInfo avec le nom normalisé
        $normalizedPathInfo = new PathInfo(
            className: $normalizedClassName,
            subPath: $pathInfo->subPath,
            segments: $pathInfo->segments,
        );

        // Get the full stub path (with type suffix if needed)
        $stubPath = $config->stubPath;
        if ($config->supportsType && $type) {
            $stubPath = str_replace('.stub', ".{$type}.stub", $stubPath);
        }

        // Create destination path avec le nom normalisé
        $destinationPath = $normalizedPathInfo->getFilePath($config->basePath);

        // Get replacements avec le nom normalisé
        $replacements = $this->getReplacements($normalizedPathInfo, $type, $itemType);

        // Merge extra replacements from config
        if (!empty($config->extraReplacements)) {
            $replacements = array_merge($replacements, $config->extraReplacements);
        }

        // Ensure directory exists
        $this->ensureDirectoryExists(dirname($destinationPath));

        // Create file
        if (!$this->createFile($stubPath, $destinationPath, $replacements)) {
            return ExitCode::FAILURE;
        }

        // Success messages avec le nom normalisé
        $this->interaction->info("✅ {$config->type->value} created successfully!");
        $this->interaction->line("   Class: {$normalizedPathInfo->getNamespace($config->baseNamespace)}\\{$normalizedClassName}");
        $this->interaction->line("   Path: {$destinationPath}");

        if ($type) {
            $this->interaction->line("   Type: {$type}");
        }

        if ($itemType) {
            $this->interaction->line("   Item Type: {$itemType}");
        }

        return ExitCode::SUCCESS;
    }

    abstract public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): array;

    protected function normalizeClassName(string $className, string $suffix): string
    {
        $pascalCase = $this->toPascalCase($className);

        if (!str_ends_with($pascalCase, $suffix)) {
            $pascalCase .= $suffix;
        }

        return $pascalCase;
    }

    protected function extractSignature(string $className, string $suffix): string
    {
        $baseName = preg_replace("/{$suffix}$/", '', $className);
        return $this->toKebabCase($baseName);
    }

    /**
     * Display an error message
     */
    protected function error(string $message): void
    {
        $this->interaction->error($message);
    }
}
