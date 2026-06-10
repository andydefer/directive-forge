<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Contexts\DirectiveContext;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Services\FileCreatorService;
use AndyDefer\DirectiveForge\Generators\TaskGenerator;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

final class MakeTaskDirective extends BaseDirective
{
    public function __construct(
        DirectiveContext $context,
        DirectiveInteractionService $interaction,
        FileCreatorService $fileCreator
    ) {
        parent::__construct($context, $interaction, $fileCreator, new TaskGenerator($interaction, $fileCreator));
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
        $aliases->add('create-task');
        $aliases->add('make-job');

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
