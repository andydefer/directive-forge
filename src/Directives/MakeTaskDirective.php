<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\TaskGenerator;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

final class MakeTaskDirective extends BaseDirective
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->generator = new TaskGenerator($interaction);
    }

    public function getSignature(): string
    {
        return 'make-task {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new task class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-task', 'make-job');

        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Task name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        return parent::execute();
    }
}
