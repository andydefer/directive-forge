<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\RequestGenerator;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

/**
 * Directive to create a new Form Request class.
 *
 * @author Andy Defer
 */
final class MakeRequestDirective extends BaseDirective
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->generator = new RequestGenerator($interaction);
    }

    public function getSignature(): string
    {
        return 'make-request {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new form request class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-request', 'make-req');

        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Request name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        return parent::execute();
    }
}
