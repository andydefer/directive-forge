<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Contracts;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DirectiveForge\Collections\ReplacementCollection;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

/**
 * Contract for code generator implementations.
 *
 * Defines the interface for generating PHP class files from templates.
 * Implementations handle the file creation logic and provide replacement
 * variables for template placeholders.
 *
 * @author Andy Defer
 */
interface GeneratorInterface
{
    /**
     * Generates a PHP class file based on the provided path information.
     *
     * This method is responsible for:
     * - Loading the appropriate stub template
     * - Replacing placeholders with dynamic values
     * - Creating the destination directory if it doesn't exist
     * - Writing the generated content to the file system
     *
     * @param  PathInfo  $pathInfo  The path information containing class name and directory structure
     * @param  string|null  $type  The generation type (e.g., 'directive', 'action', 'repository')
     * @param  string|null  $itemType  The item type for typed collections (e.g., 'User', 'Post')
     * @return ExitCode The exit code indicating success or failure
     */
    public function generate(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ExitCode;

    /**
     * Returns the replacement values for template placeholders.
     *
     * This method builds a ReplacementCollection where each item contains
     * a placeholder (e.g., '{{class}}', '{{signature}}') and its corresponding
     * replacement value.
     *
     * The collection can be converted to an associative array using
     * `toAssociativeArray()` for use in file generation.
     *
     * @param  PathInfo  $pathInfo  The path information containing class name and directory structure
     * @param  string|null  $type  The generation type (e.g., 'directive', 'action', 'repository')
     * @param  string|null  $itemType  The item type for typed collections (e.g., 'User', 'Post')
     * @return ReplacementCollection A collection of placeholder/value pairs
     *
     * @example
     * $replacements = $generator->getReplacements($pathInfo, $type, $itemType);
     * $array = $replacements->toAssociativeArray();
     * // Returns:
     * // ReplacementCollection containing:
     * // - ReplacementRecord(placeholder: '{{class}}', value: 'UserDirective')
     * // - ReplacementRecord(placeholder: '{{signature}}', value: 'user')
     * // - ReplacementRecord(placeholder: '{{description}}', value: 'Generated directive for user')
     * // - ReplacementRecord(placeholder: '{{date}}', value: '2024-01-15 14:30:00')
     */
    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ReplacementCollection;
}
