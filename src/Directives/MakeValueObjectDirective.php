<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\ValueObjectGenerator;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

/**
 * Directive to create a new Value Object class.
 *
 * @author Andy Defer
 */
final class MakeValueObjectDirective extends BaseDirective
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->generator = new ValueObjectGenerator($interaction);
    }

    public function getSignature(): string
    {
        return 'make-vo {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new value object class (VO)';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-vo', 'make-value-object');

        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Value Object name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        return parent::execute();
    }
}
