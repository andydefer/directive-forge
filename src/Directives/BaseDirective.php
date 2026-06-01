<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Traits\FileCreator;
use AndyDefer\DirectiveForge\Contracts\GeneratorInterface;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;

/**
 * Abstract base class for all Forge code generation directives.
 *
 * This class provides the foundation for directives that generate PHP files
 * from stubs. It handles:
 * - Argument parsing (name, type, item-type)
 * - Path information extraction
 * - Delegation to the appropriate generator implementation
 *
 * @example
 * final class MakeDirective extends BaseDirective
 * {
 *     protected function getGenerator(): GeneratorInterface
 *     {
 *         return new DirectiveGenerator();
 *     }
 * }
 *
 * @author Andy Defer
 */
abstract class BaseDirective extends AbstractDirective
{
    use FileCreator;

    /**
     * The generator instance responsible for file creation.
     */
    protected GeneratorInterface $generator;

    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->initFileCreator();
    }

    /**
     * Executes the directive to generate a new file.
     *
     * Validates the required 'name' argument, extracts path information,
     * and delegates the generation to the configured generator.
     *
     * @return ExitCode The exit code indicating success or failure
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
    private function extractPathInfo(string $name): PathInfo
    {
        $segments = explode('/', $name);
        $rawClassName = array_pop($segments);
        $className = $this->toPascalCase($rawClassName);
        $subPath = ! empty($segments) ? implode('\\', array_map('ucfirst', $segments)) : '';

        // Convert segments array to ScalarTypedCollection
        $segmentsCollection = new ScalarTypedCollection;
        $segmentsCollection->add(...$segments);

        // Use from() for hydration instead of direct constructor
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
     */
    private function toPascalCase(string $string): string
    {
        $string = str_replace(['-', '_'], ' ', $string);
        $string = ucwords($string);

        return str_replace(' ', '', $string);
    }
}
