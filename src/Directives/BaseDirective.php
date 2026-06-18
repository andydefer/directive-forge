<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Contexts\FileCreationContext;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\FileCreatorService;
use AndyDefer\DirectiveForge\Contracts\GeneratorInterface;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;

/**
 * Abstract base class for all Forge code generation directives.
 */
abstract class BaseDirective extends AbstractDirective
{
    /**
     * The file creator service instance.
     */
    protected FileCreatorService $fileCreator;

    /**
     * The generator instance responsible for file creation.
     */
    protected GeneratorInterface $generator;

    /**
     * Determines whether Laravel should be bootstrapped before executing this directive.
     *
     * Forge directives need Laravel to access file system, configuration,
     * and other Laravel-specific features.
     *
     * @return bool True if Laravel bootstrapping is required
     */
    public function shouldBootLaravel(): bool
    {
        return true;
    }

    /**
     * Executes the directive to generate a new file.
     */
    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        // Extract path information from the provided name
        $pathInfo = $this->extractPathInfo($name);

        $type = $this->option('type');
        $itemType = $this->option('item-type');

        return $this->generator->generate($pathInfo, $type, $itemType);
    }

    /**
     * Creates a file from a stub template.
     *
     * @param  string  $stubPath  Path to the stub file
     * @param  string  $destinationPath  Destination file path
     * @param  ReplacementCollection  $replacements  Placeholder replacements
     * @param  FileCreationContext  $context  Creation context
     * @return bool True on success, false on failure
     */
    protected function createFile(
        string $stubPath,
        string $destinationPath,
        ReplacementCollection $replacements,
        FileCreationContext $context
    ): bool {
        $result = $this->fileCreator->createFile(
            $stubPath,
            $destinationPath,
            $replacements,
            $context
        );

        if (! $result->success) {
            $this->error($result->message);

            return false;
        }

        $this->info($result->message);

        return true;
    }

    /**
     * Creates a file from a stub template using a name to build the destination path.
     *
     * @param  string  $stubPath  Path to the stub file
     * @param  string  $name  Name used to build the destination path
     * @param  string  $baseDirectory  Base directory for the file
     * @param  ReplacementCollection  $replacements  Placeholder replacements
     * @param  FileCreationContext  $context  Creation context
     * @return bool True on success, false on failure
     */
    protected function createFileFromName(
        string $stubPath,
        string $name,
        string $baseDirectory,
        ReplacementCollection $replacements,
        FileCreationContext $context
    ): bool {
        $result = $this->fileCreator->createFileFromName(
            $stubPath,
            $name,
            $baseDirectory,
            $replacements,
            $context
        );

        if (! $result->success) {
            $this->error($result->message);

            return false;
        }

        $this->info($result->message);

        return true;
    }

    /**
     * Extracts path information from a directive name.
     *
     * Converts a path like 'admin/user/create-user' into:
     * - className: 'CreateUser'
     * - subPath: 'Admin\\User' (or empty string)
     * - segments: ['admin', 'user'] as ScalarTypedCollection
     *
     * @param  string  $name  The input name (may contain slashes for subdirectories)
     * @return PathInfo The extracted path information as a Value Object
     */
    protected function extractPathInfo(string $name): PathInfo
    {
        $segments = explode('/', $name);
        $rawClassName = array_pop($segments);
        $className = $this->toPascalCase($rawClassName);

        // Normaliser chaque segment individuellement avec toPascalCase
        $normalizedSegments = array_map([$this, 'toPascalCase'], $segments);
        $subPath = ! empty($normalizedSegments) ? implode('\\', $normalizedSegments) : '';

        // Convert segments array to ScalarTypedCollection (garder les originaux)
        $segmentsCollection = new ScalarTypedCollection;
        $segmentsCollection->add(...$segments);

        return PathInfo::from([
            'className' => $className,
            'subPath' => $subPath,
            'segments' => $segmentsCollection,
        ]);
    }

    /**
     * Converts a string to PascalCase.
     *
     * Handles kebab-case, snake_case, and mixed case inputs.
     *
     * @param  string  $string  The input string to convert
     * @return string The PascalCase version of the input
     *
     * @example
     * toPascalCase('create-user')     // 'CreateUser'
     * toPascalCase('create_user')     // 'CreateUser'
     * toPascalCase('CreateUser')      // 'CreateUser'
     * toPascalCase('user-profile')    // 'UserProfile'
     */
    protected function toPascalCase(string $string): string
    {
        $string = str_replace(['-', '_'], ' ', $string);
        $string = ucwords($string);

        return str_replace(' ', '', $string);
    }

    /**
     * Converts a string to kebab-case.
     *
     * @param  string  $string  The input string in PascalCase
     * @return string The kebab-case version
     *
     * @example
     * toKebabCase('UserProfile')     // 'user-profile'
     * toKebabCase('SendWelcomeEmail') // 'send-welcome-email'
     */
    protected function toKebabCase(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '-$1', $string));
    }
}
