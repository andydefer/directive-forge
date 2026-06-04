<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\ConfigGenerator;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

/**
 * Directive to create a new Configuration class.
 *
 * @author Andy Defer
 */
final class MakeConfigDirective extends BaseDirective
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->generator = new ConfigGenerator($interaction);
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
        $aliases->add('create-config', 'make-cfg');

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
