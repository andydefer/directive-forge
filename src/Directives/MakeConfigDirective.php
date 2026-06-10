<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Contexts\DirectiveContext;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Services\FileCreatorService;
use AndyDefer\DirectiveForge\Generators\ConfigGenerator;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

/**
 * Directive to create a new Configuration class.
 *
 * @author Andy Defer
 */
final class MakeConfigDirective extends BaseDirective
{
    public function __construct(
        DirectiveContext $context,
        DirectiveInteractionService $interaction,
        FileCreatorService $fileCreator
    ) {
        parent::__construct($context, $interaction, $fileCreator, new ConfigGenerator($interaction, $fileCreator));
    }

    public function getSignature(): string
    {
        return 'make-config {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new configuration class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-config');
        $aliases->add('make-cfg');

        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Config name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        return parent::execute();
    }
}
